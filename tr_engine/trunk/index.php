<?php

// On est pass� dans l'index
define("TR_ENGINE_INDEX", 1);

// V�rification de la version PHP
require("engine/core/phpversion.inc.php");

// Inclusion et d�marrage du syst�me de s�curit�
require("engine/core/secure.class.php");
Core_Secure::getInstance();

// Inclusion du chargeur
require("engine/core/loader.class.php");

// Chargement de la classe principal
Core_Loader::classLoader("Core_Main");

// Pr�paration du moteur
$TR_ENGINE = new Core_Main();

// D�marrage du moteur
$TR_ENGINE->start();


?>