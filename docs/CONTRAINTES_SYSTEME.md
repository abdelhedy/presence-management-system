# Contraintes du système de gestion de présence

## 📋 Contraintes de planification des séances

### 1. **Interdiction de planifier dans le passé**

- ✅ **Implémenté dans** : `models/Seance.php` → `validate()`
- **Règle** : Une séance ne peut pas être planifiée si `date_seance + heure_debut < date/heure actuelle`
- **Message d'erreur** : "Impossible de planifier une séance dans le passé"
- **Impact** : L'enseignant ne peut pas créer ou modifier une séance avec une date/heure déjà passée

```php
// Vérification automatique lors de la création/modification
if ($dateTimeSeance < $now) {
    $errors[] = "Impossible de planifier une séance dans le passé";
}
```

---

## 🎯 Contraintes de marquage de présence

### 2. **Marquage uniquement pour séances EN COURS**

- ✅ **Implémenté dans** : `controllers/PresenceController.php` → `marquerPresenceReconnaissanceFaciale()`
- **Règles** :
  - ❌ Séance **planifiée** (pas encore commencée) → Refusé
  - ✅ Séance **en_cours** → Autorisé
  - ❌ Séance **terminée** → Refusé (absent automatique)
  - ❌ Séance **annulée** → Refusé

**Messages d'erreur selon le statut :**

```php
// Séance planifiée
"Cette séance n'a pas encore commencé. Veuillez attendre le début de la séance."

// Séance terminée
"Cette séance est terminée. Vous êtes marqué(e) absent(e)."

// Heure non atteinte
"Cette séance n'a pas encore commencé. Début prévu à HH:MM"

// Heure dépassée
"Cette séance est terminée. Vous êtes marqué(e) absent(e)."
```

### 3. **Vérification de l'heure en temps réel**

- ✅ **Implémenté dans** : `controllers/PresenceController.php`
- **Règles** :
  - `NOW() < heure_debut` → Refusé (trop tôt)
  - `heure_debut <= NOW() <= heure_fin` → Autorisé
  - `NOW() > heure_fin` → Refusé (trop tard, absent)

```php
$dateTimeNow = date('Y-m-d H:i:s');
$dateTimeDebut = $seance->date_seance . ' ' . $seance->heure_debut;
$dateTimeFin = $seance->date_seance . ' ' . $seance->heure_fin;
```

### 4. **Affichage uniquement des séances marquables**

- ✅ **Implémenté dans** : `dao/SeanceDAO.php` → `findTodayActiveByEtudiant()`
- **Règles** :
  - Uniquement les séances avec `statut = 'en_cours'`
  - `date_seance = TODAY`
  - `heure_debut <= NOW() <= heure_fin`

```sql
WHERE s.statut = 'en_cours'
AND CONCAT(s.date_seance, ' ', s.heure_debut) <= NOW()
AND CONCAT(s.date_seance, ' ', s.heure_fin) >= NOW()
```

---

## ⚙️ Système automatique de mise à jour

### 5. **Mise à jour automatique des statuts**

- ✅ **Implémenté dans** : `dao/SeanceDAO.php` → `updateStatutsAutomatique()`
- **Fréquence** : Toutes les 60 secondes (1 minute)
- **Déclenchement** : À chaque chargement de page (dashboard, marquer présence, etc.)

**Transitions automatiques :**

```
planifie → en_cours  (quand heure_debut atteinte)
en_cours → terminee  (quand heure_fin dépassée)
```

**Requêtes SQL automatiques :**

```sql
-- Passer en "en_cours"
UPDATE seances SET statut = 'en_cours'
WHERE statut = 'planifie'
AND CONCAT(date_seance, ' ', heure_debut) <= NOW()
AND CONCAT(date_seance, ' ', heure_fin) >= NOW();

-- Passer en "terminee"
UPDATE seances SET statut = 'terminee'
WHERE statut IN ('en_cours', 'planifie')
AND CONCAT(date_seance, ' ', heure_fin) < NOW();
```

### 6. **Marquage automatique des absents**

- ✅ **Implémenté dans** : `dao/SeanceDAO.php` → `marquerAbsentsAutomatique()`
- **Règle** : À la fin d'une séance, tous les étudiants inscrits qui n'ont pas marqué leur présence sont automatiquement marqués **absents**
- **Méthode de validation** : `'automatique'`

```sql
INSERT INTO presences (id_seance, id_etudiant, statut, methode_validation)
SELECT s.id_seance, i.id_etudiant, 'absent', 'automatique'
FROM seances s
JOIN inscriptions i ON s.id_cours = i.id_cours
WHERE s.statut = 'terminee'
AND NOT EXISTS (SELECT 1 FROM presences p WHERE ...)
```

---

## 📂 Fichiers modifiés

| Fichier                               | Modification                   |
| ------------------------------------- | ------------------------------ |
| `models/Seance.php`                   | Validation anti-passé          |
| `controllers/PresenceController.php`  | Vérifications statut + heure   |
| `dao/SeanceDAO.php`                   | Filtrage séances + auto-update |
| `config/auto_update_seances.php`      | Script auto-update (nouveau)   |
| `cron/update_seances_statuts.php`     | Script cron manuel (nouveau)   |
| `views/etudiant/dashboard.php`        | Inclusion auto-update          |
| `views/etudiant/marquer_presence.php` | Inclusion auto-update          |

---

## 🧪 Tests à effectuer

### Test 1 : Planification dans le passé

1. Essayer de créer une séance avec date/heure passée
2. ✅ **Résultat attendu** : Erreur "Impossible de planifier une séance dans le passé"

### Test 2 : Marquage avant l'heure

1. Créer une séance pour dans 2 heures
2. Essayer de marquer présence maintenant
3. ✅ **Résultat attendu** : "Cette séance n'a pas encore commencé"

### Test 3 : Marquage pendant la séance

1. Créer une séance en cours (NOW entre heure_debut et heure_fin)
2. Marquer présence
3. ✅ **Résultat attendu** : Succès ✓

### Test 4 : Marquage après la séance

1. Créer une séance terminée (heure_fin < NOW)
2. Essayer de marquer présence
3. ✅ **Résultat attendu** : "Cette séance est terminée. Vous êtes marqué(e) absent(e)."

### Test 5 : Absents automatiques

1. Créer une séance et attendre sa fin
2. Ne pas marquer présence
3. ✅ **Résultat attendu** : Statut "absent" ajouté automatiquement

---

## 🔧 Configuration optionnelle

### Exécution périodique (optionnel)

Si vous voulez une mise à jour indépendante des chargements de pages :

**Windows (Planificateur de tâches)** :

```batch
php "C:\xampp\htdocs\presence-management-system\cron\update_seances_statuts.php"
```

- Fréquence recommandée : Toutes les 5 minutes

**Linux (crontab)** :

```bash
*/5 * * * * php /path/to/cron/update_seances_statuts.php
```

---

## ✅ Résumé

Toutes les contraintes demandées sont implémentées :

- ✅ Pas de planification dans le passé
- ✅ Marquage uniquement pour séances EN COURS
- ✅ Pas de marquage pour séances planifiées (pas encore commencées)
- ✅ Pas de marquage pour séances terminées
- ✅ Absents automatiques à la fin des séances
- ✅ Mise à jour automatique des statuts
