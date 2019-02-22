<?php

namespace App\Controller\Api;

use App\Managers\LocationsApiManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * Class SellerController
 * @package App\Controller\Api
 */
class LocationController extends Controller implements ApiExceptionInterface
{
    /**
     *
     * Create location
     *
     * @Route("/api/seller/location", methods={"POST"})
     *
     * @SWG\Parameter(
     *     name="name",
     *     in="body",
     *     schema={"string"},
     *     description="Name of location",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="address",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="optionalAddress",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="latitude",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="longitude",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="nearby",
     *     in="body",
     *     schema={"array"},
     *     type="array",
     *     items=@SWG\Items(type="object"),
     *     description="[{'type': 'subway_station', 'name': 'Subway Name', 'distance': '1.4 km', 'duration': '18 mins'}]",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="town",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=true
     * )
     *
     *  @SWG\Parameter(
     *     name="postcode",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     maxLength=10,
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="description",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="workSpaceTypes",
     *     in="body",
     *     schema={"string"},
     *     description="desk, private-office, meeting-room",
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Returns when location was created",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="id",
     *             type="integer",
     *             description="id of created location"
     *          )
     *      )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Return on failed validation",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="errors",
     *             type ="array",
     *             @SWG\Items (
     *                 @SWG\Property(property="attribute", type ="string"),
     *                 @SWG\Property(property="details", type ="string")
     *             )
     *          )
     *      )
     * )
     *
     * @SWG\Tag(name="Seller")
     *
     * @param Request $request
     * @param LocationsApiManager $locationsApiManager
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return JsonResponse
     */
    public function createLocation(Request $request, LocationsApiManager $locationsApiManager) : JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null || empty($data)) {
            return new JsonResponse([
                'message' => 'Requested data is empty'
            ], Response::HTTP_BAD_REQUEST);
        }

        $res = $locationsApiManager->create($this->getUser(), $data);

        return new JsonResponse($res->getData(), $res->getStatus());
    }

    /**
     *
     * Update location
     *
     * @Route("/api/seller/location/{id}", methods={"PUT"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Location id",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="name",
     *     in="body",
     *     schema={"string"},
     *     description="Name of location",
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="address",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="optionalAddress",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="latitude",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="longitude",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="nearby",
     *     in="body",
     *     schema={"array"},
     *     type="array",
     *     items=@SWG\Items(type="object"),
     *     description="[{'type': 'subway_station', 'name': 'Subway Name', 'distance': '1.4 km', 'duration': '18 mins'}]",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="town",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     *  @SWG\Parameter(
     *     name="postcode",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     maxLength=10,
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="description",
     *     in="body",
     *     schema={"string"},
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="workSpaceTypes",
     *     in="body",
     *     schema={"string"},
     *     description="desk, private-office, meeting-room",
     *     type="string",
     *     required=false
     * )
     *
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns when location was updated successfully"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Return on failed validation",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="errors",
     *             type ="array",
     *             @SWG\Items (
     *                 @SWG\Property(property="attribute", type ="string"),
     *                 @SWG\Property(property="details", type ="string")
     *             )
     *          )
     *      )
     * )
     *
     * @SWG\Tag(name="Seller")
     *
     * @param Request $request
     * @param LocationsApiManager $locationsApiManager
     * @param integer $id
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return JsonResponse
     */
    public function updateLocation(Request $request, LocationsApiManager $locationsApiManager, int $id) : JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null || empty($data)) {
            return new JsonResponse([
                'message' => 'Requested data is empty'
            ], Response::HTTP_BAD_REQUEST);
        }

        $res = $locationsApiManager->update($this->getUser(), $id, $data);

        return new JsonResponse($res->getData(), $res->getStatus());
    }

    /**
     *
     * Delete location
     *
     * @Route("/api/seller/location/{id}", methods={"DELETE"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Location id",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=204,
     *     description="Returns when location was removed"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Returns when something went wrong"
     * )
     *
     * @SWG\Tag(name="Seller")
     *
     * @param LocationsApiManager $locationsApiManager
     * @param int $id
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return JsonResponse
     */
    public function deleteLocation(LocationsApiManager $locationsApiManager, int $id) : JsonResponse
    {
        $res = $locationsApiManager->delete($this->getUser(), $id);

        return new JsonResponse($res->getData(), $res->getStatus());
    }

    /**
     *
     * Get location
     *
     * @Route("/api/seller/location/{id}", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Location id",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns when location was found",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="name", type ="string"),
     *          @SWG\Property(property="address", type ="string"),
     *          @SWG\Property(property="optionalAddress", type ="string"),
     *          @SWG\Property(property="latitude", type ="string"),
     *          @SWG\Property(property="longitude", type ="string"),
     *          @SWG\Property(property="town", type ="string"),
     *          @SWG\Property(property="area", type ="string"),
     *          @SWG\Property(property="postcode", type ="string"),
     *          @SWG\Property(property="description", type ="string"),
     *          @SWG\Property(
     *              property="workSpaceTypes",
     *              type="array",
     *              @SWG\Items()
     *          )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Returns when something went wrong"
     * )
     *
     * @SWG\Tag(name="Seller")
     *
     * @param LocationsApiManager $locationsApiManager
     * @param int $id
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return JsonResponse
     */
    public function getLocation(LocationsApiManager $locationsApiManager, int $id) : JsonResponse
    {
        $res = $locationsApiManager->view($this->getUser(), $id);

        return new JsonResponse($res->getData(), $res->getStatus());
    }

    /**
     *
     * Add workspace to location
     *
     * @Route("/api/seller/location/{id}/workspace", methods={"POST"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Location id",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="type",
     *     in="body",
     *     schema={"string"},
     *     description="Type of workspace. Possible options: desk, private-office, meeting-room. See the description of the fields for the corresponding types",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="quantity",
     *     in="body",
     *     schema={"integer"},
     *     description="required for all types",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="price",
     *     in="body",
     *     schema={"number"},
     *     description="required for all types",
     *     type="number",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="size",
     *     in="body",
     *     schema={"integer"},
     *     description="private-office, meeting-room",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="capacity",
     *     in="body",
     *     schema={"integer"},
     *     description="private-office, meeting-room",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="opensFrom",
     *     in="body",
     *     schema={"string"},
     *     description="only for Hourly spaces",
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="closesAt",
     *     in="body",
     *     schema={"string"},
     *     description="only for Hourly spaces",
     *     type="string",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="deskType",
     *     in="body",
     *     schema={"string"},
     *     description="One of the options: hourly_hot_desk, monthly_hot_desk, monthly_fixed_desk",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="minContractLength",
     *     in="body",
     *     schema={"integer"},
     *     description="private|desk",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="availableFrom",
     *     in="body",
     *     schema={"datetime"},
     *     description="private",
     *     type="timestamp",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="facilities",
     *     in="body",
     *     schema={"array"},
     *     type="array",
     *     items=@SWG\Items(type="string"),
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="description",
     *     in="body",
     *     schema={"string"},
     *     description="required for all types",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Returns when location was created",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="id",
     *             type="integer",
     *             description="id of created location"
     *          )
     *      )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Return on failed validation",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="errors",
     *             type ="array",
     *             @SWG\Items (
     *                 @SWG\Property(property="attribute", type ="string"),
     *                 @SWG\Property(property="details", type ="string")
     *             )
     *          )
     *      )
     * )
     *
     * @SWG\Tag(name="Seller")
     *
     * @param Request $request
     * @param LocationsApiManager $locationsApiManager
     * @param int $id
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addWorkspace(Request $request, LocationsApiManager $locationsApiManager, int $id) : JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null || empty($data)) {
            return new JsonResponse([
                'message' => 'Requested data is empty'
            ], Response::HTTP_BAD_REQUEST);
        }

        $res = $locationsApiManager->addWorkspace($this->getUser(), $id, $data);

        return new JsonResponse($res->getData(), $res->getStatus());
    }

    /**
     * Get seller locations
     *
     * @Route("/api/seller/locations", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of locations",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="locations",
     *             type="array",
     *             @SWG\Items (
     *                 type="object",
     *                 @SWG\Property(property="id", type ="integer"),
     *                 @SWG\Property(property="name", type ="string"),
     *                 @SWG\Property(property="status", type ="string"),
     *                 @SWG\Property(property="address", type ="string"),
     *                 @SWG\Property(
     *                      property="desks",
     *                      type="array",
     *                      @SWG\Items(
     *                          type="object",
     *                          @SWG\Property(property="id", type ="integer"),
     *                          @SWG\Property(property="quantity", type ="integer"),
     *                          @SWG\Property(property="type", type ="integer"),
     *                          @SWG\Property(property="price", type ="integer"),
     *                          @SWG\Property(property="status", type ="string"),
     *                          @SWG\Property(property="bookings", type ="integer"),
     *                          @SWG\Property(property="viewings", type ="integer"),
     *                      )
     *                ),
     *                @SWG\Property(
     *                      property="meetingRooms",
     *                      type="array",
     *                      @SWG\Items(
     *                          type="object",
     *                          @SWG\Property(property="id", type ="integer"),
     *                          @SWG\Property(property="quantity", type ="integer"),
     *                          @SWG\Property(property="size", type ="integer"),
     *                          @SWG\Property(property="price", type ="integer"),
     *                          @SWG\Property(property="status", type ="string"),
     *                          @SWG\Property(property="bookings", type ="integer"),
     *                          @SWG\Property(property="viewings", type ="integer"),
     *                      )
     *                ),
     *                @SWG\Property(
     *                      property="privateOffices",
     *                      type="array",
     *                      @SWG\Items(
     *                          type="object",
     *                          @SWG\Property(property="id", type ="integer"),
     *                          @SWG\Property(property="quantity", type ="integer"),
     *                          @SWG\Property(property="size", type ="integer"),
     *                          @SWG\Property(property="price", type ="integer"),
     *                          @SWG\Property(property="status", type ="string"),
     *                          @SWG\Property(property="bookings", type ="integer"),
     *                          @SWG\Property(property="viewings", type ="integer"),
     *                      )
     *                )
     *             )
     *          )
     *      )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Returns when something went wrong"
     * )
     *
     * @SWG\Tag(name="Seller")
     *
     * @param LocationsApiManager $locationsApiManager
     * @return JsonResponse
     * @throws \Exception
     */
    public function sellerLocations(LocationsApiManager $locationsApiManager) : JsonResponse
    {
        $res = $locationsApiManager->getSellerLocations($this->getUser());

        return new JsonResponse($res->getData(), $res->getStatus());
    }
}
