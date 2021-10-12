<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Form\SearchEvent;
use App\Entity\Location;
use App\Entity\State;
use App\Form\CancelEventType;
use App\Form\CityChoiceType;
use App\Form\EventType;
use App\Form\LocationType;
use App\Form\SearchEventType;
use App\Repository\EventRepository;
use App\Utils\StateUpdater;
use App\Utils\UploaderHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    /**
     * Handling search on events
     * @param Request $request
     * @param EventRepository $repository
     * @param EntityManagerInterface $manager
     * @param StateUpdater $updater
     * @return Response
     */
    #[Route('/', name: 'event')]
    public function index(
        Request $request,
        EventRepository $repository,
        EntityManagerInterface $manager,
        StateUpdater $updater
    ): Response {
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
            $events = $repository->getAllBasic();
        }

        return $this->render(
            'event/index.html.twig',
            [
                'events' => $events,
                'searchForm' => $form->createView()
            ]
        );
    }

    /**
     * Show event's details page
     * @param int $id
     * @param EventRepository $repository
     * @return Response
     */
    #[Route(
        path: '/sortie/{id}',
        name: 'event_detail',
        requirements: ['id' => '\d+']
    )]
    public function detail(int $id, EventRepository $repository): Response
    {
        try {
            $event = $repository->getAllEventDataById($id);
            return $this->render(
                'event/detail.html.twig',
                [
                    'event' => $event
                ]
            );
        } catch (EntityNotFoundException $e) {
            return $this->addFlashAndRedirectToHome($e->getMessage());
        }
    }

    /**
     * Handle singing in events
     * @param int $id
     * @param EventRepository $repository
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route(
        path: '/inscription-sortie/{id}',
        name: 'event_subscribe',
        requirements: ['id' => '\d+']
    )]
    public function subscribeTo(int $id, EventRepository $repository, EntityManagerInterface $manager): Response
    {
        try {
            $user = $this->getUser();
            $event = $repository->findOrFail($id);
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

            // if ($event->getOrganizer()->getId() === $user->getId()) {
            //     $this->addFlash(
            //         'warning',
            //         'Vous ne pouvez pas vous inscrire à une sortie dont vous êtes l\'organisateur.'
            //     );
            //     return $this->redirectToRoute('event');
            // }

            $user->addSubscribedToEvent($event);
            $manager->persist($user);

            if ($event->getParticipants()->count() === $event->getMaxParticipants() - 1) {
                $stateRepository = $manager->getRepository(State::class);
                $closedState = $stateRepository->findBy(['label' => 'CLôturée'])[0];
                $event->setState($closedState);
                $manager->persist($event);
            }

            $manager->flush();
            $this->addFlash('success', 'Inscription à la sortie ' . $event->getName() . ' validé.');
            return $this->redirectToRoute('event');
        } catch (EntityNotFoundException $e) {
            return $this->addFlashAndRedirectToHome($e->getMessage());
        }
    }

    /**
     * Handles unsubscribing for events
     * @param int $id
     * @param EventRepository $repository
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route(
        path: '/desistement-sortie/{id}',
        name: 'event_unsubscribe',
        requirements: ['id' => '\d+']
    )]
    public function unsubscribeTo(int $id, EventRepository $repository, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        try {
            $event = $repository->findOrFail($id);
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

            $this->addFlash('success', 'Désinscription à la sortie ' . $event->getName() . ' validé.');
            return $this->redirectToRoute('event');
        } catch (EntityNotFoundException $e) {
            return $this->addFlashAndRedirectToHome($e->getMessage());
        }
    }

    /**
     * Handles publish event action
     * @param int $id
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route(
        path: '/publication-sortie/{id}',
        name: 'event_publish',
        requirements: ['id' => '\d+']
    )]
    public function publishEvent(int $id, EntityManagerInterface $manager): Response
    {
        try {
            $eventRepository = $manager->getRepository(Event::class);
            $stateRepository = $manager->getRepository(State::class);
            $user = $this->getUser();
            $event = $eventRepository->findOrFail($id);
            $openState = $stateRepository->findBy(['label' => 'Ouverte'])[0];

            if ($user->getId() !== $event->getOrganizer()->getId()) {
                $this->addFlash('danger', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
                return $this->redirectToRoute('event');
            }

            if ($event->getState()->getLabel() !== 'En création') {
                $this->addFlash('warning', 'La sortie ' . $event->getName() . ' est déjà publié.');
                return $this->redirectToRoute('event');
            }

            $event->setState($openState);
            $manager->persist($event);
            $manager->flush();
            $id = $event->getId();
            $this->addFlash('success', 'Sortie ' . $event->getName() . ' publié.');
            return $this->redirectToRoute('event_subscribe', ['id' => $id]);
        } catch (EntityNotFoundException $e) {
            return $this->addFlashAndRedirectToHome($e->getMessage());
        }
    }

    /**
     * Create and persist new event
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/create', name: 'event_new')]
    public function create(Request $request): Response
    {
        $event = new Event();
        $user = $this->getUser();
        $event->setCampus($user->getCampus());
        $event->setOrganizer($user);

        $eventForm = $this->createForm(EventType::class, $event);
        $eventForm->handleRequest($request);
        $locationForm = $this->createForm(LocationType::class, new Location());

        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            $event = $eventForm->getData();
            $state = $request->request->get('send');
            $this->addFlash('success', 'Vous avez créé une sortie ! Yahoo !!');
            $id = $this->saveEvent($event, $state)->getId();
            if ($state === 'Ouverte') {
                return $this->redirectToRoute('event_subscribe', ['id' => $id]);
            } else {
                return $this->redirectToRoute('event');
            }
        }

        return $this->render(
            'event/new-event.html.twig',
            [
                'eventForm' => $eventForm->createView(),
                'locationForm' => $locationForm->createView(),
                'title' => 'Créer une sortie',
            ]
        );
    }

    /**
     * Edit the event and persisting in database
     * @param $id
     * @param EventRepository $repository
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(
        path: '/edit-event/{id}',
        name: 'event_edit',
        requirements: ['id' => '\d+']
    )]
    public function edit($id, EventRepository $repository, Request $request)
    {
        $user = $this->getUser();
        try {
            $event = $repository->getAllEventDataById($id);
            $locationForm = $this->createForm(LocationType::class, new Location());

            if (($user->isOrganizer($event) || $user->getIsAdmin()) && $event->getState()->getLabel() === "En création") {
                $eventForm = $this->createForm(EventType::class, $event);
                $eventForm->handleRequest($request);
                if ($eventForm->isSubmitted() && $eventForm->isValid()) {
                    $event = $eventForm->getData();
                    $state = $request->request->get('send');
                    $this->saveEvent($event, $state);
                    $this->addFlash('success', "L'evenement a été modifié avec succès");
                    return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
                }
                return $this->render(
                    'event/new-event.html.twig',
                    [
                        'eventState' => $event->getState()->getLabel(),
                        'locationForm' => $locationForm->createView(),
                        'eventForm' => $eventForm->createView(),
                        'title' => 'Modifier une sortie',
                    ]
                );
            } else {
                $this->addFlash('danger', "Vous n'avez pas le droit de modifier cette sortie");
                return $this->redirectToRoute('event');
            }
        } catch (EntityNotFoundException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('event');
        }
    }

    /**
     * Helps to persist event to database
     * @param $event
     * @param string $state
     * @return mixed
     */
    private function saveEvent($event, string $state)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $stateRepository = $entityManager->getRepository(State::class);
        $states = $stateRepository->findAll();
        $state = array_values(
            array_filter(
                $states,
                function ($element) use ($state) {
                    return $element->getLabel() === $state;
                }
            )
        );
        $event->setState($state[0]);
        $entityManager->persist($event);
        $entityManager->flush();
        return $event;
    }

    /**
     * Redirects to hime page with an error flash message
     * @param $flashMessage
     * @return RedirectResponse
     */
    public function addFlashAndRedirectToHome($flashMessage)
    {
        $this->addFlash('danger', $flashMessage);
        return $this->redirectToRoute('event');
    }

    /**
     * @param $id
     * @param Request $request
     * @param EventRepository $eventRepo
     * @return Response
     */
    #[Route(
        path: '/cancel/{id}',
        name: 'event_cancel',
        requirements: ['id' => '\d+']
    )]
    public function cancel($id, Request $request, EventRepository $eventRepo): Response
    {
        $user = $this->getUser();
        $event = $eventRepo->find($id);
        //event should be open or passed the subscription deadline (cloturee) and user should be its organizer
        if (($user->isOrganizer($event) || $user->getIsAdmin()) && ($event->getState()->getLabel() === 'Ouverte' || $event->getState()->getLabel() === 'Clôturée')) {
            $infos = $event->getInfos();
            $cancelForm = $this->createForm(CancelEventType::class, $event);
            $cancelForm->get('infos')->setData('');
            $cancelForm->handleRequest($request);

            if ($cancelForm->isSubmitted() && $cancelForm->isValid()) {
                $newInfos = $cancelForm->getData()->getInfos();
                $event->setInfos($infos . '<br /> Motif d\'annulation : ' . $newInfos);
                $this->saveEvent($event, 'Annulée');
                $this->addFlash('success', "L'évenement a été annulé");
                return $this->redirectToRoute('event_detail', ['id' => $id]);
            }

            return $this->render(
                'event/cancel.html.twig',
                [
                    'title' => 'Annulation de la sortie',
                    'event' => $event,
                    'cancelForm' => $cancelForm->createView()
                ]
            );
        } else {
            $this->addFlash('danger', "Vous ne pouvez pas annuler cet évenement !");
            return $this->redirectToRoute('event');
        }
    }
}
