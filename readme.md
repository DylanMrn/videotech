# VIDEOTECH (OS : Linux/Ubuntu)

### Description
 * Réalisation d'une videotèque
> Dylan Marion

#### Pré-Requis
* Symfony 5.3
* Php 7.4 (actuel 8)
* Composer / Symfony-CLI

> symfony check:requirements

### Etape d'installation
 * Après un clone des sources, lancer la migration de la BDD :
> php bin/console doctrine:migrations:migrate
 * /!\ la BDD est vide et je n'ai fait pas de fixture...

### Deuxièmes étapes
* Lancer le serveur : 
> symfony server:start
* Création d'un compte
* Aller sur le site, sur la page 'Ajouter une catégorie' et créer en une :)
* Aller sur la page 'Ajouter un film' et ajouter un film.
* Bonne navigation ! :)