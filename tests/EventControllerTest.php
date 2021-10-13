<?php


namespace App\Tests;


use App\Repository\EventRepository;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    const USER_MAIL = "madeleine50@garnier.com";
    public function testSubscribeToEventSuccessful(){
        $client = $this->logInUser(self::USER_MAIL);
        $eventRepository = static::$container->get(EventRepository::class);
    }

    private function logInUser($mail){
        $client = static::createClient();
        $userRepository = static::$container->get(ParticipantRepository::class);

        $user = $userRepository->findOneByEmail($mail);

        $client->loginUser($user);

        return $client;
    }

}