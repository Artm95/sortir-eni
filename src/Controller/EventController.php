<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Form\SearchEvent;
use App\Entity\Location;
use App\Entity\State;
use App\Form\CityChoiceType;
use App\Form\EventType;
use App\Form\LocationType;
use App\Form\SearchEventType;
use App\Repository\EventRepository;
use App\Utils\StateUpdater;
use App\Utils\UploaderHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController {
    #[Route('/', name: 'event')]
    public function index(Request $request, EventRepository $repository, EntityManagerInterface $manager, StateUpdater $updater): Response {
        $updater->updateEventsState($manager);
        $searchEvent = new SearchEvent();
        $form = $this->createForm(SearchEventType::class, $searchEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $searchEvent = $form->getData();
            $events = $repository->search(
                $searchEvent,
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
        $event = $repository->getAllEventDataById($id);

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

        if ($event->getParticipants()->count() === $event->getMaxParticipants() - 1) {
            $stateRepository = $manager->getRepository(State::class);
            $closedState = $stateRepository->findBy(['label' => 'CLôturée'])[0];
            $event->setState($closedState);
            $manager->persist($event);
        }

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
        $user = $this->getUser();
        $event = $repository->find($id);

        if (!$user->getSubscribedToEvents()->contains($event)) {
            $this->addFlash('warning', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('event');
        }

        if (!in_array($event->getState()->getLabel(), ['Ouverte', 'Clôturée'])) {
            $this->addFlash('danger', 'Il est impossible de se désinscrire d\'une sortie en cours ou terminée.');
            return $this->redirectToRoute('event');
        }

        $user->removeSubscribedToEvent($event);
        $manager->persist($user);
        if ($event->getParticipants()->count() === $event->getMaxParticipants() && $event->getSignUpDeadline()->format('Y-m-d') >= date('Y-m-d')) {
            $stateRepository = $manager->getRepository(State::class);
            $openState = $stateRepository->findBy(['label' => 'Ouverte'])[0];
            $event->setState($openState);
            $manager->persist($event);
        }
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
        $eventRepository = $manager->getRepository(Event::class);
        $stateRepository = $manager->getRepository(State::class);
        $user = $this->getUser();
        $event = $eventRepository->find($id);
        $openState = $stateRepository->findBy(['label' => 'Ouverte'])[0];

        if ($user->getId() !== $event->getOrganizer()->getId()) {
            $this->addFlash('danger', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
            return $this->redirectToRoute('event');
        }

        if ($event->getState()->getLabel() !== 'En création') {
            $this->addFlash('warning', 'La sortie '.$event->getName().' est déjà publié.');
            return $this->redirectToRoute('event');
        }

        $event->setState($openState);
        $manager->persist($event);
        $manager->flush();

        $this->addFlash('success', 'Sortie '.$event->getName().' publié.');
        return $this->redirectToRoute('event');
    }

    #[Route(path: '/create', name: 'event_new')]
    public function create(Request $request): Response
    {
        $event = new Event();
        $user = $this->getUser();
        $event->setCampus($user->getCampus());
        $event->setOrganizer($user);

        $eventForm = $this->createForm(EventType::class, $event);
        $eventForm->handleRequest($request);

        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            $event = $eventForm->getData();
            $state = $request->request->get('send');
            $this->saveEvent($event, $state);

            $this->addFlash('success', 'Vous avez créé une sortie ! Yahoo !!');
            return $this->redirectToRoute('event');
        }

        return $this->render('event/new-event.html.twig', [
            'eventForm' => $eventForm->createView(),
            'title' => 'Créer une sortie',
        ]);
    }

    #[Route(
    path: '/edit-event/{id}',
    name: 'event_edit',
    requirements: ['id' => '\d+']
    )]
    public function edit($id, EventRepository $repository, Request $request){
        $user = $this->getUser();
        $event = $repository->getAllEventDataById($id);

        if ($user->isOrganizer($event) && $event->getState()->getLabel() === "En création" ){
            $eventForm = $this->createForm(EventType::class, $event);
            $eventForm->handleRequest($request);
            if ($eventForm->isSubmitted() && $eventForm->isValid()) {
                $event = $eventForm->getData();
                $state = $request->request->get('send');
                $this->saveEvent($event, $state);
                $this->addFlash('success', "L'evenement a été modifié avec succès");
                return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
            }
            return $this->render('event/new-event.html.twig', [
                'eventState' => $event->getState()->getLabel(),
                'eventForm' => $eventForm->createView(),
                'title' => 'Modifier une sortie',
            ]);
        } else {
            $this->addFlash('danger', "Vous n'avez pas le droit de modifier cette sortie");
            return $this->redirectToRoute('event');
        }

    }


    private function saveEvent($event, string $state){
        $entityManager = $this->getDoctrine()->getManager();
        $stateRepository = $entityManager->getRepository(State::class);
        $states = $stateRepository->findAll();
        $state = array_values(array_filter($states, function ($element) use ($state)  {
            return $element->getLabel() === $state;
        }));
        $event->setState($state[0]);
        $entityManager->persist($event);
        $entityManager->flush();
        return $event;
    }


}
