<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ParticipantController extends AbstractController
{

    #[Route('/edit-profile', name: 'participant_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $newPass = $user->getPlainPassword();
            $confirmation = $user->getConfirmation();
            if ($newPass !== null && $confirmation !== null && $newPass == $confirmation) {
                $user->setPassword($passwordEncoder->encodePassword(
                    $user,
                    $user->getPlainPassword()
                ));
                $this->addFlash('success', 'Mot de passe modifié avec succés. New pass ' . $newPass);
            }
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profil modifié avec succés.');
            return $this->redirectToRoute('event');
        }

        return $this->render('participant/edit-profile.html.twig', [
            'controller_name' => 'ParticipantController',
            'userForm' => $form->createView(),
            'title' => 'Mon profil'
        ]);
    }

    #[Route('/profile/{id}', name: 'participant_profile', requirements: ['id' => '\d+'])]
    public function showProfile(){

    }
}
