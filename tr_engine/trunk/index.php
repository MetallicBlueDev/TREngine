<?php

// On est pass� dans l'index
define("TR_ENGINE_INDEX", 1);

// V�rification de la version PHP
require("engine/core/info.class.php");

// Inclusion du chargeur
require("engine/core/loader.class.php");

// Chargement du Marker
Core_Loader::classLoader("Exec_Marker");
Exec_Marker::startTimer("all");
Exec_Marker::startTimer("main");

// Chargement du syst�me de s�curit�
Core_Loader::classLoader("Core_Secure");
Core_Secure::getInstance();

// Chargement de la classe principal
Core_Loader::classLoader("Core_Main");

// Pr�paration du moteur
$TR_ENGINE = new Core_Main();

// D�marrage du moteur
$TR_ENGINE->start();

Exec_Marker::stopTimer("all");

// Affichage des exceptions
Core_Exception::displayException();

?>