<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de traduction de texte.
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
     * @var array
     */
    private $languageUsed = array();

    /**
     * Mémorise (temporairement) les données de tradution.
     *
     * @var array
     */
    private $cache = array();

    /**
     * Mémorise les fichiers traduit.
     *
     * @var array
     */
    private $translated = array();

    /**
     * Nouveau gestionnaire.
     */
    private function __construct() {
        $this->languageUsed['extension'] = self::getLanguageExtension();
        $this->languageUsed['name'] = $this->getLanguageTranslated();

        $this->configureLocale();
    }

    /**
     * Retourne le gestionnaire de traduction.
     *
     * @return CoreTranslate
     */
    public static function &getInstance(): CoreTranslate {
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
    public function &getCurrentLanguage(): string {
        return $this->languageUsed['name'];
    }

    /**
     * Retourne l'extension de la langue courante.
     *
     * @return string
     */
    public function &getCurrentLanguageExtension(): string {
        return $this->languageUsed['extension'];
    }

    /**
     * Traduction de la page via le fichier.
     *
     * @param string $pathLang chemin du fichier de traduction.
     */
    public function translate(string $pathLang) {
        if (!$this->translated($pathLang)) {
            $this->fireTranslation($pathLang);
            $this->setTranslated($pathLang);
        }
    }

    /**
     * Retourne un tableau contenant les langues disponibles.
     *
     * @return array
     */
    public static function &getLangList(): array {
        return CoreCache::getInstance()->getFileList("Engine\Translate", ".lang");
    }

    /**
     * Suppression du cache de traduction.
     *
     * @param string $pathLang
     */
    public static function removeCache(string $pathLang = "") {
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_TRANSLATE);
        $langCacheFileName = self::getLangCachePrefixFileName($pathLang);
        $langues = self::getLangList();

        foreach ($langues as $langue) {
            $coreCache->removeCache($langCacheFileName . $langue . ".lang.php");
        }
    }

    /**
     * Détermine si la traduction a déjà été appliquée.
     *
     * @param string $pathLang
     * @return bool
     */
    private function translated(string $pathLang): bool {
        return isset($this->translated[$pathLang]);
    }

    /**
     * Signale que la traduction a déjà été appliquée.
     *
     * @param string $pathLang
     */
    private function setTranslated(string $pathLang) {
        $this->translated[$pathLang] = true;
    }

    /**
     * Procédure de traduction.
     *
     * @param string $pathLang
     */
    private function fireTranslation(string $pathLang) {
        if (!$this->translateWithCache($pathLang)) {
            $content = $this->getTranslation($pathLang);

            if (!empty($content)) {
                $this->createTranslationCache($pathLang, $content);
                $this->translateWithBuffer($content);
            }
        }
    }

    /**
     * Traduction via le cache.
     *
     * @param string $pathLang
     * @return bool
     */
    private function translateWithCache(string $pathLang): bool {
        $translated = false;

        if (CoreLoader::isCallable("CoreCache")) {
            $coreCache = CoreCache::getInstance(CoreCache::SECTION_TRANSLATE);
            $langCacheFileName = $this->getLangCacheFileName($pathLang);

            if ($coreCache->cached($langCacheFileName)) {
                $langOriginalPath = CoreLoader::getTranslateAbsolutePath($pathLang);

                if ($coreCache->getCacheMTime($langCacheFileName) >= filemtime($langOriginalPath)) {
                    $coreCache->readCache($langCacheFileName);
                    $translated = true;
                }
            }
        }
        return $translated;
    }

    /**
     * Retourne le nom du fichier cache pour la traduction.
     *
     * @param string $pathLang
     * @return string
     */
    private function getLangCacheFileName(string $pathLang): string {
        return self::getLangCachePrefixFileName($pathLang) . $this->getCurrentLanguage() . ".php";
    }

    /**
     * Retourne les données de traduction.
     *
     * @param string $pathLang
     * @return string
     */
    private function getTranslation(string $pathLang): string {
        $content = "";

        // Initialisation de la variable de cache
        $this->cache = array();

        if (CoreLoader::translateLoader($pathLang) && !empty($this->cache)) {
            $content = $this->serializeTranslation();

            // Vide la variable de cache après utilisation
            $this->cache = array();
        }
        return $content;
    }

    /**
     * Retourne les données de tradution pour mise en cache.
     *
     * @return string
     */
    private function serializeTranslation(): string {
        $content = "";

        foreach ($this->cache as $key => $value) {
            if (!empty($key) && !empty($value)) {
                $content .= "define(\"" . $key . "\",\"" . self::entitiesTranslate($value) . "\");";
            }
        }
        return $content;
    }

    /**
     * Création du cache de traduction.
     *
     * @param string $pathLang
     * @param string $content
     */
    private function createTranslationCache(string $pathLang, string $content) {
        if (CoreLoader::isCallable("CoreCache")) {
            $langCacheFileName = $this->getLangCacheFileName($pathLang);
            CoreCache::getInstance(CoreCache::SECTION_TRANSLATE)->writeCache($langCacheFileName, $content);
        }
    }

    /**
     * Traduction via mémoire tampon et évaluation à la volée.
     *
     * @param string $content
     */
    private function translateWithBuffer(string $content) {
        ob_start();
        print eval(" $content ");
        ob_get_contents();
        ob_end_clean();
    }

    /**
     * Retourne le nom du fichier cache de langue.
     *
     * @param string $pathLang
     * @return string
     */
    private static function getLangCachePrefixFileName(string $pathLang = ""): string {
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
    private static function &getLanguageExtension(): string {
        $validExtension = "";

        // Recherche de la langue du client
        $acceptedLanguages = explode(',', CoreRequest::getString("HTTP_ACCEPT_LANGUAGE", "", "SERVER"));
        $extension = strtolower(substr(trim($acceptedLanguages[0]), 0, 2));

        if (self::canUseExtension($extension)) {
            $validExtension = $extension;
        } else {
            // Recherche de l'URL
            if (!defined("TR_ENGINE_URL")) {
                $url = CoreRequest::getString("SERVER_NAME", "", "SERVER");
            } else {
                $url = TR_ENGINE_URL;
            }

            // Recherche de l'extension de URL
            $matches = array();
            preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);
            preg_match('/[^.]+\.[^.]+$/', $matches[1], $matches);
            preg_match('/[^.]+$/', $matches[0], $matches);

            $extension = $matches[0];

            if (self::canUseExtension($extension)) {
                $validExtension = $extension;
            }
        }
        return $validExtension;
    }

    /**
     * Formate l'heure locale.
     */
    private function configureLocale() {
        if ($this->getCurrentLanguage() === "french" && TR_ENGINE_PHP_OS === "WIN") {
            setlocale(LC_TIME, "french");
        } else if ($this->getCurrentLanguage() === "french" && TR_ENGINE_PHP_OS === "BSD") {
            setlocale(LC_TIME, "fr_FR.ISO8859-1");
        } else if ($this->getCurrentLanguage() === "french") {
            setlocale(LC_TIME, 'fr_FR');
        } else {
            // Tentative de formatage via le nom de la langue
            if (!setlocale(LC_TIME, $this->getCurrentLanguage())) {
                // Dernière tentative de formatage sous forme "fr_FR"
                setlocale(LC_TIME, strtolower($this->getCurrentLanguageExtension()) . "_" . strtoupper($this->getCurrentLanguageExtension()));
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
    private static function &entitiesTranslate(string $text): string {
        $text = ExecEntities::entitiesUtf8($text);
        //$text = ExecEntities::addSlashes($text);
        return $text;
    }

    /**
     * Détermine si l'extension de langue est connue.
     *
     * @param string $extension
     * @return bool
     */
    private static function canUseExtension(string $extension): bool {
        return isset(self::$languageList[$extension]);
    }

    /**
     * Retourne la langue la plus appropriée.
     *
     * @return string
     */
    private function &getLanguageTranslated(): string {
        $language = self::getLanguageBySession();

        if (empty($language)) {
            $language = self::getLanguageByExtension($this->getCurrentLanguageExtension());

            if (empty($language)) {
                $language = self::getLanguageByDefault();
            }
        }
        return $language;
    }

    /**
     * Retourne la langue du client (via le cookie de session).
     *
     * @return string
     */
    private static function &getLanguageBySession(): string {
        $language = "";

        if (CoreLoader::isCallable("CoreSession")) {
            $language = strtolower(trim(CoreSession::getInstance()->getUserInfos()->getLangue()));

            if (!self::isValid($language)) {
                $language = "";
            }
        }
        return $language;
    }

    /**
     * Retourne la langue trouvée via l'extension.
     *
     * @param string $extension
     * @return string
     */
    private static function &getLanguageByExtension(string $extension): string {
        $language = strtolower(trim(self::$languageList[$extension]));

        if (!self::isValid($language)) {
            $language = "";
        }
        return $language;
    }

    /**
     * Retourne la langue par défaut.
     *
     * @return string
     */
    private static function &getLanguageByDefault(): string {
        $language = "";

        if (CoreLoader::isCallable("CoreMain")) {
            // Utilisation de la langue par défaut du site
            $language = CoreMain::getInstance()->getDefaultLanguage();
        }

        // Malheureusement la langue par défaut est aussi invalide
        if (empty($language) || !self::isValid($language)) {
            $language = "english";
        }
        return $language;
    }

    /**
     * Vérifie si le langage est disponible.
     *
     * @param string $language
     * @return bool true langue disponible.
     */
    private static function isValid(string $language): bool {
        $rslt = false;

        if (!empty($language)) {
            $translatePath = CoreLoader::getFilePathFromTranslate("Engine", $language);
            $rslt = is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $translatePath . ".php");
        }
        return $rslt;
    }

}
