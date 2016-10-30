<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreRequest;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation des cookies.
 *
 * @author Sébastien Villemain
 */
class ExecCookie {

    /**
     * Création d'un cookie.
     *
     * @param string $cookieName
     * @param string $cookieContent
     * @param string $cookieTimeLimit
     * @return bool true succès
     */
    public static function &createCookie($cookieName, $cookieContent, $cookieTimeLimit = "") {
        $cookieName = urlencode($cookieName);
        $cookieContent = urlencode($cookieContent);

        if (empty($cookieTimeLimit)) {
            $rslt = setcookie($cookieName, $cookieContent);
        } else if ($cookieTimeLimit == "-1" && !empty(CoreRequest::getString($cookieName, "", "COOKIE"))) {
            $rslt = setcookie($cookieName, "");
        } else {
            $rslt = setcookie($cookieName, $cookieContent, $cookieTimeLimit);
        }
        return $rslt;
    }

    /**
     * Destruction d'un cookie.
     *
     * @param string $cookieName
     * @return bool true succès
     */
    public static function &destroyCookie($cookieName) {
        return self::createCookie($cookieName, "", "-1");
    }

    /**
     * Retourne le contenu du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    public static function &getCookie($cookieName) {
        $cookieName = urlencode($cookieName);
        $cookieContent = CoreRequest::getString($cookieName, "", "COOKIE");
        $cookieContent = urldecode($cookieContent);
        return $cookieContent;
    }

}
