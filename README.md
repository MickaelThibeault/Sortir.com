

<img src="public/images/base/logo.png" alt="Nom du Logo" width="100"/>
## Sortir.com

Sortir.com est un projet web développé avec le framework Symfony 6.4. Ce projet a été réalisé par **Mickael Thibeault**, **Guillaume Gauvreau**, **Sandrine Navarro**, et **Arthur Le Goux**. 
La société ENI souhaite développer pour ses stagiaires actifs ainsi que ses anciens stagiaires
une plateforme web leur permettant d’organiser des sorties. La plateforme est une
plateforme privée dont l’inscription sera gérée par le ou les administrateurs. Les sorties ainsi
que les participants sont rattachés à un campus pour permettre une organisation
géographique des sorties.


## Prérequis

Avant de commencer, assurez-vous d'avoir les éléments suivants installés sur votre machine :

- **PHP** 8.1 ou supérieur
- **Composer** (https://getcomposer.org/)
- **Symfony CLI** (optionnel mais recommandé) (https://symfony.com/download)
- **MySQL** ou un autre système de gestion de base de données compatible

## Installation

Suivez les étapes ci-dessous pour configurer et exécuter le projet en local :

1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/ArthurLGX/Projet_Sorties.git
2. **Installer les dépendances** :
   Cette commande installe toutes les dépendances nécessaires à partir du fichier `composer.json` et garantit que ton projet est correctement configuré pour fonctionner.
   ```bash
   composer install
4. **Configurer les variables d'environnement** :
   ```bash
   cp .env .env.local
5. **Ajouter les informations à la base de données** :
   ```bash
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/nom_de_la_base_de_donnees?serverVersion=8&charset=utf8mb4"

6. **Créer la base de données** :
   ```bash
   php bin/console doctrine:database:create
7. **Exécuter les migrations pour créer les tables nécessaires :
   ```bash
   php bin/console doctrine:migrations:migrate
8. **Lancer le serveur de développement**
   ```bash
   symfony server:start

##Structure du projet

Le projet est structuré de manière à suivre les meilleures pratiques de Symfony et du modèle MVC (Modèle-Vue-Contrôleur).

- **assets/** : Contient les fichiers statiques comme les CSS, JavaScript, images, etc., qui sont compilés et utilisés dans l'application.
- **bin/** : Contient les fichiers exécutables, notamment `console`, qui est l'outil en ligne de commande de Symfony.
- **config/** : Contient les fichiers de configuration de l'application, y compris les paramètres des services, des routes, des packages, etc.
- **migrations/** : Contient les fichiers de migration de la base de données générés par Doctrine.
- **public/** : Contient les fichiers accessibles publiquement, comme les assets (CSS, JS) et le point d'entrée `index.php`.
- **src/** : Contient le code source de l'application, y compris les contrôleurs, les entités, les services, etc.
- **templates/** : Contient les fichiers Twig pour le rendu des vues.
- **tests/** : Contient les tests unitaires et fonctionnels pour l'application.
- **translations/** : Contient les fichiers de traduction pour l'internationalisation de l'application.
- **var/** : Contient les fichiers générés par Symfony à l'exécution, comme les logs, le cache, etc.
- **vendor/** : Contient les dépendances installées via Composer.
- **.env** : Fichier contenant les variables d'environnement par défaut pour l'application.
- **.env.local** : Fichier contenant les variables d'environnement spécifiques à l'environnement local.
- **.gitignore** : Ce fichier spécifie les fichiers et dossiers que Git doit ignorer et ne pas suivre dans le contrôle de version.
- **compose.override.yaml** : Un fichier de configuration Docker Compose utilisé pour surcharger les paramètres définis dans `compose.yaml`.
- **compose.yaml** : Fichier de configuration Docker Compose principal, utilisé pour définir les services, réseaux, et volumes Docker nécessaires à l'application.
- **composer.json** : Le fichier de configuration de Composer qui définit les dépendances PHP nécessaires pour l'application ainsi que les scripts et configurations spécifiques.
- **composer.lock** : Ce fichier verrouille les versions exactes des dépendances installées via Composer, assurant une installation identique sur différentes machines.
- **importmap.php** : Fichier de configuration utilisé pour gérer les imports JavaScript dans les applications Symfony utilisant Importmap.
- **php.ini** : Fichier de configuration de PHP qui définit les paramètres de l'environnement PHP utilisé par l'application.
- **phpunit.xml.dist** : Fichier de configuration par défaut pour PHPUnit, utilisé pour exécuter les tests unitaires et fonctionnels de l'application.
- **symfony.lock** : Fichier généré par Symfony Flex, verrouillant les versions des recettes installées pour garantir un environnement cohérent.

Créé avec ❤️ par **Mickael Thibeault**, **Guillaume Gauvreau**, **Sandrine Navarro**, et **Arthur Le Goux**.



