<?php

namespace App\Utils;

use App\Entity\Event;
use App\Entity\State;
use Doctrine\ORM\EntityManagerInterface;

class StateUpdater {
    public function updateEventsState(EntityManagerInterface $manager) {
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
            if ($event->getSignUpDeadline()->format('Y-m-d') <= $today) {
                $event->setState($deadLine[0]);
                $manager->persist($event);
            }
        }
        $manager->flush();
    }
}