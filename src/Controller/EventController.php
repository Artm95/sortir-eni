<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Form\SearchEvent;
use App\Entity\Location;
use App\Entity\Participant;
use App\Entity\State;
use App\Form\CancelEventType;
use App\Form\CityChoiceType;
use App\Form\EventType;
use App\Form\LocationType;
use App\Form\SearchEventType;
use App\Repository\EventRepository;
use App\Utils\StateUpdater;
use App\Utils\UploaderHelper;
use Detection\MobileDetect;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Error;
use Knp\Component\Pager\PaginatorInterface;
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
        StateUpdater $updater,
        PaginatorInterface $paginator
    ): Response {
        $detect = new MobileDetect();
        $updater->updateEventsState($manager);

        $user = $this->getUser();
        //if mobile version we send only user's campus events
        if ($detect->isMobile() && !$detect->isTablet()){
            $events = $repository->findBy(["campus" => $user->getCampus()->getId()]);
            $pagination = $paginator->paginate($events, $request->query->getInt('page', 1), 10);
            return $this->render('event/index.html.twig', [ 'events' => $events, 'isMobile' => true, 'pagination' => $pagination]);
        }else{
            $searchEvent = new SearchEvent();
            $form = $this->createForm(SearchEventType::class, $searchEvent);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $searchEvent = $form->getData();
                $events = $repository->search(
                    $searchEvent,
                    $user
                );
            } else {
                $events = $repository->getAllBasic();
            }
            $pagination = $paginator->paginate($events, $request->query->getInt('page', 1), 10);
            return $this->render(
                'event/index.html.twig',
                [
                    'events' => $events,
                    'searchForm' => $form->createView(),
                    'isMobile' => false,
                    'pagination' => $pagination
                ]
            );
        }
    }

    /**
     * Show event's details page
     * @param int $id
     * @param EventRepository $repository
     * @return Response
     */
    #[Route(
        path: '/event/{id}',
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
        path: '/subscribe/{id}',
        name: 'event_subscribe',
        requirements: ['id' => '\d+']
    )]
    public function subscribeTo(int $id, Request $request, EventRepository $repository, EntityManagerInterface $manager): Response
    {
        try {
            $user = $this->getUser();
            $event = $repository->findOrFail($id);
            if ($event->getState()->getLabel() !== 'Ouverte') {
                $this->addFlash('danger', 'Les inscriptions ?? cette sortie ne sont pas ouvertes.');
                return $this->redirectToRoute('event');
            }
            if ($event->getParticipants()->count() >= $event->getMaxParticipants()) {
                $this->addFlash('danger', 'Le nombre maximum de participants est d??j?? atteint.');
                return $this->redirectToRoute('event');
            }
            if ($event->getSignUpDeadline()->format('Y-m-d') < date('Y-m-d')) {
                $this->addFlash('danger', 'La date d\'inscription pour cette sortie est d??pass??e.');
                return $this->redirectToRoute('event');
            }

            if ($user->getSubscribedToEvents()->contains($event)) {
                $this->addFlash('warning', 'Vous ??tes d??j?? inscrit ?? cette sortie.');
                return $this->redirectToRoute('event');
            }

            // if ($event->getOrganizer()->getId() === $user->getId()) {
            //     $this->addFlash(
            //         'warning',
            //         'Vous ne pouvez pas vous inscrire ?? une sortie dont vous ??tes l\'organisateur.'
            //     );
            //     return $this->redirectToRoute('event');
            // }

            $user->addSubscribedToEvent($event);
            $manager->persist($user);

            if ($event->getParticipants()->count() === $event->getMaxParticipants() - 1) {
                $stateRepository = $manager->getRepository(State::class);
                $closedState = $stateRepository->findBy(['label' => 'CL??tur??e'])[0];
                $event->setState($closedState);
                $manager->persist($event);
            }

            $manager->flush();
            $this->addFlash('success', 'Inscription ?? la sortie ' . $event->getName() . ' valid??.');
            return $this->redirect($request->headers->get('referer'));

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
        path: '/unsubscribe/{id}',
        name: 'event_unsubscribe',
        requirements: ['id' => '\d+']
    )]
    public function unsubscribeTo(int $id, Request $request, EventRepository $repository, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        try {
            $event = $repository->findOrFail($id);
            if (!$user->getSubscribedToEvents()->contains($event)) {
                $this->addFlash('warning', 'Vous n\'??tes pas inscrit ?? cette sortie.');
                return $this->redirect($request->headers->get('referer'));
            }

            if (!in_array($event->getState()->getLabel(), ['Ouverte', 'Cl??tur??e'])) {
                $this->addFlash('danger', 'Il est impossible de se d??sinscrire d\'une sortie en cours ou termin??e.');
                return $this->redirect($request->headers->get('referer'));
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

            $this->addFlash('success', 'D??sinscription ?? la sortie ' . $event->getName() . ' valid??.');
            return $this->redirect($request->headers->get('referer'));


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
        path: '/publish/{id}',
        name: 'event_publish',
        requirements: ['id' => '\d+']
    )]
    public function publishEvent(int $id, Request $request, EntityManagerInterface $manager): Response
    {
        try {
            $eventRepository = $manager->getRepository(Event::class);
            $stateRepository = $manager->getRepository(State::class);
            $user = $this->getUser();
            $event = $eventRepository->findOrFail($id);
            $openState = $stateRepository->findBy(['label' => 'Ouverte'])[0];

            if ($user->getId() !== $event->getOrganizer()->getId()) {
                $this->addFlash('danger', 'Vous n\'??tes pas l\'organisateur de cette sortie.');
                return $this->redirect($request->headers->get('referer'));

            }

            if ($event->getState()->getLabel() !== 'En cr??ation') {
                $this->addFlash('warning', 'La sortie ' . $event->getName() . ' est d??j?? publi??.');
                return $this->redirect($request->headers->get('referer'));

            }

            $event->setState($openState);
            $manager->persist($event);
            $manager->flush();
            $id = $event->getId();
            $this->addFlash('success', 'Sortie ' . $event->getName() . ' publi??.');
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
        $detect = new MobileDetect();
        if ($detect->isMobile() && !$detect->isTablet())
            return $this->addFlashAndRedirectToHome("Vous ne pouvez pas cr??er des ??v??nement en version mobile");

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
            $this->addFlash('success', 'Vous avez cr???? une sortie ! Yahoo !!');
            $id = $this->saveEvent($event, $state)->getId();
            if ($state === 'Ouverte') {
                return $this->redirectToRoute('event_subscribe', ['id' => $id]);
            } else {
                return $this->redirectToRoute('event_detail', ['id' => $id]);
            }
        }

        return $this->render(
            'event/new-event.html.twig',
            [
                'eventForm' => $eventForm->createView(),
                'locationForm' => $locationForm->createView(),
                'title' => 'Cr??er une sortie',
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
        $detect = new MobileDetect();
        if ($detect->isMobile() && !$detect->isTablet())
            return $this->addFlashAndRedirectToHome("Vous ne pouvez pas modifier des ??v??nements en version mobile");
        $user = $this->getUser();
        try {
            $event = $repository->getAllEventDataById($id);
            $locationForm = $this->createForm(LocationType::class, new Location());

            if (($user->isOrganizer($event) || $user->getIsAdmin()) && $event->getState()->getLabel() === "En cr??ation") {
                $eventForm = $this->createForm(EventType::class, $event);
                $eventForm->handleRequest($request);
                if ($eventForm->isSubmitted() && $eventForm->isValid()) {
                    $event = $eventForm->getData();
                    $state = $request->request->get('send');
                    $this->saveEvent($event, $state);
                    $this->addFlash('success', "L'evenement a ??t?? modifi?? avec succ??s");
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
     * Redirects to home page with an error flash message
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
        if (($user->isOrganizer($event) || $user->getIsAdmin()) && ($event->getState()->getLabel() === 'Ouverte' || $event->getState()->getLabel() === 'Cl??tur??e')) {
            $infos = $event->getInfos();
            $cancelForm = $this->createForm(CancelEventType::class, $event);
            $cancelForm->get('infos')->setData('');
            $cancelForm->handleRequest($request);

            if ($cancelForm->isSubmitted() && $cancelForm->isValid()) {
                $newInfos = $cancelForm->getData()->getInfos();
                $event->setInfos($infos . '<br /> Motif d\'annulation : ' . $newInfos);
                $this->saveEvent($event, 'Annul??e');
                $this->addFlash('success', "L'??venement a ??t?? annul??");
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
            $this->addFlash('danger', "Vous ne pouvez pas annuler cet ??venement !");
            return $this->redirectToRoute('event');
        }
    }
}
