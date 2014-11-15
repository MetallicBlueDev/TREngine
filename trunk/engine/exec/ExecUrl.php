<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire d'adresse URL
 *
 * @author Sébastien Villemain
 */
class ExecUrl {

    /**
     * Nettoie l'adresse web (URL) du protocole
     *
     * @param string $url
     * @return string
     */
    public static function &cleanUrl($url) {
        if (substr($url, 0, 7) == "http://") {
            $url = substr($url, 7, strlen($url));
        }
        return $url;
    }

}

?>