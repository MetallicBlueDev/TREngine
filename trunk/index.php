<?php
// Définition de l'index
define("TR_ENGINE_INDEX", true);

// Import de base
require 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

if (CoreSecure::debuggingMode()) {
    ExecTimeMarker::startMeasurement("all");
}

ExecTimeMarker::startMeasurement("main");

// Préparation du moteur
CoreMain::checkInstance();

// Recherche de nouveaux composants
if (CoreMain::getInstance()->newComponentDetected()) {
    // Installation des nouveaux composants
    CoreMain::getInstance()->install();
} else {
    // Démarrage classique
    CoreMain::getInstance()->start();
}

if (CoreSecure::debuggingMode()) {
    ExecTimeMarker::stopMeasurement("all");
    CoreLogger::displayDebugInformations();
}