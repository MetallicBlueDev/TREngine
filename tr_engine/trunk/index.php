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
echo "<br />Timer Core : " . Exec_Marker::getTime("core");
echo "<br />Timer LAUNCHER : " . Exec_Marker::getTime("launcher");
echo "<br />Timer CORE+LAUNCHER : " . (Exec_Marker::getTime("core")+Exec_Marker::getTime("launcher"));
echo "<br />Timer ALL : " . Exec_Marker::getTime("all");

?>