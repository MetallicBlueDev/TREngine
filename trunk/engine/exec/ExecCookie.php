<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil destinée au cookie
 *
 * @author Sébastien Villemain
 */
class ExecCookie {

    /**
     * Création d'un cookie
     *
     * @param $cookieName
     * @param $cookieContent
     * @param $cookieTimeLimit
     * @return boolean true succès
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
     * Destruction d'un cookie
     *
     * @param $cookieName
     * @return boolean true succès
     */
    public static function &destroyCookie($cookieName) {
        return self::createCookie($cookieName, "", "-1");
    }

    /**
     * Retourne le contenu du cookie
     *
     * @param $cookieName string
     * @return string
     */
    public static function &getCookie($cookieName) {
        $cookieName = urlencode($cookieName);
        $cookieContent = CoreRequest::getString($cookieName, "", "COOKIE");
        $cookieContent = urldecode($cookieContent);
        return $cookieContent;
    }

}

?>