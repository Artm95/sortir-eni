<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use App\Repository\CampusRepository;
use App\Utils\SerializerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CampusController extends AbstractController
{
    #[Route('admin/campuses', name: 'admin_campuses')]
    public function index(Request $request, EntityManagerInterface $entityManager, CampusRepository $campusRepository): Response
    {
        $campuses = $campusRepository->findAll();
        $campus = new Campus();
        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $campus = $form->getData();
                $entityManager->persist($campus);
                $entityManager->flush();
                $this->addFlash('success', 'Campus rajouté : ' . $campus->getName());
                return $this->redirectToRoute('admin_campuses');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur de traitement : ' . $e->getMessage());
                return $this->redirectToRoute('admin_campuses');
            }
        }

        return $this->render('campus/index.html.twig', [
            'form' => $form->createView(),
            'title' => 'Gérer les campus',
            'action' => 'Ajouter',
            'campuses' => $campuses
        ]);
    }

    #[Route('admin/campus/edit/{id}', name: 'admin_campus_edit', requirements: ['id' => '\d+'])]
    public function edit($id, Request $request, EntityManagerInterface $entityManager, CampusRepository $campusRepository): Response
    {
            $campuses = $campusRepository->findAll();
            $campus = $campusRepository->find($id);
            $form = $this->createForm(CampusType::class, $campus);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $campus = $form->getData();
                    $entityManager->persist($campus);
                    $entityManager->flush();
                    $this->addFlash('success', 'Campus modifié : ' . $campus->getName());
                    return $this->redirectToRoute('admin_campuses');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur de traitement : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_campuses');
                }
            }

        return $this->render('campus/index.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le campus',
            'action'=>'Modifier',
            'campuses' => $campuses
        ]);

    }

    #[Route('admin/campus/delete/{id}', name: 'admin_campus_delete', requirements: ['id' => '\d+'])]
    public function delete($id, EntityManagerInterface $entityManager, CampusRepository $campusRepository): Response
    {
        try {
            $campus = $campusRepository->find($id);
            $entityManager->remove($campus);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Campus  ' . $campus->getName() . ' supprimé'
            );
            return $this->redirectToRoute('admin_cities');
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', 'Cette campuse n\'existe pas ou a déjà été supprimé');
            return $this->redirectToRoute('admin_campuses');
        }
    }

    #[Route('admin/get/campuses')]
    public function getAllCampuses(CampusRepository $repository, SerializerHelper $serializerHelper){
        $campuses = $repository->findAll();
        $response = new Response($serializerHelper->getSerializer()->serialize($campuses, 'json', ['groups'=>'campus']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
