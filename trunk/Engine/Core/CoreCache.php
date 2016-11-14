<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Cache\CacheModel;
use TREngine\Engine\Exec\ExecEntities;
use Exception;

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

    /**
     * Nouveau gestionnaire de cache.
     */
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
        $cacheClassName = CoreLoader::getFullQualifiedClassName("Cache" . ucfirst($cacheConfig['type']));
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

    /**
     * Ne réalise aucune action dans ce contexte.
     *
     * @param array $cache
     */
    public function initialize(array &$cache) {
        // NE RIEN FAIRE
        unset($cache);
    }

    /**
     * Destruction du gestionnaire de cache.
     */
    public function __destruct() {
        $this->selectedCache = null;
    }

    /**
     * Retourne l'instance du gestionnaire de cache.
     *
     * @return CoreCache
     */
    public static function &getInstance($newSectionPath = null): CoreCache {
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
     * Retourne la liste des types de cache supporté.
     *
     * @return array
     */
    public static function &getCacheList(): array {
        return self::getInstance()->getFileList("Engine/Cache", "Cache");
    }

    /**
     * {@inheritDoc}
     */
    public function netConnect() {
        $this->selectedCache->netConnect();
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function netConnected(): bool {
        return $this->selectedCache !== null && $this->selectedCache->netConnected();
    }

    /**
     * {@inheritDoc}
     */
    public function netDeconnect() {
        $this->selectedCache->netDeconnect();
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function &netSelect(): bool {
        return $this->selectedCache->netSelect();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getTransactionHost(): string {
        return $this->selectedCache->getTransactionHost();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getTransactionUser(): string {
        return $this->selectedCache->getTransactionUser();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getTransactionPass(): string {
        return $this->selectedCache->getTransactionPass();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getTransactionType(): string {
        return $this->selectedCache->getTransactionType();
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function &getServerPort(): int {
        return $this->selectedCache->getServerPort();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getServerRoot(): string {
        return $this->selectedCache->getServerRoot();
    }

    /**
     * {@inheritDoc}
     *
     * @param string $newRoot
     */
    public function setServerRoot(string &$newRoot) {
        $this->selectedCache->setServerRoot($newRoot);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path
     * @param mixed $content
     * @param bool $overwrite
     */
    public function writeCache(string $path, $content, bool $overwrite = true) {
        $this->writeCacheWithReturn($path, $content, $overwrite);
    }

    /**
     * Demande une écriture dans le cache d'une chaine classique.
     * Prépare les données pour la soumission dans le cache et retourne les données telles qu’elles seront écrites.
     *
     * @param string $path
     * @param mixed $content
     * @param bool $overwrite
     * @return string
     */
    public function &writeCacheWithReturn(string $path, $content, bool $overwrite = true): string {
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

        $variableName = $this->getVariableName();

        // Supprime la déclaration de la variable $tmp = "myData"; = > myData
        $pos = strpos($content, "$" . $variableName . " = \"");

        if ($pos !== false && $pos === 0) {
            $content = str_replace("$" . $variableName . " = \"", "", $content);
            $pos = strrpos($content, "\";");

            if ($pos !== false && $pos > 0) {
                $content = substr($content, 0, $pos);
            }
        }
        return $content;
    }

    /**
     * Demande une écriture dans le cache d'une chaine contenant des variables à remplacer.
     * Prépare les données pour la soumission dans le cache en tenant compte des variables et retourne les données telles qu’elles seront écrites.
     *
     * @param string $path
     * @param string $content
     * @param bool $overwrite
     * @param string $cacheVariableName
     * @param array $cacheVariables
     * @return string
     */
    public function &writeCacheWithVariable(string $path, string $content, bool $overwrite = true, string $cacheVariableName = "", array $cacheVariables = array()): string {
        $content = $this->writeCacheWithReturn($path, $content, $overwrite);

        if (!empty($cacheVariableName)) {
            $matches = array();

            // Recherche la variable à remplacer
            if (preg_match_all("/[$]" . $cacheVariableName . "([[]([A-Za-z0-9]+)[]]|)/", $content, $matches, PREG_PATTERN_ORDER) !== false) {
                // Suppression des caractères d'échappements
                $content = str_replace("\\\"", "\"", $content);

                // Utilisation des variables en cache
                ${$cacheVariableName} = $cacheVariables;
                $hasEmpty = false;

                // Remplacement de toutes les variables
                foreach ($matches[2] as $value) {
                    if ($value === "") {
                        $hasEmpty = true;
                        continue;
                    }

                    // Remplace la variable par sa valeur
                    $content = str_replace("\$" . $cacheVariableName . "[" . $value . "]", ${$cacheVariableName}[$value], $content);
                }

                if ($hasEmpty) {
                    $content = str_replace($cacheVariableName, ${$cacheVariableName}, $content);
                }
            }
        }
        return $content;
    }

    /**
     * Demande d'écriture dans le cache d'un objet.
     * Prépare l'objet pour la soumission dans le cache et retourne les données telles qu’elles seront écrites.
     *
     * @param string $path
     * @param mixed $content
     * @return string
     */
    public function &writeCacheWithSerialize(string $path, $content): string {
        $content = serialize($content);
        // Préserve les données (protection utilisateur et protection des quotes)
        $content = base64_encode($content);
        $content = $this->serializeData($content);
        return $this->writeCacheWithReturn($path, $content);
    }

    /**
     * Lecture du cache ciblé.
     *
     * @param string $path chemin du cache
     * @param string $cacheVariableName
     * @param array $cacheVariables
     * @return mixed
     */
    public function &readCache(string $path, string $cacheVariableName = "", array $cacheVariables = array()) {
        // Ajout des valeurs en cache
        if (!empty($cacheVariableName)) {
            ${$cacheVariableName} = &$cacheVariables;
        }

        // Rend la variable global à la fonction
        $variableName = $this->getVariableName();
        ${$variableName} = "";

        // Capture du fichier
        if ($this->cached($path)) {
            require $this->getCurrentSectionPath($path, true);
        }
        return ${$variableName};
    }

    /**
     * Lecture du cache ciblé.
     *
     * @param string $path
     * @return mixed
     */
    public function &readCacheWithUnserialize(string $path) {
        $content = $this->readCache($path);
        $content = base64_decode($content);
        $content = unserialize($content);
        return $content;
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache(string $path, int $updateTime = 0) {
        if ($updateTime <= 0) {
            $updateTime = time();
        }

        $this->touchCache[$this->getCurrentSectionPath($path)] = $updateTime;
    }

    /**
     * Supprime tous fichiers trop vieux.
     *
     * @param string $path chemin vers le fichier ou le dossier
     * @param int $timeLimit limite de temps
     */
    public function removeCache(string $path, int $timeLimit = 0) {
        $this->removeCache[$this->getCurrentSectionPath($path)] = $timeLimit;
    }

    /**
     * Retourne la liste des fichiers et dossiers présents.
     * Un filtre automatique est appliqué sur les éléments tel que "..", "." ou encore "index.html"...
     *
     * @param string $path
     * @return array
     */
    public function &getNameList(string $path): array {
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
    public function &getCacheMTime(string $path): int {
        return $this->selectedCache->getCacheMTime($this->getCurrentSectionPath($path));
    }

    /**
     * Retourne la liste des fichiers trouvés avec l'extension demandé.
     *
     * @param string $dirPath
     * @param string $extension
     * @return array
     */
    public function &getFileList(string $dirPath, string $extension = ".class"): array {
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
     * Change le chemin de la section.
     *
     * @param string $newSectionPath
     */
    public function changeCurrentSection(string $newSectionPath = self::SECTION_TMP) {
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
     * @param mixed $data ($data = "data") or array $data ($data = array("name" => "data"))
     * @param string $lastKey clé supplémentaire
     * @return string
     */
    public function &serializeData($data, string $lastKey = ""): string {
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
     * @param string $path chemin vers le fichier cache
     * @return bool true le fichier est en cache
     */
    public function cached(string $path): bool {
        return is_file($this->getCurrentSectionPath($path, true));
    }

    /**
     * Parcours récursivement le dossier du cache actuel afin de supprimer les fichiers trop vieux.
     * (Nettoie le dossier courant du cache).
     *
     * @param int $timeLimit la limite de temps
     */
    public function cleanCache(int $timeLimit) {
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
    public function runJobs() {
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
     * Retourne le chemin de la section courante.
     *
     * @param string $dir
     * @param bool $includeRoot
     * @return string
     */
    private function &getCurrentSectionPath(string $dir, bool $includeRoot = false): string {
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
    private function &serializeVariable(string $key, string $value): string {
        $content = "$" . $this->getVariableName($key) . " = \"" . ExecEntities::addSlashes($value) . "\"; ";
        return $content;
    }

    /**
     * Retourne le nom de la variable de cache.
     *
     * @param string $key
     * @return string
     */
    private function &getVariableName(string $key = ""): string {
        if (empty($this->currentSection)) {
            $this->changeCurrentSection();
        }

        $variableName = str_replace(DIRECTORY_SEPARATOR, "_", $this->currentSection) . $key;
        return $variableName;
    }

}
