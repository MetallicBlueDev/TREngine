<?php

/**
 * Analyseur de protocole USER AGENT
 * 
 * @author Sébastien Villemain
 *
 */
class Exec_Agent {
	
	/**
	 * Adresse IP du client
	 * 
	 * @var String
	 */
	public static $userIp;
	
	/**
	 * Host du client
	 * 
	 * @var String
	 */
	public static $userHost;
	
	/**
	 * OS du client
	 * 
	 * @var String
	 */
	public static $userOs;
	
	/**
	 * Navigateur du client
	 * 
	 * @var String
	 */
	public static $userBrowser;
	
	/**
	 * Referer du client
	 * 
	 * @var String
	 */
	public static $userReferer;
	
	/**
	 * User agent complet du client
	 * 
	 * @var String
	 */
	public static $userAgent;
	
	/**
	 * Tableau des correspondances agent -> navigateur
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
		"Mozilla" => "Mozilla"
	);
	
	/**
	 * Tableau des OS
	 * 
	 * @var array
	 */
	private static $osRessources = array (
		// Windows
		"Windows NT 6.1" => "Windows Seven",
		"Windows NT 6.0" => "Windows Vista",
		"Windows NT 5.2" => "Windows Server 2003",
		"Windows NT 5.1" => "Windows XP",
		"Windows NT 5.0" => "Windows 2000",
		"Windows 2000" => "Windows 2000",
		"Windows CE" => "Windows Mobile",
		"Win 9x 4.90" => "Windows Me.",
		"Windows 98" => "Windows 98",
		"Win98" => "Windows 98",
		"Windows 95" => "Windows 95",
		"Windows_95" => "Windows 95",
		"Win95" => "Windows 95",
		"Windows NT" => "Windows NT",
	
		// Linux
		"Ubuntu" => "Linux Ubuntu",
		"Fedora" => "Linux Fedora",
		"Linux" => "Linux",
	
		// Mac
		"iPhone" => "iPhone",
		"Mac OS X" => "Mac OS X",
		"Mac_PowerPC" => "Mac OS 9",
		"Macintosh" => "Mac",
	
		// Autres
		"Playstation portable" => "PSP",
		"FreeBSD" => "FreeBSD",
		"SunOS" => "SunOS",
		"OpenSolaris" => "SunOS",
		"BeOS" => "BeOS",
		"AIX" => "AIX",
		"IRIX" => "IRIX",
		"Unix" => "Unix",
		"Nintendo Wii" => "Nintendo Wii",
		"Linux" => "Linux"
);
	
	/**
	 * Execute une vérification sur l'host
	 * 
	 * @return String
	 */
	private static function checkUserHost() {
		if (self::$userHost == self::$userIp) {
			return "";
		} else if (preg_match("/([^.]{1,})((\.(co|com|net|org|edu|gov|mil))|())
				((\.(ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|az|ba|bb|bd|be|bf|bg|
				bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|
				cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|fi|fj|fk|fm|fo|fr|
				fx|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|
				hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|
				kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|
				ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|pa|pe|pf|
				pg|ph|pk|pl|pm|pn|pr|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|
				sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|
				tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zr|zw))|())$/ie", self::$userHost, $res)) {
			return $res[0];
		}
		return self::$userHost;
	}
	
	/**
	 * Retourne le nom du browser ou du bot
	 * 
	 * @return String
	 */
	private static function checkUserBrower() {
		// Boucle sur tout les Browsers et Bot connus
		foreach (self::$browserRessouces as $browserAgent => $browserName) {
			if (preg_match("/" . $browserAgent . "[ \/]([0-9\.]+)/ie", self::$userAgent, $version)
					|| preg_match("/" . $browserAgent . "/ie", self::$userAgent, $version)) {
				return $browserName . ((isset($version[1])) ? " " . $version[1] : "");
			}
		}
		return "Unknown Browser";
	}
	
	/**
	 * Retourne le nom de l'Os
	 * 
	 * @return String
	 */
	private static function checkUserOs() {
		// Boucle sur tout les systemes d'exploitations
		foreach (self::$osRessources as $osAgent => $osName) {
			if (preg_match("/" . $osAgent . "/ie", self::$userAgent)) {
				return $osName;
			}
		}
		return "Unknown Os";
	}
	
	/**
	 * Lance l'analyse et la récuperation d'information sur le client
	 */
	public static function getVisitorsStats() {
		// Adresse Ip du client
		self::$userIp = Core_Secure::getUserIp();
		
		// Analyse pour les statistiques
		self::$userReferer = htmlentities($_SERVER['HTTP_REFERER'], ENT_QUOTES);
		self::$userHost = strtolower(@gethostbyaddr(self::$userIp));
		self::$userAgent = $_SERVER['HTTP_USER_AGENT'];
		
		// Details sur le client
		self::$userHost = self::checkUserHost();
		self::$userBrowser = self::checkUserBrower();
		self::$userOs = self::checkUserOs();
	}
}
?>