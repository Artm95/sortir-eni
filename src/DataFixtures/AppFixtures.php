<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\City;
use App\Entity\Event;
use App\Entity\Participant;
use App\Entity\State;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder) {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $state = new State();
        $state->setLabel("Créée");
        $manager->persist($state);

        $city = new City();
        $city->setName("Rennes");
        $city->setZipCode("35000");
        $manager->persist($city);
        for ($i = 0; $i < 3; $i++) {
            $campus = new Campus();
            $campus->setName("Campus $i");
            $manager->persist($campus);

            for ($j = 0; $j < 3; $j++) {
                $location = new \App\Entity\Location();
                $location->setName("BarBar $i$j");
                $location->setStreet("$i$j rue des Fleurs");
                $location->setLatitude(100.55 + $i);
                $location->setLongitude(100.55 + $j);
                $location->setCity($city);
                $manager->persist($location);

                $participant = new Participant();
                $participant->setEmail("participant$i$j@example.com");
                $participant->setCampus($campus);
                $participant->setFirstName("Pierre$j");
                $participant->setLastName("Dupont$j");
                $participant->setPhoneNumber("+336123456$i$j");
                $participant->setPassword($this->passwordEncoder->encodePassword(
                                 $participant,
                                 'password123'));
                if ($j == 1) $participant->setIsAdmin(true);
                $manager->persist($participant);

                $event = new Event();
                $event->setName("Bar$i$j");
                $event->setDuration(60);

                $daysToAdd=$i+3;
                $event->setStartDate(new DateTime(date('Y-m-d H:i:s', strtotime("+ $daysToAdd days"))));

                $event->setSignUpDeadline($event->getStartDate());

                $event->setMaxParticipants(20);
                $event->setState($state);

                $event->setInfos("Petite sortie au bar !!!");
                $event->setLocation($location);
                $event->setOrganizer($participant);
                $event->setCampus($campus);
                $manager->persist($event);

            }
        }

        $states = ['Ouverte', 'Clôturée', 'Activité en cours', 'Passée', 'Annulée'];
        foreach ($states as $state) {
            $newState = new State();
            $newState->setLabel($state);
            $manager->persist($newState);
        }
        $manager->flush();
    }


}
