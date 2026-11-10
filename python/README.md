# Configuration Python pour la Reconnaissance Faciale

Les scripts utilisent [DeepFace](https://github.com/serengil/deepface) avec le modèle **Facenet**, le détecteur **OpenCV** et la distance **cosinus**.

## Installation des dépendances

Depuis la racine du projet, dans un environnement virtuel :

```bash
python -m venv .venv
.venv/Scripts/python.exe -m pip install -r python/requirements.txt   # Linux/macOS : .venv/bin/python
```

PHP utilise automatiquement le `.venv` du projet. Pour un autre interpréteur, définir `PYTHON_BIN` dans `.env`.

Au premier lancement, DeepFace télécharge les poids du modèle Facenet : la première vérification est donc plus lente.

## Test des scripts

### 1. Extraction de l'encodage

```bash
python python/extract_face_encoding.py chemin/vers/photo.jpg
```

Affiche un JSON contenant `success` et l'encodage facial.

### 2. Vérification

```bash
python python/face_recognition_verify.py chemin/vers/reference.jpg chemin/vers/capture.jpg
```

Résultat attendu :

```json
{
  "success": true,
  "match": true,
  "confidence": 87.4,
  "distance": 0.126,
  "threshold_used": 0.4,
  "model": "Facenet",
  "detector": "opencv"
}
```

## Paramètres ajustables

- **Modèle / détecteur / métrique** : `face_recognition_verify.py` (appel à `DeepFace.verify`)
- **Seuil de confiance** : `api/marquer_presence.php` (défaut : 70 %)
  ```php
  if (!$match || $confidence < 70) {
      // Rejet
  }
  ```

La confiance est calculée comme `(1 − distance cosinus) × 100`. La capture doit aussi passer le seuil `verified` de DeepFace.

## Dépannage

| Problème | Solution |
| --- | --- |
| `Module deepface non installé` | Installer les dépendances dans `.venv` (ou dans l'interpréteur défini par `PYTHON_BIN`) |
| Erreur liée à `tf_keras` / TensorFlow | `pip install tf-keras` |
| `Aucun visage détecté` | Photo bien éclairée, de face, avec un seul visage |
| Permission refusée (Linux) | `chmod +x python/*.py` |

## Sécurité

- Les captures webcam temporaires sont supprimées après la vérification
- La comparaison est faite localement (aucun envoi vers un service tiers)
