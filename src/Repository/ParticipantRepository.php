<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Expr\Array_;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**06/08/24 : Arthur
     * Identification par le nom d'utilisateur ou l'email
     * @param string $identifier
     * @return Participant|null
     */
    public function loadUserByIdentifier(string $identifier): ?Participant
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT p
            FROM App\Entity\Participant p
            WHERE p.pseudo = :query
            OR p.email = :query'
        )
            ->setParameter('query', $identifier)
            ->getOneOrNullResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Participant) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setMotDePasse($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findParticipantWithCampusById ($participantId): ?Participant
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->join('p.campus', 'c');
        $queryBuilder->where('p.id = :id')
            ->setParameter('id', $participantId);
        $query = $queryBuilder->getQuery();
        return $query->getOneOrNullResult();
    }


    public function findOneByMail ($mail): ?Participant
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->where('p.email = :email')
            ->setParameter('email', $mail);
        $query = $queryBuilder->getQuery();
        return $query->getOneOrNullResult();
    }

    public function findOneByPseudo ($pseudo): ?Participant
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->where('p.pseudo = :pseudo')
            ->setParameter('pseudo', $pseudo);
        $query = $queryBuilder->getQuery();
        return $query->getOneOrNullResult();
    }

    public function findAllExceptUser($user): ?array{
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->where('p.id != :user')
            ->setParameter('user', $user->getId());
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    //    /**
    //     * @return Participant[] Returns an array of Participant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * findAllExceptGroupe : Fonction qui retourne tous les participants sauf ceux du groupe passé en paramètre
     * @param mixed $groupeId
     * @return mixed
     */
    public function findAllExceptGroupe(mixed $groupeId): mixed
    {
        //On récupère tous les participants sauf ceux du groupe passé en paramètre
        //attention, tous les participants ne sont pas dans un groupe
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->leftJoin('p.groupes', 'g')
            ->where('g.id IS NULL OR g.id != :groupeId')
            ->setParameter('groupeId', $groupeId);
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
}
