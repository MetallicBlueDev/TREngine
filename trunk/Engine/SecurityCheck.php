<?php

namespace TREngine\Engine;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreInfo;
use TREngine\Engine\Core\CoreSecure;

/**
 * Attention, ce fichier va être inclus dans tous les fichiers PHP.
 * Le code doit être impérativement léger et rapide à exécuter.
 *
 * A utiliser avec l'instruction suivante :
 * require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';
 */
// Initialisation principal
if (!defined("TR_ENGINE_INITIALIZED")) {
    define("TR_ENGINE_INITIALIZED", true);

    // Vérification du serveur et de la version PHP
    if (!class_exists("CoreInfo", false)) {
        require DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'CoreInfo.php';
        CoreInfo::initialize();

        // Si une version PHP OO moderne n'est pas détectée, c'est la fin
        if (!CoreInfo::compatibleVersion()) {
            echo"<h1>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</h1>"
            . "<br />Please, seriously consider updating your system."
            . "<br />Your PHP version: " . TR_ENGINE_PHP_VERSION;
            exit();
        }
    }

    // Vérification de l'inclusion du chargeur
    if (!class_exists("CoreLoader", false)) {
        require DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'CoreLoader.php';
    }
}

// Initialisation du chargeur de classe
if (!defined("TR_ENGINE_AUTOLOADED")) {
    define("TR_ENGINE_AUTOLOADED", true);
    CoreLoader::affectRegister();
}

// Initialisation du système de sécurité
if (!defined("TR_ENGINE_SECURE")) {
    define("TR_ENGINE_SECURE", true);
    CoreSecure::checkInstance();
}
