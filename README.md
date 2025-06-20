# 🎫 Ticket API — Documentation technique

API de gestion de tickets conforme aux **standards professionnels**, basée sur Symfony 7, Doctrine ORM, PostgreSQL (Docker), et pensée pour l’industrialisation, la sécurité, les tests et l’extensibilité.

---

## 0. 📦 Installation rapide

```bash
# Cloner le dépôt
git clone <repo-url> TicketApi
cd TicketApi

# Installer les dépendances PHP
composer install

# Démarrer les services nécessaires (PostgreSQL, Mailpit)
docker compose up -d

# Générer les clés JWT pour l’authentification
php bin/console lexik:jwt:generate-keypair

# Appliquer la migration initiale
php bin/console doctrine:migrations:migrate

# Lancer les tests unitaires
php bin/phpunit
```

Les variables d’environnement par défaut sont définies dans `.env`. Personnalisez-les au besoin (base de données, mailer…).

---

## 1. 🚧 Structure des entités

### **User**

| Champ           | Type        | Contraintes                |
| --------------- | ----------- | -------------------------- |
| id              | auto        | Clé primaire               |
| email           | string(180) | Unique, not null           |
| password        | string(255) | Hashé, not null            |
| roles           | json        | Tableau de rôles, not null |
| name            | string(100) | not null                   |
| tickets         | OneToMany   | Vers Ticket (propriétaire) |
| assignedTickets | OneToMany   | Vers Ticket (assigné)      |
| comments        | OneToMany   | Vers Comment (auteur)      |

### **Ticket**

| Champ           | Type                | Contraintes/Enum                                            |
| --------------- | ------------------- | ----------------------------------------------------------- |
| id              | auto                | Clé primaire                                                |
| title           | string(255)         | not null                                                    |
| description     | text                | not null                                                    |
| priority        | string(20)          | Enum: basse, normale, haute (logique métier)                |
| status          | string(20)          | Enum: pending, waiting, in\_progress, done (logique métier) |
| createdAt       | datetime\_immutable | not null                                                    |
| owner           | ManyToOne (User)    | not null, inversedBy=tickets                                |
| assignee        | ManyToOne (User)    | nullable, inversedBy=assignedTickets                        |
| assignedAtFirst | datetime\_immutable | nullable                                                    |
| assignedAtLast  | datetime\_immutable | nullable                                                    |
| comments        | OneToMany (Comment) | inverse côté `ticket`                                       |

### **Comment**

| Champ     | Type                | Contraintes                   |
| --------- | ------------------- | ----------------------------- |
| id        | auto                | Clé primaire                  |
| content   | text                | not null                      |
| createdAt | datetime\_immutable | not null                      |
| author    | ManyToOne (User)    | not null, inversedBy=comments |
| ticket    | ManyToOne (Ticket)  | not null, inversedBy=comments |

**Remarque** :
Les relations inverses sont bien présentes (`User.tickets`, `User.assignedTickets`, `User.comments`, `Ticket.comments`).
Aucune relation ManyToMany inutile. Les propriétés sont nommées selon les standards Symfony/Doctrine.

---

## 2. 🛡️ Sécurité & droits

* Authentification prévue via JWT (tokens).
* Permissions **granulaires** via [Voters](https://symfony.com/doc/current/security/voters.html) Symfony :

  * Seuls l’auteur, l’assigné, ou le propriétaire d’un ticket/comment peuvent effectuer les actions.
  * Aucun accès non-autorisé par défaut (fail-safe).
* Effets de bord gérés par Doctrine : suppression en cascade ou nullification contrôlée des entités liées.

---

## 3. ⚙️ Fonctionnalités principales

* **Tickets :** création, modification, suppression, assignation, clôture, listing (par user, par statut…).
* **Commentaires :** CRUD par ticket, visibilité et droits selon l’auteur/ticket.
* **Authentification sécurisée** et récupération du user courant.
* **API Documentée** automatiquement (API Platform/OpenAPI).
* **Sécurité** : aucun mot de passe stocké en clair, tout est hashé par l’API, respect des bonnes pratiques RGPD/OWASP.

---

## 4. 🚀 Environnement de développement / Docker

### **Stack utilisée :**

* **Symfony 7** (API Platform intégré)
* **PostgreSQL 16** (via Docker)
* **Mailpit** (test email, via Docker)
* **PHP 8.2+**
* **Docker Desktop**

### **Fichiers clés :**

* `.env` : configuration environnementale (voir DATABASE\_URL pour PostgreSQL)
* `compose.yaml` : configuration Docker (base de données et mailer)
* `compose.override.yaml` : ports exposés localement

### **Commandes principales :**

```bash
# Démarrer Docker Desktop
docker compose up -d

# Vérifier la santé des containers
docker compose ps

# Générer et appliquer les migrations Doctrine
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Vérifier la cohérence du schéma
php bin/console doctrine:schema:validate
```

---

## 5. 🧪 Qualité logicielle & outillage

* **Tests unitaires/fonctionnels** :

  * Framework : [PHPUnit](https://symfony.com/doc/current/testing.html)
  * Lancer les tests : `php bin/phpunit`
  * Méthodologie : TDD/DDD possible, cf. fichiers fournis (checklists)
* **Fixtures & Faker** pour les données de tests/démo.
* **CI/CD** possible (GitLab CI, GitHub Actions) pour automatiser tests, build, déploiement.
* **API Platform** pour l’exposition REST + documentation Swagger automatique.
* **Mailpit** pour la capture et vérification des emails en développement.

---

## 6. 💡 Extensions prévues / ToDo

* Ajout de la gestion d’upload de pièces jointes sur les tickets.
* Gestion des notifications par email pour les actions critiques.
* Possibilité d’étendre les rôles pour des profils support/admin avancés.
* Dashboard analytics pour les tickets/performances.
* Tests de montée en charge avec [Symfony Panther](https://symfony.com/doc/current/components/panther.html) ou [Blackfire](https://www.blackfire.io/).

---

## 7. 📚 Documentation officielle

* [Symfony - Doctrine ORM](https://symfony.com/doc/current/doctrine.html)
* [Symfony - Sécurité (Voters)](https://symfony.com/doc/current/security/voters.html)
* [API Platform](https://api-platform.com/docs/)
* [Docker Desktop](https://docs.docker.com/desktop/)
* [PHPUnit](https://symfony.com/doc/current/testing.html)

---

## 8. 📝 Historique technique (log, migration, structure)

```text
- Création entités : User, Ticket, Comment (relations, contraintes OK)
- Docker Compose monté (PostgreSQL/Mailpit), containers healthy
- Génération et application des migrations (schema synchronisé)
- Validation mapping et schéma Doctrine : OK
```

---

## 9. ✅ Bonnes pratiques respectées

* Hashage des mots de passe.
* Séparation des rôles via JSON (Doctrine).
* Relations explicites (inversedBy / mappedBy) pour navigabilité et cohérence.
* Dockerisation pour portabilité et reproductibilité.
* Migration versionnée pour traçabilité.
* Sécurité par défaut, tests prêts à implémenter.

---

## 10. 📈 État du projet & prochaines étapes

### Ce qui est en place

* Modèle de données complet (User, Ticket, Comment) avec relations Doctrine
* API Platform exposant les entités et un contrôleur personnalisé pour la création de tickets
* Authentification JWT configurée (via LexikJWTAuthenticationBundle)
* Migrations initiales versionnées
* Suite de tests unitaires et fonctionnels prête à l’emploi

### Ce qu’il reste à faire

* Implémenter la logique d’assignation/désassignation via des endpoints dédiés **(fait)**
* Ajouter la gestion de l’avancement des tickets (start progress/close) **(fait)**
* Couvrir ces nouvelles routes par des tests et une documentation Swagger *(en cours)*
* Mettre en place une pipeline CI/CD pour automatiser tests et déploiement
* Optionnel : notifications email et tableau de bord statistiques

---

> **Projet structuré, industrialisable, prêt pour la CI/CD, la documentation et les tests avancés.**
> *Relancer `php bin/console make:migration` puis `php bin/console doctrine:migrations:migrate` à chaque évolution du modèle.*

---

### Pour toute nouvelle étape (tests, fixtures, API Platform, sécurité), envoie ta demande :

**“Next step : \[objectif]”** pour une guidance ultra-performante et à jour.

---

## 11. 🚀 Exemples d'appels Workflow

### `POST /api/tickets/{id}/assign`

```bash
curl -X POST http://localhost/api/tickets/1/assign \
     -H "Authorization: Bearer <JWT>" \
     -H "Content-Type: application/json" \
     -d '{"assignee_id": 2}'
```

Réponse :

```json
{"message": "Ticket assigned"}
```

### `POST /api/tickets/{id}/unassign`

```bash
curl -X POST http://localhost/api/tickets/1/unassign \
     -H "Authorization: Bearer <JWT>"
```

Réponse :

```json
{"message": "Ticket unassigned"}
```

### `POST /api/tickets/{id}/start`

```bash
curl -X POST http://localhost/api/tickets/1/start \
     -H "Authorization: Bearer <JWT>"
```

Réponse :

```json
{"message": "Ticket started"}
```

### `POST /api/tickets/{id}/close`

```bash
curl -X POST http://localhost/api/tickets/1/close \
     -H "Authorization: Bearer <JWT>"
```

Réponse :

```json
{"message": "Ticket closed"}
```

### `GET /api/my-tickets`

```bash
curl -H "Authorization: Bearer <JWT>" http://localhost/api/my-tickets
```

Réponse (exemple) :

```json
[
  {"id": 1, "title": "Bug", "status": "pending"}
]
```

### `GET /api/assigned-tickets`

```bash
curl -H "Authorization: Bearer <JWT>" http://localhost/api/assigned-tickets
```

Réponse (exemple) :

```json
[
  {"id": 1, "title": "Bug", "status": "in_progress"}
]
```

### `GET /api/me`

```bash
curl -H "Authorization: Bearer <JWT>" http://localhost/api/me
```

Réponse :

```json
{"id": 1, "email": "user@example.com", "name": "John Doe"}
```

**Fin de documentation**