<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
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
    public function index(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        $cities = $cityRepository->findAll();
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $city = $form->getData();
                $entityManager->persist($city);
                $entityManager->flush();
                $this->addFlash('success', 'Ville rajouté : ' . $city->getName() . ' ' . $city->getZipCode());
                return $this->redirectToRoute('admin_cities');
            } catch (Exception $e) {
                $this->addFlash('danger', 'Erreur de traitement : ' . $e->getMessage());
            }
        }

        return $this->render('city/index.html.twig', [
            'controller_name' => 'CityController',
            'form' => $form->createView(),
            'title' => 'Gérer les villes',
            'cities' => $cities
        ]);
    }

    #[Route('admin/cities/delete/{id}', name: 'admin_city_delete', requirements: ['id' => '\d+'])]
    public function delete($id, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        try {
            $city = $cityRepository->find($id);
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
}
