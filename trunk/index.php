<?php
// On est passé dans l'index
define("TR_ENGINE_INDEX", true);

// Vérification de la version PHP
// Classe compatible PHP 4
require("engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "info.class.php");

// Inclusion du chargeur
require("engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "loader.class.php");

// Chargement du système de sécurité
Core_Secure::checkInstance(true);

if (Core_Secure::isDebuggingMode()) {
    Exec_Marker::startTimer("all");
}

Exec_Marker::startTimer("main");

// Préparation du moteur
Core_Main::checkInstance();

// Recherche de nouveau composant
if (Core_Main::getInstance()->newComponentDetected()) {
    // Installtion des nouveaux composants
    Core_Main::getInstance()->install();
} else {
    Core_Main::getInstance()->start();
}

if (Core_Secure::isDebuggingMode()) {
    Exec_Marker::stopTimer("all");
    Core_Logger::displayDebugInformations();
}