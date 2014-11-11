<?php
// Définition de l'index
define("TR_ENGINE_INDEX", true);

// Import de base
require 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

if (Core_Secure::debuggingMode()) {
    Exec_TimeMarker::startMeasurement("all");
}

Exec_TimeMarker::startMeasurement("main");

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
    Exec_TimeMarker::stopMeasurement("all");
    Core_Logger::displayDebugInformations();
}