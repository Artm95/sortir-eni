<?php

namespace App\Controller;

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

class ParticipantController extends AbstractController
{

    #[Route('/edit-profile', name: 'participant_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, UploaderHelper $uploaderHelper): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $photo = $form["avatar"]->getData();
            $newPass = $user->getPlainPassword();
            $confirmation = $user->getConfirmation();
            if ($newPass !== null && $confirmation !== null && $newPass == $confirmation) {
                $user->setPassword($passwordEncoder->encodePassword(
                    $user,
                    $user->getPlainPassword()
                ));
                $this->addFlash('success', 'Mot de passe modifié avec succés. New pass ' . $newPass);
            }

            if ($photo){
                $destination = $this->getParameter('kernel.project_dir') . '/public/uploads';
                if ($user->getPhoto()){
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
    public function showProfile(int $id, ParticipantRepository $repository){
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
}
