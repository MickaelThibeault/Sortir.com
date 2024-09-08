<?php

namespace App\Services;

use App\Entity\Lieu;
use Doctrine\ORM\EntityManagerInterface;

class LieuService
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getChoices(): array
    {
        $repository = $this->entityManager->getRepository(Lieu::class);
        $lieux = $repository->findAll();

        $choices = [];
        foreach ($lieux as $lieu) {
            $choices[$lieu->getNom()] = $lieu->getNom();
        }

        return $choices;
    }
}