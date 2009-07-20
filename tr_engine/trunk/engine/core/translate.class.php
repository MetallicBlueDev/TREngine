<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Traducteur de langue
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Translate {
	
	/**
	 * Langue utilisé actuellement
	 * 
	 * @var String
	 */
	private static $currentLanguage = "";
	
	/**
	 * Extension de la langue utilisé
	 * 
	 * @var String
	 */
	private static $currentLanguageExtension = "";
	
	/**
	 * Memorise les fichiers déjà traduit
	 * 
	 * @var array
	 */
	private static $translated = array();
	
	/**
	 * Liste des differentes langues
	 * 
	 * @var array
	 */
	private static $languageList = array(
		"aa" => "Afar",
		"ab" => "Abkhazian",
		"af" => "Afrikaans",
		"am" => "Amharic",
		"ar" => "Arabic",
		"as" => "Assamese",
		"ae" => "Avestan",
		"ay" => "Aymara",
		"az" => "Azerbaijani",
		"ba" => "Bashkir",
		"be" => "Belarusian",
		"bn" => "Bengali",
		"bh" => "Bihari",
		"bi" => "Bislama",
		"bo" => "Tibetan",
		"bs" => "Bosnian",
		"br" => "Breton",
		"bg" => "Bulgarian",
		"ca" => "Catalan",
		"cs" => "Czech",
		"ch" => "Chamorro",
		"ce" => "Chechen",
		"cn" => "ChineseSimp",
		"cv" => "Chuvash",
		"kw" => "Cornish",
		"co" => "Corsican",
		"cy" => "Welsh",
		"da" => "Danish",
		"de" => "German",
		"dz" => "Dzongkha",
		"el" => "Greek",
		"en" => "English",
		"eo" => "Esperanto",
		"et" => "Estonian",
		"eu" => "Basque",
		"fo" => "Faroese",
		"fa" => "Persian",
		"fj" => "Fijian",
		"fi" => "Finnish",
		"fr" => "French",
		"fy" => "Frisian",
		"gd" => "Gaelic",
		"ga" => "Irish",
		"gl" => "Gallegan",
		"gv" => "Manx",
		"gn" => "Guarani",
		"gu" => "Gujarati",
		"ha" => "Hausa",
		"he" => "Hebrew",
		"hz" => "Herero",
		"hi" => "Hindi",
		"ho" => "Hiri Motu",
		"hr" => "Croatian",
		"hu" => "Hungarian",
		"hy" => "Armenian",
		"iu" => "Inuktitut",
		"ie" => "Interlingue",
		"id" => "Indonesian",
		"ik" => "Inupiaq",
		"is" => "Icelandic",
		"it" => "Italian",
		"jw" => "Javanese",
		"ja" => "Japanese",
		"kl" => "Kalaallisut",
		"kn" => "Kannada",
		"ks" => "Kashmiri",
		"ka" => "Georgian",
		"kk" => "Kazakh",
		"km" => "Khmer",
		"ki" => "Kikuyu",
		"rw" => "Kinyarwanda",
		"ky" => "Kirghiz",
		"kv" => "Komi",
		"ko" => "Korean",
		"ku" => "Kurdish",
		"lo" => "Lao",
		"la" => "Latin",
		"lv" => "Latvian",
		"ln" => "Lingala",
		"lt" => "Lithuanian",
		"lb" => "Letzeburgesch",
		"mh" => "Marshall",
		"ml" => "Malayalam",
		"mr" => "Marathi",
		"mk" => "Macedonian",
		"mg" => "Malagasy",
		"mt" => "Maltese",
		"mo" => "Moldavian",
		"mn" => "Mongolian",
		"mi" => "Maori",
		"ms" => "Malay",
		"my" => "Burmese",
		"na" => "Nauru",
		"nv" => "Navajo",
		
		"ng" => "Ndonga",
		"ne" => "Nepali",
		"nl" => "Dutch",
		"nb" => "Norwegian",
		
		"ny" => "Chichewa",
		"or" => "Oriya",
		"om" => "Oromo",
		"pa" => "Panjabi",
		"pi" => "Pali",
		"pl" => "Polish",
		"pt" => "Portuguese",
		"ps" => "Pushto",
		"qu" => "Quechua",
		"ro" => "Romanian",
		"rn" => "Rundi",
		"ru" => "Russian",
		"sg" => "Sango",
		"sa" => "Sanskrit",
		"si" => "Sinhalese",
		"sk" => "Slovak",
		"sl" => "Slovenian",
		
		"sm" => "Samoan",
		"sn" => "Shona",
		"sd" => "Sindhi",
		"so" => "Somali",
		
		"es" => "Spanish",
		"sq" => "Albanian",
		"sc" => "Sardinian",
		"sr" => "Serbian",
		"ss" => "Swati",
		"su" => "Sundanese",
		"sw" => "Swahili",
		"sv" => "Swedish",
		"ty" => "Tahitian",
		"ta" => "Tamil",
		"tt" => "Tatar",
		"te" => "Telugu",
		"tg" => "Tajik",
		"tl" => "Tagalog",
		"th" => "Thai",
		"ti" => "Tigrinya",
		
		"tn" => "Tswana",
		"ts" => "Tsonga",
		"tk" => "Turkmen",
		"tr" => "Turkish",
		"tw" => "ChineseTrad",
		"ug" => "Uighur",
		"uk" => "Ukrainian",
		"ur" => "Urdu",
		"uz" => "Uzbek",
		"vi" => "Vietnamese",
		
		"wo" => "Wolof",
		"xh" => "Xhosa",
		"yi" => "Yiddish",
		"yo" => "Yoruba",
		"za" => "Zhuang",
		"zh" => "Chinese",
		"zu" => "Zulu"
	);
	
	/**
	 * Configure et charge les 1ere données de traduction
	 */
	public static function makeInstance() {
		if (!self::$currentLanguage) {
			self::setLanguage();
			self::translate();
		}
	}
	
	/**
	 * Sélection de la langue la plus appropriée
	 * 
	 * @param string $user_langue : langue via cookie du client
	 */
	public static function setLanguage() {
		// Langue finale
		$language = "";
		// Langage du client via le cookie de session
		if (class_exists("Core_Session")) $userLanguage = strtolower(trim(Core_Session::$userLanguage));
		else $userLanguage = "";
		
		if (self::isValid($userLanguage)) {
			// Langue du client via cookie valide
			$language = $userLanguage;
		} else {
			// Recherche de l'extension de la langue
			self::setCurrentLanguageExtension();
			
			// Langue trouvée via la recherche
			$language = strtolower(trim(self::$languageList[self::$currentLanguageExtension]));
			
			// Si la langue trouvé en invalide
			if (!self::isValid($language)) {
				// Utilisation de la langue par défaut du site
				if (class_exists("Core_Main")) $language = Core_Main::$coreConfig['defaultLanguage'];
				else $language = "";
				
				// Malheureusement la langue par défaut est aussi invalide
				if (!self::isValid($language)) {
					$language = "english";
				}
			}
		}
		// Langue finale retenu
		self::$currentLanguage = $language;
		
		// Formate l'heure local
		if (self::$currentLanguage == "french" && TR_ENGINE_PHP_OS == "WIN") {
			setlocale(LC_TIME, "french");
		} else if (self::$currentLanguage == "french" && TR_ENGINE_PHP_OS == "BSD") {
			setlocale(LC_TIME, "fr_FR.ISO8859-1");
		} else if (self::$currentLanguage == "french") {
			setlocale(LC_TIME, 'fr_FR');
		} else {
			// Tentative de formatage via le nom de la langue
			if (!setlocale(LC_TIME, self::$currentLanguage)) {
				// Si la recherche n'a pas été faite, on la lance maintenant
				if (!self::$currentLanguageExtension) self::setCurrentLanguageExtension();
				// Dernère tentative de formatage sous forme "fr_FR"
				setlocale(LC_TIME, strtolower(self::$currentLanguageExtension) . "_" . strtoupper(self::$currentLanguageExtension));
			}
		}
		
	}
	
	/**
	 * Recherche l'extension de la langue du client
	 * a partir du client ou de l'extension du serveur
	 */
	private static function setCurrentLanguageExtension() {
		// Recherche de la langue du client
		$languageClient = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$languageClient = strtolower(substr(trim($languageClient[0]), 0, 2));
		
		if (self::$languageList[$languageClient] != "") {
			self::$currentLanguageExtension = $languageClient;
		} else {
			// Recherche de l'URL
			if (!defined("TR_ENGINE_URL")) $url = $_SERVER["SERVER_NAME"];
			else $url = TR_ENGINE_URL;
			
			// Recherche de l'extension de URL
			preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);
			preg_match('/[^.]+\.[^.]+$/', $matches[1], $matches);
			preg_match('/[^.]+$/', $matches[0], $languageExtension);
			
			if (self::$languageList[$languageExtension[0]] != "") {
				self::$currentLanguageExtension = $languageExtension[0];
			}
		}
	}
	
	/**
	 * Vérifie si le langage en disponible
	 * 
	 * @param $language
	 * @return boolean true langue disponible
	 */
	private static function isValid($language) {
		if ($language != "" && is_file(TR_ENGINE_DIR . "/lang/" . $language . ".lang.php")) return true;
		else return false;
	}
	
	/**
	 * Retourne le vrai chemin vers le fichier de langue
	 * 
	 * @param $pathLang
	 * @return String
	 */
	private static function getPath($pathLang) {
		if ($pathLang != "" && substr($pathLang, -1) != "/") $pathLang .= "/";
		return $pathLang . "lang/" . self::$currentLanguage . ".lang.php";
	}
	
	/**
	 * Traduction de la page via le fichier
	 * 
	 * @param string $path_lang : chemin du fichier de traduction
	 */
	public static function translate($pathLang = "") {
		// Capture du chemin vers le fichier
		$pathLang = self::getPath($pathLang);
		
		// Fichier déjà traduit
		if (self::$translated[$pathLang] != "") return null;
		
		// Vérification du fichier de langue
		if (is_file(TR_ENGINE_DIR . "/" . $pathLang)) {		
			// Préparation du Path et du contenu
			$langCacheFile = str_replace("/", "_", $pathLang);
			$content = "";
			
			// Recherche dans le cache
			if (class_exists("Core_CacheBuffer")) Core_CacheBuffer::setSectionName("lang");
			if (!class_exists("Core_CacheBuffer") 
					|| !Core_CacheBuffer::cached($langCacheFile)
					|| (Core_CacheBuffer::cacheMTime($langCacheFile) < @filemtime(TR_ENGINE_DIR . "/" . $pathLang))) {
				// Ecriture du fichier cache
				$lang = "";
				
				// Fichier de traduction original
				require(TR_ENGINE_DIR . "/" . $pathLang);
				
				if (is_array($lang)) {
					foreach ($lang as $key => $value) {
						if ($key && $value) {
							$content .= "define(\"" . $key . "\",\"" . self::entitiesTranslate($value) . "\");";
						}
					}
					if (class_exists("Core_CacheBuffer")) Core_CacheBuffer::writingCache($langCacheFile, $content);
				}
			}
			
			// Donnée de traduction
			if (class_exists("Core_CacheBuffer") && Core_CacheBuffer::cached($langCacheFile)) $data = "require(TR_ENGINE_DIR . '/tmp/lang/" . $langCacheFile . "');";
			else if ($content != "") $data = $content;
			else $data = "";
			
			// Traduction disponible
			if ($data != "") {
				// Ajoute le fichier traduit dans le tableau
				self::$translated[$pathLang] = "1";
				
				ob_start();
				print eval(" $data ");
				$langDefine = ob_get_contents();
				ob_end_clean();
				return $langDefine;
			}
		}
	}
	
	/**
	 * Conversion des caratères spéciaux en entitiées UTF-8
	 * Ajout d'antislashes pour utilisation dans le cache
	 * 
	 * @param String
	 * @return String
	 */
	private static function entitiesTranslate($text) {
		Core_Loader::classLoader("Exec_Entities");
		$text = Exec_Entities::entitiesUtf8($text);
		//$text = Exec_Entities::addSlashes($text);
		return $text;
	}
}
?>