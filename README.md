# Presence Management System

A web-based attendance management system with **facial recognition**, built for the École Nationale d'Électronique et des Télécommunications de Sfax (ENET'Com). Students check in to a live session from their webcam; the system verifies their face against a reference photo, records their attendance with a confidence score, and automatically marks absentees once the session ends.

> The user interface and source code identifiers are in French.

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.10-3776AB?logo=python&logoColor=white)
![DeepFace](https://img.shields.io/badge/DeepFace-Facenet-FF6F00)
![Chart.js](https://img.shields.io/badge/Chart.js-4.4-FF6384?logo=chartdotjs&logoColor=white)

---

## Table of Contents

- [Features](#features)
- [How It Works](#how-it-works)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Database Overview](#database-overview)
- [Getting Started](#getting-started)
- [Configuration](#configuration)
- [Usage](#usage)
- [Business Rules](#business-rules)
- [Maintenance Scripts](#maintenance-scripts)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)
- [Author](#author)

---

## Features

### Student

- Personal dashboard with today's sessions and attendance statistics
- Reference photo upload (drag & drop, JPG/PNG, max 5 MB) with face-embedding extraction
- **Webcam check-in** with real-time facial verification
- Enrolled courses and session list
- Full attendance history with monthly filters and per-course statistics

### Teacher

- Dashboard with active courses, today's sessions and attendance rate
- Course creation (name, code, level, specialty, academic year) with **automatic student enrollment**
- Session scheduling (date, start/end time, room, type: *cours*, TD, TP, *examen*)
- Monthly agenda view of scheduled sessions
- Per-session attendance sheets with manual status correction (present / absent / excused)
- Export of attendance sheets to **Excel** (`.xls`) and **PDF** (print-ready page)

### Administrator

- Analytics dashboard with seven Chart.js charts: attendance breakdown, session status, top courses, user distribution, 7-day attendance trend, validation methods, attendance rate per course
- User management: create, activate/deactivate and delete students, teachers and admins
- Global view of all courses, sessions and attendance records with filters
- Absence alerts (students above an absence threshold), most assiduous students, students without a reference photo
- Excel and PDF exports

---

## How It Works

```
 Teacher creates a course ──► students matching level + specialty + year are enrolled
            │
            ▼
 Teacher schedules a session          status: planifie
            │
            ▼  (start time reached)
 Session goes live                    status: en_cours
            │
            ├──► Student opens "Marquer présence", webcam captures a frame
            │        │
            │        ▼
            │    api/marquer_presence.php
            │        │  saves capture to uploads/temp/
            │        ▼
            │    python/face_recognition_verify.py   (DeepFace · Facenet · cosine)
            │        │  returns { match, confidence, distance }
            │        ▼
            │    match && confidence ≥ 70 %  ──► presence recorded
            │                                     (statut = present, methode = image, score)
            ▼  (end time passed)
 Session closes                       status: terminee
            │
            ▼
 Every enrolled student without a record is marked absent  (methode = automatique)
```

Status transitions and automatic absences are applied by `SeanceDAO::updateStatutsAutomatique()` and `SeanceDAO::marquerAbsentsAutomatique()`. They run on page load (throttled to once per minute per session through `config/auto_update_seances.php`) and can also be run on a schedule with `cron/update_seances_statuts.php`.

---

## Tech Stack

| Layer            | Technology                                                       |
| ---------------- | ---------------------------------------------------------------- |
| Backend          | PHP 7.4+ (PDO, sessions, `password_hash`)                        |
| Database         | MySQL 8.0 / MariaDB (`utf8mb4`)                                  |
| Face recognition | Python 3.10, [DeepFace](https://github.com/serengil/deepface) with the Facenet model and OpenCV detector |
| Frontend         | HTML5, CSS3, vanilla JavaScript, `getUserMedia` webcam API      |
| Charts / icons   | Chart.js 4.4, Font Awesome 6.4                                   |
| Dev environment  | XAMPP (Apache + MySQL) on Windows                               |

---

## Architecture

The application follows an **MVC + DAO** layered design:

| Layer           | Folder         | Responsibility                                                      |
| --------------- | -------------- | ------------------------------------------------------------------- |
| Models          | `models/`      | Domain entities (`User`, `Etudiant`, `Enseignant`, `Cours`, `Seance`, `Presence`) with validation |
| DAO             | `dao/`         | All SQL access through PDO prepared statements                     |
| Controllers     | `controllers/` | Business logic, authorization and orchestration                    |
| Views           | `views/`       | Role-specific PHP pages (`admin/`, `enseignant/`, `etudiant/`)     |
| API             | `api/`         | JSON endpoint for face-based check-in                              |
| ML scripts      | `python/`      | Face embedding extraction and verification, called from PHP via `shell_exec` |

`Etudiant` and `Enseignant` extend `User`. Access control is enforced in every view with `AuthController::requireUserType('<role>')`.

---

## Project Structure

```
presence-management-system/
├── api/
│   └── marquer_presence.php          # POST: webcam capture → face verification → attendance
├── assets/css/style.css              # Shared stylesheet
├── config/
│   ├── database.php                  # PDO connection settings
│   └── auto_update_seances.php       # Throttled status / absence updater (included by pages)
├── controllers/                      # Admin, Auth, Cours, Enseignant, Etudiant, Image, Presence, Seance
├── cron/
│   └── update_seances_statuts.php    # Same updater, for Task Scheduler / crontab
├── dao/                              # Cours, Enseignant, Etudiant, ImageReference, Inscription, Presence, Seance, User
├── models/                           # Cours, Enseignant, Etudiant, Presence, Seance, User
├── python/
│   ├── extract_face_encoding.py      # Facenet embedding of the reference photo
│   └── face_recognition_verify.py    # Compares reference photo with webcam capture
├── uploads/
│   ├── images_reference/<id>/        # Student reference photos
│   └── temp/                         # Short-lived webcam captures
├── views/
│   ├── index.php · login.php · register.php · logout.php
│   ├── admin/                        # dashboard, utilisateurs, ajouter_utilisateur, cours, seances, presences, exports
│   ├── enseignant/                   # dashboard, mes_cours, create_cours, planifier_seance, mes_seances, agenda, presences, exports
│   └── etudiant/                     # dashboard, profil, marquer_presence, mes_cours, mes_seances, historique
├── insert_admin.php                  # Creates the initial administrator account
└── *.php (root)                      # Diagnostic / repair scripts (see below)
```

---

## Database Overview

Database name: **`systeme_presence`**

| Table              | Key columns                                                                                     |
| ------------------ | ----------------------------------------------------------------------------------------------- |
| `utilisateurs`     | `id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe` (hashed), `type_utilisateur` (`etudiant` / `enseignant` / `administrateur`), `numero_telephone`, `date_inscription` |
| `etudiants`        | `id_etudiant`, `id_utilisateur`, `numero_etudiant`, `niveau`, `specialite`, `annee_scolaire`    |
| `enseignants`      | `id_enseignant`, `id_utilisateur`, `departement`, `grade`                                       |
| `cours`            | `id_cours`, `nom_cours`, `code_cours`, `id_enseignant`, `description`, `niveau`, `specialite`, `annee_scolaire`, `statut` |
| `inscriptions`     | `id_cours`, `id_etudiant`, `date_inscription`, `statut` (`inscrit`, …)                          |
| `seances`          | `id_seance`, `id_cours`, `date_seance`, `heure_debut`, `heure_fin`, `salle`, `type_seance`, `statut` (`planifie` / `en_cours` / `terminee` / `annule`) |
| `presences`        | `id_seance`, `id_etudiant`, `date_heure_marquage`, `statut` (`present` / `absent` / `justifie`), `methode_validation` (`image` / `manuel` / `automatique`), `score_reconnaissance` |
| `images_reference` | `id_etudiant`, `chemin_image`, `encodage_facial` (JSON), `date_ajout`                           |

Automatic enrollment relies on an `AFTER INSERT` trigger on `cours` (`trg_after_cours_insert`) that enrolls every student whose `niveau`, `specialite` and `annee_scolaire` match the new course. Its definition is in [`GUIDE_DEMARRAGE.md`](GUIDE_DEMARRAGE.md).

> **Note:** The repository does not yet include a SQL dump. Create the tables above (or import your own dump) before first launch.

---

## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP 7.4+ + MySQL 8 stack) with the `pdo_mysql`, `gd` and `fileinfo` extensions
- Python 3.8+ (3.10 recommended), available on the `PATH` as `python` (Windows) or `python3` (Linux/macOS)
- A browser with webcam access (served from `localhost` or HTTPS)

### 1. Clone the repository

```bash
cd C:\xampp\htdocs
git clone https://github.com/abdelhedy/presence-management-system.git
```

### 2. Set up the database

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Create a database named `systeme_presence` (`utf8mb4`) and its tables.
3. Create the enrollment trigger `trg_after_cours_insert` (see `GUIDE_DEMARRAGE.md`).

### 3. Install the Python dependencies

```bash
pip install deepface opencv-python numpy tf-keras
```

DeepFace downloads the Facenet weights on first use, so the first verification takes longer.

Check the scripts:

```bash
python python/extract_face_encoding.py path/to/photo.jpg
python python/face_recognition_verify.py path/to/reference.jpg path/to/capture.jpg
```

Both print a JSON result.

### 4. Create the upload folders

```bash
mkdir uploads\images_reference uploads\temp
```

Make sure Apache can write to them.

### 5. Create the administrator account

```bash
php insert_admin.php
```

This creates `admin@presence.com` with a default password defined in the script. **Change it right after your first login** (or edit the script before running it).

### 6. Open the application

```
http://localhost/presence-management-system/views/index.php
```

---

## Configuration

| Setting                         | Location                                       | Default                     |
| ------------------------------- | ---------------------------------------------- | --------------------------- |
| DB host / name / user / password | `config/database.php`                         | `localhost` / `systeme_presence` / `root` / *(empty)* |
| Minimum confidence to accept a face | `api/marquer_presence.php` (`$confidence < 70`) | 70 %                     |
| Face model / detector / metric  | `python/face_recognition_verify.py`            | Facenet / OpenCV / cosine   |
| Reference photo size / types    | `controllers/ImageController.php`              | 5 MB · JPEG, PNG            |
| Status auto-update interval     | `config/auto_update_seances.php`               | 60 s                        |
| PHP upload limits               | `php.ini` (`upload_max_filesize`, `post_max_size`) | 10M recommended         |

Confidence is computed as `(1 − cosine distance) × 100`, and the capture must also pass DeepFace's own `verified` threshold.

---

## Usage

1. **Sign up**: students and teachers register at `views/register.php` (students choose their specialty and level; teachers their department and grade). Administrators can also create accounts.
2. **Teacher**: create a course; matching students are enrolled automatically. Then schedule a session.
3. **Student**: go to **Mon Profil** and upload a clear, front-facing reference photo.
4. **During the session**: the student opens **Marquer Présence**, selects the live session, allows the webcam and captures their face. Attendance is recorded with the recognition score.
5. **After the session**: absentees are recorded automatically. The teacher can review the sheet, correct statuses and export it to Excel or PDF.

---

## Business Rules

- A session's end time must be after its start time (checked on the server). Past dates and times are disabled in the scheduling form (checked in the browser).
- Check-in is allowed **only** while a session is `en_cours` and the current time is between `heure_debut` and `heure_fin`.
- Check-in requires a reference photo on file.
- The photo must contain exactly one detectable face.
- Webcam captures are deleted right after verification.
- At the end of a session, every enrolled student without a record is marked `absent` with `methode_validation = automatique`.

More detail is in [`CONTRAINTES_SYSTEME.md`](CONTRAINTES_SYSTEME.md).

### Optional: scheduled updates

Run the updater independently of page traffic:

```bash
# Linux crontab (every 5 minutes)
*/5 * * * * php /path/to/presence-management-system/cron/update_seances_statuts.php
```

On Windows, create a Task Scheduler job that runs `php C:\xampp\htdocs\presence-management-system\cron\update_seances_statuts.php`.

---

## Maintenance Scripts

These root-level scripts are run from the command line (`php <script>.php`) for diagnostics and one-off fixes:

| Script                      | Purpose                                                                 |
| --------------------------- | ----------------------------------------------------------------------- |
| `insert_admin.php`          | Create the initial administrator                                        |
| `fix_statuts.php`           | Assign a correct status to sessions with an empty status, then record absentees |
| `add_statut_constraint.php` | Back-fill missing statuses and add a constraint on `seances.statut`     |
| `fix_absences.php` / `update_absences.php` | Re-run automatic absence marking                         |
| `check_statut.php`          | Print the current status of each session                                |
| `enable_events.php` / `test_events.php` | Enable and test the optional MySQL scheduled events         |

> These scripts change data directly. Back up the database before running them, and keep them off a public web server.

---

## Troubleshooting

| Problem                                         | Fix                                                                              |
| ----------------------------------------------- | -------------------------------------------------------------------------------- |
| "Module deepface non installé"                  | `pip install deepface tf-keras` with the same Python interpreter that PHP calls  |
| Verification always fails / empty output        | Run the Python script by hand to see the error; check that `python` is on Apache's `PATH` |
| "Aucun visage détecté"                          | Use a well-lit, front-facing photo with a single face                            |
| Webcam is blocked                               | Serve the app from `localhost` or HTTPS and allow camera access in the browser   |
| Upload fails                                    | Check write permissions on `uploads/` and the `php.ini` upload limits            |
| Session statuses don't change                   | Load any dashboard page, or schedule `cron/update_seances_statuts.php`          |

Useful logs (XAMPP): `apache/logs/error.log` and `mysql/data/<hostname>.err`.

---

## Roadmap

- [ ] Add a versioned SQL schema / migration file
- [ ] Move credentials into environment variables (`.env`)
- [ ] Add a `.gitignore` for `.venv/`, `uploads/` and logs
- [ ] Liveness detection (anti-spoofing) for webcam check-in
- [ ] Email notifications for absence alerts
- [ ] Pagination and advanced search on admin lists
- [ ] Linux / Docker deployment support

---

## Author

**Yasmine Abdelhedi**: [@abdelhedy](https://github.com/abdelhedy)

Academic project, ENET'Com Sfax, 2025.
