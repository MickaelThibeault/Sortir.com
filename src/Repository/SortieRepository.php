<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use http\Client\Curl\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findByCriteria(array $criteria, int $userId)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->leftJoin('s.participantsInscrits', 'sp', 'WITH', 'sp.id = :userId');
        $qb->setParameter('userId', $userId);
        if (isset($criteria['dateDebut']) && $criteria['dateDebut']) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $criteria['dateDebut']);
        }
        if (isset($criteria['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $criteria['dateFin']);
        }

        if (isset($criteria['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $criteria['nom'] . '%');
        }
        if (isset($criteria['campus'])) {
            $qb->andWhere('s.campus = :campus')
                ->setParameter('campus', $criteria['campus']);
        } else {
            // ne rien appliquer
        }

        if (isset($criteria['organisateur'])) {
            if (isset($criteria['inscrit'])) {
                if (isset($criteria['pasinscrit'])) {
                    $qb->andWhere('s.organisateur = :user OR :user MEMBER OF s.participantsInscrits OR :user NOT MEMBER OF s.participantsInscrits')
                        ->setParameter('user', $userId);
                } else {
                    $qb->andWhere('s.organisateur = :user OR :user MEMBER OF s.participantsInscrits ')
                        ->setParameter('user', $userId);
                }
            } else {
                if (isset($criteria['pasinscrit'])) {
                    $qb->andWhere('s.organisateur = :user OR :user NOT MEMBER OF s.participantsInscrits')
                        ->setParameter('user', $userId);
                } else {
                    $qb->andWhere('s.organisateur = :user')
                        ->setParameter('user', $userId);
                }
            }
        } else {
            if (isset($criteria['inscrit'])) {
                if (isset($criteria['pasinscrit'])) {
                    $qb->andWhere(':user MEMBER OF s.participantsInscrits  OR :user NOT MEMBER OF s.participantsInscrits AND s.organisateur != :user')
                        ->setParameter('user', $userId);
                } else {
                    $qb->andWhere(':user MEMBER OF s.participantsInscrits AND s.organisateur != :user')
                        ->setParameter('user', $userId);
                }
            } else {
                if (isset($criteria['pasinscrit'])) {
                    $qb->andWhere(':user NOT MEMBER OF s.participantsInscrits AND s.organisateur != :user')
                        ->setParameter('user', $userId);
                }
            }
        }
        if (isset($criteria['passees'])) {
            $qb->andWhere('s.dateHeureDebut < :now')
                ->setParameter('now', new \DateTime());
        }
        if (!(isset($criteria['passees']))) {
            $qb->andWhere('s.dateHeureDebut >= :now')
                ->setParameter('now', new \DateTime());
        }


        dump($qb);
        return $qb->getQuery()->getResult();
    }


//    public function findSortiesParEtat()
//    {
//        $qb = $this->createQueryBuilder('s');
//        $qb->join('s.etat', 'e');
//        $qb->andWhere('s.etat = :idEtat5 OR s.etat = :idEtat6')
//            ->setParameter('idEtat5', 5)
//            ->setParameter('idEtat6', 6);
//        return $qb->getQuery()->getResult();
//    }
    public function findSortiesParEtat(int $etat)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e');
        $qb->andWhere('s.etat = :idEtat')
            ->setParameter('idEtat', $etat);
        return $qb->getQuery()->getResult();
    }

    public function findSortiesByUser(int $userId)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)')
            ->andWhere('s.organisateur = :user OR :user MEMBER OF s.participantsInscrits')
            ->setParameter('user', $userId);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}