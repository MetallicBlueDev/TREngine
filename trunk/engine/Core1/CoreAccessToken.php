<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Représente un jeton d'accès à une zone.
 *
 * @author Sébastien Villemain
 */
interface CoreAccessToken {

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
     * Retourne le nom spécifique de la zone d'échange.
     *
     * @return string
     */
    public function &getName();

    /**
     * Retourne le rang de la zone d'échange.
     *
     * @return int
     */
    public function &getRank();
}
