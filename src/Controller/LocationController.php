<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\CityRepository;
use App\Repository\LocationRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocationController extends AbstractController
{
    private $serializer;
    public function __construct()
    {
        $encoders = [new JsonEncoder()];
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            }
        ];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $normalizer = new ObjectNormalizer($classMetadataFactory, null, null, null, null, null, $defaultContext);
        $normalizers = [$normalizer];

        $this->serializer =  new Serializer($normalizers, $encoders);
    }

    /**
     * @Route("/post/location", methods={"POST"})
     * @param Request $request
     * @param CityRepository $cityRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function create(Request $request, CityRepository $cityRepository, ValidatorInterface $validator)
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

            $response = new Response($this->serializer->serialize($location, 'json', ['groups'=>'location']));
            $response->headers->set('Content-Type', 'application/json');

            return $response;

        } catch (\Exception $e){
            return new Response("Désolé unr erreur innatendue s'est produite");
        }

    }

    /**
     * @Route("/get/locations", name="locations")
     * @param LocationRepository $repository
     * @return Response
     */
    public function getAllLocations(LocationRepository $repository){

        $locations = $repository->findAllWithCity();

        $json = $this->serializer->serialize($locations, 'json', ['groups'=>'location']);
        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


}
