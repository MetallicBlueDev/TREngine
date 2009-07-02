<?php

class Core_Translate {
	
	/**
	 * Langue utilis� actuellement
	 * 
	 * @var String
	 */
	private static $currentLanguage = "";
	
	/**
	 * Extension de la langue utilis�
	 * 
	 * @var String
	 */
	private static $currentLanguageExtension = "";
	
	/**
	 * Memorise les fichiers d�j� traduit
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
	 * S�lection de la langue la plus appropri�e
	 * 
	 * @param string $user_langue : langue via cookie du client
	 */
	public static function setLanguage() {
		// Langue finale
		$language = "";
		// Langage du client via le cookie de session
		$userLanguage = strtolower(trim(Core_Session::$userLanguage));
		
		if (self::isValid($userLanguage)) {
			// Langue du client via cookie valide
			$language = $userLanguage;
		} else {
			// Recherche de l'extension de la langue
			self::setCurrentLanguageExtension();
			
			// Langue trouv�e via la recherche
			$language = strtolower(trim(self::$languageList[self::$currentLanguageExtension]));
			
			// Si la langue trouv� en invalide
			if (!self::isValid($language)) {
				// Utilisation de la langue par d�faut du site
				$language = Core_Main::$coreConfig['defaultLanguage'];
				
				// Malheureusement la langue par d�faut est aussi invalide
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
				// Si la recherche n'a pas �t� faite, on la lance maintenant
				if (!self::$currentLanguageExtension) self::setCurrentLanguageExtension();
				// Dern�re tentative de formatage sous forme "fr_FR"
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
		
		// Recherche de l'URL
		if (!defined("TR_ENGINE_URL")) $url = $_SERVER["SERVER_NAME"];
		else $url = TR_ENGINE_URL;
		
		// Recherche de l'extension de URL
		preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);
		preg_match('/[^.]+\.[^.]+$/', $matches[1], $matches);
		preg_match('/[^.]+$/', $matches[0], $languageExtension);
		
		if (self::$languageList[$languageClient] != "") {
			self::$currentLanguageExtension = $languageClient;
		} else if ($language_list[$languageExtension[0]] != "") {
			self::$currentLanguageExtension = $languageExtension[0];
		}
	}
	
	/**
	 * V�rifie si le langage en disponible
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
		
		// Fichier d�j� traduit
		if (self::$translated[$pathLang] != "") return null;
		
		// V�rification du fichier de langue
		if (is_file(TR_ENGINE_DIR . "/" . $pathLang)) {			
			// Pr�paration du Path et du contenu
			$langCacheFile = str_replace("/", "_", $pathLang);
			$content = "";
			
			// Recherche dans le cache
			Core_CacheBuffer::setSectionName("lang");
			if (!Core_CacheBuffer::cached($langCacheFile)
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
					Core_CacheBuffer::writingCache($langCacheFile, $content);
				}
			}
			
			// Donn�e de traduction
			if (Core_CacheBuffer::cached($langCacheFile)) $data = "require(TR_ENGINE_DIR . '/tmp/lang/" . $langCacheFile . "');";
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
	 * Conversion des carat�res sp�ciaux en entiti�es UTF-8
	 * Ajout d'antislashes pour utilisation dans le cache
	 * 
	 * @param String
	 * @return String
	 */
	private static function entitiesTranslate($text) {
		$text = self::entitiesUtf8($text);
		$text = addslashes($text);
		return $text;
	}
	
	/**
	 * Transforme une chaine non-encod�e, et la convertit en entiti�es unicode &#xxx;
	 * pour que �a s'affiche correctement dans les navigateurs
	 * Thanks to ??? (sorry!) @ ???
	 * http://
	 * 
	 * @param string $source : la chaine
	 * @return string $encodedString : chaine et ses entit�es
	 */
	private static function entitiesUtf8($source) {
		// Remplace les entit�s num�riques
		$source = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $source);
		$source = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $source);
		
		// Remplace les entit�s lit�rales
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		$source = strtr($source, $trans_tbl);
		
		// Entit�es UTF-8
		$source = utf8_encode($source);
		
		// array used to figure what number to decrement from character order value 
		// according to number of characters used to map unicode to ascii by utf-8
		$decrement[4] = 240;
		$decrement[3] = 224;
		$decrement[2] = 192;
		$decrement[1] = 0;
		
		// the number of bits to shift each charNum by
		$shift[1][0] = 0;
		$shift[2][0] = 6;
		$shift[2][1] = 0;
		$shift[3][0] = 12;
		$shift[3][1] = 6;
		$shift[3][2] = 0;
		$shift[4][0] = 18;
		$shift[4][1] = 12;
		$shift[4][2] = 6;
		$shift[4][3] = 0;
		
		$pos = 0;
		$len = strlen($source);
		$encodedString = '';
		while ($pos < $len) {
			$charPos = substr($source, $pos, 1);
			$asciiPos = ord($charPos);
			
			if ($asciiPos < 128) {
				$encodedString .= htmlentities($charPos);
				$pos++;
				continue;
			}
			
			if (($asciiPos >= 240) && ($asciiPos <= 255)) $i = 4; // 4 chars representing one unicode character
			else if (($asciiPos >= 224) && ($asciiPos <= 239)) $i = 3; // 3 chars representing one unicode character
			else if (($asciiPos >= 192) && ($asciiPos <= 223)) $i = 2; // 2 chars representing one unicode character
			else $i = 1; // 1 char (lower ascii)
			
			$thisLetter = substr($source, $pos, $i);
			$pos += $i;
			
			// process the string representing the letter to a unicode entity
			$thisLen = strlen($thisLetter);
			$decimalCode = 0;
			
			for ($thisPos = 0; $thisPos < $thisLen; $thisPos++) {
				$thisCharOrd = ord(substr($thisLetter, $thisPos, 1));
				
				if ($thisPos == 0) {
					$charNum = intval($thisCharOrd - $decrement[$thisLen]);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				} else {
					$charNum = intval($thisCharOrd - 128);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				}
			}
			
			$encodedLetter = '&#'. str_pad($decimalCode, ($thisLen==1) ? 3 : 5, '0', STR_PAD_LEFT).';';
			$encodedString .= $encodedLetter;
		}
		return $encodedString;
	}
}
?>