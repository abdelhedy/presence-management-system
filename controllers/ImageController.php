<?php
require_once __DIR__ . '/../dao/ImageReferenceDAO.php';
require_once __DIR__ . '/../dao/EtudiantDAO.php';
require_once __DIR__ . '/../config/env.php';

/**
 * ImageController - Gestion des photos de profil et reconnaissance faciale
 */
class ImageController
{
    private $imageDAO;
    private $etudiantDAO;
    private $uploadDir;
    private $pythonScriptPath;

    public function __construct()
    {
        $this->imageDAO = new ImageReferenceDAO();
        $this->etudiantDAO = new EtudiantDAO();

        // Dossier d'upload
        $this->uploadDir = __DIR__ . '/../uploads/images_reference/';

        // Script Python pour extraction d'encodage
        $this->pythonScriptPath = __DIR__ . '/../python/extract_face_encoding.py';
    }

    /**
     * Upload d'une photo de profil
     */
    public function uploadPhoto($idEtudiant, $file)
    {
        // Vérifier que l'étudiant existe
        $etudiant = $this->etudiantDAO->findById($idEtudiant);
        if (!$etudiant) {
            return [
                'success' => false,
                'error' => 'Étudiant non trouvé'
            ];
        }

        // Validation du fichier
        $validation = $this->validateImageFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }

        // Créer le dossier de l'étudiant si nécessaire
        $etudiantDir = $this->uploadDir . $idEtudiant . '/';
        if (!is_dir($etudiantDir)) {
            mkdir($etudiantDir, 0755, true);
        }

        // Générer un nom de fichier unique
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'profile_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $etudiantDir . $filename;

        // Déplacer le fichier uploadé
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'error' => 'Erreur lors du téléchargement du fichier'
            ];
        }

        // Chemin relatif pour la BD
        $relativePath = 'uploads/images_reference/' . $idEtudiant . '/' . $filename;

        // Extraire l'encodage facial avec Python (optionnel pour le moment)
        $encodage = null;
        if (file_exists($this->pythonScriptPath)) {
            $encodageResult = $this->extractFaceEncoding($filepath);
            if ($encodageResult['success']) {
                $encodage = json_encode($encodageResult['encoding']);
            }
            // Note: On continue même si l'extraction échoue pour permettre l'upload
        }

        // Enregistrer en base de données
        $imageId = $this->imageDAO->create(
            $idEtudiant,
            $relativePath,
            $encodage
        );

        if ($imageId) {
            return [
                'success' => true,
                'message' => 'Photo de profil enregistrée avec succès',
                'image_id' => $imageId,
                'image_path' => $relativePath
            ];
        }

        // Supprimer le fichier si l'insertion en BD a échoué
        unlink($filepath);
        return [
            'success' => false,
            'error' => 'Erreur lors de l\'enregistrement en base de données'
        ];
    }

    /**
     * Validation du fichier image
     */
    private function validateImageFile($file)
    {
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => 'Erreur lors du téléchargement : code ' . $file['error']
            ];
        }

        // Vérifier la taille (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return [
                'valid' => false,
                'error' => 'Le fichier est trop volumineux. Maximum 5MB.'
            ];
        }

        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Type de fichier non autorisé. Utilisez JPG, JPEG ou PNG.'
            ];
        }

        // Vérifier que c'est bien une image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'error' => 'Le fichier n\'est pas une image valide.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Interpréteur Python à utiliser :
     * PYTHON_BIN (.env) > .venv du projet > python / python3 du PATH
     */
    private function getPythonBin()
    {
        $root = dirname(__DIR__);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $bin = env('PYTHON_BIN', '');
        if ($bin !== '') {
            // Chemin relatif : par rapport à la racine du projet
            $isAbsolute = preg_match('#^([a-zA-Z]:)?[\\\\/]#', $bin);
            $isPath = strpbrk($bin, '/\\') !== false;
            return ($isPath && !$isAbsolute) ? $root . '/' . $bin : $bin;
        }

        $venvPython = $isWindows ? $root . '/.venv/Scripts/python.exe' : $root . '/.venv/bin/python';
        if (file_exists($venvPython)) {
            return $venvPython;
        }

        return $isWindows ? 'python' : 'python3';
    }

    /**
     * Exécuter un script Python et retourner sa sortie
     * (stderr est ignoré pour éviter les warnings TensorFlow)
     */
    private function runPython($script, array $args)
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $parts = array_map('escapeshellarg', array_merge([$this->getPythonBin(), $script], $args));
        $command = implode(' ', $parts) . ' 2>' . ($isWindows ? 'NUL' : '/dev/null');

        // Sortie en UTF-8 : sinon les logs DeepFace (emojis) échouent sous Windows
        putenv('PYTHONUTF8=1');

        return shell_exec($command);
    }

    /**
     * Extraire l'encodage facial avec Python
     */
    private function extractFaceEncoding($imagePath)
    {
        // Vérifier que le script Python existe
        if (!file_exists($this->pythonScriptPath)) {
            return [
                'success' => false,
                'error' => 'Script Python non trouvé : ' . $this->pythonScriptPath
            ];
        }

        $output = $this->runPython($this->pythonScriptPath, [$imagePath]);

        // Parser le résultat JSON
        $result = json_decode($output, true);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'extraction de l\'encodage facial. Output: ' . $output
            ];
        }

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Erreur inconnue lors de l\'extraction'
            ];
        }

        return [
            'success' => true,
            'encoding' => $result['encoding']
        ];
    }

    /**
     * Vérifier une photo captée contre l'image de référence
     * Pour la reconnaissance faciale en temps réel
     */
    public function verifyFace($idEtudiant, $capturedImagePath)
    {
        // Récupérer l'image de référence
        $referenceImage = $this->imageDAO->findLatestByEtudiant($idEtudiant);

        if (!$referenceImage) {
            return [
                'success' => false,
                'error' => 'Aucune image de référence trouvée pour cet étudiant'
            ];
        }

        // Script Python pour comparaison
        $pythonScript = __DIR__ . '/../python/face_recognition_verify.py';

        if (!file_exists($pythonScript)) {
            return [
                'success' => false,
                'error' => 'Script de vérification non trouvé'
            ];
        }

        // Chemin complet de l'image de référence
        $referencePath = __DIR__ . '/../' . $referenceImage['chemin_image'];

        $output = $this->runPython($pythonScript, [$referencePath, $capturedImagePath]);

        // Parser le résultat
        $result = json_decode($output, true);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification faciale. Veuillez réessayer.'
            ];
        }

        return [
            'success' => true,
            'match' => $result['match'] ?? false,
            'confidence' => $result['confidence'] ?? 0,
            'distance' => $result['distance'] ?? 1
        ];
    }

    /**
     * Récupérer l'image de profil d'un étudiant
     */
    public function getImageEtudiant($idEtudiant)
    {
        $image = $this->imageDAO->findLatestByEtudiant($idEtudiant);

        if ($image) {
            return [
                'success' => true,
                'image' => $image
            ];
        }

        return [
            'success' => false,
            'error' => 'Aucune image trouvée'
        ];
    }

    /**
     * Supprimer une image
     */
    public function deleteImage($idImage)
    {
        if ($this->imageDAO->delete($idImage)) {
            return [
                'success' => true,
                'message' => 'Image supprimée avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la suppression'
        ];
    }

    /**
     * Nettoyer les anciennes images d'un étudiant
     */
    public function cleanOldImages($idEtudiant)
    {
        if ($this->imageDAO->cleanOldImages($idEtudiant)) {
            return [
                'success' => true,
                'message' => 'Anciennes images nettoyées'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors du nettoyage'
        ];
    }
}
