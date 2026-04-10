<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PsychologistSearchType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(Request $request, UserRepository $userRepository, MessageRepository $messageRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $searchForm = $this->createForm(PsychologistSearchType::class, null, ['method' => 'GET']);
        $searchForm->handleRequest($request);
        $search = null;

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $searchData = $searchForm->get('search')->getData();
            $search = $searchData ? trim($searchData) : null;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Get user-specific stats
        $userInbox = $messageRepository->findInbox($user);
        $unreadMessages = $messageRepository->findUnreadMessages($user);

        $stats = [
            'totalMessages' => count($userInbox),
            'totalUnreadMessages' => count($unreadMessages),
        ];

        // Get recent messages for the user (last 10)
        $recentMessages = array_slice($userInbox, 0, 10);

        // Admin stats (only for admin users)
        $totalUsers = 0;
        $activeUsers = 0;

        if ($this->isGranted('ROLE_ADMIN')) {
            // Get all users count
            $allUsers = $userRepository->findAll();
            $totalUsers = count($allUsers);

            // Count active users (users who have logged in recently or have messages)
            $activeUsers = count(array_filter($allUsers, function($u) {
                return $u->isActive();
            }));
        }

        return $this->render('dashboard/index.html.twig', [
            'searchForm' => $searchForm->createView(),
            'psychologists' => $userRepository->findPsychologists($search),
            'stats' => $stats,
            'recentMessages' => $recentMessages,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'isPatient' => $user->isPatient(),
        ]);
    }
}
