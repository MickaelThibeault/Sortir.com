<?php
//Créé le 05/08/2024 par Mickael

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void{
        $faker = Faker\Factory::create('fr_FR');
        $faker->seed(1234);

        // Fixtures des campus
        $campusSites = ['Rennes', 'Nantes', 'Quimper', 'Niort', 'En ligne'];
        $lesCampus = [];
        foreach ($campusSites as $site) {
            $campus = new Campus();
            $campus->setNom($site);
            $manager->persist($campus);
            $lesCampus[] = $campus;
        }

// Fixtures des états
$etatsLibelle = ['En création', 'Ouverte', 'Clôturée', 'En cours', 'Terminée', 'Annulée', 'Historisée'];
$etats = [];
foreach ($etatsLibelle as $libelle) {
    $etat = new Etat();
    $etat->setLibelle($libelle);
    $manager->persist($etat);
    $etats[] = $etat;
}

// Fixtures des participants
$participants = array();

$participants[0] = new Participant();
$participants[0]->setEmail("admin@sortir.com");
$participants[0]->setNom($faker->lastName);
$participants[0]->setPrenom($faker->firstName);
$participants[0]->setPseudo('admin');
$participants[0]->setAdministrateur(true);
$participants[0]->setActif(true);
$participants[0]->setTelephone('02' . $faker->numerify('########'));
$participants[0]->setCampus($lesCampus[1]);
$participants[0]->setPhoto('image_default.jpg');

$password = $this->hasher->hashPassword($participants[0], 'beans');
$participants[0]->setMotDePasse($password);
$manager->persist($participants[0]);

for ($i = 1; $i < 10; $i++) {
    $participants[$i] = new Participant();
    $participants[$i]->setEmail("participant$i@participant.com");
    $participants[$i]->setNom($faker->lastName);
    $participants[$i]->setPrenom($faker->firstName);
    $participants[$i]->setPseudo($faker->userName);
    $participants[$i]->setAdministrateur(false);
    $participants[$i]->setActif(true);
    $participants[$i]->setTelephone('02' . $faker->numerify('########'));
    $participants[$i]->setCampus($lesCampus[mt_rand(0, 4)]);
    $participants[$i]->setPhoto('image_default.jpg');

    $password = $this->hasher->hashPassword($participants[$i], 'beans');
    $participants[$i]->setMotDePasse($password);

    $manager->persist($participants[$i]);
}

// Fixtures des villes
$villes = [];
for ($i = 0; $i < 10; $i++) {
    $ville = new Ville();
    $ville->setNom($faker->city());
    $ville->setCodePostal($faker->postcode);
    $manager->persist($ville);
    $villes[] = $ville;
}

// Fixtures des lieux
$types = ['Monument', 'Parc', 'Musée', 'Théâtre', 'Bibliothèque', 'Galerie d\'art', 'Stade', 'Cinéma', 'Zoo', 'Plage'];
$lieux = [];
for ($i = 0; $i < 15; $i++) {
    $lieux[$i] = new Lieu();

    $lieux[$i]->setRue($faker->streetAddress);
    $villeLieu = $villes[mt_rand(0, count($villes) - 1)];
    $lieux[$i]->setVille($villeLieu);
    $typeLieu = $types[mt_rand(0, count($types) - 1)];
    $nomLieu = $typeLieu . ' ' . $villeLieu->getNom();
    $lieux[$i]->setNom($nomLieu);
    $lieux[$i]->setLatitude($faker->latitude);
    $lieux[$i]->setLongitude(($faker->longitude));
    $manager->persist($lieux[$i]);
}

// Fixtures des sorties

$sortieNom = ['Philo', 'Origamie', 'Perles', 'Concert métal', 'Jardinage', 'Cinéma', 'Pâte à sel'];
for ($i = 0; $i < 10; $i++) {
    $sortie = new Sortie();
    $sortie->setNom($sortieNom[mt_rand(0, count($sortieNom) - 1)]);
    $sortie->setDateHeureDebut($faker->dateTimeBetween('now', '+40 days'));
    $sortie->setDuree($faker->randomNumber(3, false));
    $sortie->setDateLimiteInscription($faker->dateTimeBetween('now', $sortie->getDateHeureDebut())->setTime(0, 0, 0,));
    $sortie->setNbInscriptionsMax(mt_rand(2, 25));
    $sortie->setCampus($lesCampus[mt_rand(0, 4)]);
    $organisateur = $participants[mt_rand(1, 9)];
    $sortie->setOrganisateur($organisateur);
    $sortie->addParticipantsInscrit($organisateur);
    $sortie->setLieu($lieux[mt_rand(0, 14)]);
    $sortie->setInfosSortie('Just have fun with ' . $sortie->getNom() . ' ' . $sortie->getLieu()->getRue() . ' à ' . $sortie->getLieu()->getVille()->getNom());
    $sortie->setEtat($etats[mt_rand(0, 1)]);
    $manager->persist($sortie);
}

$manager->flush();
}

}
