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
    private $selectedCache = null;

    /**
     * Chemin de la section actuelle.
     *
     * @var string
     */
    private $currentSection = self::SECTION_TMP;

    /**
     * Réécriture du cache.
     *
     * @var array
     */
    private $writingCache = array();

    /**
     * Ecriture du cache à la suite.
     *
     * @var array
     */
    private $writingNewCache = array();

    /**
     * Suppression du cache
     *
     * @var array
     */
    private $removeCache = array();

    /**
     * Mise à jour de dernière modification du cache
     *
     * @var array
     */
    private $touchCache = array();

    protected function __construct() {
        parent::__construct();

        $cacheClassName = "";
        $loaded = false;
        $cacheConfig = Core_Main::getInstance()->getConfigCache();

        // Mode par défaut
        if (empty($cacheConfig) || !isset($cacheConfig['type'])) {
            $cacheConfig['type'] = "php";
        }

        // Chargement des drivers pour le cache
        $cacheClassName = "Cache_" . ucfirst($cacheConfig['type']);
        $loaded = Core_Loader::classLoader($cacheClassName);

        if (!$loaded) {
            Core_Secure::getInstance()->throwException("cacheType", null, array(
                $cacheConfig['type']));
        }

        if (!Core_Loader::isCallable($cacheClassName, "initialize")) {
            Core_Secure::getInstance()->throwException("cacheCode", null, array(
                $cacheClassName));
        }

        try {
            $this->selectedCache = new $cacheClassName();
            $this->selectedCache->initialize($cacheConfig);
        } catch (Exception $ex) {
            $this->selectedCache = null;
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    public function initialize(array &$cache) {
        // NE RIEN FAIRE
        unset($cache);
    }

    public function __destruct() {
        $this->selectedCache = null;
    }

    /**
     * Retourne l'instance du gestionnaire de cache.
     *
     * @return Core_Cache
     */
    public static function &getInstance($newSectionPath = null) {
        self::checkInstance();

        if ($newSectionPath !== null) {
            self::$coreCache->changeCurrentSection($newSectionPath);
        }
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
     * Etablie une connexion au serveur.
     */
    public function netConnect() {
        $this->selectedCache->netConnect();
    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function netConnected() {
        return $this->selectedCache !== null && $this->selectedCache->netConnected();
    }

    /**
     * Déconnexion du serveur.
     */
    public function netDeconnect() {
        $this->selectedCache->netDeconnect();
    }

    /**
     * Sélectionne l'utilisateur.
     *
     * @return boolean
     */
    public function &netSelect() {
        return $this->selectedCache->netSelect();
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost() {
        return $this->selectedCache->getTransactionHost();
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser() {
        return $this->selectedCache->getTransactionUser();
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass() {
        return $this->selectedCache->getTransactionPass();
    }

    /**
     * Retourne le type de base (exemple ftp).
     *
     * @return string
     */
    public function &getTransactionType() {
        return $this->selectedCache->getTransactionType();
    }

    /**
     * Retourne le port.
     *
     * @return int
     */
    public function &getServerPort() {
        return $this->selectedCache->getServerPort();
    }

    /**
     * Retourne le chemin racine.
     *
     * @return string
     */
    public function &getServerRoot() {
        return $this->selectedCache->getServerRoot();
    }

    /**
     * Affecte le chemin racine.
     *
     * @param string $newRoot
     */
    public function setServerRoot(&$newRoot) {
        $this->selectedCache->setServerRoot($newRoot);
    }

    /**
     * Ecriture du fichier cache.
     *
     * @param string $path chemin vers le fichier cache
     * @param string $content contenu du fichier cache
     * @param boolean $overWrite écrasement du fichier
     */
    public function writingCache($path, $content, $overWrite = true) {
        if (is_array($content)) {
            $content = $this->serializeData($content);
        }

        // Mise en forme de la clé
        $key = $this->getCurrentSectionPath($path);

        // Ajout dans le cache
        if ($overWrite) {
            $this->writingCache[$key] = $content;
        } else {
            $this->writingNewCache[$key] = $content;
        }
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache($path) {
        $this->touchCache[$this->getCurrentSectionPath($path)] = time();
    }

    /**
     * Supprime tous fichiers trop vieux.
     *
     * @param string $dir chemin vers le fichier ou le dossier
     * @param string $timeLimit limite de temps
     */
    public function removeCache($dir, $timeLimit = 0) {
        $this->removeCache[$this->getCurrentSectionPath($dir)] = $timeLimit;
    }

    /**
     * Retourne la liste des fichiers et dossiers présents.
     * Un filtre automatique est appliqué sur les éléments tel que "..", "." ou encore "index.html"...
     *
     * @param string $dirPath
     * @return array
     */
    public function &getFileNames($dirPath) {
        $dirList = array();

        $this->changeCurrentSection(self::SECTION_FILELISTER);
        $fileName = str_replace(DIRECTORY_SEPARATOR, "_", $dirPath) . ".php";

        if ($this->cached($fileName)) {
            $dirList = $this->getCache($fileName);
        } else {
            $dirList = $this->selectedCache->getFileNames($dirPath);
            $this->writingCache($fileName, $dirList);
        }
        return $dirList;
    }

    /**
     * Retourne la date de dernière modification du fichier.
     *
     * @param string $path
     * @return int
     */
    public function &getCacheMTime($path) {
        return $this->selectedCache->getCacheMTime($path);
    }

    /**
     * Retourne la liste des fichiers trouvés avec l'extension demandé.
     *
     * @param string $dirPath
     * @param string $extension
     * @return array
     */
    public function &getClassNames($dirPath, $extension = ".class") {
        $names = array();
        $files = $this->getFileNames($dirPath);

        foreach ($files as $fileName) {
            $pos = strpos($fileName, $extension);

            if ($pos !== false && $pos > 0) {
                $names[] = substr($fileName, 0, $pos);
            }
        }
        return $names;
    }

    /**
     * Retourne la liste des types de base supporté
     *
     * @return array
     */
    public function &getCacheList() {
        return $this->getClassNames("engine/cache");
    }

    /**
     * Change le chemin de la section.
     *
     * @param string $newSectionPath
     */
    public function changeCurrentSection($newSectionPath = self::SECTION_TMP) {
        $newSectionPath = empty($newSectionPath) ? self::SECTION_TMP : $newSectionPath;
        $newSectionPath = str_replace("/", DIRECTORY_SEPARATOR, $newSectionPath);

        if (substr($newSectionPath, -1) === DIRECTORY_SEPARATOR) {
            $newSectionPath = substr($newSectionPath, 0, -1);
        }

        $this->currentSection = $newSectionPath;
    }

    /**
     * Serialise la variable en chaine de caractères pour une mise en cache.
     *
     * @param string $data ($data = "data") or array $data ($data = array("name" => "data"))
     * @param string $lastKey clé supplémentaire
     * @return string
     */
    public function &serializeData($data, $lastKey = "") {
        $content = "";

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $lastKey .= "['" . $key . "']";
                    $content .= $this->serializeData($value, $lastKey);
                } else {
                    $content .= $this->serializeVariable($lastKey . "['" . $key . "']", $value);
                }
            }
        } else {
            if (!empty($lastKey)) {
                $lastKey = "['" . $lastKey . "']";
            }

            $content .= $this->serializeVariable($lastKey, $data);
        }
        return $content;
    }

    /**
     * Détermine si le fichier est en cache.
     *
     * @param $path chemin vers le fichier cache
     * @return boolean true le fichier est en cache
     */
    public function cached($path) {
        return is_file($this->getCurrentSectionPath($path, true));
    }

    /**
     * Capture le cache ciblé dans un tableau.
     *
     * @param $path Chemin du cache
     * @param $vars array tableau supplementaire contenant des variables pour résoudre les problèmes de visiblilité (par exemple)
     * @return string
     */
    public function &getCache($path, $vars = array()) {
        // Réglage avant capture
        $variableName = $this->currentSection;

        // Rend la variable global a la fonction
        ${$variableName} = "";

        // Capture du fichier
        if ($this->cached($path)) {
            require($this->getCurrentSectionPath($path, true));
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
     * Parcours récursivement le dossier cache courant afin de supprimer les fichiers trop vieux.
     * Nettoie le dossier courant du cache.
     *
     * @param $timeLimit la limite de temps
     */
    public function cleanCache($timeLimit) {
        // Vérification de la validité du checker
        if (!self::checked($timeLimit)) {
            // Mise à jour ou creation du fichier checker
            self::touchChecker();
            // Suppression du cache périmé
            self::removeCache("", $timeLimit);
        }
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
     * Vérifie la présence du checker et sa validité
     *
     * @return boolean true le checker est valide
     */
    public static function checked($timeLimit = 0) {
        $rslt = false;

        if ($this->cached("checker.txt")) {
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
     * Mise à jour du checker
     */
    public static function touchChecker() {
        if (!$this->cached("checker.txt")) {
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
        return $this->getCacheMTime("checker.txt");
    }

    /**
     * Retourne le chemin de la section courante
     *
     * @param string $dir
     * @param boolean $includeRoot
     * @return string
     */
    private function &getCurrentSectionPath($dir, $includeRoot = false) {
        $dir = $this->currentSection . DIRECTORY_SEPARATOR . $dir;

        if ($includeRoot) {
            $dir = TR_ENGINE_DIR . DIRECTORY_SEPARATOR . $dir;
        }
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
        if (empty($this->currentSection)) {
            $this->changeCurrentSection();
        }

        $prefix = str_replace(DIRECTORY_SEPARATOR, "_", $this->currentSection);
        $content .= "$" . $prefix . $key . " = \"" . Exec_Entities::addSlashes($value) . "\"; ";
        return $content;
    }

    /**
     * Ecriture du checker
     */
    private static function writingChecker() {
        self::writingCache("checker.txt", "1");
    }

}
