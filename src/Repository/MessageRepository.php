<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findConversation(User $user, User $partner): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('(m.sender = :user AND m.receiver = :partner) OR (m.sender = :partner AND m.receiver = :user)')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('partner', $partner)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all messages received by the given user (inbox).
     *
     * This method mirrors the typical "inbox" concept: messages where the
     * user is the receiver, the message has not been soft‑deleted, and the
     * ordering is most recent first. It returns an array of Message entities.
     */
    public function findInbox(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.receiver = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.receiver = :user')
            ->andWhere('m.isRead = false')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationPartners(User $user): array
    {
        $messages = $this->createQueryBuilder('m')
            ->andWhere('m.sender = :user OR m.receiver = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $conversations = [];
        foreach ($messages as $message) {
            $partner = $message->getSender() === $user ? $message->getReceiver() : $message->getSender();
            if (!$partner || $partner->getDeletedAt()) {
                continue;
            }

            $id = $partner->getId();
            if (!isset($conversations[$id])) {
                // Count unread messages from this partner
                $unreadCount = $this->createQueryBuilder('m2')
                    ->select('COUNT(m2.id)')
                    ->andWhere('m2.sender = :partner')
                    ->andWhere('m2.receiver = :user')
                    ->andWhere('m2.isRead = false')
                    ->andWhere('m2.deletedAt IS NULL')
                    ->setParameter('partner', $partner)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult();

                $conversations[$id] = [
                    'id' => $id,
                    'otherUser' => [
                        'firstName' => $partner->getFirstName(),
                        'lastName' => $partner->getLastName(),
                        'roles' => $partner->getRoles(),
                    ],
                    'lastMessage' => [
                        'content' => $message->getContent(),
                        'createdAt' => $message->getCreatedAt(),
                    ],
                    'unreadCount' => (int) $unreadCount,
                ];
            }
        }

        return array_values($conversations);
    }
}
