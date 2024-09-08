<?php

namespace App\archivage;

use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

class Archivage
{
    protected $sortieRepository;
    protected $etatRepository;
    protected $entityManager;

    public function __construct(SortieRepository $sortieRepository, EtatRepository $etatRepository, EntityManagerInterface $entityManager
    )
    {
        $this->sortieRepository = $sortieRepository;
        $this->etatRepository = $etatRepository;
        $this->entityManager = $entityManager;
    }


    public function archivage()
    {
        $dateDuJour = new \DateTime('now');
//        $sortiesAArchiver= $this->sortieRepository->findSortiesParEtat();
        // Modif par Sandrine : passage d'argument à la fonction
        //récupération de toutes les sorties avec état 5 et 6.
        $sortiesAArchiver= $this->sortieRepository->findSortiesParEtat(5);
        $sortiesAArchiver+= $this->sortieRepository->findSortiesParEtat(6);
        $etatArchive = $this->etatRepository->find(7);

        if (!$etatArchive) {
            throw new \Exception('L\'état avec ID 7 n\'existe pas.');
        }

        $sortiesAMettreAJour = [];

        foreach ($sortiesAArchiver as $sortie) {
            $dateDebutSortie = $sortie->getDateHeureDebut();
            $duree= $sortie->getDuree();

            //ajout de la durée de la sortie à l'heure de début de la sortie
            $interval = new \DateInterval('PT' . $duree . 'M');
            $dateSortiePlusDuree = $dateDebutSortie->add($interval);

            //ajout d'un mois à la date de la sortie plus la durée
            $interval = new \DateInterval('P1M');
            $dateSortiePlusDureePlusMois = $dateSortiePlusDuree->add($interval);

            //Si la date du jour dépasse la date de la sortie + la durée, on passe l'état à archivé.
            if ($dateDuJour > $dateSortiePlusDureePlusMois) {
                $sortie->setEtat($etatArchive);
                $sortiesAMettreAJour[] = $sortie;
            }
        }

        // on fait un fluch uniquement s'il y a des sorties qui correspondent aux critères.
        if (!empty($sortiesAMettreAJour)) {
            $this->entityManager->flush();
        }
    }

    public function cloture()
    {
        $dateDuJour = new \DateTime('now');

        $sortiesACloturer= $this->sortieRepository->findSortiesParEtat(2);

        $etatArchive = $this->etatRepository->find(3);

        if (!$etatArchive) {
            throw new \Exception('L\'état avec ID 3 n\'existe pas.');
        }

        $sortiesAMettreAJour = [];

        foreach ($sortiesACloturer as $sortie) {
            $dateCloture = $sortie->getDateLimiteInscription();
            if ($dateDuJour > $dateCloture) {
                $sortie->setEtat($etatArchive);
                $sortiesAMettreAJour[] = $sortie;
            }
        }
        if (!empty($sortiesAMettreAJour)) {
            $this->entityManager->flush();
        }
    }
}
