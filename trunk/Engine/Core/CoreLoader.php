<?php

namespace TREngine\Engine\Core;

use Exception;
use TREngine\Engine\Fail\FailLoader;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de chargeur de classe.
 *
 * @author Sébastien Villemain
 */
class CoreLoader {

    /**
     * Fichier représentant une classe PHP pure.
     *
     * @var string
     */
    const TYPE_CLASS = "Class";

    /**
     * Fichier de pilotage de base de données.
     *
     * @var string
     */
    const TYPE_BASE = "Base";

    /**
     * Fichier de gestion du cache.
     *
     * @var string
     */
    const TYPE_CACHE = "Cache";

    /**
     * Fichier du base du moteur.
     *
     * @var string
     */
    const TYPE_CORE = "Core";

    /**
     * Fichier d'aide à la manipulation.
     *
     * @var string
     */
    const TYPE_EXEC = "Exec";

    /**
     * Fichier d'erreur.
     *
     * @var string
     */
    const TYPE_FAIL = "Fail";

    /**
     * Fichier de bibliothèque.
     *
     * @var string
     */
    const TYPE_LIB = "Lib";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_BLOCK = "Block";

    /**
     * Fichier représentant un module.
     *
     * @var string
     */
    const TYPE_MODULE = "Module";

    /**
     * Fichier représentant une traduction.
     *
     * @var string
     */
    const TYPE_TRANSLATE = "Translate";

    /**
     * Fichier représentant une inclusion spécifique.
     *
     * @var string
     */
    const TYPE_INCLUDE = "inc";

    /**
     * Fichier attaché au moteur.
     *
     * @var string
     */
    const SUBTYPE_ENGINE = "Engine";

    /**
     * Fichier détaché du moteur.
     *
     * @var string
     */
    const SUBTYPE_CUSTOM = "Custom";

    /**
     * Namespace de base.
     *
     * @var string
     */
    const NAMESPACE_BASE = "TREngine\{ORIGIN}\{TYPE}\{PREFIX}{KEYNAME}";

    /**
     * Les types de namespace possible.
     */
    const NAMESPACE_TYPES = array(
        self::TYPE_BASE,
        self::TYPE_CACHE,
        self::TYPE_CORE,
        self::TYPE_EXEC,
        self::TYPE_FAIL,
        self::TYPE_LIB,
        self::TYPE_BLOCK,
        self::TYPE_MODULE
    );

    /**
     * Tableau des classes chargées.
     *
     * @var array array("name" => "path")
     */
    private static $loadedFiles = null;

    /**
     * Inscription du chargeur de classe.
     *
     * @throws FailLoader
     */
    public static function affectRegister() {
        try {
            if (!is_null(self::$loadedFiles)) {
                throw new FailLoader("Loader already registered");
            }

            self::$loadedFiles = array();

            if (!spl_autoload_register(array(
                'TREngine\Engine\Core\CoreLoader',
                'classLoader'), true)) {
                throw new FailLoader("spl_autoload_register fail");
            }
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException("loader", $ex);
        }
    }

    /**
     * Chargeur de classe.
     *
     * @param string $class Nom de la classe.
     * @return bool true chargé.
     */
    public static function &classLoader(string $class): bool {
        return self::manageLoad($class, ""); // Type indéterminé
    }

    /**
     * Chargeur de fichier de traduction.
     *
     * @param string $plugin Module de traduction.
     * @return bool true chargé.
     */
    public static function &translateLoader(string $plugin): bool {
        return self::manageLoad($plugin, self::TYPE_TRANSLATE);
    }

    /**
     * Chargeur de fichier include.
     *
     * @param string $include Nom de l'include.
     * @return bool true chargé.
     */
    public static function &includeLoader(string $include): bool {
        return self::manageLoad($include, self::TYPE_INCLUDE);
    }

    /**
     * Vérifie la disponibilité de la classe et de ca methode éventuellement.
     *
     * @param string $className Nom de la classe
     * @param string $methodName Nom de la méthode
     * @param bool $static Appel d'instance ou statique
     * @return bool true l'appel peut être effectué
     */
    public static function &isCallable(string $className, string $methodName = "", bool $static = false): bool {
        $rslt = false;
        $ext = "";

        if (!empty($methodName)) {
            self::buildExtensionAndKeyName($className, $ext);

            // Vérifie si la classe est en mémoire
            if (self::isLoaded($className, $ext)) {
                // Pour les pages plus complexes comme le module management
                $pos = strpos($className, ".");

                if ($pos !== false) { // exemple : Module_Management_Security.setting
                    $className = substr($className, 0, $pos);
                }

                // Défini le comportement de l'appel
                if ($static) {
                    $rslt = is_callable("{$className}::{$methodName}");
                } else {
                    $rslt = is_callable(array(
                        $className,
                        $methodName));
                }
            }
        } else {
            self::buildExtensionAndKeyName($className, $ext);
            $rslt = self::isLoaded($className, $ext);
        }
        return $rslt;
    }

    /**
     * Appel une methode d'un objet ou une méthode statique d'une classe.
     *
     * @param string $callback
     * @return mixed
     */
    public static function &callback(string $callback) {
        $ext = "";
        self::buildExtensionAndKeyName($callback, $ext);

        // Récupère un seul paramètre supplémentaire
        $args = func_get_args();
        $args = array_splice($args, 1, 1);

        // Appel de la méthode
        $rslt = call_user_func_array($callback, $args);

        if ($rslt === false) {
            CoreLogger::addException("Failed to execute callback '" . $callback . "'. Extension: " . $ext);
        }
        return $rslt;
    }

    /**
     * Retourne le chemin absolu.
     *
     * @param string $keyName Fichier demandé.
     * @return string chemin absolu ou nulle.
     */
    public static function &getIncludeAbsolutePath(string $keyName): string {
        return self::getAbsolutePath($keyName, CoreLoader::TYPE_INCLUDE);
    }

    /**
     * Retourne le chemin absolu.
     *
     * @param string $keyName Fichier demandé.
     * @return string chemin absolu ou nulle.
     */
    public static function &getTranslateAbsolutePath(string $keyName): string {
        return self::getAbsolutePath($keyName, CoreLoader::TYPE_TRANSLATE);
    }

    /**
     * Retourne le nom complet de la classe.
     *
     * @param string $className
     * @param string $prefixName
     * @return string
     */
    public static function &getFullQualifiedClassName(string $className, string $prefixName = ""): string {
        $ext = "";

        if (!empty($prefixName)) {
            $prefixName .= "\\";
        }

        self::buildExtensionAndKeyName($className, $ext, $prefixName);
        return $className;
    }

    /**
     * Retourne le chemin vers le fichier contenant la classe.
     *
     * @param string $keyName
     * @return string
     */
    public static function &getFilePathFromNamespace(string $keyName): string {
        // Supprime le premier namespace
        $path = str_replace("TREngine\\", "", $keyName);

        // Conversion du namespace en dossier
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        return $path;
    }

    /**
     * Retourne le chemin vers le fichier contenant la tranduction.
     *
     * @param string $keyName
     * @param string $currentLanguage
     * @return string
     */
    public static function &getFilePathFromTranslate(string $keyName, string $currentLanguage = ""): string {
        $path = $keyName . DIRECTORY_SEPARATOR . self::TYPE_TRANSLATE . DIRECTORY_SEPARATOR;

        if (!empty($currentLanguage)) {
            $path .= $currentLanguage;
        } else if (self::isCallable("CoreTranslate")) {
            $path .= CoreTranslate::getInstance()->getCurrentLanguage();
        }

        $path .= ".lang";
        return $path;
    }

    /**
     * Retourne le chemin absolu.
     *
     * @param string $keyName Fichier demandé.
     * @param string $ext
     * @return string chemin absolu ou nulle.
     */
    private static function &getAbsolutePath(string $keyName, string $ext): string {
        $rslt = "";

        if (self::isLoaded($keyName, $ext)) {
            $rslt = self::$loadedFiles[self::getLoadedFileKey($keyName, $ext)];
        }
        return $rslt;
    }

    /**
     * Retourne la clé correspondant au fichier.
     *
     * @param string $keyName
     * @param string $ext
     * @return string
     */
    private static function &getLoadedFileKey(string $keyName, string $ext): string {
        $loadKeyName = $keyName;

        switch ($ext) {
            case self::TYPE_TRANSLATE:
                $loadKeyName .= ".lang";
                break;
        }

        return $loadKeyName;
    }

    /**
     * Vérifie si le fichier demandé a été chargé.
     *
     * @param string $keyName Fichier demandé.
     * @param string $ext Extension.
     * @return bool true si c'est déjà chargé.
     */
    private static function isLoaded(string $keyName, string $ext): bool {
        return isset(self::$loadedFiles[self::getLoadedFileKey($keyName, $ext)]);
    }

    /**
     * Chargeur de fichier (uniquement si besoin).
     *
     * @param string $keyName Nom de la classe ou du fichier.
     * @param string $ext Extension.
     * @return bool true chargé.
     * @throws FailLoader
     */
    private static function &manageLoad(string &$keyName, string $ext): bool {
        try {
            if (empty($keyName)) {
                throw new FailLoader("loader");
            }

            self::buildExtensionAndKeyName($keyName, $ext);
            $loaded = self::isLoaded($keyName, $ext);

            // Si ce n'est pas déjà chargé
            if (!$loaded) {
                $loaded = self::load($keyName, $ext);
            }
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex, array(
                $keyName,
                $ext));
        }
        return $loaded;
    }

    /**
     * Chargeur de classe.
     *
     * @param string $keyName
     * @param string $ext
     * @return bool
     * @throws FailLoader
     */
    private static function &load(string $keyName, string $ext): bool {
        $loaded = false;
        $path = self::getFilePath($keyName, $ext);

        if (is_file($path)) {
            $loaded = self::loadFilePath($keyName, $ext, $path);
        } else {
            switch ($ext) {
                case self::TYPE_BLOCK:
                    CoreLogger::addErrorMessage(ERROR_BLOCK_NO_FILE);
                    break;
                case self::TYPE_MODULE:
                    CoreLogger::addErrorMessage(ERROR_MODULE_NO_FILE);
                    break;
                case self::TYPE_TRANSLATE:
                    // Aucune traduction disponible
                    break;
                default:
                    throw new FailLoader("loader");
            }
        }
        return $loaded;
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param string $keyName
     * @param string $ext
     * @param string $prefixName
     */
    private static function buildExtensionAndKeyName(string &$keyName, string &$ext, string $prefixName = "") {
        if (empty($ext)) {
            self::buildGenericExtensionAndKeyName($keyName, $ext, $prefixName);
        }

        if ($ext === self::TYPE_TRANSLATE) {
            $fakeExt = "";
            self::buildGenericExtensionAndKeyName($keyName, $fakeExt, $prefixName);

            if ($fakeExt !== null && $fakeExt !== $ext) {
                $keyName = self::getFilePathFromNamespace($keyName);
            }
        }
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param string $keyName
     * @param string $ext
     * @param string $prefixName
     */
    private static function buildGenericExtensionAndKeyName(string &$keyName, string &$ext, string $prefixName) {
        if (strpos($keyName, "\Block\Block") !== false) {
            $ext = self::TYPE_BLOCK;
        } else if (strpos($keyName, "Module\Module") !== false) {
            $ext = self::TYPE_MODULE;
        } else if (strpos($keyName, "\\") === false) {
            foreach (self::NAMESPACE_TYPES as $namespaceType) {
                if (strrpos($keyName, $namespaceType, -strlen($keyName)) !== false) {
                    $ext = $namespaceType;

                    $keyName = str_replace("{KEYNAME}", $keyName, self::NAMESPACE_BASE);
                    $keyName = str_replace("{PREFIX}", $prefixName, $keyName);
                    $keyName = str_replace("{TYPE}", $namespaceType, $keyName);

                    $namespaceSubtype = (strrpos($keyName, $namespaceType . self::SUBTYPE_CUSTOM) !== false) ? self::SUBTYPE_CUSTOM : self::SUBTYPE_ENGINE;
                    $keyName = str_replace("{ORIGIN}", $namespaceSubtype, $keyName);
                    break;
                }
            }
        }

        if (empty($ext)) {
            // Extension par défaut
            $ext = self::TYPE_CLASS;
        }
    }

    /**
     * Détermine le chemin vers le fichier.
     *
     * @param string $keyName
     * @param string $ext
     * @return string
     */
    private static function &getFilePath(string $keyName, string $ext): string {
        $path = "";

        switch ($ext) {
            case self::TYPE_BASE:
            case self::TYPE_CACHE:
            case self::TYPE_CORE:
            case self::TYPE_EXEC:
            case self::TYPE_FAIL:
            case self::TYPE_LIB :
            case self::TYPE_CLASS:
            case self::TYPE_BLOCK:
            case self::TYPE_MODULE:
                $path = self::getFilePathFromNamespace($keyName);
                break;
            case self::TYPE_TRANSLATE:
                $path = self::getFilePathFromTranslate($keyName);
                break;
            case self::TYPE_INCLUDE:
                $path = str_replace("_", DIRECTORY_SEPARATOR, $keyName) . "." . $ext;
                break;
            default:
                throw new FailLoader("loader");
        }

        $path = TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path . ".php";
        return $path;
    }

    /**
     * Charge le fichier suivant son type, son nom et son chemin.
     *
     * @param string $keyName
     * @param string $ext
     * @param string $path
     * @return bool
     */
    private static function &loadFilePath(string $keyName, string $ext, string $path): bool {
        $loaded = false;

        switch ($ext) {
            case self::TYPE_TRANSLATE:
                $lang = array();
                break;
            case self::TYPE_INCLUDE:
                $inc = array();
                break;
        }

        require $path;
        self::$loadedFiles[self::getLoadedFileKey($keyName, $ext)] = $path;
        $loaded = true;

        switch ($ext) {
            case self::TYPE_TRANSLATE:
                if (!empty($lang) && is_array($lang)) {
                    CoreTranslate::getInstance()->affectCache($lang);
                }
                break;
            case self::TYPE_INCLUDE:
                CoreMain::getInstance()->addInclude($keyName, $inc);
                break;
        }
        return $loaded;
    }

}
