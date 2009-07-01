<?php

// On est passé dans l'index
define("TR_ENGINE_INDEX", 1);

// Vérification de la version PHP
require("engine/core/phpversion.inc.php");

// Inclusion et démarrage du système de sécurité
require("engine/core/secure.class.php");
Core_Secure::getInstance();

// Inclusion du chargeur
require("engine/core/loader.class.php");

// Chargement de la classe principal
Core_Loader::classLoader("Core_Main");

// Préparation du moteur
$TR_ENGINE = new Core_Main();

// Démarrage du moteur
$TR_ENGINE->start();


?>