#!/usr/bin/env python3
"""
Script d'extraction d'encodage facial
Utilisé lors de l'upload de la photo de profil d'un étudiant
"""

import sys
import json
import numpy as np

try:
    import face_recognition
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Module face_recognition non installé. Exécutez: pip install face_recognition"
    }))
    sys.exit(1)


def extract_face_encoding(image_path):
    """
    Extrait l'encodage facial d'une image
    
    Args:
        image_path (str): Chemin vers l'image
        
    Returns:
        dict: Résultat avec success, encoding ou error
    """
    try:
        # Charger l'image
        image = face_recognition.load_image_file(image_path)
        
        # Détecter les visages et extraire les encodages
        face_locations = face_recognition.face_locations(image)
        face_encodings = face_recognition.face_encodings(image, face_locations)
        
        # Vérifications
        if len(face_encodings) == 0:
            return {
                "success": False,
                "error": "Aucun visage détecté dans l'image. Assurez-vous que votre visage est bien visible."
            }
        
        if len(face_encodings) > 1:
            return {
                "success": False,
                "error": "Plusieurs visages détectés. Veuillez prendre une photo où vous êtes seul(e)."
            }
        
        # Convertir l'encodage numpy en liste pour JSON
        encoding = face_encodings[0].tolist()
        
        return {
            "success": True,
            "encoding": encoding,
            "face_location": face_locations[0]  # (top, right, bottom, left)
        }
    
    except FileNotFoundError:
        return {
            "success": False,
            "error": f"Fichier introuvable: {image_path}"
        }
    
    except Exception as e:
        return {
            "success": False,
            "error": f"Erreur lors du traitement de l'image: {str(e)}"
        }


if __name__ == "__main__":
    # Vérifier les arguments
    if len(sys.argv) < 2:
        print(json.dumps({
            "success": False,
            "error": "Usage: python extract_face_encoding.py <image_path>"
        }))
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    # Extraire l'encodage
    result = extract_face_encoding(image_path)
    
    # Retourner le résultat en JSON
    print(json.dumps(result))
