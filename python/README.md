# Configuration Python pour la Reconnaissance Faciale

## Installation des dépendances

### Windows

```bash
# Installer CMake (requis pour dlib)
pip install cmake

# Installer les bibliothèques
pip install face_recognition
pip install opencv-python
pip install numpy
```

### Linux/Mac

```bash
# Installer les dépendances système
sudo apt-get update
sudo apt-get install -y python3-pip cmake

# Installer les bibliothèques Python
pip3 install face_recognition
pip3 install opencv-python
pip3 install numpy
```

## Test des scripts

### 1. Tester l'extraction d'encodage

```bash
python python/extract_face_encoding.py uploads/test_photo.jpg
```

Résultat attendu :

```json
{
  "success": true,
  "encoding": [0.123, -0.456, ...],
  "face_location": [top, right, bottom, left]
}
```

### 2. Tester la reconnaissance

```bash
python python/face_recognition_verify.py uploads/reference.jpg uploads/capture.jpg
```

Résultat attendu :

```json
{
  "success": true,
  "match": true,
  "confidence": 95.3,
  "distance": 0.047,
  "tolerance_used": 0.6
}
```

## Paramètres ajustables

### Dans `face_recognition_verify.py`

- **tolerance** : Seuil de correspondance (défaut: 0.6)
  - Plus bas (0.4) = plus strict
  - Plus haut (0.7) = plus permissif

### Dans `api/marquer_presence.php`

- **Seuil de confiance** : Minimum requis pour valider (défaut: 70%)
  ```php
  if (!$match || $confidence < 70) {
      // Rejet
  }
  ```

## Dépannage

### Erreur "No module named 'face_recognition'"

```bash
pip install --upgrade face_recognition
```

### Erreur "dlib not found"

```bash
pip install dlib
# ou sur Windows
pip install dlib-binary
```

### Permission denied sur les scripts

```bash
chmod +x python/*.py
```

## Performance

- Extraction d'encodage : ~1-2 secondes
- Comparaison de visages : ~0.5 seconde
- Temps total de marquage : ~2-3 secondes

## Sécurité

- Les images temporaires sont automatiquement supprimées
- Les encodages sont stockés en base de données (non les images complètes)
- Comparaison locale (pas d'envoi vers des services tiers)
