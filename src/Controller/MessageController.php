<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageFormType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MessageController extends AbstractController
{
    #[Route('/messages', name: 'app_messages')]
    public function index(MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('message/index.html.twig', [
            'conversations' => $messageRepository->findConversationPartners($user),
            'currentConversation' => null,
        ]);
    }

    #[Route('/messages/new/{psychologistId}', name: 'app_messages_new')]
    public function new(UserRepository $userRepository, Request $request, EntityManagerInterface $em, int $psychologistId): Response
    {
        $sender = $this->getUser();
        if (!$sender instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$sender->isPatient()) {
            $this->addFlash('error', 'Only patients can start new conversations. Please use a patient account to contact psychologists.');
            return $this->redirectToRoute('app_dashboard');
        }

        $receiver = $userRepository->find($psychologistId);
        if (!$receiver || !$receiver->isPsychologist() || !$receiver->isActive()) {
            throw $this->createNotFoundException('Psychologist not found.');
        }

        $message = new Message();
        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($sender);
            $message->setReceiver($receiver);
            $message->setIsRead(false);
            $em->persist($message);
            $em->flush();

            $this->addFlash('success', 'Message sent to psychologist.');

            return $this->redirectToRoute('app_messages');
        }

        return $this->render('message/new.html.twig', [
            'messageForm' => $form->createView(),
            'receiver' => $receiver,
        ]);
    }

    #[Route('/messages/conversation/{partnerId}', name: 'app_messages_conversation')]
    public function conversation(UserRepository $userRepository, MessageRepository $messageRepository, Request $request, EntityManagerInterface $em, int $partnerId): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $partner = $userRepository->find($partnerId);
        if (!$partner || !$partner->isActive()) {
            throw $this->createNotFoundException('Conversation partner not found.');
        }

        $conversation = $messageRepository->findConversation($user, $partner);
        if (empty($conversation)) {
            $this->addFlash('info', 'No messages yet in this conversation.');
        }

        foreach ($conversation as $message) {
            if ($message->getReceiver() === $user && !$message->isRead()) {
                $message->setIsRead(true);
            }
        }
        $em->flush();

        $message = new Message();
        $replyForm = $this->createForm(MessageFormType::class, $message);
        $replyForm->handleRequest($request);

        if ($replyForm->isSubmitted() && $replyForm->isValid()) {
            $message->setSender($user);
            $message->setReceiver($partner);
            $message->setIsRead(false);
            $em->persist($message);
            $em->flush();
            $this->addFlash('success', 'Reply sent.');

            return $this->redirectToRoute('app_messages_conversation', ['partnerId' => $partner->getId()]);
        }

        return $this->render('message/conversation.html.twig', [
            'partner' => $partner,
            'conversation' => $conversation,
            'replyForm' => $replyForm->createView(),
        ]);
    }

    #[Route('/messages/{id}/delete', name: 'app_messages_delete', methods: ['POST'])]
    public function delete(MessageRepository $messageRepository, EntityManagerInterface $em, int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $message = $messageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        if (!$user->isAdmin() && $message->getSender() !== $user && $message->getReceiver() !== $user) {
            throw $this->createAccessDeniedException('You cannot delete this message.');
        }

        $message->setDeletedAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Message deleted successfully.');

        return $this->redirectToRoute('app_messages');
    }
}
