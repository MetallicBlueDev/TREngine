<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation d'adresse URL.
 *
 * @author Sébastien Villemain
 */
class ExecUrl {

    /**
     * Nettoie l'adresse web du protocole.
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