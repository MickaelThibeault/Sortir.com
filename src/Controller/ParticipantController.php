<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Participant;
use App\Form\GroupeType;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use function PHPUnit\Framework\throwException;

class ParticipantController extends AbstractController
{
    #[Route('/participant/{id}/edit', name: 'app_participant_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function participantEditAction(Request  $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository,
                                          Security $security, UserPasswordHasherInterface $passwordHasher, #[Autowire('%photo_dir%')] string $photoDir
    ): Response
    {

        $userEnSessionId = $security->getUser()->getId();
        $userId = $request->attributes->get('id');

        //On récupère les groupes de l'utilisateur connecté
        $groupes = $security->getUser()->getGroupes();

        //on vérifie que le participant qui souhaite modifier son profil est bien le bon participant
        if (!$userEnSessionId === $userId) {
            throw $this->createAccessDeniedException("Accès aux données refusé");
        }

        // on recherche le participant via l'ID.
        $participant = $participantRepository->findParticipantWithCampusById($userId);

        if (!$participant) {
            throw $this->createNotFoundException("Participant non trouvé");
        }

        //Si l'ID correspond bien à un participant et si le participant connecté est le bon, on créer le formulaire.
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasse = $form->get('motDePasse')->getData();
            //traitement du mot de passe si modifié.
            if ($motDePasse) {
                $hashedPassword = $passwordHasher->hashPassword($participant, $motDePasse);
                $participant->setMotDePasse($hashedPassword);
            }

            //récupération de la photo si modifiée.
            $photo = $form->get('photo')->getData();
            if ($photo) {
                //récupération du nom de l'ancienne photo
                $anciennePhoto = $participant->getPhoto();
                //création du nom de la photo.
                $fileName = uniqid() . '.' . $photo->guessExtension();
                //remplacement de l'ancienne photo par la nouvelle dans le répertoire
                $photo->move($photoDir, $fileName);
                $participant->setPhoto($fileName);
                //suppression de l'ancienne photo si elle existe
                if ($anciennePhoto && file_exists($photoDir . '/' . $anciennePhoto)) {
                    unlink($photoDir . '/' . $anciennePhoto);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Modifications enregistrées avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('participant/edit.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    #[Route('/participant/{id}/details', name: 'app_participant_details', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function participantDetailsAction(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository, Security $security, UserPasswordHasherInterface $passwordHasher): Response
    {
        $userEnSessionId = $security->getUser();

        //on récupère les groupes de l'utilisateur connecté
        $groupes = $security->getUser()->getGroupes();

        if (!$userEnSessionId) {
            throw $this->createAccessDeniedException("Accès aux données refusé");
        }

        $idParticipant = $request->attributes->get('id');
        $participant = $participantRepository->findParticipantWithCampusById($idParticipant);

        if (!$participant) {
            throw $this->createNotFoundException("Participant non trouvé");
        }

        return $this->render('participant/details.html.twig', [
            'participant' => $participant,
            'groupes' => $groupes
        ]);
    }

    //***************************************************
    // Creation d'un groupe
    //***************************************************

    #[Route('/participant/{id}/creer_groupe', name: 'app_participant_creer_groupe', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function creerGroupe(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository, Security $security): Response
    {
        $userEnSessionId = $security->getUser()->getId();
        $userId = $request->attributes->get('id');

        if (!$userEnSessionId === $userId) {
            throw $this->createAccessDeniedException("Accès aux données refusé");
        }

        //récupération du créateur du groupe

        $createurGroupe = $participantRepository->findParticipantWithCampusById($userId);

        if (!$createurGroupe) {
            throw $this->createNotFoundException("Participant non trouvé");
        }

        $groupe = new Groupe();
        $form = $this->createForm(GroupeType::class, $groupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //on récupère les participants sélectionnés
            $participants = $form->get('participants')->getData();
            $groupeName = $form->get('nom')->getData();

            if (!$groupeName) {
                $this->addFlash('danger', 'Le nom du groupe est obligatoire.');
                return $this->redirectToRoute('app_participant_creer_groupe', ['id' => $userId]);
            }

            //On vérifie que le nom de groupe n'est pas déjà utilisé
            $groupeExiste = $entityManager->getRepository(Groupe::class)->findOneBy(['nom' => $groupeName]);
            if ($groupeExiste) {
                $this->addFlash('danger', 'Le nom de groupe ' . $groupeName . ' est déjà utilisé.');
                return $this->redirectToRoute('app_participant_creer_groupe', ['id' => $userId]);
            }

            //on vérifie que le participant n'est pas déjà dans le groupe de nom $groupeName

            foreach ($participants as $participant) {
                $groupeRepository = $entityManager->getRepository(Groupe::class);

// Utilisation de QueryBuilder pour rechercher un groupe par nom et participant
                $dql = 'SELECT g FROM App\Entity\Groupe g
                        JOIN g.participants p
                        WHERE g.nom = :nom
                        AND p.id = :participantId';

                $query = $entityManager->createQuery($dql)
                    ->setParameter('nom', $groupeName)
                    ->setParameter('participantId', $participant->getId());

                $participantExiste = $query->getOneOrNullResult();

                if ($participantExiste) {
                    $this->addFlash('danger', 'Le participant ' . $participant->getPseudo() . ' est déjà dans le groupe ' . $groupeName);
                    return $this->redirectToRoute('app_participant_creer_groupe', ['id' => $userId]);
                }
                $groupe->addParticipant($participant);
            }

            //on ajoute le créateur du groupe
            $groupe->addParticipant($createurGroupe);
            //on ajoute le créateur du groupe comme chef de groupe
            $groupe->setChefDeGroupe($createurGroupe);
            $entityManager->persist($groupe);
            $entityManager->flush();

            $this->addFlash('success', 'Groupe créé avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('Groupes/groupe.html.twig', [
            'form' => $form->createView()
        ]);
    }

    //***************************************************
    // Détails d'un groupe
    //***************************************************

    #[Route('/participant/{id}/voir_groupe/{idGroupe}', name: 'app_groupe_voir', requirements: ['id' => '\d+', 'idGroupe' => '\d+'], methods: ['GET', 'POST'])]
    public function voirGroupe(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository, Security $security): Response
    {
        $userId = $request->attributes->get('id');
        $groupeId = $request->attributes->get('idGroupe');
        $groupe = $entityManager->getRepository(Groupe::class)->find($groupeId);


        if (!$groupe) {
            throw $this->createNotFoundException("Groupe non trouvé");
        }
        // On récupère les participants du groupe
        $participants = $groupe->getParticipants();

        //On récupère tous les participants sauf ceux qui sont dans le groupe
        $autresParticipants = $participantRepository->findAllExceptGroupe($groupeId);

        return $this->render('Groupes/details.html.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
            'autresParticipants' => $autresParticipants
        ]);
    }
    //****************************************************************
    //Ajouter un participant -> uniquement possible si chef de groupe
    //***************************************************************

    #[Route('/participant/ajouter/{idGroupe}', name: 'app_groupe_ajouter_membre', requirements: ['idGroupe' => '\d+'], methods: ['GET', 'POST'])]
    public function ajouterAuGroupe(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $action = 'ajouter';
        return $this->actionSurGroupe($request, $entityManager, $security, $action);
    }

    //***************************************************
    // Retirer une personne du groupe si chef de groupe
    //***************************************************
    #[Route('/participant/{id}/retirer_groupe/{idGroupe}', name: 'app_groupe_retirer', requirements: ['id' => '\d+', 'idGroupe' => '\d+'], methods: ['GET', 'POST'])]
    public function retirerDuGroupe(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $action = 'retirer';
        return $this->actionSurGroupe($request, $entityManager, $security, $action);
    }

    //**************************************************************
    // Fonction pour ajouter ou retirer un participant d'un groupe
    //**************************************************************

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param Security $security
     * @param $action
     * @return Response
     */

    private function actionSurGroupe(Request $request, EntityManagerInterface $entityManager, Security $security, $action): Response
    {
        $groupeId = $request->attributes->get('idGroupe');

        // On récupère le groupe à partir du groupeId
        $groupe = $entityManager->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            throw $this->createNotFoundException("Groupe non trouvé");
        }

        // On vérifie si l'utilisateur actuel est le chef de groupe
        $chefDeGroupe = $groupe->getChefDeGroupe();
        if ($chefDeGroupe->getId() !== $security->getUser()->getId()) {
            throw $this->createAccessDeniedException(
                $action === 'retirer' ? "Vous n'êtes pas autorisé à retirer un participant du groupe." :
                    "Vous n'êtes pas autorisé à ajouter un participant au groupe."
            );
        }

        if ($action === 'ajouter') {
            // On récupère les IDs des participants sélectionnés
            $participantsId = $request->request->all('participants');
            //Si ce n'est pas un tableau on crée ce tableau
            if (!$participantsId) {
                $this->addFlash('danger', 'Veuillez sélectionner un participant.');
                return $this->redirectToRoute('app_groupe_voir', ['id' => $security->getUser()->getId(), 'idGroupe' => $groupeId]);
            }

            if (!is_array($participantsId)) {
                $participantsId = [$participantsId];
            }
            // Ajout des participants au groupe
            foreach ($participantsId as $participantId) {
                $participant = $entityManager->getRepository(Participant::class)->find($participantId);

                //On vérifie que le participant n'est pas déjà dans le groupe
                $groupeRepository = $entityManager->getRepository(Groupe::class);
                //on utilsie la methode findParticipantInGroupe pour vérifier si le participant est déjà dans le groupe
                $participantExiste = $groupeRepository->findParticipantInGroupe($groupeId, $participantId);

                //Si le participant est déjà dans le groupe on affiche un message d'erreur
                if ($participantExiste) {
                    $this->addFlash('danger', 'Le participant ' . $participant->getPseudo() . ' est déjà dans le groupe.');
                    return $this->redirectToRoute('app_groupe_voir', ['id' => $security->getUser()->getId(), 'idGroupe' => $groupeId]);
                }
                //on compte le nombre de participants ajoutés
                $nbParticipants = count($participantsId);

                if ($participant) {
                    $groupe->addParticipant($participant);
                }
            }
            //on affiche un message de succès selon le nombre de participants ajoutés
            if ($nbParticipants > 1) {
                $this->addFlash('success', 'Participants ajoutés au groupe avec succès.');
            } else {
                $this->addFlash('success', 'Participant ajouté au groupe avec succès.');
            }
        } else {
            // Pour retirer un participant
            $participantId = $request->attributes->get('id');
            $participant = $entityManager->getRepository(Participant::class)->find($participantId);
            if (!$participant) {
                throw $this->createNotFoundException("Participant non trouvé");
            }

            $groupe->removeParticipant($participant);
            $this->addFlash('success', 'Participant retiré du groupe avec succès.');
        }

        // On sauvegarde les modifications
        $entityManager->flush();

        // Redirection vers la vue du groupe
        return $this->render('Groupes/details.html.twig', [
            'groupe' => $groupe,
            'participants' => $groupe->getParticipants(),
            'autresParticipants' => $entityManager->getRepository(Participant::class)->findAllExceptGroupe($groupeId)
        ]);
    }
}


