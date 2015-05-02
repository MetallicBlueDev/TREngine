<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Moteur de traduction de texte.
 *
 * @author Sébastien Villemain
 */
class CoreTranslate {

    /**
     * Instance du gestionnaire du traducteur.
     *
     * @var CoreTranslate
     */
    private static $coreTranslate = null;

    /**
     * Liste des differentes langues.
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
     * Langue utilisée.
     *
     * @var string
     */
    private $languageUsed = "";

    /**
     * Memorise les fichiers déjà traduit.
     *
     * @var array
     */
    private $cache = array();

    /**
     * Nouveau gestionnaire.
     */
    private function __construct() {
        $extension = self::getLanguageExtension();
        $this->languageUsed = self::getLanguage($extension);

        $this->configureLocale($extension);
    }

    /**
     * Retourne le gestionnaire de traduction.
     *
     * @return CoreTranslate
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$coreTranslate;
    }

    /**
     * Vérification de l'instance du gestionnaire de traduction.
     */
    public static function checkInstance() {
        if (self::$coreTranslate === null) {
            self::$coreTranslate = new CoreTranslate();
            self::$coreTranslate->translate("Engine");
        }
    }

    /**
     * Ajout d'un tableau de traduction pour utilisation prochaine.
     *
     * @param array $cache
     */
    public function affectCache(array $cache) {
        if (!empty($cache) && empty($this->cache)) {
            $this->cache = $cache;
        }
    }

    /**
     * Retourne la langue courante.
     *
     * @return string
     */
    public function &getCurrentLanguage() {
        return $this->languageUsed;
    }

    /**
     * Traduction de la page via le fichier.
     *
     * @param string $pathLang chemin du fichier de traduction.
     */
    public function translate($pathLang) {
        $loaded = !empty(CoreLoader::getTranslateAbsolutePath($pathLang));

        // Traduction uniquement si besoin
        if (!$loaded) {
            $this->cache = array();
            $loaded = CoreLoader::translateLoader($pathLang);

            if ($loaded && !empty($this->cache)) {
                $langCacheFileName = self::getLangCacheFileName($pathLang) . $this->languageUsed . ".php";
                $langOriginalPath = CoreLoader::getTranslateAbsolutePath($pathLang);
                $content = "";

                // Pour la sélection dans le cache
                $coreCache = null;
                if (CoreLoader::isCallable("CoreCache")) {
                    $coreCache = CoreCache::getInstance(CoreCache::SECTION_TRANSLATE);
                }

                // Chargement du fichier de traduction
                if ($coreCache === null || !$coreCache->cached($langCacheFileName) || ($coreCache->getCacheMTime($langCacheFileName) < filemtime($langOriginalPath))) {
                    foreach ($this->cache as $key => $value) {
                        if (!empty($key) && !empty($value)) {
                            $content .= "define(\"" . $key . "\",\"" . self::entitiesTranslate($value) . "\");";
                        }
                    }

                    if ($coreCache !== null) {
                        $coreCache->writeCache($langCacheFileName, $content);
                    }
                }

                $this->cache = array();

                // Données de traduction
                if ($coreCache !== null && $coreCache->cached($langCacheFileName)) {
                    $coreCache->readCache($langCacheFileName);
                } else if (!empty($content)) {
                    if (!empty($content)) {
                        ob_start();
                        print eval(" $content ");
                        ob_get_contents();
                        ob_end_clean();
                    }
                }
            }
        }
    }

    /**
     * Retourne un tableau contenant les langues disponibles.
     *
     * @return array
     */
    public static function &getLangList() {
        return CoreCache::getInstance()->getFileList("Engine\Translate", ".lang");
    }

    /**
     * Suppression du cache de traduction.
     *
     * @param string $pathLang
     */
    public static function removeCache($pathLang = "") {
        $langCacheFileName = self::getLangCacheFileName($pathLang);

        $coreCache = CoreCache::getInstance(CoreCache::SECTION_TRANSLATE);

        $langues = self::getLangList();
        foreach ($langues as $langue) {
            $coreCache->removeCache($langCacheFileName . $langue . ".lang.php");
        }
    }

    /**
     * Retourne le nom du fichier cache de langue.
     *
     * @param string $pathLang
     * @return string
     */
    private static function getLangCacheFileName($pathLang = "") {
        if (!empty($pathLang) && substr($pathLang, -1) !== DIRECTORY_SEPARATOR) {
            $pathLang .= DIRECTORY_SEPARATOR;
        }
        return str_replace(DIRECTORY_SEPARATOR, "_", $pathLang) . "lang_";
    }

    /**
     * Recherche et retourne l'extension de la langue.
     *
     * @return string
     */
    private static function &getLanguageExtension() {
        $validExtension = "";

        // Recherche de la langue du client
        $languageClient = explode(',', CoreRequest::getString("HTTP_ACCEPT_LANGUAGE", "", "SERVER"));
        $extension = strtolower(substr(trim($languageClient[0]), 0, 2));

        if (isset(self::$languageList[$extension])) {
            $validExtension = $extension;
        } else {
            // Recherche de l'URL
            if (!defined("TR_ENGINE_URL")) {
                $url = CoreRequest::getString("SERVER_NAME", "", "SERVER");
            } else {
                $url = TR_ENGINE_URL;
            }

            // Recherche de l'extension de URL
            preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);
            preg_match('/[^.]+\.[^.]+$/', $matches[1], $matches);
            preg_match('/[^.]+$/', $matches[0], $languageExtension);

            $extension = $languageExtension[0];

            if (isset(self::$languageList[$extension])) {
                $validExtension = $extension;
            }
        }
        return $validExtension;
    }

    /**
     * Retourne la langue la plus appropriée.
     *
     * @param string $extension l'extension de la langue détectée
     * @return string
     */
    private static function &getLanguage($extension) {
        $language = "";

        // Langage du client via le cookie de session
        if (CoreLoader::isCallable("CoreSession")) {
            $userLanguage = strtolower(trim(CoreSession::getInstance()->getUserInfos()->getLangue()));
        } else {
            $userLanguage = "";
        }

        if (self::isValid($userLanguage)) {
            // Langue du client via cookie valide
            $language = $userLanguage;
        } else {
            // Langue trouvée via l'extension
            $language = strtolower(trim(self::$languageList[$extension]));

            // Si la langue trouvé en invalide
            if (!self::isValid($language)) {
                // Utilisation de la langue par défaut du site
                if (CoreLoader::isCallable("CoreMain")) {
                    $language = CoreMain::getInstance()->getDefaultLanguage();
                } else {
                    $language = "";
                }

                // Malheureusement la langue par défaut est aussi invalide
                if (!self::isValid($language)) {
                    $language = "english";
                }
            }
        }
        return $language;
    }

    /**
     * Vérifie si le langage est disponible.
     *
     * @param string $language
     * @return boolean true langue disponible.
     */
    private static function isValid($language) {
        $rslt = false;

        if (!empty($language)) {
            $translatePath = CoreLoader::getFilePathFromTranslate("Engine", $language);
            $rslt = is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $translatePath . ".php");
        }
        return $rslt;
    }

    /**
     * Formate l'heure locale.
     *
     * @param string $extension l'extension de la langue détectée
     */
    private function configureLocale($extension) {
        if ($this->languageUsed === "french" && TR_ENGINE_PHP_OS === "WIN") {
            setlocale(LC_TIME, "french");
        } else if ($this->languageUsed === "french" && TR_ENGINE_PHP_OS === "BSD") {
            setlocale(LC_TIME, "fr_FR.ISO8859-1");
        } else if ($this->languageUsed === "french") {
            setlocale(LC_TIME, 'fr_FR');
        } else {
            // Tentative de formatage via le nom de la langue
            if (!setlocale(LC_TIME, $this->languageUsed)) {
                // Dernière tentative de formatage sous forme "fr_FR"
                setlocale(LC_TIME, strtolower($extension) . "_" . strtoupper($extension));
            }
        }
    }

    /**
     * Conversion des caratères spéciaux en entitiées UTF-8.
     * Ajout d'antislashes pour utilisation dans le cache.
     *
     * @param string
     * @return string
     */
    private static function &entitiesTranslate($text) {
        $text = ExecEntities::entitiesUtf8($text);
        //$text = ExecEntities::addSlashes($text);
        return $text;
    }

}
