<?php

namespace App\DataFixtures;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@example.com')
            ->setFirstName('Admin')
            ->setLastName('PsyCare')
            ->setPhone('0123456789')
            ->setBirthDate(new \DateTime('1985-01-01'))
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword(new User(), 'admin'));
        $manager->persist($admin);

        $specialties = ['Cognitive Therapy', 'Child Psychology', 'Family Therapy'];
        $psychologists = [];

        foreach ($specialties as $index => $specialty) {
            $psychologist = (new User())
                ->setEmail(sprintf('psy%d@example.com', $index + 1))
                ->setFirstName('Dr')
                ->setLastName(sprintf('Psych%d', $index + 1))
                ->setPhone('061234567'. $index)
                ->setBirthDate(new \DateTime(sprintf('197%d-05-0%d', $index, $index + 1)))
                ->setRoles(['ROLE_PSYCHOLOGIST'])
                ->setDiploma('Master of Psychology')
                ->setSpecialty($specialty)
                ->setBio(sprintf('Experienced psychologist focused on %s.', $specialty))
                ->setPassword($this->passwordHasher->hashPassword(new User(), 'psychologist'));
            $manager->persist($psychologist);
            $psychologists[] = $psychologist;
        }

        $patients = [];
        for ($i = 1; $i <= 5; $i++) {
            $patient = (new User())
                ->setEmail(sprintf('patient%d@example.com', $i))
                ->setFirstName('Patient'.$i)
                ->setLastName('Test')
                ->setPhone('070000000'. $i)
                ->setBirthDate(new \DateTime(sprintf('199%d-08-%02d', $i, $i + 1)))
                ->setRoles(['ROLE_PATIENT'])
                ->setPassword($this->passwordHasher->hashPassword(new User(), 'patient'));
            $manager->persist($patient);
            $patients[] = $patient;
        }

        $messagesData = [
            [$patients[0], $psychologists[0], 'Hello Dr, I would like to discuss anxiety management.'],
            [$psychologists[0], $patients[0], 'Hello, I am available on Tuesdays and Thursdays.'],
            [$patients[1], $psychologists[1], 'I am looking for support with family stress.'],
            [$psychologists[1], $patients[1], 'We can schedule an appointment this week.'],
            [$patients[2], $psychologists[2], 'Do you offer remote consultations?'],
            [$psychologists[2], $patients[2], 'Yes, remote sessions are possible via secure video.'],
            [$patients[3], $psychologists[0], 'I have trouble sleeping recently.'],
            [$psychologists[0], $patients[3], 'Let us explore your daily routine during our next session.'],
            [$patients[4], $psychologists[1], 'Do you accept new patients for child therapy?'],
            [$psychologists[1], $patients[4], 'Yes, I can take a short intake call first.'],
        ];

        foreach ($messagesData as [$sender, $receiver, $content]) {
            $message = (new Message())
                ->setSender($sender)
                ->setReceiver($receiver)
                ->setContent($content)
                ->setIsRead(false);
            $manager->persist($message);
        }

        $manager->flush();
    }
}
