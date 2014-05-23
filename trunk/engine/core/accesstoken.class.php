<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Représente un jeton d'accès à une zone.
 * 
 * @author Sébastien Villemain
 */
interface Core_AccessToken {

    /**
     * Retourne la zone d'échange actuelle.
     *
     * @return string
     */
    public function &getZone();

    /**
     * Retourne l'identifiant de la zone d'échange.
     *
     * @return string
     */
    public function &getId();

    /**
     * Retourne le rang de la zone d'échange.
     *
     * @return int
     */
    public function &getRank();
}
