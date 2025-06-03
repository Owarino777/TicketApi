# API Ticket - Spécifications

## 1. Structure d'entités conforme à tous les attendus

### User
- **id**: auto
- **email**: unique, obligatoire
- **password**: hashé
- **roles**: JSON, pour sécurité par rôle
- **name**: obligatoire

### Ticket
- **id**: auto
- **title**: obligatoire
- **description**: obligatoire
- **priority**: enum : basse, normale, haute
- **status**: enum : pending, waiting, in_progress, done
- **createdAt**: datetime_immutable
- **owner**: ManyToOne User
- **assignee**: ManyToOne User, nullable
- **assignedAtFirst**: datetime_immutable, nullable
- **assignedAtLast**: datetime_immutable, nullable
- **comments**: OneToMany Commentaire

### Comment
- **id**: auto
- **content**: obligatoire
- **createdAt**: datetime_immutable
- **author**: ManyToOne User
- **ticket**: ManyToOne Ticket

Toutes les relations sont conformes au cahier des charges et prévues pour la gestion des effets de bord.

## 2. Sécurité et droits
- CRUD sur tickets/commentaires réservé aux rôles exacts demandés (propriétaire, assigné, auteur)
- Gestion fine des permissions via Voters/Policies Symfony
- Effets de bord (cascade sur suppressions, nullification ou suppression des entités liées)

## 3. Fonctionnalités à couvrir (extraits du doc)
- ✅ Création/modification/suppression de tickets et commentaires
- ✅ Assignation/désassignation
- ✅ Début/clôture traitement
- ✅ Authentification et gestion des tokens
- ✅ Lister tickets créés ou assignés, récupérer user courant

## 4. Qualité logicielle & outillage
- API Platform = documentation OpenAPI générée
- Tests unitaires/func Symfony (PHPUnit) + tests manuels Postman
- Faker pour seeders/fixtures
- Organisation modulaire du code, respect des conventions Symfony
- Commit et CI/CD GitLab possibles
