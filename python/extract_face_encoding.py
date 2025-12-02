#!/usr/bin/env python3
"""
Script d'extraction d'encodage facial avec DeepFace
Utilisé lors de l'upload de la photo de profil d'un étudiant
"""

import sys
import json
import numpy as np
import os

# Supprimer les logs TensorFlow
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'  # FATAL
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'

import warnings
warnings.filterwarnings('ignore')

try:
    from deepface import DeepFace
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Module deepface non installé. Exécutez: pip install deepface"
    }))
    sys.exit(1)


def extract_face_encoding(image_path):
    """
    Extrait l'encodage facial d'une image avec DeepFace
    
    Args:
        image_path (str): Chemin vers l'image
        
    Returns:
        dict: Résultat avec success, encoding ou error
    """
    try:
        # Extraire les embeddings avec DeepFace (modèle Facenet par défaut)
        result = DeepFace.represent(
            img_path=image_path,
            model_name='Facenet',  # Plus rapide et précis
            enforce_detection=True,
            detector_backend='opencv',
            align=True
        )
        
        # Vérifications
        if not result or len(result) == 0:
            return {
                "success": False,
                "error": "Aucun visage détecté dans l'image. Assurez-vous que votre visage est bien visible."
            }
        
        if len(result) > 1:
            return {
                "success": False,
                "error": "Plusieurs visages détectés. Veuillez prendre une photo où vous êtes seul(e)."
            }
        
        # Récupérer l'embedding (encodage facial)
        embedding = result[0]["embedding"]
        face_confidence = result[0].get("face_confidence", 1.0)
        facial_area = result[0].get("facial_area", {})
        
        return {
            "success": True,
            "encoding": embedding,  # Liste de 128 valeurs
            "face_confidence": face_confidence,
            "face_location": {
                "x": facial_area.get("x", 0),
                "y": facial_area.get("y", 0),
                "w": facial_area.get("w", 0),
                "h": facial_area.get("h", 0)
            }
        }
    
    except FileNotFoundError:
        return {
            "success": False,
            "error": f"Fichier introuvable: {image_path}"
        }
    
    except ValueError as e:
        # DeepFace lève ValueError si aucun visage n'est détecté
        return {
            "success": False,
            "error": "Aucun visage détecté dans l'image. Assurez-vous que votre visage est bien visible."
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
