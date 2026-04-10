<?php

namespace App\Controller;

use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(UserRepository $userRepository, MessageRepository $messageRepository): Response
    {
        $totalUsers = count($userRepository->findAll());
        $activeUsers = count($userRepository->findBy(['isActive' => true, 'deletedAt' => null]));
        $messages = $messageRepository->findBy(['deletedAt' => null]);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'recentMessages' => array_slice($messages, 0, 10),
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $role = $this->container->get('request_stack')->getCurrentRequest()->query->get('role');
        $users = $role ? $userRepository->findActiveUsersByRole($role) : $userRepository->findBy(['deletedAt' => null]);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'selectedRole' => $role,
        ]);
    }

    #[Route('/users/{id}', name: 'admin_user_show')]
    public function show(UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        return $this->render('admin/user_show.html.twig', ['user' => $user]);
    }

    #[Route('/users/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(UserRepository $userRepository, EntityManagerInterface $em, int $id): RedirectResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $user->setIsActive(!$user->isActive());
        if (!$user->isActive()) {
            $user->setDeletedAt(new \DateTimeImmutable());
        } else {
            $user->setDeletedAt(null);
        }
        $em->flush();

        $this->addFlash('success', 'User status updated.');

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(UserRepository $userRepository, EntityManagerInterface $em, int $id): RedirectResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setIsActive(false);
        $em->flush();
        $this->addFlash('success', 'User was soft deleted.');

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/messages', name: 'admin_messages')]
    public function messages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findBy(['deletedAt' => null], ['createdAt' => 'DESC']);

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
        ]);
    }
}
