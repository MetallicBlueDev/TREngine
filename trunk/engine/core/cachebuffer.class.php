<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

class Core_CacheBuffer {

    /**
     * Instance d'un protocole déjà initialisé
     *
     * @var Libs_CacheModel
     */
    private static $protocol = null;

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

    /**
     * Tableau avec les chemins des differentes rubriques
     *
     * @var array
     */
    private static $sectionDir = array(
        "configs" => "configs/",
        "tmp" => "tmp",
        "log" => "tmp/log",
        "sessions" => "tmp/sessions",
        "lang" => "tmp/lang",
        "menus" => "tmp/menus",
        "modules" => "tmp/modules",
        "fileList" => "tmp/fileList",
        "form" => "tmp/form"
    );

    /**
     * Nom de la section courante
     *
     * @var string
     */
    private static $sectionName = "";

    /**
     * état des modes de gestion des fichiers
     *
     * @var arry
     */
    private static $modeActived = array(
        "php" => false,
        "ftp" => false,
        "sftp" => false
    );

    /**
     * Donnée du ftp
     *
     * @var array
     */
    private static $ftp = array();

    /**
     * Modifier le nom de la section courante
     *
     * @param $sectionName
     */
    public static function setSectionName($sectionName = "") {
        if (!empty($sectionName) && isset(self::$sectionDir[$sectionName])) {
            self::$sectionName = $sectionName;
        } else {
            self::$sectionName = "tmp";
        }
    }

    /**
     * Retourne le chemin de la section courante
     *
     * @return string
     */
    private static function &getSectionPath() {
        // Si pas de section, on met par défaut
        if (empty(self::$sectionName))
            self::setSectionName();
        // Chemin de la section courante
        return self::$sectionDir[self::$sectionName];
    }

    /**
     * Nom de la section courante
     *
     * @return string Section courante
     */
    public static function &getSectionName() {
        return self::$sectionName;
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
        $key = self::encodePath(self::getSectionPath() . "/" . $path);
        // Ajout dans le cache
        if ($overWrite)
            self::$writingCache[$key] = $content;
        else
            self::$addCache[$key] = $content;
    }

    /**
     * Supprime un fichier ou supprime tout fichier trop vieux
     *
     * @param $dir chemin vers le fichier ou le dossier
     * @param $timeLimit limite de temps
     */
    public static function removeCache($dir, $timeLimit = 0) {
        // Configuration du path
        if (!empty($dir))
            $dir = "/" . $dir;
        $dir = self::encodePath(self::getSectionPath() . $dir);
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
        self::$updateCache[self::encodePath(self::getSectionPath() . "/" . $path)] = time();
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
        if (self::cached("checker.txt")) {
            // Si on a demandé un comparaison de temps
            if ($timeLimit > 0) {
                if ($timeLimit < self::checkerMTime())
                    return true;
            } else {
                return true;
            }
        }
        return false;
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
     * Serialize la variable en chaine de caractères pour une mise en cache
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    private static function &serializeVariable($key, $value) {
        $content .= "$" . self::getSectionName() . $key . " = \"" . Exec_Entities::addSlashes($value) . "\"; ";
        return $content;
    }

    /**
     * Ecriture du checker
     */
    private static function writingChecker() {
        self::writingCache("checker.txt", "1");
    }

    /**
     * Mise à jour du checker
     */
    public static function touchChecker() {
        if (!self::cached("checker.txt"))
            self::writingChecker();
        else
            self::touchCache("checker.txt");
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
     * Encode un chemin
     *
     * @param string $path
     * @return string
     */
    private static function &encodePath($path) {
        return $path;
    }

    /**
     * Décode un chemin
     *
     * @param string $encodePath
     * @return string
     */
    private static function &decodePath($encodePath) {
        return $encodePath;
    }

    /**
     * Retourne le chemin complet vers le fichier cache
     *
     * @param $path chemin du fichier
     * @return string chemin complet
     */
    public static function &getPath($path) {
        $path = TR_ENGINE_DIR . "/" . self::getSectionPath() . "/" . $path;
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
        $variableName = self::$sectionName;
        // Rend la variable global a la fonction
        ${$variableName} = "";

        // Capture du fichier
        if (self::cached($path)) {
            require(self::getPath($path));
        }
        return ${$variableName};
    }

    /**
     * Recherche si le cache a besoin de génére une action
     *
     * @param array $required
     * @return boolean true action demandée
     */
    private static function cacheRequired($required) {
        return (is_array($required) && count($required) > 0);
    }

    /**
     * Test si le chemin est celui d'un dossier
     *
     * @param $path
     * @return boolean true c'est un dossier
     */
    public static function &isDir($path) {
        $pathIsDir = false;

        if (substr($path, -1) == "/") {
            // Nettoyage du path qui est enfaite un dir
            $path = substr($path, 0, -1);
            $pathIsDir = true;
        } else {
            // Recherche du bout du path
            $last = "";
            $pos = strrpos("/", $path);
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
        if ($ext == "php") {
            // Recherche du dossier parent
            $dirBase = "";
            $nbDir = count(explode("/", $pathFile));
            for ($i = 1; $i < $nbDir; $i++) {
                $dirBase .= "../";
            }

            // Ecriture de l'entête
            $content = "<?php\n"
            . "if (!defined(\"TR_ENGINE_INDEX\")){"
            . "if(!class_exists(\"Core_Secure\")){"
            . "include(\"" . $dirBase . "engine/core/secure.class.php\");"
            . "}new Core_Secure();}"
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
    public static function setModeActived($modes = array()) {
        if (!is_array($modes))
            $modes = array(
                $modes);

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
     * Injecter les données du FTP
     *
     * @param array
     */
    public static function setFtp($ftp = array()) {
        self::$ftp = $ftp;
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

        self::setSectionName("fileList");
        $fileName = str_replace("/", "_", $dirPath) . ".php";

        if (Core_CacheBuffer::cached($fileName)) {
            $dirList = self::getCache($fileName);
        } else {
            $protocol = self::getExecProtocol();
            if ($protocol != null) {
                $dirList = $protocol->listNames($dirPath);
                self::writingCache($fileName, $dirList);
            }
        }
        return $dirList;
    }

    /**
     * Démarre et retourne le protocole du cache
     *
     * @return Libs_CacheModel
     */
    private static function &getExecProtocol() {
        if (self::$protocol == null) {
            if (self::$modeActived['ftp']) {
                // Démarrage du gestionnaire FTP
                self::$protocol = new Libs_CacheFtp();
            } else if (self::$modeActived['sftp']) {
                // Démarrage du gestionnaire SFTP
                self::$protocol = new Libs_CacheSftp();
            } else {
                // Démarrage du gestionnaire de fichier
                self::$protocol = new Libs_CacheFile();
            }

            // Si il y a un souci, on démarre par défaut le gestionnaire de fichier
            if (!self::$protocol->canUse()) {
                // Démarrage du gestionnaire de fichier
                self::$protocol = new Libs_CacheFile();
            }
        }
        return self::$protocol;
    }

    /**
     * Execute la routine du cache
     */
    public static function valideCacheBuffer() {
        // Si le cache a besoin de générer une action
        if (self::cacheRequired(self::$removeCache) || self::cacheRequired(self::$writingCache) || self::cacheRequired(self::$addCache) || self::cacheRequired(self::$updateCache)) {
            // Protocole a utiliser
            $protocol = self::getExecProtocol();

            if ($protocol != null) {
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

}

?>