<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\AddUserType;
use App\Form\ProfileType;
use App\Repository\ParticipantRepository;
use App\Utils\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ParticipantController extends AbstractController {

    #[Route('/edit-profile', name: 'participant_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, UploaderHelper $uploaderHelper): Response {
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $photo = $form["avatar"]->getData();
            $newPass = $user->getPlainPassword();
            if ($newPass !== null) {
                $user->setPassword($passwordEncoder->encodePassword(
                    $user,
                    $user->getPlainPassword()
                ));
            }

            if ($photo) {
                $destination = $this->getParameter('kernel.project_dir') . '/public/uploads';
                if ($user->getPhoto()) {
                    //we delete the previous avater
                    $uploaderHelper->deleteUploadedFile($destination . '/' . $user->getPhoto());
                }
                //we create a unique file name based on user id
                $fileName = $uploaderHelper->uploadPhoto($photo, $destination);
                $user->setPhoto($fileName);

            }
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profil modifié avec succés.');
        }

        return $this->render('participant/edit-profile.html.twig', [
            'userForm' => $form->createView(),
            'userPhoto' => $user->getPhoto(),
            'title' => 'Mon profil'
        ]);
    }

    #[Route('/profile/{id}', name: 'user_detail', requirements: ['id' => '\d+'])]
    public function showProfile(int $id, ParticipantRepository $repository) {
        try {
            $user = $repository->findOrFail($id);

            return $this->render('participant/show-profile.html.twig', [
                'user' => $user
            ]);
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', "Le profil demandé n'existe pas");
            return $this->redirectToRoute('event');
        }
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function addUsers(ParticipantRepository $repository) {
        $participants = $repository->getAllWithCampus();

        return $this->render('participant/add-new.html.twig', [
            'title' => 'Gestion des utilisateurs',
            'participants' => $participants
        ]);
    }

    #[Route('/admin/users/ajout', name: 'admin_users_add')]
    public function addOne(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder): Response {
        $participant = new Participant();
        $form = $this->createForm(AddUserType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant = $form->getData();
            $participant->setPassword($passwordEncoder->encodePassword(
                $participant,
                'password123'
            ));
            if ($participant->getIsAdmin()) {
                $participant->setRoles(['ROLE_ADMIN']);
            }
            $entityManager->persist($participant);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur ajouté avec succés.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('participant/add-one.html.twig', [
            'userForm' => $form->createView(),
            'title' => 'Ajouter un utilisateur'
        ]);
    }

    #[Route(
        path: '/admin/users/activation/{id}',
        name: 'admin_users_active',
        requirements: ['id' => '\d+']
    )]
    public function setParticipantActive(int $id, ParticipantRepository $repository, EntityManagerInterface $manager): Response
    {
        try {
            $user = $repository->findOrFail($id);
            if ($user->getId() === $this->getUser()->getId()) {
                $this->addFlash('warning', 'Vous ne pouvez désactiver votre propre compte');
                return $this->redirectToRoute('admin_users');
            }
            $user->setIsActive(!$user->getIsActive());
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Utilisateur  '.$user->getFirstName().' '.$user->getLastName().' '.($user->getIsActive() ? 'activé' : 'désactivé')
            );
            return $this->redirectToRoute('admin_users');
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users');
        }
    }

    #[Route(
        path: '/admin/users/supprimer/{id}',
        name: 'admin_users_remove',
        requirements: ['id' => '\d+']
    )]
    public function removeParticipant(int $id, ParticipantRepository $repository, EntityManagerInterface $manager): Response
    {
        try {
            $user = $repository->findOrFail($id);
            if ($user->getId() === $this->getUser()->getId()) {
                $this->addFlash('warning', 'Vous ne pouvez supprimer votre propre compte');
                return $this->redirectToRoute('admin_users');
            }
            $manager->remove($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Utilisateur  '.$user->getFirstName().' '.$user->getLastName().' supprimé'
            );
            return $this->redirectToRoute('admin_users');
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', 'Cette utilisateur n\'existe pas ou est déjà supprimé');
            return $this->redirectToRoute('admin_users');
        }
    }
}
