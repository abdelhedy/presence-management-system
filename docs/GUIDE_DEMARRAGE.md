# 🎓 Guide de mise en route - Système de Présence ENET'Com Sfax

Ce guide décrit l'installation locale du projet avec XAMPP sous Windows, puis un scénario de test complet.

---

## 🚀 Étapes de configuration

### 1️⃣ Récupérer le projet

```bash
cd C:\xampp\htdocs
git clone https://github.com/abdelhedy/presence-management-system.git
cd presence-management-system
```

### 2️⃣ Variables d'environnement (.env)

```bash
copy .env.example .env
```

Puis adapter `.env` :

```ini
APP_URL=http://localhost/presence-management-system
APP_DEBUG=false          # true pour afficher le détail des erreurs (développement)

DB_HOST=localhost
DB_NAME=systeme_presence
DB_USER=root
DB_PASSWORD=

ADMIN_EMAIL=admin@presence.com
ADMIN_PASSWORD=          # 8 caractères minimum
```

> Le fichier `.env` n'est jamais versionné. Sans `.env`, les valeurs par défaut de XAMPP (`root`, sans mot de passe) sont utilisées.

### 3️⃣ Base de données (MySQL)

1. Démarrer **Apache** et **MySQL** depuis le XAMPP Control Panel.
2. Importer le schéma (crée la base `systeme_presence` et toutes ses tables) :

```bash
C:\xampp\mysql\bin\mysql -u root -p < database\schema.sql
```

Ou dans phpMyAdmin : onglet **Importer** → `database/schema.sql`.

> Aucun trigger ni événement MySQL n'est nécessaire :
> - l'**inscription automatique** des étudiants est faite en PHP à la création d'un cours (`InscriptionDAO::inscrireEtudiantsEligibles()`) et à l'inscription d'un étudiant (`InscriptionDAO::inscrireAuxCoursEligibles()`) ;
> - les **changements de statut des séances** et l'**enregistrement des absents** sont faits en PHP (`config/auto_update_seances.php`, exécuté au chargement du tableau de bord enseignant et des pages de séances d'un cours).

### 4️⃣ Compte administrateur

Après avoir défini `ADMIN_EMAIL` et `ADMIN_PASSWORD` dans `.env` :

```bash
C:\xampp\php\php.exe scripts\insert_admin.php
```

### 5️⃣ Python (reconnaissance faciale)

#### A. Installer Python

- Télécharger depuis https://www.python.org/downloads/ (3.10 recommandé)
- ⚠️ Cocher **"Add Python to PATH"** lors de l'installation

#### B. Créer l'environnement virtuel et installer les bibliothèques

```bash
python -m venv .venv
.venv\Scripts\python.exe -m pip install --upgrade pip
.venv\Scripts\python.exe -m pip install -r python/requirements.txt
```

Cela installe DeepFace (modèle Facenet), OpenCV, NumPy et tf-keras dans `.venv`. PHP utilise automatiquement ce `.venv` ; pour un autre interpréteur, renseigner `PYTHON_BIN` dans `.env`.

Au premier usage, DeepFace télécharge les poids du modèle : la première vérification est plus lente.

#### C. Tester l'installation

```bash
.venv\Scripts\python.exe python/extract_face_encoding.py chemin\vers\photo.jpg
# Doit afficher un JSON avec "success": true
```

### 6️⃣ Dossiers d'upload

Les dossiers `uploads/images_reference/` et `uploads/temp/` sont fournis (vides) dans le dépôt. Ils doivent être accessibles en lecture/écriture par Apache.

### 7️⃣ Configuration PHP

#### A. Vérifier php.ini (`C:\xampp\php\php.ini`)

```ini
; Limites d'upload
upload_max_filesize = 10M
post_max_size = 10M

; Extensions
extension=gd
extension=pdo_mysql
extension=fileinfo
```

Vérifier aussi que `shell_exec` n'est pas listé dans `disable_functions` : il sert à appeler les scripts Python.

#### B. Redémarrer Apache

Dans XAMPP Control Panel : Stop puis Start Apache.

---

## ✅ Vérification de l'installation

### Test 1 : Base de données

```sql
USE systeme_presence;
SHOW TABLES;
-- Doit afficher 8 tables : cours, enseignants, etudiants, images_reference,
-- inscriptions, presences, seances, utilisateurs
```

### Test 2 : Python

```bash
python --version
# Doit afficher Python 3.x

.venv\Scripts\python.exe -c "from deepface import DeepFace; print('OK')"
# Doit afficher : OK
```

### Test 3 : Application

Ouvrir http://localhost/presence-management-system/views/index.php et se connecter avec le compte administrateur.

---

## 🎯 Scénario de test complet

### Étape 1 : Créer les comptes

1. Créer un **étudiant** via `views/register.php` (ou depuis l'espace admin), avec un niveau, une spécialité et une année scolaire, par exemple : 2ème année / Ingénierie Données / 2025-2026.
2. Créer un **enseignant** de la même façon.

> L'inscription est automatique dans les deux sens : à la création d'un cours (étudiants existants) et à l'inscription d'un étudiant (cours actifs existants).

### Étape 2 : Créer un cours (enseignant)

1. Dashboard Enseignant → **Créer un cours**
2. Remplir avec **le même niveau, la même spécialité et la même année scolaire** que l'étudiant :
   - Nom : Développement Web
   - Code : DEV101
3. Créer le cours
4. ✅ **Vérifier que l'étudiant est inscrit automatiquement**

```sql
SELECT * FROM inscriptions WHERE id_cours = [id du cours créé];
-- Doit contenir l'étudiant avec statut = 'inscrit'
```

### Étape 3 : Planifier une séance

1. Dashboard → **Planifier une séance**
2. Remplir :
   - Cours : [celui créé]
   - Date : aujourd'hui
   - Heure début : [dans 2 minutes]
   - Heure fin : [dans 10 minutes]
   - Salle : A101
   - Type : Cours
3. Planifier
4. ✅ **Après l'heure de début, recharger le tableau de bord enseignant : le statut passe à `en_cours`**

```sql
SELECT id_seance, statut FROM seances ORDER BY id_seance DESC LIMIT 1;
-- 'planifie' → 'en_cours'
```

### Étape 4 : Ajouter la photo de référence (étudiant)

1. Se connecter en tant qu'étudiant → **Mon Profil**
2. Glisser-déposer une photo de face, bien éclairée, avec un seul visage (JPG/PNG, 5 Mo max)
3. ✅ **Vérifier que l'encodage est extrait**

```sql
SELECT id_image, chemin_image, LEFT(encodage_facial, 50) FROM images_reference
WHERE id_etudiant = [id étudiant];
```

### Étape 5 : Marquer sa présence

1. Dashboard → **Marquer Présence**
2. Sélectionner la séance en cours
3. Autoriser la webcam et capturer le visage
4. ✅ **Vérifier l'enregistrement**

```sql
SELECT * FROM presences WHERE id_etudiant = [id] AND id_seance = [id];
-- statut = 'present', methode_validation = 'image', score_reconnaissance ≥ 70
```

### Étape 6 : Fin de séance et absents

1. Attendre l'heure de fin, puis recharger le tableau de bord enseignant
2. ✅ **La séance passe à `terminee` et les inscrits sans présence sont marqués absents**

```sql
SELECT * FROM presences WHERE id_seance = [id de la séance];
-- Tous les inscrits : présents + absents (methode_validation = 'automatique')
```

---

## ⏱️ Mise à jour planifiée (optionnelle)

Les statuts sont mis à jour au chargement de certaines pages (au plus une fois par minute). Pour qu'ils soient à jour même sans visite, planifier :

```bash
C:\xampp\php\php.exe C:\xampp\htdocs\presence-management-system\cron\update_seances_statuts.php
```

- **Windows** : Planificateur de tâches, toutes les 5 minutes
- **Linux** : `*/5 * * * * php /chemin/vers/cron/update_seances_statuts.php`

---

## 🔧 Dépannage

| Problème | Solution |
| --- | --- |
| « Service momentanément indisponible » | Connexion MySQL impossible : vérifier MySQL et `.env`, ou passer `APP_DEBUG=true` pour voir l'erreur |
| Aucun étudiant inscrit à la création d'un cours | Niveau, spécialité et année scolaire doivent être **identiques** à ceux de l'étudiant |
| Le statut des séances ne change pas | Recharger le tableau de bord enseignant, ou planifier `cron/update_seances_statuts.php` |
| Python non reconnu | Ajouter `C:\Users\[user]\AppData\Local\Programs\Python\Python3x\` au PATH, puis redémarrer Apache |
| « Module deepface non installé » | `.venv\Scripts\python.exe -m pip install -r python/requirements.txt` (ou dans l'interpréteur défini par `PYTHON_BIN`) |
| « Aucun visage détecté » | Photo de face, bien éclairée, un seul visage |
| Webcam non accessible | Autoriser la caméra dans le navigateur ; utiliser `localhost` ou HTTPS |
| Les images ne s'uploadent pas | Vérifier les droits sur `uploads/` et `upload_max_filesize` dans php.ini |

### Logs à consulter

- Apache / PHP : `C:\xampp\apache\logs\error.log`
- MySQL : `C:\xampp\mysql\data\[hostname].err`

### Scripts de diagnostic

Les scripts de `scripts/` s'exécutent uniquement en ligne de commande (sauvegarder la base avant) :

```bash
C:\xampp\php\php.exe scripts\check_statut.php    # Afficher le statut des séances
C:\xampp\php\php.exe scripts\fix_statuts.php     # Corriger les statuts vides, puis enregistrer les absents
C:\xampp\php\php.exe scripts\fix_absences.php    # Compléter la méthode de validation des absences
```

---

_Dernière mise à jour : 9 novembre 2026_
