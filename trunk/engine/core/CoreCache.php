<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de fichier cache.
 *
 * @author Sébastien Villemain
 */
class CoreCache extends CacheModel {

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
     * @var string
     */
    const CHECKER_FILENAME = "checker.txt";

    /**
     * Gestionnnaire de cache.
     *
     * @var CoreCache
     */
    private static $coreCache = null;

    /**
     * Gestionnaire de fichier.
     *
     * @var CacheModel
     */
    private $selectedCache = null;

    /**
     * Chemin de la section actuelle.
     *
     * @var string
     */
    private $currentSection = self::SECTION_TMP;

    /**
     * Réécriture du complète du cache.
     *
     * @var array
     */
    private $overwriteCache = array();

    /**
     * Ecriture du cache à la suite.
     *
     * @var array
     */
    private $writeCache = array();

    /**
     * Suppression du cache.
     *
     * @var array
     */
    private $removeCache = array();

    /**
     * Mise à jour de dernière modification du cache.
     *
     * @var array
     */
    private $touchCache = array();

    protected function __construct() {
        parent::__construct();

        $cacheClassName = "";
        $loaded = false;
        $cacheConfig = CoreMain::getInstance()->getConfigCache();

        // Mode par défaut
        if (empty($cacheConfig) || !isset($cacheConfig['type'])) {
            $cacheConfig['type'] = "php";
        }

        // Chargement des drivers pour le cache
        $cacheClassName = "Cache" . ucfirst($cacheConfig['type']);
        $loaded = CoreLoader::classLoader($cacheClassName);

        if (!$loaded) {
            CoreSecure::getInstance()->throwException("cacheType", null, array(
                $cacheConfig['type']));
        }

        if (!CoreLoader::isCallable($cacheClassName, "initialize")) {
            CoreSecure::getInstance()->throwException("cacheCode", null, array(
                $cacheClassName));
        }

        try {
            $this->selectedCache = new $cacheClassName();
            $this->selectedCache->initialize($cacheConfig);
        } catch (Exception $ex) {
            $this->selectedCache = null;
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
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
     * @return CoreCache
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
            self::$coreCache = new CoreCache();
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
     * @param boolean $overwrite écrasement du fichier
     */
    public function writeCache($path, $content, $overwrite = true) {
        if (is_array($content)) {
            $content = $this->serializeData($content);
        }

        // Mise en forme de la clé
        $key = $this->getCurrentSectionPath($path);

        // Ajout dans le cache
        if ($overwrite) {
            $this->overwriteCache[$key] = $content;
        } else {
            $this->writeCache[$key] = $content;
        }
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache($path, $updateTime = 0) {
        if ($updateTime <= 0) {
            $updateTime = time();
        }

        $this->touchCache[$this->getCurrentSectionPath($path)] = $updateTime;
    }

    /**
     * Supprime tous fichiers trop vieux.
     *
     * @param string $path chemin vers le fichier ou le dossier
     * @param string $timeLimit limite de temps
     */
    public function removeCache($path, $timeLimit = 0) {
        $this->removeCache[$this->getCurrentSectionPath($path)] = $timeLimit;
    }

    /**
     * Retourne la liste des fichiers et dossiers présents.
     * Un filtre automatique est appliqué sur les éléments tel que "..", "." ou encore "index.html"...
     *
     * @param string $path
     * @return array
     */
    public function &getNameList($path) {
        $dirList = array();

        $this->changeCurrentSection(self::SECTION_FILELISTER);
        $fileName = str_replace(DIRECTORY_SEPARATOR, "_", $path) . ".php";

        if ($this->cached($fileName)) {
            $dirList = $this->readCache($fileName);
        } else {
            $dirList = $this->selectedCache->getNameList($path);
            $this->writeCache($fileName, $dirList);
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
        return $this->selectedCache->getCacheMTime($this->getCurrentSectionPath($path));
    }

    /**
     * Retourne la liste des fichiers trouvés avec l'extension demandé.
     *
     * @param string $dirPath
     * @param string $extension
     * @return array
     */
    public function &getFileList($dirPath, $extension = ".class") {
        $names = array();
        $files = $this->getNameList($dirPath);

        foreach ($files as $fileName) {
            $pos = strpos($fileName, $extension);

            if ($pos !== false && $pos > 0) {
                $names[] = substr($fileName, 0, $pos);
            }
        }
        return $names;
    }

    /**
     * Retourne la liste des types de cache supporté.
     *
     * @return array
     */
    public function &getCacheList() {
        return $this->getFileList("engine/cache");
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

        if ($this->currentSection !== $newSectionPath) {
            $this->currentSection = $newSectionPath;
        }
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
                    $lastKey = "['" . $key . "']";
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
     * Lecture du cache ciblé dans un tableau.
     *
     * @param $path Chemin du cache
     * @param $vars array tableau supplementaire contenant des variables pour résoudre les problèmes de visiblilité (par exemple)
     * @return string
     */
    public function &readCache($path, $vars = array()) {
        $variableName = $this->getVariableName();

        // Rend la variable global a la fonction
        ${$variableName} = "";

        // Capture du fichier
        if ($this->cached($path)) {
            require $this->getCurrentSectionPath($path, true);
        }
        return ${$variableName};
    }

    /**
     * Parcours récursivement le dossier du cache actuel afin de supprimer les fichiers trop vieux.
     * (Nettoie le dossier courant du cache).
     *
     * @param $timeLimit la limite de temps
     */
    public function cleanCache($timeLimit) {
        $exist = $this->cached(self::CHECKER_FILENAME);
        $valid = false;

        if ($exist) {
            // Vérification de la validité du checker
            if ($timeLimit > 0) {
                if ($timeLimit < $this->getCacheMTime(self::CHECKER_FILENAME)) {
                    $valid = true;
                }
            } else {
                $valid = true;
            }
        }

        if (!$valid) {
            // Mise à jour ou creation du fichier checker
            if (!$exist) {
                $this->writeCache(self::CHECKER_FILENAME, "1");
            } else {
                $this->touchCache(self::CHECKER_FILENAME);
            }

            // Suppression du cache périmé
            $this->removeCache("", $timeLimit);
        }
    }

    /**
     * Exécute la routine du cache.
     */
    public function workspaceCache() {
        // Si le cache a besoin de générer une action
        if (!empty($this->removeCache)) {
            // Suppression de cache demandée
            foreach ($this->removeCache as $path => $timeLimit) {
                $this->selectedCache->removeCache($path, $timeLimit);
            }
        }

        if (!empty($this->writeCache)) {
            // Ecriture de cache demandée
            foreach ($this->writeCache as $path => $content) {
                $this->selectedCache->writeCache($path, $content, false);
            }
        }

        if (!empty($this->overwriteCache)) {
            // Ecriture à la suite de cache demandée
            foreach ($this->overwriteCache as $path => $content) {
                $this->selectedCache->writeCache($path, $content, true);
            }
        }

        if (!empty($this->touchCache)) {
            // Mise à jour de cache demandée
            foreach ($this->touchCache as $path => $updateTime) {
                $this->selectedCache->touchCache($path, $updateTime);
            }
        }
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
            $dir = TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $dir;
        }
        return $dir;
    }

    /**
     * Serialize la variable en chaine de caractères pour une mise en cache
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    private function &serializeVariable($key, $value) {
        $content = "$" . $this->getVariableName($key) . " = \"" . ExecEntities::addSlashes($value) . "\"; ";
        return $content;
    }

    /**
     * Retourne le nom de la variable de cache.
     *
     * @param string $key
     * @return string
     */
    private function &getVariableName($key = "") {
        if (empty($this->currentSection)) {
            $this->changeCurrentSection();
        }

        $variableName = str_replace(DIRECTORY_SEPARATOR, "_", $this->currentSection) . $key;
        return $variableName;
    }

}
