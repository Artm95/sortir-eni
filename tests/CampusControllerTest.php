<?php


namespace App\Tests;


use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CampusControllerTest extends WebTestCase
{
    const ADMIN_EMAIL = "honore42@laposte.net";
    public function testAccessDeniedIfNotAdmin(){
        $client = static::createClient();
        $userRepository = static::$container->get(ParticipantRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByEmail('arthur.tessier@lebrun.com');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', "/admin/campuses");
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminHasAccess(){
        $client = $this->logInAdmin(self::ADMIN_EMAIL);
        $client->request('GET', "/admin/campuses");
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAddCampus(){
        $client = $this->logInAdmin(self::ADMIN_EMAIL);
        $client = $this->submitNewCampusForm($client, "Paris");

        $this->assertResponseRedirects('/admin/campuses');
        $client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');
    }

    public function testEmptyDataSent(){
        $client = $this->logInAdmin(self::ADMIN_EMAIL);
        $this->submitNewCampusForm($client, "");
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testEditCampus(){
        $client = $this->logInAdmin(self::ADMIN_EMAIL);
        $crawler = $client->request('GET', '/admin/campus/edit/1');
        $this->assertSelectorTextContains("#submit-btn", "Modifier");
        $form = $crawler->selectButton('Modifier')->form();
        $client->submit($form);
        $this->assertResponseRedirects('/admin/campuses');
        $client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');
    }


    private function logInAdmin($mail){
        $client = static::createClient();
        $userRepository = static::$container->get(ParticipantRepository::class);

        $admin = $userRepository->findOneByEmail($mail);

        $client->loginUser($admin);

        return $client;
    }


    private function submitNewCampusForm($client, $value){
        $crawler = $client->request('GET', '/admin/campuses');
        $form = $crawler->selectButton('Ajouter')->form([
            'campus[name]' => $value
        ]);
        $client->submit($form);
        return $client;
    }
}