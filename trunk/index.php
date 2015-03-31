<?php

use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Exec\ExecTimeMarker;

// Marque le passage dans l'index
define("TR_ENGINE_INDEX", true);

// Chargement et exécution de la sécurité
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