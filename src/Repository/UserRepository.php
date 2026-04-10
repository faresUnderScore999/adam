<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findPsychologists(?string $search = null): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(User::class, 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'email', 'email');
        $rsm->addFieldResult('u', 'roles', 'roles');
        $rsm->addFieldResult('u', 'password', 'password');
        $rsm->addFieldResult('u', 'first_name', 'firstName');
        $rsm->addFieldResult('u', 'last_name', 'lastName');
        $rsm->addFieldResult('u', 'phone', 'phone');
        $rsm->addFieldResult('u', 'birth_date', 'birthDate');
        $rsm->addFieldResult('u', 'diploma', 'diploma');
        $rsm->addFieldResult('u', 'specialty', 'specialty');
        $rsm->addFieldResult('u', 'bio', 'bio');
        $rsm->addFieldResult('u', 'is_active', 'isActive');
        $rsm->addFieldResult('u', 'deleted_at', 'deletedAt');

        $sql = "SELECT id, email, roles, password, first_name, last_name, phone, birth_date, diploma, specialty, bio, is_active, deleted_at FROM users WHERE roles::jsonb @> :role AND is_active = true AND deleted_at IS NULL";

        if ($search) {
            $sql .= " AND (LOWER(first_name) LIKE :search OR LOWER(last_name) LIKE :search OR LOWER(specialty) LIKE :search)";
        }

        $sql .= " ORDER BY specialty ASC, last_name ASC";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('role', json_encode(['ROLE_PSYCHOLOGIST']));

        if ($search) {
            $query->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        return $query->getResult();
    }

    public function findActiveUsersByRole(string $role): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(User::class, 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'email', 'email');
        $rsm->addFieldResult('u', 'roles', 'roles');
        $rsm->addFieldResult('u', 'password', 'password');
        $rsm->addFieldResult('u', 'first_name', 'firstName');
        $rsm->addFieldResult('u', 'last_name', 'lastName');
        $rsm->addFieldResult('u', 'phone', 'phone');
        $rsm->addFieldResult('u', 'birth_date', 'birthDate');
        $rsm->addFieldResult('u', 'diploma', 'diploma');
        $rsm->addFieldResult('u', 'specialty', 'specialty');
        $rsm->addFieldResult('u', 'bio', 'bio');
        $rsm->addFieldResult('u', 'is_active', 'isActive');
        $rsm->addFieldResult('u', 'deleted_at', 'deletedAt');

        $sql = "SELECT id, email, roles, password, first_name, last_name, phone, birth_date, diploma, specialty, bio, is_active, deleted_at FROM users WHERE roles::jsonb @> :role AND is_active = true AND deleted_at IS NULL ORDER BY last_name ASC";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('role', json_encode([$role]));

        return $query->getResult();
    }
}
