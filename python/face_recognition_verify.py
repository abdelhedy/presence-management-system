#!/usr/bin/env python3
"""
Script de vérification de reconnaissance faciale
Utilisé lors du marquage de présence pour comparer le visage capturé
avec la photo de référence de l'étudiant
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


def verify_faces(reference_image_path, captured_image_path, tolerance=0.6):
    """
    Compare deux images pour vérifier si c'est la même personne
    
    Args:
        reference_image_path (str): Chemin vers l'image de référence
        captured_image_path (str): Chemin vers l'image capturée
        tolerance (float): Seuil de tolérance (plus bas = plus strict)
        
    Returns:
        dict: Résultat avec match, confidence, distance
    """
    try:
        # Charger les images
        reference_image = face_recognition.load_image_file(reference_image_path)
        captured_image = face_recognition.load_image_file(captured_image_path)
        
        # Extraire les encodages de l'image de référence
        ref_face_locations = face_recognition.face_locations(reference_image)
        ref_face_encodings = face_recognition.face_encodings(reference_image, ref_face_locations)
        
        if len(ref_face_encodings) == 0:
            return {
                "success": False,
                "error": "Aucun visage détecté dans l'image de référence"
            }
        
        # Extraire les encodages de l'image capturée
        cap_face_locations = face_recognition.face_locations(captured_image)
        cap_face_encodings = face_recognition.face_encodings(captured_image, cap_face_locations)
        
        if len(cap_face_encodings) == 0:
            return {
                "success": False,
                "error": "Aucun visage détecté dans la capture. Réessayez en vous plaçant face à la caméra."
            }
        
        if len(cap_face_encodings) > 1:
            return {
                "success": False,
                "error": "Plusieurs visages détectés. Assurez-vous d'être seul(e) dans le cadre."
            }
        
        # Comparer les visages
        reference_encoding = ref_face_encodings[0]
        captured_encoding = cap_face_encodings[0]
        
        # Calculer la distance entre les encodages
        face_distance = face_recognition.face_distance([reference_encoding], captured_encoding)[0]
        
        # Déterminer si c'est un match
        is_match = face_distance <= tolerance
        
        # Calculer le score de confiance (0-100%)
        # Plus la distance est faible, plus la confiance est élevée
        confidence = max(0, min(100, (1 - face_distance) * 100))
        
        return {
            "success": True,
            "match": bool(is_match),
            "confidence": float(confidence),
            "distance": float(face_distance),
            "tolerance_used": tolerance
        }
    
    except FileNotFoundError as e:
        return {
            "success": False,
            "error": f"Fichier introuvable: {str(e)}"
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
            "error": "Usage: python face_recognition_verify.py <reference_image> <captured_image> [tolerance]"
        }))
        sys.exit(1)
    
    reference_image = sys.argv[1]
    captured_image = sys.argv[2]
    tolerance = float(sys.argv[3]) if len(sys.argv) > 3 else 0.6
    
    # Effectuer la vérification
    result = verify_faces(reference_image, captured_image, tolerance)
    
    # Retourner le résultat en JSON
    print(json.dumps(result))
