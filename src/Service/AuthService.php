<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Input\Auth\LoginRequestDTO;
use App\DTO\Input\Auth\RegisterRequestDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validateLogin(LoginRequestDTO $dto): User
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $user = $this->userRepository->findOneBy(['email' => $dto->email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new \RuntimeException('Invalid credentials');
        }

        return $user;
    }

    public function register(RegisterRequestDTO $dto): User
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $existing = $this->userRepository->findOneBy(['email' => $dto->email]);
        if ($existing) {
            throw new \InvalidArgumentException('Email already in use');
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setDepartment($dto->department);
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
} 