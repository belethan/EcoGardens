# 🌿 EcoGardens – API Symfony 7

API REST sécurisée pour la gestion de conseils écologiques et de données météo locales.  
Basée sur **Symfony 7**, **JWT Authentication**, **Doctrine ORM**, et **Memcached** pour le caching.

---

## ⚙️ 1. Configuration minimale du serveur

| Élément | Version / Outil recommandé |
|----------|----------------------------|
| **OS** | Ubuntu 24.04 / macOS / WSL2 |
| **Serveur web** | Apache 2.4 ou Nginx |
| **PHP** | ≥ 8.3 (extensions listées ci-dessous) |
| **Base de données** | MySQL ≥ 8.0 |
| **Cache** | Memcached ou Redis |
| **Port local** | `http://localhost:8000` (via Symfony CLI) |

### Extensions PHP nécessaires


---

## 🧰 2. Applications et outils requis

| Outil | Utilisation | Installation |
|--------|--------------|---------------|
| **Composer** | Gestion des dépendances PHP | `sudo apt install composer` |
| **Symfony CLI** | Serveur + outils de dev | `curl -sS https://get.symfony.com/cli/installer | bash` |
| **MySQL Server** | Base de données principale | `sudo apt install mysql-server` |
| **Memcached** | Cache pour les données météo | `sudo apt install memcached php-memcached` |
| **Git** | Versionnement | `sudo apt install git` |
| **Postman** | Tests des routes API | [postman.com/downloads](https://www.postman.com/downloads/) |

---

## 🧩 3. Installation du projet

### 1️⃣ Cloner le dépôt
```bash
git clone git@github.com:belethan/EcoGardens.git
cd EcoGardens
composer install
```
### 3.1 Configurer les variables d’environnement
```
DATABASE_URL="mysql://root:root@127.0.0.1:3306/ecogardens?serverVersion=8.0"
OPENWEATHERMAP_API_KEY="ta_cle_api"
MEMCACHED_DSN="memcached://127.0.0.1:11211"
JWT_PASSPHRASE="ta_passphrase"
```
## 🔐 4. Sécurité & Authentification JWT
Génération des clés JWT
```
php bin/console lexik:jwt:generate-keypair
```
Les clés seront générées dans config/jwt/ :
private.pem, public.pem

## 🧱 5. Bundles Symfony utilisés

| Bundle                            | Description                   |
| --------------------------------- | ----------------------------- |
| `symfony/orm-pack`                | Gestion de la base de données |
| `symfony/security-bundle`         | Gestion des rôles et sécurité |
| `lexik/jwt-authentication-bundle` | Authentification JWT          |
| `symfony/http-client`             | Appels à l’API météo          |
| `symfony/cache`                   | Intégration Memcached         |
| `symfony/validator`               | Validation des entités        |
| `symfony/serializer`              | Transformation en JSON        |
| `symfony/maker-bundle`            | Outils de génération (dev)    |

## 🌤️ 6. Configuration spécifique à EcoGardens

| Élément                        | Exemple                                       |
| ------------------------------ | --------------------------------------------- |
| **Base de données**            | `php bin/console doctrine:database:create`    |
| **Migrations**                 | `php bin/console doctrine:migrations:migrate` |
| **Données de test (fixtures)** | `php bin/console doctrine:fixtures:load`      |
| **Serveur local Symfony**      | `symfony serve -d`                            |

## 🧪 7. Routes principales de l’API

| Méthode  | Route                 | Auth     | Description                        |
|----------|-----------------------| -------- |------------------------------------|
| `POST`   | `/api/user`           | ❌        | Créer un utilisateur               |
| `PUT`    | `/api/user/{id}`      | 🔒        | modification un utilisateur        |
| `DELETE` | `/api/user/{id}`      | 🔒        | modification un utilisateur        |
| `POST`   | `/api/auth`           | ❌        | Authentification JWT               |
| `GET`    | `/api/conseil`        | ✅        | Liste des conseils du mois courant |
| `GET`    | `/api/conseil/{mois}` | ✅        | Conseils pour un mois donné        |
| `POST`   | `/api/conseil`        | 🔒  | Ajout d’un conseil                 |
| `PUT`    | `/api/conseil/{id}`   | 🔒  | Modification d’un conseil          |
| `DELETE` | `/api/conseil/{id}`   | 🔒  | Suppression d’un conseil           |
| `GET`    | `/api/meteo/{ville}`  | ✅        | Données météo d’une ville          |
| `GET`    | `/api/meteo`          | ✅        | Météo basée sur la ville du user   |

## ✅ 8. Tests API de base (Postman)

Créer un compte utilisateur → POST /api/user

S’authentifier → POST /api/auth → récupérer le token JWT

Utiliser le token dans l’onglet Authorization → Type: Bearer Token

Tester :

/api/conseil

/api/meteo

/api/conseil/{mois}
