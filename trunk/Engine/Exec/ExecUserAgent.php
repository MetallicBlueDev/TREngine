<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreRequest;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil d'analyse des informations contenu dans le User-Agent.
 *
 * @author Sébastien Villemain
 */
class ExecUserAgent {

    /**
     * Liste des systèmes d'exploitation.
     * Attention, les underscores sont remplacés par des points dans les clés.
     * Vérifié à l'aide de croisement d'information.
     * Notamment https://udger.com/resources/ua-list/os
     *
     * @var array
     */
    private static $osResources = array(
        "Playstation" => array(
            "Playstation portable" => "PlayStation Portable (PSP)", // XrossMediaBar
            "Playstation vita" => "Playstation Vita", // LiveArea
            "Playstation 3" => "Playstation 3", // XrossMediaBar
            "Playstation 4" => "Playstation 4", // Orbis OS
            "Playstation" => "Playstation" // Terme générique
        ),
        "Nintendo" => array(
            "Nintendo dsi" => "Nintendo DSi",
            "Nintendo ds" => "Nintendo DS",
            "Nintendo 3ds" => "Nintendo 3DS",
            "Nintendo Wii" => "Nintendo Wii",
            "Nintendo Switch" => "Nintendo Switch",
            "Nintendo" => "Nintendo" // Terme générique
        ),
        "Xbox" => array(
            "Xbox 360" => "Xbox 360", // Windows NT 6.1
            "Xbox One" => "Xbox One", // Windows NT 10.0
            "Xbox" => "Xbox" // Terme générique
        ),
        "Windows" => array(
            "Windows NT 10.0" => "Windows 10",
            "Windows NT 6.3" => "Windows 8.1",
            "Windows NT 6.2" => "Windows 8",
            "Windows NT 6.1" => "Windows 7",
            "Windows NT 6.0" => "Windows Vista",
            "Windows NT 5.2" => "Windows Server 2003",
            "Windows NT 5.1" => "Windows XP",
            "Windows xp" => "Windows XP",
            "Windows NT 5.0" => "Windows 2000",
            "Windows 2000" => "Windows 2000",
            "Windows Mobile" => "Windows Mobile",
            "Windows CE" => "Windows Mobile",
            "Win 9x 4.90" => "Windows Me.",
            "Windows me" => "Windows Me.",
            "Windows 98" => "Windows 98",
            "Windows 95" => "Windows 95",
            "Win95" => "Windows 95",
            "Windows Phone" => "Windows Phone",
            "Windows" => "Unknown Windows OS" // Terme générique
        ),
        "Unix" => array(
            "Ubuntu" => "Ubuntu",
            "Fedora" => "Fedora",
            "LynxOS" => "LynxOS",
            "Raspbian" => "Raspbian",
            "UnixWare" => "UnixWare",
            "ChorusOS" => "ChorusOS",
            "FreeBSD" => "FreeBSD",
            "OpenSolaris" => "OpenSolaris",
            "SunOS" => "Solaris",
            "Oracle Solaris" => "Solaris",
            "Solaris" => "Solaris",
            "BlueEyedOS" => "BlueEyedOS",
            "Minix" => "Minix",
            "NetBSD" => "NetBSD",
            "DragonFly" => "DragonFly BSD",
            "BSDi" => "BSDi",
            "OpenBSD" => "OpenBSD",
            "SymbOS" => "Symbian OS",
            "Symbian" => "Symbian OS",
            "QNX" => "QNX",
            "XNU" => "XNU",
            "AIX" => "AIX",
            "IRIX64" => "Irix",
            "IRIX" => "Irix",
            "OSF" => "DEC OSF",
            "HP-UX" => "HP-UX",
            "CentOS" => "CentOS",
            "Mandriva" => "Mandriva",
            "Red Hat" => "Red Hat",
            "Slackware" => "Slackware",
            "SUSE" => "openSUSE",
            "openSUSE" => "openSUSE",
            "CrOs" => "Chromium OS",
            "CrKey" => "Android",
            "Android" => "Android",
            "webOS" => "WebOS",
            "Debian" => "Debian",
            "Linux" => "Linux",
            "Unix" => "Unknown Unix OS" // Terme générique
        ),
        "Be OS" => array(
            "AtheOS" => "AtheOS",
            "Syllable" => "Syllable",
            "NewOS" => "NewOS",
            "Haiku " => "Haiku",
            "AmigaOS" => "AmigaOS",
            "Amiga" => "AmigaOS",
            "MorphOS" => "MorphOS",
            "Icaros" => "Icaros",
            "AROS" => "AROS",
            "BeOS " => "BeOS"
        ),
        "Apple" => array(
            "iPhone" => "iOS",
            "iPod" => "iOS",
            "iPad" => "iOS",
            "AppleTV" => "tvOS",
            "Mac OS X 10.12" => "macOS 10.12 Sierra",
            "Mac OS X 10.11" => "OS X 10.11 El Capitan",
            "Mac OS X 10.10" => "OS X 10.10 Yosemite",
            "Mac OS X 10.9" => "OS X 10.9 Mavericks",
            "Mac OS X 10.8" => "OS X 10.8 Mountain Lion",
            "Mac OS X 10.7" => "Mac OS X 10.7 Lion",
            "Mac OS X 10.6" => "Mac OS X 10.6 Snow Leopard",
            "Mac OS X 10.5" => "Mac OS X 10.5 Leopard",
            "Mac OS X 10.4" => "Mac OS X 10.4 Tiger",
            "Mac OS X 10.3" => "Mac OS X 10.3 Panther",
            "Mac OS X 10.2" => "Mac OS X 10.2 Jaguar",
            "Mac OS X 10.1" => "Mac OS X 10.1 Puma",
            "Mac OS X 10.0" => "Mac OS X 10.0 Cheetah",
            "Macintosh" => "Unknown Mac OS" // Terme générique
        ),
        "Other" => array(
            "ApacheBench" => "ApacheBench",
            "MenuetOS" => "MenuetOS",
            "KolibriOS" => "KolibriOS",
            "HotJava" => "Java",
            "Java" => "Java",
            "RIM Tablet OS 1" => "BlackBerry",
            "RIM Tablet OS 2" => "BlackBerry",
            "Blackberry" => "BlackBerry",
            "BB10" => "BlackBerry"
        )
    );

    /**
     * Liste des navigateurs internet.
     *
     * @var array
     */
    private static $browserResouces = array(
        "Browser-Mobile" => array(
            // FireFox
            "fennec" => "Firefox Mobile",
            "Minimo" => "Minimo", // Ancien projet Mozilla
            // Internet Explorer Mobile
            "Pocket Internet Explorer" => "Internet Explorer Mobile",
            "MSPIE" => "Internet Explorer Mobile",
            "IEMobile" => "Internet Explorer Mobile",
            // Opera
            "opera mobi" => "Opera Mobile",
            "opera mini" => "Opera Mini",
            "operamini" => "Opera Mini",
            // Autre
            "Kindle Fire" => "Kindle",
            "Silk" => "Kindle",
            "Mobile" => "Generic Browser",
            "smartphone" => "Generic Browser",
            "cellphone" => "Generic Browser",
            "wireless" => "Generic Browser"
        ),
        "Browser-Desktop" => array(
            // Netscape
            "Nav" => "Netscape",
            "Gold" => "Netscape",
            "X11" => "Netscape",
            "Netscape" => "Netscape",
            // Internet Explorer
            "MSIE" => "Internet Explorer",
            "Internet Explorer" => "Internet Explorer",
            "Trident" => "Internet Explorer",
            "Maxthon" => "Maxthon",
            "Edge" => "Edge",
            // FireFox
            "Firebird" => "Firefox",
            "Iceweasel" => "Firefox",
            "Firefox" => "Firefox",
            // Autre
            "ELinks" => "ELinks",
            "iCab" => "iCab",
            "Konqueror" => "Konqueror",
            "Links" => "Links",
            "Lynx" => "Lynx",
            "midori" => "Midori",
            "SeaMonkey" => "SeaMonkey",
            "OffByOne" => "OffByOne",
            "OmniWeb" => "OmniWeb",
            "w3m" => "w3m",
            // Chrome
            "Chrome" => "Chrome",
            // Opera
            "OPR" => "Opera",
            "Opera" => "Opera",
            // Safari
            "Safari" => "Safari"
        ),
        "Bot" => array(
            "alexa" => "Alexa",
            "GotSiteMonitor" => "GotSiteMonitor.com (Vannet Technology)",
            "DotBot" => "moz.com (SEOmoz)",
            "NotifyNinja" => "NotifyNinja.com",
            "PINGOMETER" => "Pingometer",
            "ia_archiver" => "Alexa",
            "Baiduspider" => "Baidu",
            "Gigabot" => "Gigablast",
            "GigablastOpenSource" => "Gigablast",
            "yandex" => "Yandex",
            "curious george" => "Analytics SEO",
            "MJ12bot" => "Majestic-12",
            "Uptimebot" => "Uptime.com",
            "UptimeRobot" => "UptimeRobot.com",
            "Yahoo" => "Yahoo!",
            "Y!J" => "Yahoo!",
            "Scooter" => "AltaVista",
            "Wget" => "wget.alanreed.org",
            "Inspingbot" => "Insping",
            "Hatena" => "Hatena",
            "Webshot" => "ShrinkTheWeb.com (Neosys Consulting)",
            "shrinktheweb.com" => "ShrinkTheWeb.com (Neosys Consulting)",
            "Site-Shot" => "Site-Shot.com",
            "Easy-Thumb" => "Easy-Thumb",
            "SeznamBot" => "Seznam",
            "Seznam-Zbozi-robot" => "Seznam",
            "Seznam " => "Seznam",
            "Exabot" => "ExaLead.com (Dassault Systèmes)",
            "ExaleadCloudview" => "ExaLead.com (Dassault Systèmes)",
            "Googlebot" => "Google",
            "mediapartners-google" => "Google",
            "Google-Site-Verification" => "Google",
            "Google-SearchByImage" => "Google",
            "Google Page Speed Insights" => "Google",
            "adsbot-google" => "Google",
            "feedfetcher-google" => "Google",
            "Google favicon" => "Google",
            "Google Web Preview" => "Google",
            "GoogleWebLight" => "Google",
            "msnbot" => "Bing (Microsoft)",
            "SkypeUriPreview" => "Bing (Microsoft)",
            "BingPreview" => "Bing (Microsoft)",
            "adidxbot" => "Bing (Microsoft)",
            "bingbot" => "Bing (Microsoft)",
            "bot" => "Unknown bot",
            "crawl" => "Unknown bot",
            "spider" => "Unknown bot"
        ),
        "Other" => array(
            // Uniquement à la fin - Terme générique
            "Mozilla" => "Mozilla",
            "j2me" => "Generic Browser",
            "midp" => "Generic Browser",
            "cldc" => "Generic Browser"
        )
    );

    /**
     * Retourne l'adresse IP du client.
     *
     * @return string
     */
    public static function &getAddressIp(): string {
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
    public static function &getHost(string &$currentIp): string {
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
     * Retourne la chaine User-Agent.
     *
     * @return string
     */
    public static function &getRawUserAgent(): string {
        $currentUserAgent = CoreRequest::getString("HTTP_USER_AGENT", "", "SERVER");
        return $currentUserAgent;
    }

    /**
     * Retourne le système d'exploitation du client.
     *
     * @param string $currentUserAgent
     * @return array
     */
    public static function &getOsData(string &$currentUserAgent): array {
        $currentOs = array(
            "category" => "",
            "name" => "Unknown Os");

        foreach (self::$osResources as $osCategory => $osSubResources) {
            foreach ($osSubResources as $osAgent => $osName) {
                // Remplace les underscores par un point afin d'obtenir qu'une version de l'agent
                // Exemple avec Mac OS X 10_7 et Mac OS X 10.7
                if (preg_match("/" . str_replace("_", ".", $osAgent) . "/ie", $currentUserAgent)) {
                    $currentOs['category'] = $osCategory;
                    $currentOs['name'] = $osName;
                    break 2;
                }
            }
        }
        return $currentOs;
    }

    /**
     * Retourne le navigateur du client.
     *
     * @param string $currentUserAgent
     * @return array
     */
    public static function &getBrowserData(string &$currentUserAgent): array {
        $currentBrowser = array(
            "category" => "",
            "name" => "Unknown Browser",
            "version" => ""
        );

        foreach (self::$browserResouces as $browserCategory => $browserSubResources) {
            foreach ($browserSubResources as $browserAgent => $browserName) {
                if (preg_match("/" . $browserAgent . "[ \/]([0-9\.]+)/ie", $currentUserAgent, $version) || preg_match("/" . $browserAgent . "/ie", $currentUserAgent, $version)) {
                    $currentBrowser['category'] = $browserCategory;
                    $currentBrowser['name'] = $browserName;
                    $currentBrowser['version'] = isset($version[1]) ? trim($version[1]) : "";
                    break 2;
                }
            }
        }
        return $currentBrowser;
    }

    /**
     * Retourne le chemin référent qu'a suivi le client.
     *
     * @return string
     */
    public static function &getReferer(): string {
        $currentReferer = htmlentities(CoreRequest::getString("HTTP_REFERER", "", "SERVER"), ENT_QUOTES);
        return $currentReferer;
    }

}
