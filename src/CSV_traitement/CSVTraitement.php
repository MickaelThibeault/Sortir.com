<?php

namespace App\CSV_traitement;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class CSVTraitement
{
    private $campusRepository;
    private $entityManager;
    private $passwordHasher;
    private $validator;

    function __construct(EntityManagerInterface      $entityManager,
                         UserPasswordHasherInterface $passwordHasher,
                         CampusRepository            $campusRepository,
                         ValidatorInterface          $validator)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->campusRepository = $campusRepository;
        $this->validator = $validator;

    }

    public function traiterCSV(UploadedFile $csvFile)
    {

        //Permet d'obtenir le fichier temporairement téléchargé.
        $filePath = $csvFile->getPathname();

        //ouvre le fichier pour le lire, s'il échoue, une erreur est levée.
        if (($handle = fopen($filePath, 'r')) !== false) {

            //lecture des entêtes du fichier CSV.
            $header = fgetcsv($handle, 1000, ';');

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                //association des entêtes avec les données
                $participantsCSV[] = array_combine($header, $data);
            }

            //fermeture du fichier.
            fclose($handle);
        } else {
            throw new \Exception("Impossible d'ouvrir le fichier : $filePath");
        }

        foreach ($participantsCSV as $p) {
            $email = $p['email'];
            $pseudo = $p['pseudo'];
            //transformation des données au format String en Integer.
            $actif = (int)$p['actif'];
            $administrateur = (int)$p['administrateur'];

            //Set des données du participant.
            $participant = new Participant();
            $participant->setEmail($email);
            $participant->setNom($p['nom']);

            $participant->setPrenom($p['prenom']);
            $participant->setActif($actif);
            $participant->setPseudo($pseudo);
            $participant->setTelephone($p['telephone']);
            //hash du mot de passe.
            $hashedPassword = $this->passwordHasher->hashPassword($participant, $p['mot_de_passe']);
            $participant->setMotDePasse($hashedPassword);
            $participant->setAdministrateur($administrateur);

            //vérification de l'existence du campus
            $campus = $this->campusRepository->find($p['campus_id']);
            if (!$campus) {
                throw new \Exception("Aucun campus trouvé pour le participant $email");
            }
            $participant->setCampus($campus);

            //validation des données grâce à l'interface validator.
            $errors = $this->validator->validate($participant);

            //Si présence d'erreur, levée d'exception
            if (count($errors) > 0) {
                $errorsString = (string)$errors;
                throw new \Exception("Erreur de validation: $errorsString");
            }

            //Si non, on persiste et on flush.
            $this->entityManager->persist($participant);
        }

        $this->entityManager->flush();
    }

}



