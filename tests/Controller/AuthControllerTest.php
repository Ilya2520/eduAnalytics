<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->clearUsers();
    }

    private function clearUsers(): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement('ALTER TABLE users DISABLE TRIGGER ALL');
        $connection->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
        $connection->executeStatement('ALTER TABLE users ENABLE TRIGGER ALL');

        $this->entityManager->clear();
    }

    public function testSuccessfulRegistration(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'department' => 'marketing'
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('User registered successfully', $responseData['message']);

        $this->assertArrayHasKey('user', $responseData);
        $this->assertEquals($userData['email'], $responseData['user']['email']);
        $this->assertEquals($userData['firstName'], $responseData['user']['firstName']);
        $this->assertEquals($userData['lastName'], $responseData['user']['lastName']);
        $this->assertEquals($userData['department'], $responseData['user']['department']);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
        $this->assertNotNull($user);
        $this->assertEquals($userData['firstName'], $user->getFirstName());
    }

    public function testRegistrationWithMissingFields(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'lastName' => 'User',
            'department' => 'marketing'
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Missing field: firstName', $responseData['error']);
    }

    public function testRegistrationWithExistingEmail(): void
    {
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setFirstName('Existing');
        $user->setLastName('User');
        $user->setDepartment('admissions');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userData = [
            'email' => 'existing@example.com',
            'password' => 'newpassword',
            'firstName' => 'New',
            'lastName' => 'User',
            'department' => 'marketing'
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    public function testSuccessfulLogin(): void
    {
        $email = 'login-test@example.com';
        $password = 'password123';

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setFirstName('Login');
        $user->setLastName('Test');
        $user->setDepartment('administration');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'email' => $email,
            'password' => $password
        ];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertEquals($email, $responseData['user']['email']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $email = 'invalid-login@example.com';
        $password = 'correctpassword';

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setFirstName('Invalid');
        $user->setLastName('Login');
        $user->setDepartment('marketing');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'email' => $email,
            'password' => 'wrongpassword'
        ];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid credentials', $responseData['error']);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'anypassword'
        ];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid credentials', $responseData['error']);
    }
}
