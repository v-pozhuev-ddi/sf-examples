<?php

namespace App\Managers;

use App\Controller\DataResponse;
use App\Entity\InternalNotification;
use App\Entity\Location;
use App\Entity\User;
use App\Entity\Viewing;
use App\Entity\WorkSpace;
use App\InternalNotification\PushNotificationType;
use App\Model\InternalNotificationModelManager;
use App\Model\BuyerInternalNotificationModelManager;
use App\Model\ViewingModelManager;
use App\Notifications\FrontUrls;
use App\Notifications\NotificationService;
use App\Notifications\NotificationsEvent;
use App\Services\RouterWrapper;
use App\Services\UserStatusChecker;
use App\Traits\YieldTrait;
use App\Validators\ViewingValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ViewingApiManager
 * @package App\Managers
 */
class ViewingApiManager implements Schedule
{
    use YieldTrait;

    /**
     * @var ViewingModelManager
     */
    private $viewingModelManager;

    /**
     * @var ViewingValidator
     */
    private $viewingValidator;

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * @var RouterWrapper
     */
    private $router;

    /**
     * @var InternalNotificationModelManager
     */
    private $internalNotificationModelManager;

    /**
     * @var UserStatusChecker
     */
    private $userStatusChecker;

    /**
     * @var BuyerInternalNotificationModelManager
     */
    private $buyerInternalNotificationModelManager;

    /**
     * ViewingApiManager constructor.
     * @param ViewingModelManager $viewingModelManager
     * @param ViewingValidator $viewingValidator
     * @param NotificationService $notificationService
     * @param RouterWrapper $routerWrapper
     * @param InternalNotificationModelManager $internalNotificationModelManager
     * @param UserStatusChecker $userStatusChecker
     * @param BuyerInternalNotificationModelManager $buyerInternalNotificationModelManager
     */
    public function __construct(
        ViewingModelManager $viewingModelManager,
        ViewingValidator $viewingValidator,
        NotificationService $notificationService,
        RouterWrapper $routerWrapper,
        InternalNotificationModelManager $internalNotificationModelManager,
        UserStatusChecker $userStatusChecker,
        BuyerInternalNotificationModelManager $buyerInternalNotificationModelManager
    ) {
        $this->viewingModelManager = $viewingModelManager;
        $this->viewingValidator = $viewingValidator;
        $this->notificationService = $notificationService;
        $this->router = $routerWrapper;
        $this->internalNotificationModelManager = $internalNotificationModelManager;
        $this->userStatusChecker = $userStatusChecker;
        $this->buyerInternalNotificationModelManager = $buyerInternalNotificationModelManager;
    }

    /**
     * @return ViewingModelManager
     */
    public function getViewingModelManager(): ViewingModelManager
    {
        return $this->viewingModelManager;
    }

    /**
     * @param WorkSpace $workSpace
     * @return DataResponse
     * @throws \Exception
     */
    public function getList(WorkSpace $workSpace) : DataResponse
    {
        $list = [];

        if ($workSpace->getViewings()->isEmpty()) {
            return new DataResponse(Response::HTTP_OK);
        }

        /** @var Viewing $viewing */
        foreach ($this->yieldCollection($workSpace->getViewings()) as $viewing) {
            $user = $viewing->getUser();

            $list[] = [
                'id' => $viewing->getId(),
                'startTime' => $viewing->getStartTime()->getTimestamp(),
                'endTime' => (is_null($viewing->getEndTime())) ? null : $viewing->getEndTime()->getTimestamp(),
                'phone' => $viewing->getPhone(),
                'status' => $viewing->getStatus(),
                'meetingName' => null,
                'name' => (!is_null($user)) ? $user->getFullname() : null,
                'email' => (!is_null($user)) ? $user->getEmail() : null
            ];
        }

        return new DataResponse(Response::HTTP_OK, $list);
    }

    /**
     * @param User $user
     * @param WorkSpace $workSpace
     * @param $data
     * @return DataResponse
     * @throws \Exception
     */
    public function addViewing(User $user, WorkSpace $workSpace, $data) : DataResponse
    {
        if (empty($data['startTime'])) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => 'Please, choose a start date']
            );
        }

        if (!is_null($workSpace->getAvailableFrom())) {
            $startTime = (new \DateTime())->setTimestamp($data['startTime']);

            if ($workSpace->getAvailableFrom()->getTimestamp() > $startTime->getTimestamp()) {
                return new DataResponse(
                    Response::HTTP_BAD_REQUEST,
                    ['message' => 'Viewing for this workspace is temporarily unavailable. Please, try again later']
                );
            }
        }

        $viewing = $this->viewingModelManager->createViewing();

        if (!empty($errors = $this->viewingValidator->viewingValidator($viewing, $data))) {
            return new DataResponse(Response::HTTP_BAD_REQUEST, ['errors' => $errors]);
        }

        $startTime = (new \DateTime())->setTimestamp($data['startTime']);

        $endTime = (isset($data['endTime']))
            ? (new \DateTime())->setTimestamp($data['endTime'])
            : $startTime;

        $viewing->setStartTime($startTime);
        $viewing->setEndTime($endTime);
        $viewing->setUser($user);
        $viewing->setStatus(Viewing::PENDING);
        $viewing->setWorkSpace($workSpace);

        $this->viewingModelManager->update($viewing);

        $location = $workSpace->getLocation();

        $this->notificationService->sendEmail(
            $user,
            NotificationsEvent::VIEWING_REQUEST,
            [
                'location' => $location,
                'workspace' => $workSpace,
                'seller' => $location->getUser(),
                'buyer' => $user,
                'viewing' => $viewing,
                'link' => $this->router->generateFrontLink(sprintf(FrontUrls::IN_DEPTH, $workSpace->getId())),
                'linkNotification' => $this->router->generateFrontLink(FrontUrls::NOTIFICATIONS)
            ],
            $location->getUser()
        );

        $this->internalNotificationModelManager->create(
            $location->getUser(),
            $viewing,
            [
                'time' => $viewing->getStartTime()->format('h:ia'),
                'date' => $viewing->getStartTime()->format('l, jS F'),
                'location_name' => $location->getName()
            ]
        );

        $this->userStatusChecker->changeStatus($user, User::BOOKED_VIEWING);

        return new DataResponse(
            Response::HTTP_CREATED,
            [
                'message' => 'You have been successfully added to the viewing schedule'
            ]
        );
    }

    /**
     * @param WorkSpace $workSpace
     * @param int $viewingId
     * @param string $status
     * @return DataResponse
     * @throws \Exception
     */
    public function updateStatus(WorkSpace $workSpace, int $viewingId, string $status) : DataResponse
    {
        if (!in_array($status, Viewing::listOfStatuses())) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => "Wrong status"]
            );
        }

        $viewing = $this->viewingModelManager->findOneBy(['workSpace' => $workSpace, 'id' => $viewingId]);

        if (is_null($viewing)) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => "Booking record doesn't exist"]
            );
        }

        if ($viewing->getStatus() === $status) {
            return new DataResponse(Response::HTTP_OK, ['message' => 'Nothing to change']);
        }

        $viewing->setStatus($status);
        $this->viewingModelManager->update($viewing);

        $location = $workSpace->getLocation();

        $params = [
            'time' => $viewing->getStartTime()->format('h:ia'),
            'date' => $viewing->getStartTime()->format('l, jS F'),
            'workspace_info' => $workSpace->getWorkspaceInfo(),
            'location_name' => $location->getName()
        ];

        if ($status === Viewing::ACCEPTED) {
            $this->notificationService->sendEmail(
                $viewing->getUser(),
                NotificationsEvent::VIEWING_APPROVED,
                [
                    'seller' => $location->getUser(),
                    'buyer' => $viewing->getUser(),
                    'location' => $location,
                    'workspace' => $workSpace,
                    'link' => $this->router->generateFrontLink(sprintf(FrontUrls::IN_DEPTH, $workSpace->getId())),
                    'viewing' => $viewing
                ]
            );

            $pushParams = [
                'startTime' => $viewing->getStartTime()->format('H:i'),
                'startDate' => $viewing->getStartTime()->format('d.m.Y'),
                'address' => $workSpace->getLocation()->getAddress(),
                'user' => $viewing->getUser()->getFullname()
            ];

            $this->buyerInternalNotificationModelManager->create(
                $viewing->getUser(),
                $pushParams,
                PushNotificationType::VIEWING_ACCEPTED,
                $viewing->getId()
            );
            $this->internalNotificationModelManager->changeStatus($viewing->getInternalNotification(), InternalNotification::VIEWING_ACCEPTED, $params);
        } elseif ($status === Viewing::DECLINED) {
            $this->internalNotificationModelManager->changeStatus($viewing->getInternalNotification(), InternalNotification::VIEWING_DECLINED, $params);
        }

        return new DataResponse(
            Response::HTTP_OK,
            [
                'internalNotification' => $viewing->getCheckedInternalNotification()
            ]
        );
    }

    /**
     * @param User $user
     * @param int $viewingID
     * @return array
     */
    protected function checkViewing(User $user, int $viewingID) : array
    {
        $viewing = $this->viewingModelManager->findOneBy(['id' => $viewingID, 'user' => $user]);

        if (is_null($viewing)) {
            return [
                false,
                ['message' => "Viewing record was not found"]
            ];
        }

        if ($viewing->getStatus() === Viewing::CANCELED) {
            return [
                false,
                ['message' => "Viewing already canceled"]
            ];
        }

        $workspace = $viewing->getWorkSpace();

        if (is_null($workspace)) {
            return [
                false,
                ['message' => "Workspace was not found"]
            ];
        }

        if ($workspace->getStatus() !== WorkSpace::ACTIVE) {
            return [
                false,
                ['message' => "Workspace is not available. Please, try again later"]
            ];
        }

        /** @var Location $location */
        $location = $workspace->getLocation();

        if ($location === null) {
            return [
                false,
                ['message' => 'No location was found']
            ];
        }

        return [
            true,
            [
                'viewing' => $viewing,
                'workspace' => $workspace,
                'location' => $location
            ]
        ];
    }

    /**
     * @param User $user
     * @param int $bookingID
     * @return DataResponse
     */
    public function cancelViewing(User $user, int $bookingID) : DataResponse
    {
        [$ok, $data] = $this->checkViewing($user, $bookingID);

        if (!$ok) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                $data
            );
        }

        /** @var Viewing $viewing */
        ['viewing' => $viewing] = $data;

        $now = new \DateTime();

        if ($viewing->getStartTime()->getTimestamp() < $now->getTimestamp()) {
            return new DataResponse(Response::HTTP_BAD_REQUEST, [
                'message' => 'You cannot cancel the expired viewing'
            ]);
        }

        $viewing->setStatus(Viewing::CANCELED);
        $this->viewingModelManager->update($viewing);

        return new DataResponse(Response::HTTP_OK);
    }
}
