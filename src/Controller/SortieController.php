<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\LieuRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/sortie')]
class SortieController extends AbstractController
{
    #[Route('/', name: 'sortie_index', methods: ['GET'])]
    public function index(SortieRepository $sortieRepository): Response
    {
        return $this->render('sortie/index.html.twig', [
            'sorties' => $sortieRepository->findAll(),
        ]);
    }


    /*********************************
     * Création d'une nouvelle sortie
     * *******************************/
    #[Route('/new', name: 'sortie_new', methods: ['GET', 'POST'])]
    public function new(Request                $request,
                        EntityManagerInterface $entityManager,
                        EtatRepository         $etatRepository,
                        LieuRepository         $lieuRepository,
                        VilleRepository        $villeRepository,
                        ValidatorInterface     $validator,
    ): Response
    {
        $aujourdhui =  new DateTime();
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // Récupérer les données du formulaire pour les champs liés au lieu (switch select et input)
            $autreNom = $form->get('lieu')->get('autreNom')->getData();
            $nom = $form->get('lieu')->get('nom')->getData();
            $rue = $form->get('lieu')->get('rue')->getData();

            // Validation manuelle des champs lieu obligatoire
            if (empty($nom) && !$autreNom) {
                $form->get('lieu')->get('nom')->addError(new FormError('Le nom doit être saisi'));
            }

            if (strlen($autreNom) > 50) {
                $form->get('lieu')->get('nom')->addError(new FormError('Le nom ne peut pas dépasser 50 caractères'));
            }

            if (empty($rue)) {
                $form->get('lieu')->get('rue')->addError(new FormError('La rue doit être saisie'));
            }

            if (strlen($rue) > 150) {
                $form->get('lieu')->get('rue')->addError(new FormError('La rue ne peut pas dépasser 150 caractères'));
            }


            if ($form->isValid()) {
                // Définir le nom du lieu pour la sortie, en utilisant 'autreNom' si 'nom' est vide
                $sortie->getLieu()->setNom($nom ? $nom : $autreNom);

                // Récupérer le bouton soumis pour déterminer l'action
                $button = $request->request->get('submit');

                $lieuExistant = $lieuRepository->findOneBy(['nom' => $form->get('lieu')->get('nom')->getData(), 'rue' => $form->get('lieu')->get('rue')->getData()]);
                // Si le lieu existe (nom et rue), l'associer à la sortie
                if ($lieuExistant) {
                    $sortie->setLieu($lieuExistant);
                }

                if ($autreNom) {
                    $sortie->getLieu()->setNom($autreNom);
                    $villeExistante = $villeRepository->findOneBy(['nom' => $form->get('lieu')->get('ville')->get('nom')->getData(), 'codePostal' => $form->get('lieu')->get('ville')->get('codePostal')->getData()]);
                    if ($villeExistante)
                        $sortie->getLieu()->setVille($villeExistante);
                }

                // Définir l'organisateur de la sortie comme l'utilisateur actuel
                $organisateur = $this->getUser();
                $sortie->setOrganisateur($organisateur);
                $sortie->setCampus($organisateur->getCampus());

                // Vérifier quel bouton a été cliqué pour définir l'état de la sortie
                if ($button == 'save') {
                    $etatCree = $etatRepository->findOneBy(['libelle' => 'En création']);
                    $sortie->setEtat($etatCree);
                    $this->addFlash("success", "Félicitation ! Vous avez créé une sortie.");
                }

                if ($button == 'publish') {
                    $etatOuvert = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                    $sortie->setEtat($etatOuvert);
                    $this->addFlash("success", "Félicitation ! Vous avez publié une sortie.");
                }

                $entityManager->persist($sortie);
                $entityManager->flush();

                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'nomSelected' => '', // Initialisation d'une variable data utilisée par le js (mode edit)
            'aujourdhuiH' => $aujourdhui->format('Y-m-d\TH:i'),
            'aujourdhui' => $aujourdhui->format('Y-m-d')
        ]);
    }


    /*************************
     * Affichage d'une sortie
     * ***********************/
    #[Route('/{id}', name: 'sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie, MobileDetectorInterface $mobileDetector): Response
    {
        $isMobile = $mobileDetector->isMobile();
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'is_mobile' => $isMobile,
        ]);
    }


    /****************************
     * Modification d'une sortie
     * **************************/
    #[Route('/edit/{id}', name: 'sortie_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit','sortie', 'Sortie non trouvée', 404)]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager, LieuRepository $lieuRepository, EtatRepository $etatRepository, VilleRepository $villeRepository): Response
    {
        $user = $this->getuser();
        if ($user !== $sortie->getOrganisateur()) {
            throw new AccessDeniedException('Accès réservé à l\'organisateur.');
        }

        // Récupérer l'entité Lieu associée à la sortie
        $idlieu = $sortie->getLieu()->getId();
        $lieu = $lieuRepository->findOneBy(['id' => $sortie->getLieu()->getId()]);
        $sortie->setLieu($lieu);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($sortie->getEtat()->getLibelle() == "En création") {
            if ($form->isSubmitted()) {
                // Récupérer les données du formulaire pour les champs liés au lieu (switch select et input)
                $autreNom = $form->get('lieu')->get('autreNom')->getData();
                $nom = $form->get('lieu')->get('nom')->getData();
                $rue = $form->get('lieu')->get('rue')->getData();

                // Validation manuelle des champs lieu obligatoire
                if (empty($nom) && !$autreNom) {
                    $form->get('lieu')->get('nom')->addError(new FormError('Le nom doit être saisi'));
                }

                if (strlen($autreNom) > 50) {
                    $form->get('lieu')->get('nom')->addError(new FormError('Le nom ne peut pas dépasser 50 caractères'));
                }

                if (empty($rue)) {
                    $form->get('lieu')->get('rue')->addError(new FormError('La rue doit être saisie'));
                }

                if (strlen($rue) > 150) {
                    $form->get('lieu')->get('rue')->addError(new FormError('La rue ne peut pas dépasser 150 caractères'));
                }

                if($form->isValid()) {
                    $sortie->getLieu()->setNom($nom ? $nom : $autreNom);

                    // Récupérer le bouton soumis pour déterminer l'action
                    $button = $request->request->get('submit');

                    $lieuExistant = $lieuRepository->findOneBy(['nom' => $form->get('lieu')->get('nom')->getData(), 'rue' => $form->get('lieu')->get('rue')->getData()]);

                    if ($lieuExistant) {
                        $sortie->setLieu($lieuExistant);
                    }

                    if ($autreNom) {
                        // Si un autre nom est fourni, créer un nouveau lieu
                        $lieu = new Lieu();
                        $sortie->setLieu($lieu);
                        $sortie->getLieu()->setNom($autreNom);
                        $sortie->getLieu()->setRue($form->get('lieu')->get('rue')->getData());
                        $sortie->getLieu()->setLatitude($form->get('lieu')->get('latitude')->getData());
                        $sortie->getLieu()->setLongitude($form->get('lieu')->get('longitude')->getData());
                        $villeExistante = $villeRepository->findOneBy(['nom' => $form->get('lieu')->get('ville')->get('nom')->getData(), 'codePostal' => $form->get('lieu')->get('ville')->get('codePostal')->getData()]);
                        if ($villeExistante)
                            $sortie->getLieu()->setVille($villeExistante);
                    }

                    if ($button == 'save') {
                        $etatCree = $etatRepository->findOneBy(['libelle' => 'En création']);
                        $sortie->setEtat($etatCree);
                        $this->addFlash("success", "Félicitations ! Votre sortie est modifiée.");

                    }

                    if ($button == 'publish') {
                        $etatOuvert = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                        $sortie->setEtat($etatOuvert);
                        $this->addFlash("success", "Félicitations ! Votre sortie est publiée.");
                    }

                    $entityManager->flush();
                    return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
                }

            }

        } else {
            $this->addFlash("danger", "Vous ne pouvez pas modifier une sortie publiée !");
        }

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'nomSelected' => $sortie->getLieu()->getNom(),
        ]);
    }


    /***************************
     * Suppression d'une sortie
     * *************************/
    #[Route('/{id}', name: 'sortie_delete', methods: ['POST'])]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getuser();
        if ($user !== $sortie->getOrganisateur()) {
            throw new AccessDeniedException('Accès réservé à l\'organisateur.');
        }
        if ($sortie->getEtat()->getLibelle() == "En création") {
            if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->getPayload()->getString('_token'))) {
                $entityManager->remove($sortie);
                $entityManager->flush();
            }
        } else {
            $this->addFlash("danger", "Vous ne pouvez pas supprimer une sortie publiée !");
        }


        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    /***********************************************************************
     * Récupérer les données au format json des lieux associés à une ville
     ***********************************************************************/
    #[Route('/lieuxByVille/{villeNom}', name: 'sortie_lieuxByVille')]
    public function getLieuxByVille($villeNom, VilleRepository $villeRepository): JsonResponse
    {
        $ville = $villeRepository->findOneBy(['nom' => $villeNom]);

        // Si la ville n'existe pas, renvoyer une réponse JSON avec une liste vide de lieux
        if (!$ville) {
            return new JsonResponse(['lieux' => []]);
        }

        $codePostal = $ville->getCodePostal();

        $lieux = [];
        foreach ($ville->getLieux() as $lieu) {
            $lieux[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
                'rue' => $lieu->getRue(),
                'latitude' => $lieu->getLatitude(),
                'longitude' => $lieu->getLongitude(),
            ];
        }

        // Retourner une réponse JSON avec la liste des lieux et le code postal de la ville
        return new JsonResponse(['lieux' => $lieux, 'codePostal' => $codePostal]);
    }


    //****************************************************
    // Inscription à une sortie
    //****************************************************
    #[Route('/inscription/{id}', name: 'sortie_inscription', methods: ['GET'])]
    public function inscription(Request $request, EntityManagerInterface $entityManager, Sortie $sortie, ParticipantRepository $participantRepository): Response
    {

        $participant = $this->getUser();
        //Uniquement si la sortie est ouverte et la date limite d'inscription n'est pas dépassée
        if ($sortie->getEtat()->getLibelle() != 'Ouverte' and $sortie->getDateLimiteInscription() < new DateTime()) {

            $this->addFlash('danger', 'Impossible de s\'inscrire à cette sortie');
            return $this->redirectToRoute('app_home');
        }

        $sortie->addParticipantsInscrit($participant);
        $entityManager->flush();
        return $this->redirectToRoute('app_home');
    }

    //****************************************************
    // Désinscription à une sortie
    //****************************************************
    #[Route('/desinscription/{id}', name: 'sortie_desinscription', methods: ['GET'])]
    public function desinscription(Request $request, EntityManagerInterface $entityManager, Sortie $sortie, ParticipantRepository $participantRepository): Response
    {

        //Désinscription d'un participant à une sortie uniquement si elle est ouverte
        if ($sortie->getEtat()->getLibelle() != 'Ouverte') {
            $this->addFlash('danger', "Impossible de se désinscrire de cette sortie, elle n'est pas ouverte");
            return $this->redirectToRoute('app_home');
        }

        $participant = $this->getUser();
        $sortie->removeParticipantsInscrit($participant);
        $entityManager->flush();
        return $this->redirectToRoute('app_home');
    }

    /********************************************
     * Annulation d'une sortie avec commentaires
     * ******************************************/
    #[Route('/annulation/{id}', name: 'sortie_annulation', methods: ['GET', 'POST'])]
    public function annulation(Request $request, Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        $user = $this->getuser();
        if ($user !== $sortie->getOrganisateur() && !$this->isGranted('ROLE_ADMINISTRATEUR')) {
            throw new AccessDeniedException('Accès réservé à l\'organisateur ou à l\'administrateur.');
        }

        // Formulaire pour saisir le motif d'annulation
        $form = $this->createFormBuilder()
            ->add('motifAnnulation', TextareaType::class, [
                'label' => 'Motif : ',
                'attr' => ['rows'=>5],
                'constraints' => [
                    new NotBlank(message: 'Le motif doit être saisi')
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        // Vérifier si la sortie est en état "Ouverte" ou "Clôturée" et si la date de début est dans le futur
        if (($sortie->getEtat()->getLibelle() == "Ouverte" || $sortie->getEtat()->getLibelle() == "Clôturée") && $sortie->getDateHeureDebut() > new \DateTime()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $motif = $form->get('motifAnnulation')->getData();

                $etatAnnulee = $etatRepository->findOneBy(['libelle' => 'Annulée']);
                $sortie->setEtat($etatAnnulee);
                $sortie->setMotifAnnulation($motif);

                $this->addFlash("success", "L'annulation est enregistrée.");

                $entityManager->flush();

                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash("danger", "Vous ne pouvez annuler une sortie que si elle est en ligne et que la date de la sortie n'est pas encore arrivée.");
        }

        return $this->render('sortie/annulation.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }
}
