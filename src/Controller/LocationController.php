<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\CityRepository;
use App\Repository\LocationRepository;
use App\Utils\SerializerHelper;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocationController extends AbstractController
{


    /**
     * @Route("/post/location", methods={"POST"})
     * @param Request $request
     * @param CityRepository $cityRepository
     * @param ValidatorInterface $validator
     * @param SerializerHelper $serializerHelper
     * @return Response
     */
    public function create(Request $request, CityRepository $cityRepository, ValidatorInterface $validator, SerializerHelper $serializerHelper)
    {
        try {
            $data = $request->request->all()['location'];
            $city = $cityRepository->find($data["city"]);

            $location = new Location();
            $location->setCity($city);
            $location->setStreet($data["street"]);
            $location->setLongitude(floatval($data["longitude"]) ?: null);
            $location->setName($data["name"]);
            $location->setLatitude(floatval($data["latitude"])?: null);
            $errors = $validator->validate($location);

            //s'il y a des erreurs de validation on envoie les messages d'erreur
            if (count($errors)>0){
                $messages = [];
                foreach ($errors as $error){
                    $messages[] = $error->getMessage();
                }
                return new Response(json_encode($messages), 500);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($location);
            $entityManager->flush();

            $response = new Response($serializerHelper->getSerializer()->serialize($location, 'json', ['groups'=>'location']));
            $response->headers->set('Content-Type', 'application/json');

            return $response;

        } catch (\Exception $e){
            return new Response("Désolé unr erreur innatendue s'est produite");
        }

    }

    /**
     * @Route("/get/locations", name="locations")
     * @param LocationRepository $repository
     * @param SerializerHelper $serializerHelper
     * @return Response
     */
    public function getAllLocations(LocationRepository $repository, SerializerHelper $serializerHelper){

        $locations = $repository->findAllWithCity();

        $json =$serializerHelper->getSerializer()->serialize($locations, 'json', ['groups'=>'location']);
        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


}
