<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreRequest;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Analyseur de protocole User agent.
 *
 * @author Sébastien Villemain
 */
class ExecAgent {

    /**
     * Tableau des systèmes d'exploitation.
     *
     * @var array
     */
    private static $osRessources = array(
        // Windows
        "Windows NT 10" => "Windows 10",
        "Windows NT 6.3" => "Windows 8.1",
        "Windows NT 6.2" => "Windows 8",
        "Windows NT 6.1" => "Windows 7",
        "Windows NT 6.0" => "Windows Vista",
        "Windows NT 5.2" => "Windows Server 2003",
        "Windows NT 5.1" => "Windows XP",
        "Windows xp" => "Windows XP",
        "Windows NT 5.0" => "Windows 2000",
        "Windows 2000" => "Windows 2000",
        "Windows CE" => "Windows Mobile",
        "Win 9x 4.90" => "Windows Me.",
        "Windows me" => "Windows Me.",
        "Windows 98" => "Windows 98",
        "Win98" => "Windows 98",
        "Windows 95" => "Windows 95",
        "Windows_95" => "Windows 95",
        "Win95" => "Windows 95",
        "Windows" => "Windows",
        // Linux
        "Ubuntu" => "Linux Ubuntu",
        "Fedora" => "Linux Fedora",
        "Linux" => "Linux",
        "FreeBSD" => "FreeBSD",
        "OpenSolaris" => "SunOS",
        "SunOS" => "SunOS",
        "BeOS" => "BeOS",
        "AIX" => "AIX",
        "IRIX" => "IRIX",
        "Unix" => "Unix",
        // Mac
        "iPhone" => "iPhone",
        "iPod" => "iPod",
        "iPad" => "iPad",
        "Mac OS X" => "Mac OS X",
        "Mac_PowerPC" => "Mac OS 9",
        "Macintosh" => "Mac",
        // Autres
        "Android" => "Android",
        "Blackberry" => "BlackBerry",
        "webos" => "Mobile",
        "Playstation portable" => "PSP",
        "Nintendo Wii" => "Nintendo Wii"
    );

    /**
     * Tableau des navigateurs internet.
     *
     * @var array
     */
    private static $browserRessouces = array(
        // LES NAVIGATEURS INTERNET ---
        // Netscape
        "Nav" => "Netscape",
        "Gold" => "Netscape",
        "X11" => "Netscape",
        "Netscape" => "Netscape",
        // Internet Explorer Mobile
        "Pocket Internet Explorer" => "Internet Explorer Mobile",
        "MSPIE" => "Internet Explorer Mobile",
        "IEMobile" => "Internet Explorer Mobile",
        // Internet Explorer
        "MSIE" => "Internet Explorer",
        "Maxthon" => "Maxthon",
        // FireFox
        "Firebird" => "Firefox",
        "Firefox" => "Firefox",
        // Other...
        "ELinks" => "ELinks",
        "iCab" => "iCab",
        "Konqueror" => "Konqueror",
        "Links" => "Links",
        "Lynx" => "Lynx",
        "midori" => "Midori",
        "Minimo" => "Minimo",
        "SeaMonkey" => "SeaMonkey",
        "OffByOne" => "OffByOne",
        "OmniWeb" => "OmniWeb",
        "w3m" => "w3m",
        // Chrome
        "Chrome" => "Chrome",
        // Opera
        "Opera" => "Opera",
        // Safari
        "Safari" => "Safari",
        // LES ROBOTS INTERNET ---
        "ia_archiver" => "Alexa",
        "Ask Jeeves" => "Ask Jeeves",
        "Baiduspider" => "Baidu Spider",
        "curl" => "cURL",
        "Exabot" => "Exabot",
        "NG" => "Exabot",
        "GameSpyHTTP" => "GameSpy",
        "Gigabot" => "Gigabot",
        "Googlebot" => "Googlebot",
        "grub" => "Grub",
        "Yahoo! Slurp" => "Yahoo! Slurp",
        "Slurp" => "Inktomi Slurp",
        "teoma" => "Inktomi Slurp",
        "msnbot" => "Msnbot",
        "Scooter" => "Scooter AltaVista",
        "Wget" => "Wget",
        // Mozilla - AT LAST SEARCH!
        "Mozilla" => "Mozilla",
        "Mobile" => "Handheld Browser",
    );

    /**
     * Retourne l'adresse IP du client.
     *
     * @return string
     */
    public static function &getAddressIp() {
        $currentIp = CoreRequest::getString("HTTP_CLIENT_IP", "", "SERVER");

        if (empty($currentIp)) {
            $currentIp = CoreRequest::getString("HTTP_X_FORWARDED_FOR", "", "SERVER");

            if (empty($currentIp)) {
                $currentIp = CoreRequest::getString("REMOTE_ADDR", "", "SERVER");
            }
        }
        return $currentIp;
    }

    /**
     * Retourne l'hôte du client.
     *
     * @param string $currentIp
     * @return string
     */
    public static function &getHost(&$currentIp) {
        $currentHost = "";

        if (!empty($currentIp)) {
            $currentHost = strtolower(gethostbyaddr($currentIp));
        }

        if ($currentHost !== $currentIp && $currentHost !== false) {
            $res = array();

            if (preg_match("/([^.]{1,})((\.(co|com|net|org|edu|gov|mil))|())
                ((\.(ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|az|ba|bb|bd|be|bf|bg|
                bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|
                cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|fi|fj|fk|fm|fo|fr|
                fx|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|
                hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|
                kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|
                ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|pa|pe|pf|
                pg|ph|pk|pl|pm|pn|pr|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|
                sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|
                tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zr|zw))|())$/ie", $currentHost, $res)) {
                $currentHost = $res[0];
            }
        }
        return $currentHost;
    }

    /**
     * Retourne la chaine User agent.
     *
     * @return string
     */
    public static function &getRawUserAgent() {
        $currentUserAgent = CoreRequest::getString("HTTP_USER_AGENT", "", "SERVER");
        return $currentUserAgent;
    }

    /**
     * Retourne le système d'exploitation du client.
     *
     * @param string $currentUserAgent
     * @return string
     */
    public static function &getOsName(&$currentUserAgent) {
        $currentOs = "";

        foreach (self::$osRessources as $osAgent => $osName) {
            if (preg_match("/" . $osAgent . "/ie", $currentUserAgent)) {
                $currentOs = $osName;
                break;
            }
        }

        if (empty($currentOs)) {
            $currentOs = "Unknown Os";
        }
        return $currentOs;
    }

    /**
     * Retourne le navigateur du client.
     *
     * @param string $currentUserAgent
     * @return array
     */
    public static function &getBrowserData(&$currentUserAgent) {
        $currentBrowser = array();

        foreach (self::$browserRessouces as $browserAgent => $browserName) {
            if (preg_match("/" . $browserAgent . "[ \/]([0-9\.]+)/ie", $currentUserAgent, $version) || preg_match("/" . $browserAgent . "/ie", $currentUserAgent, $version)) {
                $currentBrowser[] = isset($version[1]) ? trim($version[1]) : "";
                $currentBrowser[] = $browserName;
                break;
            }
        }

        if (!isset($currentBrowser[0])) {
            $currentBrowser[] = "";
        }

        if (!isset($currentBrowser[1])) {
            $currentBrowser[] = "Unknown Browser";
        }
        return $currentBrowser;
    }

    /**
     * Retourne le Referer du client.
     *
     * @return string
     */
    public static function &getReferer() {
        $currentReferer = htmlentities(CoreRequest::getString("HTTP_REFERER", "", "SERVER"), ENT_QUOTES);
        return $currentReferer;
    }

}
