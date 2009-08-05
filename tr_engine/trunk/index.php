<?php

// On est passé dans l'index
define("TR_ENGINE_INDEX", 1);

// Vérification de la version PHP
// Classe compatible PHP 4
require("engine/core/info.class.php");

// Inclusion du chargeur
require("engine/core/loader.class.php");

// Chargement du Marker
Core_Loader::classLoader("Exec_Marker");
Exec_Marker::startTimer("all");
Exec_Marker::startTimer("main");

// Chargement du système de sécurité
Core_Loader::classLoader("Core_Secure");
Core_Secure::makeInstance();

// Chargement de la classe principal
Core_Loader::classLoader("Core_Main");

// Préparation du moteur
$TR_ENGINE = new Core_Main();

// Démarrage du moteur
$TR_ENGINE->start();

Exec_Marker::stopTimer("all");

// Affichage des exceptions
Core_Exception::displayException();

?>