<?php

namespace App\Controller;

use App\CSV_traitement\CSVTraitement;
use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Form\CSVType;
use App\Form\ParticipantType;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use function PHPUnit\Framework\isEmpty;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/create/participant', name: 'create_participant', methods: ['GET', 'POST'])]
    public function createParticipant(Request $request, EntityManagerInterface $entityManager,
                                      Security $security, UserPasswordHasherInterface $passwordHasher,
                                      #[Autowire('%photo_dir%')] string $photoDir,
                                        CSVTraitement $csvTraitement): Response
    {
        if (!$this->isGranted('ROLE_ADMINISTRATEUR')) {
            $this->addFlash('danger', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $formCSV = $this->createForm(CSVType::class);
        $formCSV->handleRequest($request);

        //on vérifie si un fichier CSV a été transmis et s'il est valide.
        if ($formCSV->isSubmitted() && $formCSV->isValid()) {
            // on récupère les données du formulaire
            $fichierCSV = $formCSV->get('file')->getData();
            try {
                if ($fichierCSV) {
                    //on traite le CSV
                   $csvTraitement->traiterCSV($fichierCSV);
                }
                $this->addFlash('success', 'Création réussie !');
                return $this->redirectToRoute('app_home');
            } catch (\Exception $exception) {
                $message = "Une erreur est survenue lors du traitement du fichier CSV : " . $exception->getMessage();
                $this->addFlash("danger", $message);
                return $this->redirectToRoute('create_participant', [],Response::HTTP_SEE_OTHER);
            }
        }

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            $motDePasse = $form->get('motDePasse')->getData();
            if ($motDePasse) {
                $hashedPassword = $passwordHasher->hashPassword($participant, $motDePasse);
                $participant->setMotDePasse($hashedPassword);
            } else{
                $this->addFlash('danger', 'Mot de passe obligatoire !');
                return $this->render('admin/createParticipant.html.twig', [
                    'form' => $form->createView(),
                    'formCSV' => $formCSV->createView(),
                ]);
            }
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $fileName = uniqid() . '.' . $photo->guessExtension();
                $participant->setPhoto($fileName);
                $photo->move($photoDir, $fileName);
            }
            $email=$participant->getEmail();
            $participant->setActif(true);
            $participant->setAdministrateur(false);
            try {
                $entityManager->persist($participant);
                $entityManager->flush();
            } catch (\Exception $exception) {
                $message = "Une erreur est survenue lors de la création : " . $exception->getMessage();
                $this->addFlash("danger", $message);
                return $this->render('admin/createParticipant.html.twig', [
                    'form' => $form->createView(),
                    'formCSV' => $formCSV->createView(),]);
            }

            $this->addFlash('success', 'Création réussie !');
            return $this->redirectToRoute('admin_participant');
        }
        return $this->render('admin/createParticipant.html.twig', [
            'form' => $form->createView(),
            'formCSV' => $formCSV->createView(),
        ]);
    }

    #[Route('/participant', name: 'admin_participant', methods: ['GET','POST'])]
    public function manageParticipants(Request $request, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager, SortieRepository $sortieRepository, Security $security): Response
    {
        if (!$this->isGranted('ROLE_ADMINISTRATEUR')) {
            $this->addFlash('danger', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }
        if ($request->isMethod('POST')) {
            $actionType = $request->request->get('actionType');
            $selectedParticipants = $request->request->all('selected_participants');
            if ($selectedParticipants) {
                $participants = $participantRepository->findBy(['id' => $selectedParticipants]);
                if ($actionType === 'deactivate') {
                    foreach ($participants as $participant) {
                        if ($participant->isActif()) {
                            $participant->setActif(false);
                        } else {
                            $participant->setActif(true);
                        }
                    }
                    $this->addFlash('success', 'Participant(s) désactivé(s) avec succès.');
                }elseif ($actionType === 'delete') {
                    $compteur=0;
                    foreach ($participants as $participant) {
                        $sortieCount = $sortieRepository->findSortiesByUser($participant->getId());
                        if ($sortieCount==0) {
                            $entityManager->remove($participant);
                        } else {
                            $participant->setActif(false);
                            $compteur++;
                        }
                    }
                    if ($compteur >0) {
                        $this->addFlash('success', 'Participant(s) supprimé(s) avec succès.');
                        $this->addFlash('success', 'un ou plusieurs participants étant inscrits ou organisateurs de sortie(s) ont seulement été désactivés.');
                    } else{
                        $this->addFlash('success', 'Participant(s) supprimé(s) avec succès.');
                    }

                }
                $entityManager->flush();
            } else {
                $this->addFlash('danger', 'Veuillez sélectionner au moins 1 participant.');
            }

            return $this->redirectToRoute('admin_participant');
        }

        // Gestion de la requête GET pour afficher la liste des participants (sauf l'admin connecté)
        $user = $security->getUser();
        $participants = $participantRepository->findAllExceptUser($user);
        return $this->render('admin/listeParticipant.html.twig', [
            "participants" => $participants,
        ]);

    }

    /*********************************
     * Affichage et ajout des villes
     * *******************************/
    #[Route('/ville', name: 'admin_ville')]
    public function addVille(Request $request, VilleRepository $villeRepository, EntityManagerInterface $entityManager): Response
    {

        // Formulaire de recherche par une partie du nom
        $searchVilleForm = $this->createFormBuilder()
            ->add('nomVille', TextType::class,[
                'label' => 'Le nom contient : ',
                'required'=> false
            ])
            ->getForm();

        $searchVilleForm->handleRequest($request);

        if ($searchVilleForm->isSubmitted() && $searchVilleForm->isValid()) {
            $donnees = $searchVilleForm->getData();
            $villes = $villeRepository->filtrer(
                $donnees['nomVille']
            );
        } else {
            $villes = $villeRepository->findAll();
        }

        // Formulaire pour ajouter une nouvelle ville
        $villeForm = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'required'=>false,
                'constraints' => [
                    new NotBlank(message: "Le nom de la ville est obligatoire"),
                    new Length(max: 50, maxMessage: 'Le nom de la ville ne peut pas dépasser 50 caractères')
                ]
            ])
            ->add('codePostal', TextType::class, [
                'required'=>false,
                'constraints' => [
                    new NotBlank(message: "Le code postal est obligatoire"),
                    new Length(max: 10, maxMessage: 'Le code postal ne peut pas dépasser 10 caractères')
                ]
            ])
            ->getForm();

        $villeForm->handleRequest($request);

        // Traitement des données du formulaire d'ajout de ville
        if ($villeForm->isSubmitted() && $villeForm->isValid()) {
            $villeAjout = new Ville();
            $villeAjout->setNom($villeForm->get('nom')->getData());
            $villeAjout->setCodePostal($villeForm->get('codePostal')->getData());

            $entityManager->persist($villeAjout);
            $entityManager->flush();

            $this->addFlash("success", "Bravo ! La nouvelle ville est ajouté");

            return $this->redirectToRoute('admin_ville');
        }

        return $this->render('admin/ville.html.twig', [
            'searchVilleForm'=>$searchVilleForm->createView(),
            'villes'=>$villes,
            'villeForm'=>$villeForm->createView()
        ]);
    }

    /**************************
     * Modification des villes
     * ************************/
    #[Route('/ville/edit/{id}', name: 'admin_ville_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ville $ville, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        // Récupération des données du formulaire
            $nom = $request->request->get('nom');
            $cp = $request->request->get('cp');

            $ville->setNom($nom);
            $ville->setCodePostal($cp);

        // Valider l'entité Ville avec les contraintes définies dans la classe Ville
            $errors = $validator->validate($ville);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    // Collecter les messages d'erreur
                    $errorMessages[] = $error->getMessage();
                }
                $this->addFlash(type: "danger", message: 'La modification a échouée : '.implode(' et ', $errorMessages));
                return $this->redirectToRoute('admin_ville');
            }

            $this->addFlash('success', 'La ville est mise à jour !');
            $entityManager->flush();


            return $this->redirectToRoute('admin_ville', [], Response::HTTP_SEE_OTHER);
    }

    /**************************
     * Suppression d'une ville
     * ************************/
    #[Route('/ville/delete/{id}', name: 'admin_ville_delete')]
    public function delete(Ville $ville, VilleRepository $villeRepository, EntityManagerInterface $entityManager) {

        $entityManager->remove($ville);
        $entityManager->flush();

        return $this->redirectToRoute('admin_ville');
    }

    #[Route('/campus', name: 'admin_campus')]
    public function addCampus(Request $request, CampusRepository $campusRepository, EntityManagerInterface $entityManager): Response
    {
        $searchCampusForm = $this->createFormBuilder()
            ->add('nomCampus', TextType::class,[
                'label' => 'Le nom contient : ',
                'required'=> false
            ])
            ->getForm();

        $searchCampusForm->handleRequest($request);
        if ($searchCampusForm->isSubmitted() && $searchCampusForm->isValid()) {

            $donnees = $searchCampusForm->getData();
            $campus = $campusRepository->filtrer(
                $donnees['nomCampus']
            );
        } else {
            $campus = $campusRepository->findAll();
        }
        dump($campus);
        $campusForm = $this->createFormBuilder()
            ->add('nom', TextType::class, ['required'=>false])
            ->getForm();;

        $campusForm->handleRequest($request);
        if ($campusForm->isSubmitted() && $campusForm->isValid()) {
            $campusAjout = new Campus();
            $campusAjout->setNom($campusForm->get('nom')->getData());


            $entityManager->persist($campusAjout);
            $entityManager->flush();

            return $this->redirectToRoute('admin_campus');
        }

        return $this->render('admin/campus.html.twig', [
            'searchCampusForm'=>$searchCampusForm->createView(),
            'campus'=>$campus,
            'campusForm'=>$campusForm->createView()
        ]);
    }

    #[Route('/campus/edit/{id}', name: 'admin_campus_edit', methods: ['GET', 'POST'])]
    public function editCampus(Request $request, Campus $campus, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {

        $nom  = $request->request->get('nom');
        $campus->setNom($nom);
        $errors = $validator->validate($campus);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash(type: "danger", message: implode(', ', $errorMessages));
            return $this->redirectToRoute('admin_campus');
        }
        $this->addFlash('success', 'Le campus est mis à jour !');
        $entityManager->flush();
        return $this->redirectToRoute('admin_campus', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/campus/delete/{id}', name: 'admin_campus_delete')]
    public function deleteCampus(Campus $campus, VilleRepository $villeRepository, EntityManagerInterface $entityManager) {

        $entityManager->remove($campus);
        $entityManager->flush();

        return $this->redirectToRoute('admin_campus');
    }

}
