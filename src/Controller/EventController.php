<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\State;
use App\Form\SearchEventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController {
    #[Route('/', name: 'event')]
    public function index(Request $request, EventRepository $repository, EntityManagerInterface $manager): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->updateEventsState($manager);

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

        if ($event->getState()->getLabel() !== 'Ouverte') {
            $this->addFlash('danger', 'Les inscriptions à cette sortie ne sont pas ouvertes.');
            return $this->redirectToRoute('event');
        }
        if ($event->getParticipants()->count() >= $event->getMaxParticipants()) {
            $this->addFlash('danger', 'Le nombre maximum de participants est déjà atteint.');
            return $this->redirectToRoute('event');
        }
        if ($event->getSignUpDeadline()->format('Y-m-d') < date('Y-m-d')) {
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

        if (!$user->getSubscribedToEvents()->contains($event)) {
            $this->addFlash('warning', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('event');
        }

        if (in_array($event->getState()->getLabel(), ['Activitée en cours', 'Activitée terminée', 'Activité historisée'])) {
            $this->addFlash('danger', 'Il est impossible de se désinscrire d\'une sortie en cours ou terminée.');
            return $this->redirectToRoute('event');
        }

        $user->removeSubscribedToEvent($event);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Désinscription à la sortie '.$event->getName().' validé.');
        return $this->redirectToRoute('event');
    }

    #[Route(
        path: '/publication-sortie/{id}',
        name: 'event_publish',
        requirements: ['id' => '\d+']
    )]
    public function publishEvent(int $id, EntityManagerInterface $manager): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $eventRepository = $manager->getRepository(Event::class);
        $stateRepository = $manager->getRepository(State::class);
        $user = $this->getUser();
        $event = $eventRepository->find($id);
        $openState = $stateRepository->findBy(['label' => 'Ouverte'])[0];

        if ($user->getId() !== $event->getOrganizer()->getId()) {
            $this->addFlash('danger', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
            return $this->redirectToRoute('event');
        }

        if ($event->getState()->getId() === $openState->getId()) {
            $this->addFlash('warning', 'La sortie '.$event->getName().' est déjà publié.');
            return $this->redirectToRoute('event');
        }

        $event->setState($openState);
        $manager->persist($event);
        $manager->flush();

        $this->addFlash('success', 'Sortie '.$event->getName().' publié.');
        return $this->redirectToRoute('event');
    }

    private function updateEventsState(EntityManagerInterface $manager) {
        $eventRepository = $manager->getRepository(Event::class);
        $stateRepository = $manager->getRepository(State::class);

        $events = $eventRepository->statesUpdate();
        $states = $stateRepository->findAll();

        $archived = array_values(array_filter($states, function($element) {
            return $element->getLabel() === 'Activité historisée';
        }));
        $ongoing = array_values(array_filter($states, function($element) {
            return $element->getLabel() === 'Activité en cours';
        }));
        $deadLine = array_values(array_filter($states, function($element) {
            return $element->getLabel() === 'Clôturée';
        }));
        $over = array_values(array_filter($states, function($element) {
            return $element->getLabel() === 'Activité terminée';
        }));


        $monthAgo = date_sub(new \DateTime(), new \DateInterval('P1M'))->format('Y-m-d');
        $now = new \DateTime();
        $today = $now->format('Y-m-d');

        foreach($events as $event) {
            if ($event->getStartDate()->format('Y-m-d') <= $monthAgo) {
                $event->setState($archived[0]);
                $manager->persist($event);
                continue;
            }
            if ($event->getStartDate()->format('Y-m-d') <= $today) {
                if ($event->getStartDate() <= $now) {
                    $eventEnd = date_add($event->getStartDate(), new \DateInterval('PT'.$event->getDuration().'M'));
                    if ($eventEnd < $now) {
                        $event->setState($over[0]);
                    } else {
                        $event->setState($ongoing[0]);
                    }
                    $manager->persist($event);
                    continue;
                }
            }
            if ($event->getSignUpDeadline()->format('Y-m-d') < $today) {
                $event->setState($deadLine[0]);
                $manager->persist($event);
            }
        }
        $manager->flush();
    }
}
