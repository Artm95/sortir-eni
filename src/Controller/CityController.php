<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use App\Utils\SerializerHelper;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CityController extends AbstractController
{

    #[Route('admin/cities', name: 'admin_cities')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $city = $form->getData();
                $entityManager->persist($city);
                $entityManager->flush();
                $this->addFlash('success', 'Ville rajoutée : ' . $city->getName() . ' ' . $city->getZipCode());
                return $this->redirectToRoute('admin_cities');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('danger', 'Erreur de traitement : une ville avec ce code postal existe déjà');
            }
        }

        return $this->render('city/index.html.twig', [
            'controller_name' => 'CityController',
            'form' => $form->createView(),
            'title' => 'Gérer les villes',
        ]);
    }

    #[Route('admin/cities/delete/{id}', name: 'admin_city_delete', requirements: ['id' => '\d+'])]
    public function delete($id, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        try {
            $city = $cityRepository->findOrFail($id);
            //TODO delete related events ?? 
            $entityManager->remove($city);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Ville  ' . $city->getName() . ' ' . $city->getZipCode() . ' supprimé'
            );
            return $this->redirectToRoute('admin_cities');
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', 'Cette ville n\'existe pas ou a déjà été supprimé');
            return $this->redirectToRoute('admin_cities');
        }
    }

    #[Route('admin/cities/edit/{id}', name: 'admin_city_edit', requirements: ['id' => '\d+'])]
    public function edit($id, Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        try {
            $city = $cityRepository->findOrFail($id);
            $form = $this->createForm(CityType::class, $city);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $city = $form->getData();
                    $entityManager->persist($city);
                    $entityManager->flush();
                    $this->addFlash('success', 'Ville modifiée   : ' . $city->getName() . ' ' . $city->getZipCode());
                    return $this->redirectToRoute('admin_cities');
                } catch (Exception $e) {
                    $this->addFlash('danger', 'Erreur de traitement : ' . $e->getMessage());
                }
            }

            return $this->render('city/index.html.twig', [
                'controller_name' => 'CityController',
                'form' => $form->createView(),
                'title' => 'Gérer les villes',
            ]);
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_cities');
        }
    }

    #[Route('get/cities', name: 'admin_cities_get')]
    public function getAllCities(CityRepository $repository, SerializerHelper $serialiserHelper)
    {
        $cities = $repository->findAll();
        $json = $serialiserHelper->getSerializer()->serialize($cities, 'json');
        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
