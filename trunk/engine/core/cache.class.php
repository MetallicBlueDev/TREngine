<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de fichier cache.
 *
 * @author Sébastien Villemain
 */
class Core_Cache extends Cache_Model {

    /**
     * Section de configuration.
     *
     * @var string
     */
    const SECTION_CONFIGS = "configs";

    /**
     * Section temporaire (branche principal).
     *
     * @var string
     */
    const SECTION_TMP = "tmp";

    /**
     * Section temporaire des fichiers de journaux.
     *
     * @var string
     */
    const SECTION_LOGGER = "tmp/logger";

    /**
     * Section temporaire pour le cache des sessions.
     *
     * @var string
     */
    const SECTION_SESSIONS = "tmp/sessions";

    /**
     * Section temporaire pour le cache de traduction.
     *
     * @var string
     */
    const SECTION_TRANSLATE = "tmp/translate";

    /**
     * Section temporaire pour le cache des menus.
     *
     * @var string
     */
    const SECTION_MENUS = "tmp/menus";

    /**
     * Section temporaire pour le cache des modules.
     *
     * @var string
     */
    const SECTION_MODULES = "tmp/modules";

    /**
     * Section temporaire pour le cache des listes de fichiers.
     *
     * @var string
     */
    const SECTION_FILELISTER = "tmp/fileLister";

    /**
     * Section temporaire pour le cache des formulaires.
     *
     * @var string
     */
    const SECTION_FORMS = "tmp/forms";

    /**
     * Section temporaire pour le cache des blocks.
     *
     * @var string
     */
    const SECTION_BLOCKS = "tmp/blocks";

    /**
     * Utilisation des méthodes PHP.
     *
     * @var string
     */
    const MODE_PHP = "php";

    /**
     * Utilisation des transactions FTP.
     *
     * @var string
     */
    const MODE_FTP = "ftp";

    /**
     * Utilisation des transactions FTP sécurisées.
     *
     * @var string
     */
    const MODE_SFTP = "sftp";

    /**
     * Gestionnnaire de cache.
     *
     * @var Core_Cache
     */
    private static $coreCache = null;

    /**
     * Gestionnaire de fichier.
     *
     * @var Cache_Model
     */
    private static $selectedCache = null;

    /**
     * Chemin de la section actuelle.
     *
     * @var string
     */
    private static $currentSection = self::SECTION_TMP;

    /**
     * Etat des modes de gestion des fichiers.
     *
     * @var array
     */
    private static $modeActived = array(
        self::MODE_PHP => false,
        self::MODE_FTP => false,
        self::MODE_SFTP => false
    );

    /**
     * Réécriture du cache
     *
     * @var array
     */
    private static $writingCache = array();

    /**
     * Ecriture du cache a la suite
     *
     * @var array
     */
    private static $addCache = array();

    /**
     * Suppression du cache
     *
     * @var array
     */
    private static $removeCache = array();

    /**
     * Mise à jour de dernière modification du cache
     *
     * @var array
     */
    private static $updateCache = array();

    protected function __construct() {
        parent::__construct();

        $cacheClassName = "";
        $loaded = false;
        $cacheConfig = Core_Main::getInstance()->getConfigCache();

        if (!empty($cacheConfig) && isset($cacheConfig['type'])) {
            // Chargement des drivers pour la base
            $cacheClassName = "Base_" . ucfirst($cacheConfig['type']);
            $loaded = Core_Loader::classLoader($cacheClassName);
        }

        if (!$loaded) {
            Core_Secure::getInstance()->throwException("sqlType", null, array(
                $cacheConfig['type']));
        }

        if (!Core_Loader::isCallable($cacheClassName, "initializeBase")) {
            Core_Secure::getInstance()->throwException("sqlCode", null, array(
                $cacheClassName));
        }

        try {
            $this->selectedBase = new $cacheClassName();
            $this->selectedBase->initializeBase($cacheConfig);
        } catch (Exception $ex) {
            $this->selectedBase = null;
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    public function initializeCache(array &$ftp) {
        // NE RIEN FAIRE
        unset($ftp);
    }

    /**
     * Retourne l'instance du gestionnaire de cache.
     *
     * @return Core_Cache
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$coreCache;
    }

    /**
     * Vérification de l'instance du gestionnaire de cache.
     */
    public static function checkInstance() {
        if (self::$coreCache === null) {
            self::$coreCache = new self();
        }
    }

    /**
     * Change le chemin de la section.
     *
     * @param string $newSectionPath
     */
    public static function changeCurrentSection($newSectionPath = self::SECTION_TMP) {
        $newSectionPath = empty($newSectionPath) ? self::SECTION_TMP : $newSectionPath;
        $newSectionPath = str_replace("/", DIRECTORY_SEPARATOR, $newSectionPath);

        if (substr($newSectionPath, -1) === DIRECTORY_SEPARATOR) {
            $newSectionPath = substr($newSectionPath, 0, -1);
        }

        self::$currentSection = $newSectionPath;
    }

    /**
     * Retourne le chemin de la section actuel.
     *
     * @return string Section courante
     */
    public static function &getCurrentSectionName() {
        if (empty(self::$currentSection)) {
            self::changeCurrentSection();
        }
        return self::$currentSection;
    }

    /**
     * Execute la routine du cache
     */
    public static function valideCacheBuffer() {
        // Si le cache a besoin de générer une action
        if (self::cacheRequired(self::$removeCache) || self::cacheRequired(self::$writingCache) || self::cacheRequired(self::$addCache) || self::cacheRequired(self::$updateCache)) {
            // Protocole a utiliser
            $protocol = self::getExecProtocol();

            if ($protocol !== null) {
                // Suppression de cache demandée
                if (self::cacheRequired(self::$removeCache)) {
                    foreach (self::$removeCache as $dir => $timeLimit) {
                        $protocol->removeCache($dir, $timeLimit);
                    }
                }

                // Ecriture de cache demandée
                if (self::cacheRequired(self::$writingCache)) {
                    foreach (self::$writingCache as $path => $content) {
                        $protocol->writingCache($path, $content, true);
                    }
                }

                // Ecriture à la suite de cache demandée
                if (self::cacheRequired(self::$addCache)) {
                    foreach (self::$addCache as $path => $content) {
                        $protocol->writingCache($path, $content, false);
                    }
                }

                // Mise à jour de cache demandée
                if (self::cacheRequired(self::$updateCache)) {
                    foreach (self::$updateCache as $path => $updateTime) {
                        $protocol->touchCache($path, $updateTime);
                    }
                }
            }
            // Destruction du gestionnaire
            unset($protocol);
        }
    }

    /**
     * Ecriture du fichier cache
     *
     * @param $path chemin complet
     * @param $content donnée a écrire
     * @param $overWrite boolean true réécriture complete, false écriture a la suite
     */
    public static function writingCache($path, $content, $overWrite = true) {
        if (is_array($content)) {
            $content = self::serializeData($content);
        }
        // Mise en forme de la clé
        $key = self::encodePath(self::getCurrentSectionPath() . DIRECTORY_SEPARATOR . $path);
        // Ajout dans le cache
        if ($overWrite) {
            self::$writingCache[$key] = $content;
        } else {
            self::$addCache[$key] = $content;
        }
    }

    /**
     * Supprime un fichier ou supprime tout fichier trop vieux
     *
     * @param $dir chemin vers le fichier ou le dossier
     * @param $timeLimit limite de temps
     */
    public static function removeCache($dir, $timeLimit = 0) {
        // Configuration du path
        if (!empty($dir)) {
            $dir = "/" . $dir;
        }
        $dir = self::encodePath(self::getCurrentSectionPath() . $dir);
        self::$removeCache[$dir] = $timeLimit;
    }

    /**
     * Parcours récursivement le dossier cache courant afin de supprimer les fichiers trop vieux
     * Nettoie le dossier courant du cache
     *
     * @param $timeLimit La limite de temps
     */
    public static function cleanCache($timeLimit) {
        // Vérification de la validité du checker
        if (!self::checked($timeLimit)) {
            // Mise à jour ou creation du fichier checker
            self::touchChecker();
            // Suppression du cache périmé
            self::removeCache("", $timeLimit);
        }
    }

    /**
     * Mise à jour de la date de dernière modification
     *
     * @param $path chemin vers le fichier cache
     */
    public static function touchCache($path) {
        self::$updateCache[self::encodePath(self::getCurrentSectionPath() . DIRECTORY_SEPARATOR . $path)] = time();
    }

    /**
     * Vérifie si le fichier est en cache
     *
     * @param $path chemin vers le fichier cache
     * @return boolean true le fichier est en cache
     */
    public static function cached($path) {
        return is_file(self::getPath($path));
    }

    /**
     * Date de dernière modification
     *
     * @param $path chemin vers le fichier cache
     * @return int Unix timestamp ou false
     */
    public static function cacheMTime($path) {
        return filemtime(self::getPath($path));
    }

    /**
     * Vérifie la présence du checker et sa validité
     *
     * @return boolean true le checker est valide
     */
    public static function checked($timeLimit = 0) {
        $rslt = false;

        if (self::cached("checker.txt")) {
            // Si on a demandé un comparaison de temps
            if ($timeLimit > 0) {
                if ($timeLimit < self::checkerMTime()) {
                    $rslt = true;
                }
            } else {
                $rslt = true;
            }
        }
        return $rslt;
    }

    /**
     * Serialize la variable en chaine de caractères pour une mise en cache
     *
     * @param string $data ($data = "data") or array $data ($data = array("name" => "data"))
     * @param string $lastKey clès supplementaire
     * @return string
     */
    public static function &serializeData($data, $lastKey = "") {
        $content = "";
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $lastKey .= "['" . $key . "']";
                    $content .= self::serializeData($value, $lastKey);
                } else {
                    $content .= self::serializeVariable($lastKey . "['" . $key . "']", $value);
                }
            }
        } else {
            if (!empty($lastKey)) {
                $lastKey = "['" . $lastKey . "']";
            }
            $content .= self::serializeVariable($lastKey, $data);
        }
        return $content;
    }

    /**
     * Mise à jour du checker
     */
    public static function touchChecker() {
        if (!self::cached("checker.txt")) {
            self::writingChecker();
        } else {
            self::touchCache("checker.txt");
        }
    }

    /**
     * Date de dernière modification du checker
     *
     * @return int Unix timestamp ou false
     */
    public static function checkerMTime() {
        return self::cacheMTime("checker.txt");
    }

    /**
     * Retourne le chemin complet vers le fichier cache
     *
     * @param $path chemin du fichier
     * @return string chemin complet
     */
    public static function &getPath($path) {
        $path = TR_ENGINE_DIR . DIRECTORY_SEPARATOR . self::$currentSection . DIRECTORY_SEPARATOR . $path;
        return $path;
    }

    /**
     * Capture le cache ciblé dans un tableau
     *
     * @param $path Chemin du cache
     * @param $vars array tableau supplementaire contenant des variables pour résoudre les problèmes de visiblilité (par exemple)
     * @return string
     */
    public static function &getCache($path, $vars = array()) {
        // Réglage avant capture
        $variableName = self::$currentSection;
        // Rend la variable global a la fonction
        ${$variableName} = "";

        // Capture du fichier
        if (self::cached($path)) {
            require(self::getPath($path));
        }
        return ${$variableName};
    }

    /**
     * Test si le chemin est celui d'un dossier
     *
     * @param $path
     * @return boolean true c'est un dossier
     */
    public static function &isDir($path) {
        $pathIsDir = false;

        if (substr($path, -1) === DIRECTORY_SEPARATOR) {
            // Nettoyage du path qui est enfaite un dir
            $path = substr($path, 0, -1);
            $pathIsDir = true;
        } else {
            // Recherche du bout du path
            $last = "";
            $pos = strrpos(DIRECTORY_SEPARATOR, $path);
            if (!is_bool($pos)) {
                $last = substr($path, $pos);
            } else {
                $last = $path;
            }

            // Si ce n'est pas un fichier (avec ext.)
            if (strpos($last, ".") === false) {
                $pathIsDir = true;
            }
        }
        return $pathIsDir;
    }

    /**
     * Ecriture des entêtes de fichier
     *
     * @param $pathFile
     * @param $content
     * @return string $content
     */
    public static function &getHeader($pathFile, $content) {
        $ext = substr($pathFile, -3);
        // Entête des fichier PHP
        if ($ext === "php") {
            // Recherche du dossier parent
            $dirBase = "";
            $nbDir = count(explode(DIRECTORY_SEPARATOR, $pathFile));
            for ($i = 1; $i < $nbDir; $i++) {
                $dirBase .= ".." . DIRECTORY_SEPARATOR;
            }

            // Ecriture de l'entête
            $content = "<?php\n"
            . "if (!defined(\"TR_ENGINE_INDEX\")){"
            . "if(!class_exists(\"Core_Secure\")){"
            . "include(\"" . $dirBase . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php\");"
            . "}Core_Secure::checkInstance();}"
            . "// Generated on " . date('Y-m-d H:i:s') . "\n"
            . $content
            . "\n?>";
        }
        return $content;
    }

    /**
     * Active les modes de cache disponible
     *
     * @param $modes array
     */
    public static function setModeActived(array $modes = array()) {
        foreach ($modes as $mode) {
            if (isset(self::$modeActived[$mode])) {
                self::$modeActived[$mode] = true;
            }
        }
    }

    /**
     * Retourne les modes d'écriture actif
     *
     * @return array sous la forme array('php' => true, 'ftp' => false)
     */
    public static function getModeActived() {
        return self::$modeActived;
    }

    /**
     * Récupération des données du FTP
     *
     * @param array
     */
    public static function getFtp() {
        return self::$ftp;
    }

    /**
     * Retourne le listing avec uniquement les fichiers et dossiers présent
     * Un filtre automatique est appliqué sur les éléments tel que "..", "." ou encore "index.html"...
     *
     * @param $dirPath
     * @return array
     */
    public static function &listNames($dirPath) {
        $dirList = array();

        self::changeCurrentSection(self::SECTION_FILELISTER);
        $fileName = str_replace(DIRECTORY_SEPARATOR, "_", $dirPath) . ".php";

        if (Core_Cache::cached($fileName)) {
            $dirList = self::getCache($fileName);
        } else {
            $protocol = self::getExecProtocol();
            if ($protocol !== null) {
                $dirList = $protocol->getFileNames($dirPath);
                self::writingCache($fileName, $dirList);
            }
        }
        return $dirList;
    }

    /**
     * Démarre et retourne le protocole du cache
     *
     * @return Cache_Model
     */
    private static function &getExecProtocol() {
        if (self::$selectedCache === null) {
            if (self::$modeActived['ftp']) {
                // Démarrage du gestionnaire FTP
                self::$selectedCache = new Cache_Ftp();
            } else if (self::$modeActived['sftp']) {
                // Démarrage du gestionnaire SFTP
                self::$selectedCache = new Cache_Sftp();
            } else {
                // Démarrage du gestionnaire de fichier
                self::$selectedCache = new Cache_Php();
            }

            // Si il y a un souci, on démarre par défaut le gestionnaire de fichier
            if (!self::$selectedCache->canUse()) {
                // Démarrage du gestionnaire de fichier
                self::$selectedCache = new Cache_Php();
            }
        }
        return self::$selectedCache;
    }

    /**
     * Retourne le chemin de la section courante
     *
     * @return string
     */
    private static function &getCurrentSectionPath($dir) {
        $dir = self::$currentSection . DIRECTORY_SEPARATOR . $dir;
        return $dir;
    }

    /**
     * Recherche si le cache a besoin de génére une action
     *
     * @param array $required
     * @return boolean true action demandée
     */
    private static function cacheRequired(array $required) {
        return !empty($required);
    }

    /**
     * Serialize la variable en chaine de caractères pour une mise en cache
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    private static function &serializeVariable($key, $value) {
        $content .= "$" . self::getCurrentSectionName() . $key . " = \"" . Exec_Entities::addSlashes($value) . "\"; ";
        return $content;
    }

    /**
     * Ecriture du checker
     */
    private static function writingChecker() {
        self::writingCache("checker.txt", "1");
    }

}
