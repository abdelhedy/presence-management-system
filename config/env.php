<?php
/**
 * Chargement des variables d'environnement depuis le fichier .env
 */
function loadEnv($path) {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Retirer les guillemets éventuels
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }

        // Ne pas écraser une variable déjà définie par le système
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Lire une variable d'environnement avec valeur par défaut
 */
function env($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

loadEnv(__DIR__ . '/../.env');
