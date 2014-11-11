<?php
if (!defined("TR_ENGINE_INITIALIZED")) {
    // Les fichiers de base sont instanciés
    define("TR_ENGINE_INITIALIZED", true);

    // Vérification de la version PHP (en 1er)
    if (!class_exists("Core_Info", false)) {
        require(DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "info.class.php");
    }

    // Inclusion du chargeur si besoin
    if (!class_exists("Core_Loader", false)) {
        require(DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "loader.class.php");
    }
}

if (!defined("TR_ENGINE_AUTOLOADED")) {
    // Le chargeur est pret
    define("TR_ENGINE_AUTOLOADED", true);
    Core_Loader::affectRegister();
}

// Chargement du système de sécurité
if (!defined("TR_ENGINE_SECURE")) {
    // La sécurité a été vérifiée
    define("TR_ENGINE_SECURE", true);
    Core_Secure::checkInstance();
}
