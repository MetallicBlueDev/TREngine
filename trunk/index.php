<?php
// On est passé dans l'index
define("TR_ENGINE_INDEX", true);

// Vérification de la version PHP
require("engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "info.class.php");

// Inclusion du chargeur
require("engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "loader.class.php");

// Chargement du système de sécurité
Core_Secure::checkInstance();

if (Core_Secure::debuggingMode()) {
    Exec_Marker::startTimer("all");
}

Exec_Marker::startTimer("main");

// Préparation du moteur
Core_Main::checkInstance();

// Recherche de nouveaux composants
if (Core_Main::getInstance()->newComponentDetected()) {
    // Installation des nouveaux composants
    Core_Main::getInstance()->install();
} else {
    // Démarrage classique
    Core_Main::getInstance()->start();
}

if (Core_Secure::debuggingMode()) {
    Exec_Marker::stopTimer("all");
    Core_Logger::displayDebugInformations();
}