<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("core/secure.class.php");
    new Core_Secure();
}

/**
 * Numéro de version du moteur
 *
 * Controle de révision
 * xx -> version courante
 * xx -> fonctionnalitées ajoutées
 * xx -> bugs ou failles critiques corrigés
 * xx -> bug mineur
 *
 * @var String
 */
$TR_ENGINE_VERSION = "1.1.0.0";
