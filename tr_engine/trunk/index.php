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

// D�marrage du moteur
Core_Loader::classLoader("Core_Main");
$coreMain = new Core_Main();
$coreMain->start();


?>