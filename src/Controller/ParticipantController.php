<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\AddUserType;
use App\Form\CsvUploadType;
use App\Form\ProfileType;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Utils\UploaderHelper;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ParticipantController extends AbstractController
{
    /**
     * Edit user profile
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UploaderHelper $uploaderHelper
     * @return Response
     */
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
                $fileName = $uploaderHelper->uploadFile($photo, $destination);
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

    /**
     * Fin user information by if and display it on user's profile page
     * @param int $id
     * @param ParticipantRepository $repository
     * @return RedirectResponse|Response
     */
    #[Route('/profile/{id}', name: 'user_detail', requirements: ['id' => '\d+'])]
    public function showProfile(int $id, ParticipantRepository $repository)
    {
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

    /**
     * Add users from .csv file
     * @param Request $request
     * @param UploaderHelper $uploaderHelper
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param CampusRepository $campusRepository
     * @param ParticipantRepository $repository
     * @return Response
     */
    #[Route('/admin/users', name: 'admin_users')]
    public function addUsers(Request $request, UploaderHelper $uploaderHelper, PaginatorInterface $paginator, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, CampusRepository $campusRepository, ParticipantRepository $repository)
    {
        $fileForm = $this->createForm(CsvUploadType::class);
        $fileForm->handleRequest($request);
        $participants = $repository->getAllWithCampus();

        if ($fileForm->isSubmitted() && $fileForm->isValid()) {
            $file = $fileForm->get('csv')->getData();
            $destination = $this->getParameter('kernel.project_dir') . '/public/uploads';
            $fileName = $uploaderHelper->uploadFile($file, $destination);
            $rowNo = 0;
            $campus = $campusRepository->findAll();
            $password = $passwordEncoder->encodePassword(new Participant(), "password123");
            // $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
            // $fp is file pointer to file
            try {
                if (($fp = fopen($destination . "/" . $fileName, "r")) !== FALSE) {
                    while (($row = fgetcsv($fp, 1000, ",")) !== FALSE) {
                        //skip row 
                        if ($rowNo === 0) {
                            $rowNo++;
                            continue;
                        }
                        $participant = new Participant();
                        $participant->setEmail($row[2]);
                        $participantsWithSameEmail = array_values(array_filter($participants, function ($element) use ($row) {
                            return str_contains($element->getEmail(), $row[2]);
                        }));
                        //skip participant if email already exists in the database
                        if (count($participantsWithSameEmail) > 0) {
                            continue;
                        }
                        $camp = array_values(array_filter($campus, function ($element) use ($row) {
                            return $element->getId() === intval($row[1]);
                        }));
                        if (count($camp) > 0) {
                            $participant->setCampus($camp[0]);
                        } else {
                            $participant->setCampus($campus[0]);
                        }
                        if (intval($row[8]) === 1) {
                            $participant->setIsAdmin(true);
                            $participant->setRoles(["ROLE_ADMIN"]);
                        }
                        $participant->setPassword($password);
                        $participant->setFirstName($row[5]);
                        $participant->setLastName($row[6]);
                        $participant->setPhoneNumber($row[7]);
                        $participant->setIsActive(true);
                        $entityManager->persist($participant);

                        //upload a batch of 25
                        if ($rowNo % 25 == 0) {
                            $entityManager->flush();
                            // $entityManager->clear(); //causes error - multiple non-persisted entities ?? 
                        }
                        $rowNo++;
                    }
                    fclose($fp);
                }
                $entityManager->flush();
                $entityManager->clear();
                $rowNo -= 1;
                if ($rowNo > 0) {
                    $this->addFlash('success', $rowNo . ' utilisateurs importés avec succés');
                } else {
                    $this->addFlash('success', 'La base de données est à jour. 0 utilisateurs importés.');
                }
            } catch (Exception $e) {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\'import du batch ' . round($rowNo / 25, 0) . ' : ' . $e->getMessage());
            }
            $uploaderHelper->deleteUploadedFile($destination . "/" . $fileName);
            $participants = $repository->getAllWithCampus();
        }

        $pagination = $paginator->paginate($participants, $request->query->getInt('page', 1), 10);

        return $this->render('participant/add-new.html.twig', [
            'title' => 'Gestion des utilisateurs',
            'fileForm' => $fileForm->createView(),
            'participants' => $pagination,
        ]);
    }

    /**
     * Create a new user via creation form
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    #[Route('/admin/users/new', name: 'admin_users_add')]
    public function addOne(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $participant = new Participant();
        $form = $this->createForm(AddUserType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
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
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('danger', 'Un utilisateur avec cet email existe déjà');
            }
        }

        return $this->render('participant/add-one.html.twig', [
            'userForm' => $form->createView(),
            'title' => 'Ajouter un utilisateur'
        ]);
    }

    /**
     * Activate and deactivate users
     * @param int $id
     * @param ParticipantRepository $repository
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route(
        path: '/admin/users/activate/{id}',
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

    /**
     * Delete a user by id
     * @param int $id
     * @param ParticipantRepository $repository
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route(
        path: '/admin/users/delete/{id}',
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
            $this->addFlash('danger', 'Cet utilisateur n\'existe pas ou est déjà supprimé');
            return $this->redirectToRoute('admin_users');
        }
    }
}
