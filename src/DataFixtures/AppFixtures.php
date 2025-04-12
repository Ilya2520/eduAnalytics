<?php

namespace App\DataFixtures;

use App\Entity\Applicant;
use App\Entity\MarketingCampaign;
use App\Entity\Program;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(readonly UserPasswordHasherInterface  $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('ru_RU');

        // 1. Users
        $departments = ['admissions', 'marketing', 'administration'];
        $users = [];
        for ($i = 0; $i < rand(10, 30); $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setDepartment($faker->randomElement($departments));
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive($faker->boolean(90));
            $manager->persist($user);
            $users[] = $user;
        }

        // 2. Programs
        $degrees = ['bachelor', 'master', 'phd'];
        $programs = [];
        for ($i = 0; $i < rand(10, 30); $i++) {
            $program = new Program();
            $program->setId($i * 1324);
            $program->setName($faker->words(3, true));
            $program->setCode(strtoupper($faker->bothify('??###')));
            $program->setDegree($faker->randomElement($degrees));
            $program->setDescription($faker->sentence(10));
            $program->setDuration($faker->numberBetween(12, 60));
            $program->setCapacity($faker->numberBetween(10, 100));
            $program->setIsActive($faker->boolean(80));
            $manager->persist($program);
            $programs[] = $program;
        }

        // 3. Applicants
        $applicants = [];
        for ($i = 0; $i < 100; $i++) {
            $applicant = new Applicant();
            $applicant->setFirstName($faker->firstName);
            $applicant->setLastName($faker->lastName);
            $applicant->setEmail($faker->unique()->safeEmail);
            $applicant->setPhone($faker->phoneNumber);
            $applicant->setBirthDate($faker->dateTimeBetween('-30 years', '-16 years'));
            $manager->persist($applicant);
            $applicants[] = $applicant;
        }

        // 4. Marketing Campaigns
        $statuses = ['planned', 'active', 'completed', 'cancelled'];
        $channels = ['email', 'social', 'ads', 'events'];
        for ($i = 0; $i < rand(10, 30); $i++) {
            $campaign = new MarketingCampaign();
            $campaign->setName($faker->catchPhrase);
            $campaign->setDescription($faker->paragraph);
            $campaign->setStartDate($faker->dateTimeBetween('-1 years', 'now'));
            $campaign->setEndDate($faker->dateTimeBetween('now', '+1 years'));
            $campaign->setBudget($faker->randomFloat(2, 1000, 100000));
            $campaign->setStatus($faker->randomElement($statuses));
            $campaign->setChannel($faker->randomElement($channels));

            // Привяжем случайных абитуриентов
            foreach ($faker->randomElements($applicants, rand(3, 10)) as $applicant) {
                $campaign->getApplicants()->add($applicant);
            }

            $manager->persist($campaign);
        }

        // 5. Reports
        $types = ['applications', 'marketing', 'demographics'];
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        for ($i = 0; $i < rand(10, 30); $i++) {
            $report = new Report();
            $report->setName($faker->sentence(3));
            $report->setType($faker->randomElement($types));
            $report->setParameters([
                'from' => $faker->date(),
                'to' => $faker->date(),
                'filters' => ['region' => $faker->city]
            ]);
            $report->setStatus($faker->randomElement($statuses));
            $report->setFilePath($faker->boolean(70) ? $faker->filePath() : null);
            $report->setCreatedAt($faker->dateTimeBetween('-6 months', 'now'));
            $report->setCompletedAt($faker->boolean(60) ? $faker->dateTimeBetween('now', '+1 month') : null);
            $report->setRequestedBy($faker->randomElement($users));
            $manager->persist($report);
        }

        $manager->flush();
    }
}
