<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\SearchEventType;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController {
    #[Route('/', name: 'event')]
    public function index(Request $request, EventRepository $repository): Response {
        $form = $this->createForm(SearchEventType::class, [
            'action' => $this->generateUrl('event'),
            'method' => 'GET',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $events = $repository->getAllWithOrganizer();
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
        $event = $repository->find($id);

        return $this->render('event/detail.html.twig', [
            'event' => $event,
        ]);
    }
}
