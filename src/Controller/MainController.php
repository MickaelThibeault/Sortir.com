<?php

namespace App\Controller;


use App\archivage\Archivage;
use App\Entity\Sortie;
use App\Form\SortieFilterType;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;


#[Route('/')]
class MainController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(SortieRepository $sortieRepository, Request $request, Security $security, Archivage $archivage,MobileDetectorInterface $mobileDetector ): Response
    {
        //Archivage des sorties de plus d'un mois (Guillaume si problèmes)
        $archivage->archivage();
        $archivage->cloture();

       $isMobile = $mobileDetector->isMobile();
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Créer le formulaire de filtre
        $form = $this->createForm(SortieFilterType::class,null,['user'=>$user]);
        $form->handleRequest($request);

        // Initialiser les critères de filtrage
        $criteria = [];


        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données de filtre
            $data = $form->getData();

            if ($data['dateDebut']) {
                $criteria['dateDebut'] = $data['dateDebut'];
            }
            if ($data['dateFin']) {
                $criteria['dateFin'] = $data['dateFin'];
            }
            if ($data['campus']) {
                $criteria['campus'] = $data['campus'];
            } else {
                $criteria['campus'] = null;
            }

            if ($data['nom']) {
                $criteria['nom'] = $data['nom'];
            }
            if ($data['organisateur']) {
                $criteria['organisateur'] = $data['organisateur'];
            }
            if ($data['inscrit']) {
                $criteria['inscrit'] = $data['inscrit'];
            }
            if ($data['pasinscrit']) {
                $criteria['pasinscrit'] = $data['pasinscrit'];
            }
            if ($data['passees']) {
                $criteria['passees'] = $data['passees'];
            }
            dump($criteria);
//            dump($data);
        }

        // Récupérer les sorties filtrées

        $sorties = $sortieRepository->findByCriteria($criteria,$user->getId());

        return $this->render('sortie/index.html.twig', [
            'form' => $form->createView(),
            'sorties' => $sorties,
            'is_mobile' => $isMobile,
        ]);
    }

}
