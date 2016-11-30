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
     * @param int $cookieTimeLimit
     * @return bool true succès
     */
    public static function &createCookie(string $cookieName, string $cookieContent, int $cookieTimeLimit = 0): bool {
        $rslt = false;

        if ($cookieTimeLimit >= 0) {
            $cookieName = urlencode($cookieName);
            $cookieContent = urlencode($cookieContent);
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
    public static function &destroyCookie(string $cookieName): bool {
        $rslt = true;
        $cookieName = urlencode($cookieName);

        if (!empty(self::requestCookie($cookieName))) {
            $rslt = setcookie($cookieName, "");
        }
        return $rslt;
    }

    /**
     * Retourne le contenu du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    public static function &getCookie(string $cookieName): string {
        $cookieName = urlencode($cookieName);
        $cookieContent = self::requestCookie($cookieName);
        $cookieContent = urldecode($cookieContent);
        return $cookieContent;
    }

    /**
     * Retourne le contenu brut du cookie.
     *
     * @param string $cookieEncodeName
     * @return string
     */
    private static function &requestCookie(string $cookieEncodeName): string {
        return CoreRequest::getString($cookieEncodeName, "", "COOKIE");
    }

}
