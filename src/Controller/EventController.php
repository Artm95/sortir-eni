<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\SearchEventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController {
    #[Route('/', name: 'event')]
    public function index(Request $request, EventRepository $repository): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createForm(SearchEventType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            
            $events = $repository->search(
                $data['campus'],
                $data['name'],
                $data['from'],
                $data['to'],
                $data['organized'],
                $data['subscribed'],
                $data['notSubscribed'],
                $data['over'],
                $this->getUser()
            );
        } else {
            $events = $repository->getAllWithOrganizer();
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'searchForm' => $form->createView()
        ]);
    }

    #[Route(
        path: '/sortie/{id}',
        name: 'event_detail',
        requirements: ['id' => '\d+']
    )]
    public function detail(int $id, EventRepository $repository): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = $repository->find($id);

        return $this->render('event/detail.html.twig', [
            'event' => $event
        ]);
    }

    #[Route(
        path: '/inscription-sortie/{id}',
        name: 'event_subscribe',
        requirements: ['id' => '\d+']
    )]
    public function subscribeTo(int $id, EventRepository $repository, EntityManagerInterface $manager): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $event = $repository->find($id);
        $event->getSignUpDeadline()->setTime(23, 59, 59);

        if ($event->getState()->getLabel() !== 'Ouverte') {
            $this->addFlash('danger', 'Les inscriptions à cette sortie ne sont pas ouvertes.');
            return $this->redirectToRoute('event');
        }
        if ($event->getSignUpDeadline() < new \DateTime()) {
            $this->addFlash('danger', 'La date d\'inscription pour cette sortie est dépassée.');
            return $this->redirectToRoute('event');
        }

        if ($user->getSubscribedToEvents()->contains($event)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette sortie.');
            return $this->redirectToRoute('event');
        }

        if ($event->getOrganizer()->getId() === $user->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas vous inscrire à une sortie dont vous êtes l\'organisateur.');
            return $this->redirectToRoute('event');
        }

        $user->addSubscribedToEvent($event);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Inscription à la sortie '.$event->getName().' validé.');
        return $this->redirectToRoute('event');
    }

    #[Route(
        path: '/desistement-sortie/{id}',
        name: 'event_unsubscribe',
        requirements: ['id' => '\d+']
    )]
    public function unsubscribeTo(int $id, EventRepository $repository, EntityManagerInterface $manager): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $event = $repository->find($id);
        $event->getSignUpDeadline()->setTime(23, 59, 59);

        if (!$user->getSubscribedToEvents()->contains($event)) {
            $this->addFlash('warning', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('event');
        }

        if (in_array($event->getState()->getLabel(), ['Activitée en cours', 'Passée'])) {
            $this->addFlash('danger', 'Il est impossible de se désinscrire d\'une sortie en cours.');
            return $this->redirectToRoute('event');
        }

        $user->removeSubscribedToEvent($event);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Désinscription à la sortie '.$event->getName().' validé.');
        return $this->redirectToRoute('event');
    }
}
