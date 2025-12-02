#!/usr/bin/env python3
"""
Script de vérification de reconnaissance faciale avec DeepFace
Utilisé lors du marquage de présence pour comparer le visage capturé
avec la photo de référence de l'étudiant
"""

import sys
import json
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


def verify_faces(reference_image_path, captured_image_path, threshold=0.4):
    """
    Compare deux images pour vérifier si c'est la même personne avec DeepFace
    
    Args:
        reference_image_path (str): Chemin vers l'image de référence
        captured_image_path (str): Chemin vers l'image capturée
        threshold (float): Seuil de distance pour Facenet (par défaut 0.4)
        
    Returns:
        dict: Résultat avec match, confidence, distance
    """
    try:
        # Vérifier les images avec DeepFace
        result = DeepFace.verify(
            img1_path=reference_image_path,
            img2_path=captured_image_path,
            model_name='Facenet',
            detector_backend='opencv',
            enforce_detection=True,
            align=True,
            distance_metric='cosine'
        )
        
        # Résultat de DeepFace
        is_match = result["verified"]
        distance = result["distance"]
        threshold_used = result["threshold"]
        
        # Calculer le score de confiance (0-100%)
        # Pour Facenet avec cosine, plus la distance est faible, plus la confiance est élevée
        # Distance typique: 0-1, où 0 = parfaitement identique
        confidence = max(0, min(100, (1 - distance) * 100))
        
        return {
            "success": True,
            "match": bool(is_match),
            "confidence": float(confidence),
            "distance": float(distance),
            "threshold_used": float(threshold_used),
            "model": "Facenet",
            "detector": "opencv"
        }
    
    except FileNotFoundError as e:
        return {
            "success": False,
            "error": f"Fichier introuvable: {str(e)}"
        }
    
    except ValueError as e:
        # DeepFace lève ValueError si aucun visage n'est détecté
        error_msg = str(e)
        if "could not find" in error_msg.lower() or "no face" in error_msg.lower():
            return {
                "success": False,
                "error": "Aucun visage détecté dans l'une des images. Réessayez en vous plaçant face à la caméra."
            }
        return {
            "success": False,
            "error": f"Erreur de détection: {error_msg}"
        }
    
    except Exception as e:
        return {
            "success": False,
            "error": f"Erreur lors de la vérification: {str(e)}"
        }


if __name__ == "__main__":
    # Vérifier les arguments
    if len(sys.argv) < 3:
        print(json.dumps({
            "success": False,
            "error": "Usage: python face_recognition_verify.py <reference_image> <captured_image> [threshold]"
        }))
        sys.exit(1)
    
    reference_image = sys.argv[1]
    captured_image = sys.argv[2]
    threshold = float(sys.argv[3]) if len(sys.argv) > 3 else 0.4
    
    # Effectuer la vérification
    result = verify_faces(reference_image, captured_image, threshold)
    
    # Retourner le résultat en JSON
    print(json.dumps(result))
