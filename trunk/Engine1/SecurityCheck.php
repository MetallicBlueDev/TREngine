<?php

namespace TREngine\Engine;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreInfo;
use TREngine\Engine\Core\CoreSecure;

if (!defined("TR_ENGINE_INITIALIZED")) {
    // Les fichiers de base sont instanciés
    define("TR_ENGINE_INITIALIZED", true);

    // Vérification de la version PHP (en 1er)
    if (!class_exists("CoreInfo", false)) {
        require DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'CoreInfo.php';

        // Initalisation de toutes les autres informations de base
        CoreInfo::initialize();

        // Si une version PHP OO moderne n'est pas détecté
        if (!CoreInfo::compatibleVersion()) {
            echo"<b>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</b>"
            . "<br />YOUR PHP VERSION : " . TR_ENGINE_PHP_VERSION;
            exit();
        }
    }

    // Inclusion du chargeur si besoin
    if (!class_exists("CoreLoader", false)) {
        require DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'CoreLoader.php';
    }
}

if (!defined("TR_ENGINE_AUTOLOADED")) {
    // Le chargeur est pret
    define("TR_ENGINE_AUTOLOADED", true);
    CoreLoader::affectRegister();
}

// Chargement du système de sécurité
if (!defined("TR_ENGINE_SECURE")) {
    // La sécurité a été vérifiée
    define("TR_ENGINE_SECURE", true);
    CoreSecure::checkInstance();
}
