<?php

namespace App\Services;

use App\Entity\Ville;
use Doctrine\ORM\EntityManagerInterface;

class VilleService
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getChoices(): array
    {
        $repository = $this->entityManager->getRepository(Ville::class);
        $villes = $repository->findAll();

        $choices = [];
        foreach ($villes as $ville) {
            $choices[$ville->getNom()] = $ville->getNom();
        }

        return $choices;
    }
}