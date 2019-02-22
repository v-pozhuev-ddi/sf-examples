<?php

namespace App\Managers;

use App\Controller\DataResponse;
use App\Entity\Location;
use App\Entity\User;
use App\Entity\WorkSpace;
use App\Model\AreaModelManager;
use App\Model\LocationModelManager;
use App\Notifications\FrontUrls;
use App\Notifications\NotificationService;
use App\Notifications\NotificationsEvent;
use App\Services\RouterWrapper;
use App\Traits\YieldTrait;
use App\Validators\LocationValidator;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LocationsApiManager
 * @package App\Managers
 */
class LocationsApiManager
{
    use YieldTrait;

    /**
     * @var LocationModelManager
     */
    private $locationModelManager;

    /**
     * @var LocationValidator
     */
    private $validator;

    /**
     * @var WorkSpaceApiManager
     */
    private $workSpaceApiManager;

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * @var RouterWrapper
     */
    private $router;

    private $areaModelManager;

    /**
     * LocationsApiManager constructor.
     * @param LocationModelManager $locationModelManager
     * @param LocationValidator $validator
     * @param WorkSpaceApiManager $workSpaceApiManager
     * @param NotificationService $notificationService
     * @param RouterWrapper $router
     * @param AreaModelManager $areaModelManager
     */
    public function __construct(
        LocationModelManager $locationModelManager,
        LocationValidator $validator,
        WorkSpaceApiManager $workSpaceApiManager,
        NotificationService $notificationService,
        RouterWrapper $router,
        AreaModelManager $areaModelManager
    )
    {
        $this->locationModelManager = $locationModelManager;
        $this->validator = $validator;
        $this->workSpaceApiManager = $workSpaceApiManager;
        $this->notificationService = $notificationService;
        $this->router = $router;
        $this->areaModelManager = $areaModelManager;
    }

    /**
     * @param User $user
     * @param array $data
     * @return DataResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(User $user, array $data): DataResponse
    {
        $location = $this->locationModelManager->createLocation();
        $errors = $this->validator->validateRequest($location, $data, LocationValidator::CREATE_ACTION);

        if (!empty($errors)) {
            return new DataResponse(Response::HTTP_BAD_REQUEST, ['errors' => $errors]);
        }

        $area = $this->areaModelManager->getAreaBySlug($data['area']);

        if (empty($area)) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                [
                    'errors' =>
                        [
                            'attribute' => 'area',
                            'details'   => 'This value do not exist'
                        ]
                ]
            );
        }

        $location->setUser($user);
        $location->setArea($area);

        $this->locationModelManager->updateLocation($location);

        $this->notificationService->sendEmail(
            $location->getUser(),
            NotificationsEvent::LOCATION_ADDED,
            [
                'location' => $location,
                'seller' => $location->getUser(),
                'adminLink' => $this->router->generateAdminLink('admin_app_location_edit', ['id' => $location->getId()]),
                'linkPortal' => $this->router->generateFrontLink(FrontUrls::MAINPORTAL)
            ]
        );

        return new DataResponse(Response::HTTP_CREATED, ['id' => $location->getId()]);
    }

    /**
     * @param User $user
     * @param int $id
     * @param array $data
     * @return DataResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(User $user, int $id, array $data): DataResponse
    {
        /** @var Location $location */
        $location = $this->locationModelManager->findOneBy(['user' => $user, 'id' => $id]);

        if ($location === null) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => sprintf('Location with id %d was not found', $id)]);
        }

        $errors = $this->validator->validateRequest($location, $data, LocationValidator::UPDATE_ACTION);

        if (!empty($errors)) {
            return new DataResponse(Response::HTTP_BAD_REQUEST, ['errors' => $errors]);
        }

        if (!empty($data['area'])) {
            $area = $this->areaModelManager->getAreaBySlug($data['area']);

            if (empty($area)) {
                return new DataResponse(
                    Response::HTTP_BAD_REQUEST,
                    [
                        'errors' =>
                            [
                                'attribute' => 'area',
                                'details' => 'This value do not exist'
                            ]
                    ]
                );
            }

            $location->setArea($area);
        }

        $this->locationModelManager->updateLocation($location);

        return new DataResponse(Response::HTTP_OK, ['message' => 'Location successfully updated']);
    }

    /**
     * @param User $user
     * @param int $id
     * @return DataResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(User $user, int $id): DataResponse
    {
        /** @var Location $location */
        $location = $this->locationModelManager->findOneBy(['user' => $user, 'id' => $id]);

        if ($location === null) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => sprintf('Location with id %d was not found', $id)]);
        }

        $this->notificationService->sendEmail(
            $location->getUser(),
            NotificationsEvent::LOCATION_DELETED,
            [
                'location' => $location,
                'seller' => $location->getUser()
            ]
        );

        $this->locationModelManager->deleteLocation($location);

        return new DataResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param User $user
     * @param int $id
     * @param array $data
     * @return DataResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addWorkspace(User $user, int $id, array $data): DataResponse
    {
        /** @var Location $location */
        $location = $this->locationModelManager->findOneBy(['user' => $user, 'id' => $id]);

        if ($location === null) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => sprintf('Location with id %d was not found', $id)]);
        }

        return $this->workSpaceApiManager->addWorkspace($location, $data);
    }

    /**
     * @param User $user
     * @param int $id
     * @return DataResponse
     */
    public function view(User $user, int $id) : DataResponse
    {
        /** @var Location $location */
        $location = $this->locationModelManager->findOneBy(['user' => $user, 'id' => $id]);

        if ($location === null) {
            return new DataResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => sprintf('Location with id %d was not found', $id)]);
        }

        return new DataResponse(Response::HTTP_OK, [
            'id' => $location->getId(),
            'name' => $location->getName(),
            'address' => $location->getAddress(),
            'optionalAddress' => $location->getOptionalAddress(),
            'latitude' => $location->getLatitude(),
            'longitude' => $location->getLongitude(),
            'town' => $location->getTown(),
            'area' => (!empty($location->getArea())) ? $location->getArea()->getName() : '',
            'postcode' => $location->getPostcode(),
            'description' => $location->getDescription(),
            'workSpaceTypes' => $location->getWorkSpaceTypes(),
        ]);
    }

    /**
     * @param User $user
     * @return DataResponse
     * @throws \Exception
     */
    public function getSellerLocations(User $user)
    {
        /** @var Location $location */
        $locations = $this->locationModelManager->findBy(['user' => $user]);

        if (empty($locations)) {
            return new DataResponse(Response::HTTP_OK, ['locations' => []]);
        }

        $data = [];

        /** @var Location $location */
        foreach ($this->yieldCollection($locations) as $location) {
            $desks = $meetingRooms = $privateOffices = [];

            if (!$location->getWorkspaces()->isEmpty()) {
                /** @var WorkSpace $workspace */
                foreach ($this->yieldCollection($location->getWorkspaces()) as $workspace) {
                    //the data looks the same, but I think, it will be better to split the logic in different blocks
                    //to keep logic clean and understandable
                    if ($workspace->getType() === WorkSpace::PRIVATE_OFFICE) {
                        $privateOffices[] = [
                            'id' => $workspace->getId(),
                            'quantity' => $workspace->getQuantity(),
                            'size' => $workspace->getSize(),
                            'price' => $workspace->getPrice(),
                            'status' => $workspace->getStatus(),
                            'bookings' => $workspace->getBookings()->count(),
                            'viewing' => $workspace->getViewings()->count()
                        ];
                    } elseif ($workspace->getType() === WorkSpace::MEETING_ROOM) {
                        $meetingRooms[] = [
                            'id' => $workspace->getId(),
                            'quantity' => $workspace->getQuantity(),
                            'size' => $workspace->getSize(),
                            'price' => $workspace->getPrice(),
                            'status' => $workspace->getStatus(),
                            'bookings' => $workspace->getBookings()->count(),
                            'viewing' => $workspace->getViewings()->count()
                        ];
                    } elseif ($workspace->getType() === WorkSpace::DESK) {
                        $desks[] = [
                            'id' => $workspace->getId(),
                            'quantity' => $workspace->getQuantity(),
                            'type' => $workspace->getDeskType(),
                            'price' => $workspace->getPrice(),
                            'status' => $workspace->getStatus(),
                            'bookings' => $workspace->getBookings()->count(),
                            'viewing' => $workspace->getViewings()->count()
                        ];
                    } else {
                        //looks like database row has an error
                        continue;
                    }
                }
            }

            $data[] = [
                'id' => $location->getId(),
                'name' => $location->getName(),
                'address' => $location->getAddress(),
                'desks' => $desks,
                'meetingRooms' => $meetingRooms,
                'privateOffices' => $privateOffices,
                'postcode' => $location->getPostcode()
            ];
        }

        return new DataResponse(Response::HTTP_OK, ['locations' => $data]);
    }
}
