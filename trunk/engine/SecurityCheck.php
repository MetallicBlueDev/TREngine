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
        /**
         * Version php sous forme x.x.x.x (exemple : 5.2.9.2).
         */
        define("TR_ENGINE_PHP_VERSION", preg_replace("/[^0-9.]/", "", (preg_replace("/(_|-|[+])/", ".", phpversion()))));

        // Si une version PHP OO moderne n'est pas détecté
        if (TR_ENGINE_PHP_VERSION < "5.0.0") {
            echo"<b>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</b>"
            . "<br /><br />MINIMAL PHP VERSION : 5.0.0"
            . "<br />YOUR PHP VERSION : " . TR_ENGINE_PHP_VERSION;
            exit();
        }

        // Initalisation de toutes les autres informations de base
        require DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'CoreInfo.php';
        CoreInfo::initialize();
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
