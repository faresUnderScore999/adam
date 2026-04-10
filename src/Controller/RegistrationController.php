<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PatientRegistrationFormType;
use App\Form\PsychologistRegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register/patient', name: 'app_register_patient')]
    public function registerPatient(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(PatientRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(['ROLE_PATIENT']);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Patient account created. You may log in now.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_patient.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/register/psychologist', name: 'app_register_psychologist')]
    public function registerPsychologist(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(PsychologistRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(['ROLE_PSYCHOLOGIST']);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Psychologist account created. You may log in now.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_psychologist.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
