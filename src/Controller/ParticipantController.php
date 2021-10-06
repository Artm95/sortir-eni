<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Repository\ParticipantRepository;
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
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
                    unlink($destination . '/' . $user->getPhoto());
                }
                //we create a unique file name based on user id
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = $originalFilename . '-' .$user->getId() .'.'. $photo->guessExtension();
                $user->setPhoto($fileName);
                $photo->move($destination, $fileName);
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $repository->find($id);

        return $this->render('participant/show-profile.html.twig', [
            'user' => $user
        ]);

    }
}
