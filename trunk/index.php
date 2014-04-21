<?php
// On est passé dans l'index
define("TR_ENGINE_INDEX", true);

// Vérification de la version PHP
// Classe compatible PHP 4
require("engine/core/info.class.php");

// Inclusion du chargeur
require("engine/core/loader.class.php");
Core_Loader::affectRegister();

// Chargement du système de sécurité
Core_Secure::checkInstance(true);

require("engine/fail/engine.class.php");
$test = new Fail_Engine("", Fail_Engine::FROM_SQL);
$test->getFailSourceName();

// Chargement du Marker
Core_Secure::getInstance()->throwException("TEST", new Exception("ESSAI"));

if (Core_Secure::isDebuggingMode()) {
    Exec_Marker::startTimer("all");
}

Exec_Marker::startTimer("main");

// Préparation du moteur
$TR_ENGINE = new Core_Main();

// Recherche de nouveau composant
if ($TR_ENGINE->newComponentDetected()) {
    // Installtion des nouveaux composants
    $TR_ENGINE->install();
} else {
    $TR_ENGINE->start();
}

if (Core_Secure::isDebuggingMode()) {
    Exec_Marker::stopTimer("all");
    Core_Logger::displayDebugInformations();
}