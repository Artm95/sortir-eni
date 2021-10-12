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
    /**
     * Rendering page with campuses' list
     * Creation of new campus
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('admin/campuses', name: 'admin_campuses')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
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
        ]);
    }

    /**
     * Edit campus and persist in database
     * @param $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param CampusRepository $campusRepository
     * @return Response
     */
    #[Route('admin/campus/edit/{id}', name: 'admin_campus_edit', requirements: ['id' => '\d+'])]
    public function edit($id, Request $request, EntityManagerInterface $entityManager, CampusRepository $campusRepository): Response
    {
        try {
            $campus = $campusRepository->findOrFail($id);
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
            ]);
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_campuses');
        }
    }

    /**
     * Delete campus by id
     * @param $id
     * @param EntityManagerInterface $entityManager
     * @param CampusRepository $campusRepository
     * @return Response
     */
    #[Route('admin/campus/delete/{id}', name: 'admin_campus_delete', requirements: ['id' => '\d+'])]
    public function delete($id, EntityManagerInterface $entityManager, CampusRepository $campusRepository): Response
    {
        try {
            $campus = $campusRepository->findOrFail($id);
            $entityManager->remove($campus);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Campus  ' . $campus->getName() . ' supprimé'
            );
            return $this->redirectToRoute('admin_cities');
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', 'Ce campus n\'existe pas ou a déjà été supprimé');
            return $this->redirectToRoute('admin_campuses');
        }
    }

    /**
     * Send all campuses data
     * @param CampusRepository $repository
     * @param SerializerHelper $serializerHelper
     * @return Response
     */
    #[Route('admin/get/campuses')]
    public function getAllCampuses(CampusRepository $repository, SerializerHelper $serializerHelper){
        $campuses = $repository->findAll();
        $response = new Response($serializerHelper->getSerializer()->serialize($campuses, 'json', ['groups'=>'campus']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
