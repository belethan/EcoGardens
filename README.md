# <span style="color: violet;">EcoGarden</span>
## <span style="color: blue;"> 🎯 Objectifs</span>

L’API EcoGarden permet à tout utilisateur de créer un compte lié à sa ville, puis d’accéder — après authentification par token JWT — à des conseils de jardinage mensuels et aux données météo locales obtenues depuis une API publique (comme OpenWeatherMap).
Les administrateurs disposent de routes supplémentaires pour ajouter, modifier ou supprimer des conseils et des comptes utilisateurs.
Toutes les réponses sont au format JSON, avec une gestion stricte des codes d’erreur HTTP pour assurer la fiabilité et la sécurité des échanges.

### <span style="color: blue"> 🗄️ Base de données</span>
### <span style="color: #BAFFC9">Tables</span>
<div style="text-decoration: underline;">
1. 👨🏼‍🌾 User
</div>

Contient les informations de chaque utilisateur de l’API.

| Champ       | Type                    | Description                               |
|-------------|-------------------------|-------------------------------------------|
| id          | INT (PK, AI)            | Identifiant unique                        |
| email       | VARCHAR(255)            | Adresse email de l'utilisateur            |
| password    | VARCHAR(255)            | Mot de passe haché                        |
| ville       | VARCHAR(100)            | Ville de l'utilisateur                    |
| code_postal | VARCHAR(10) (optionnel) | Pour identifier plus précisément la ville |
| roles       | JSON                    | Pour avenir valeur par ROLE_USER          |
| created_at  | DATETIME                | Date de création du compte                |
| updated_at  | DATETIME                | Dernière mise à jour                      |


Remarques :
L’utilisateur est lié à ses requêtes météo par sa ville.
Les routes /user, /auth, /user/{id} manipulent cette table.

2. 💡Conseil


Contient les conseils de jardinage.

| Champ | Type | Description |
|-------|------|-------------|
| id | INT (PK, AI) | Identifiant du conseil |
| contenu | TEXT | Contenu du conseil |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de mise à jour |
| user_id | INT | Identifiant de l'utilisateur |

Remarques :
Accessible via /conseil/{mois} ou /conseil/.
Modifiable uniquement par un administrateur.

3. 💡Temps_Conseil

   | Colonne      | Type | Attributs           | Description                                      |
   |---------------|------|--------------------|--------------------------------------------------|
   | **id**        | int  | AUTO_INCREMENT, PK | Identifiant unique de l’enregistrement            |
   | **conseil_id**| int  | NOT NULL, FK       | Référence à l’identifiant du conseil associé      |
   | **mois**      | int  | NOT NULL           | Mois concerné par le conseil (1 = janvier, etc.) |
   | **annee**     | int  | NOT NULL           | Année concernée par le conseil                   |

### Contraintes et relations
- **Clé primaire :** `id`
- **Clé étrangère :** `conseil_id` → `conseil(id)`  
  → Assure la cohérence avec la table `conseil` (suppression/mise à jour en cascade selon la config Doctrine).

### Exemple d’utilisation
Chaque ligne de `temps_conseil` relie un **conseil** à un **mois/année** spécifique, permettant d’associer un même conseil à plusieurs périodes.



## <span style="color: blue">🧩 Authentification</span>

👮‍♀️ il existe 2 manières de s'authentifier :
- **Authentification par email et mot de passe**
- **Authentification par token JWT**

Le JWT (JSON Web Token) est un jeton signé (souvent via HS256) qui contient des informations sur un utilisateur, comme son id ou son email.
Une fois généré à la connexion, il permet d’accéder à des routes protégées sans session, ni cookie.

👉 Le token est associé à un utilisateur — il contient ses identifiants dans la “payload”, mais ne stocke pas le mot de passe.
