<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\City;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\Participant;
use App\Entity\State;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Faker;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder) {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $statesData = ['En création', 'Ouverte', 'Clôturée', 'Activité en cours', 'Activité terminée', 'Annulée', 'Activité historisée'];
        $states = array();
        foreach ($statesData as $state) {
            $newState = new State();
            $newState->setLabel($state);
            array_push($states, $newState);
            $manager->persist($newState);
        }
        $citiesData = array(
            array('Rennes', '35000'),
            array('Niort', '79000'),
            array('Nantes', '44300')
        );
        $cities = array();
        $campus = array();
        foreach ($citiesData as $city) {
            $newCampus = new Campus();
            $newCampus->setName($city[0]);
            array_push($campus, $newCampus);
            $manager->persist($newCampus);

            $newCity = new City();
            $newCity->setName($city[0]);
            $newCity->setZipCode($city[1]);
            array_push($cities, $newCity);
            $manager->persist($newCity);
        }

        $faker = Faker\Factory::create('fr_FR');
        // on crée 100 participants avec infos "aléatoires" en français
        $participants = array();
        for ($i = 0; $i < 100; $i++) {
            $participants[$i] = new Participant();
            $participants[$i]->setLastName($faker->lastName);
            $participants[$i]->setFirstName($faker->firstName);
            $participants[$i]->setFirstName($faker->firstName);
            $participants[$i]->setEmail($faker->email);
            $participants[$i]->setCampus($campus[rand(0, count($campus) - 1)]);
            $participants[$i]->setPhoneNumber(str_replace(" ", "", $faker->phoneNumber));

            $participants[$i]->setPassword($this->passwordEncoder->encodePassword(
                $participants[$i],
                'password123'
            ));
            if ($i == 0) {
                $participants[$i]->setRoles(array('ROLE_ADMIN'));
                $participants[$i]->setIsAdmin(true);
            }
            $manager->persist($participants[$i]);
        }
        // on crée 100 sorties avec infos "aléatoires" en français
        $locations = array();
        for ($i = 0; $i < 100; $i++) {
            $locations[$i] = new Location();
            $locations[$i]->setName($faker->words(3, true));
            $locations[$i]->setStreet($faker->address);
            $locations[$i]->setLatitude($faker->randomFloat(8, -90, 90));
            $locations[$i]->setLongitude($faker->randomFloat(8, -180, 180));
            $city = $cities[rand(0, count($cities) - 1)];
            $locations[$i]->setCity($city);
            $manager->persist($locations[$i]);
        }

        for ($j = 0; $j < 100; $j++) {
            $event = new Event();
            $event->setName($faker->words(3, true));
            $event->setDuration($faker->numberBetween(15, 180));
            $event->setStartDate($faker->dateTimeBetween('-2 months', '+2 months'));
            $event->setSignUpDeadline(date_sub($event->getStartDate(), new \DateInterval('P' . rand(1, 10) . 'D')));
            $event->setMaxParticipants($faker->numberBetween(2, 30));
            $event->setState($states[0]);

            // $event->setInfos($faker->paragraph(3), true);
            $event->setLocation($locations[rand(0, count($locations) - 1)]);
            $event->setOrganizer($participants[rand(0, count($participants) - 1)]);
            $event->setCampus($campus[rand(0, count($campus) - 1)]);
            $manager->persist($event);
        }
        $manager->flush();
    }
}
