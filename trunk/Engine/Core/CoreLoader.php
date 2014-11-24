<?php

namespace TREngine\Engine\Core;

use Exception;
use TREngine\Engine\Fail\FailLoader;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Chargeur de classe.
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
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_BASE = "Base";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_CACHE = "Cache";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_CORE = "Core";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_EXEC = "Exec";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_FAIL = "Fail";

    /**
     * Fichier représentant un block.
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
    const TYPE_TRANSLATE = "lang";

    /**
     * Fichier représentant une inclusion spécifique.
     *
     * @var string
     */
    const TYPE_INCLUDE = "inc";

    /**
     * Tableau des namespaces.
     *
     * @var array ("baseName" => "namespace")
     */
    const NAMESPACES_RULES = array(
        self::TYPE_BASE => "TREngine\Engine\Base\\",
        self::TYPE_CACHE => "TREngine\Engine\Cache\\",
        self::TYPE_CORE => "TREngine\Engine\Core\\",
        self::TYPE_EXEC => "TREngine\Engine\Exec\\",
        self::TYPE_FAIL => "TREngine\Engine\Fail\\",
        self::TYPE_LIB => "TREngine\Engine\Lib\\",
        self::TYPE_BLOCK => "TREngine\Blocks\\",
        self::TYPE_MODULE => "TREngine\Modules\\"
    );

    /**
     * Tableau des classes chargées.
     *
     * @var array array("name" => "path")
     */
    private static $loaded = null;

    /**
     * Inscription du chargeur de classe.
     */
    public static function affectRegister() {
        try {
            if (!is_null(self::$loaded)) {
                throw new FailLoader("Loader already registered");
            }

            self::$loaded = array();

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
     * @return boolean true chargé.
     */
    public static function &classLoader($class) {
        return self::load($class, ""); // Type indéterminé
    }

    /**
     * Chargeur de fichier de traduction.
     *
     * @param string $plugin Module de traduction.
     * @return boolean true chargé.
     */
    public static function &translateLoader($plugin) {
        return self::load($plugin, self::TYPE_TRANSLATE);
    }

    /**
     * Chargeur de fichier include.
     *
     * @param string $include Nom de l'include.
     * @return boolean true chargé.
     */
    public static function &includeLoader($include) {
        return self::load($include, self::TYPE_INCLUDE);
    }

    /**
     * Vérifie la disponibilité de la classe et de ca methode éventuellement.
     *
     * @param string $className Nom de la classe
     * @param string $methodName Nom de la méthode
     * @param boolean $static Appel d'instance ou statique
     * @return boolean true l'appel peut être effectué
     */
    public static function &isCallable($className, $methodName = "", $static = false) {
        $rslt = false;

        if (!empty($methodName)) {
            self::checkExtensionAndName($ext, $className);

            // Vérifie si la classe est en mémoire
            if (self::isLoaded($className)) {
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
            $rslt = self::isLoaded($className);
        }
        return $rslt;
    }

    /**
     * Appel une methode ou un object ou une classe statique callback.
     *
     * @param string $callback Nom de la callback.
     * @return callback resultat.
     */
    public static function &callback($callback) {
        if (TR_ENGINE_PHP_VERSION < "5.2.3") {
            if (is_string($callback)) {
                if (strpos($callback, "::") !== false) {
                    $callback = explode("::", $callback);
                }
            }
        }

        $args = func_get_args();
        $args = array_splice($args, 1, 1);
        return call_user_func_array($callback, $args);
    }

    /**
     * Retourne le chemin absolu.
     *
     * @param string $keyName Fichier demandé.
     * @return string chemin absolu ou nulle.
     */
    public static function &getAbsolutePath($keyName) {
        $rslt = null;

        if (self::isLoaded($keyName)) {
            $rslt = self::$loaded[$keyName];
        }
        return $rslt;
    }

    /**
     * Vérifie si le fichier demandé a été chargé.
     *
     * @param string $keyName Fichier demandé.
     * @return boolean true si c'est déjà chargé.
     */
    public static function isLoaded($keyName) {
        return isset(self::$loaded[$keyName]);
    }

    /**
     * Retourne le nom complet de la classe.
     *
     * @param string $className
     * @return string
     */
    public static function &getFullQualifiedClassName($className) {
        $ext = null;
        self::checkExtensionAndName($ext, $className);
        return $className;
    }

    /**
     * Chargeur de fichier.
     *
     * @param string $keyName Nom de la classe ou du fichier.
     * @param string $ext Extension.
     * @return boolean true chargé.
     */
    private static function &load(&$keyName, $ext) {
        try {
            if (empty($keyName)) {
                throw new FailLoader("loader");
            }

            self::checkExtensionAndName($ext, $keyName);
            $loaded = self::isLoaded($keyName);

            // Si ce n'est pas déjà chargé
            if (!$loaded) {
                $path = self::getFilePath($ext, $keyName);

                if (is_file($path)) {
                    $loaded = self::loadFilePath($ext, $keyName, $path);
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
            }
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex, array(
                $keyName,
                $ext));
        }
        return $loaded;
    }

    /**
     * Vérifie l'emplacement et le type de fichier.
     *
     * @param string $ext
     * @param string $keyName
     * @return string
     */
    private static function checkExtensionAndName(&$ext, &$keyName) {
        if (empty($ext)) {
            // Retrouve l'extension
            if (strpos($keyName, "\Blocks\Block") !== false) {
                $ext = self::TYPE_BLOCK;
            } else if (strpos($keyName, "Modules\Module") !== false) {
                $ext = self::TYPE_MODULE;
            } else {
                if (strpos($keyName, "\\") === false) {
                    foreach (self::NAMESPACES_RULES as $baseName => $baseNamespace) {
                        if (strrpos($keyName, $baseName, -strlen($keyName)) !== false) {
                            $ext = $baseName;
                            $keyName = $baseNamespace . $keyName;
                            break;
                        }
                    }
                }

                if (empty($ext)) {
                    $ext = self::TYPE_CLASS;
                }
            }
        }
    }

    /**
     * Détermine le chemin vers le fichier.
     *
     * @param string $ext
     * @param string $keyName
     * @return string
     */
    private static function &getFilePath($ext, $keyName) {
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
                if (self::isCallable("CoreTranslate")) {
                    if ($keyName === DIRECTORY_SEPARATOR) {
                        $path = "lang" . DIRECTORY_SEPARATOR;
                    } else {
                        $path = $keyName . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
                    }

                    $path .= CoreTranslate::getInstance()->getCurrentLanguage();
                }

                $path .= "." . $ext;
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
     * Retourne le chemin vers le fichier contenant la classe.
     *
     * @param string $keyName
     * @return string
     */
    private static function &getFilePathFromNamespace($keyName) {
        // Supprime le premier namespace
        $path = str_replace("TREngine\\", "", $keyName);

        // Conversion du namespace en dossier
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        return $path;
    }

    /**
     * Charge le fichier suivant son type, son nom et son chemin.
     *
     * @param string $ext
     * @param string $keyName
     * @param string $path
     * @return boolean
     */
    private static function &loadFilePath($ext, $keyName, $path) {
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
        self::$loaded[$keyName] = $path;
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
