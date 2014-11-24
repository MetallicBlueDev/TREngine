<?php

namespace TREngine\Engine\Core;

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
    const TYPE_CLASS = "class";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    const TYPE_BLOCK = "block";

    /**
     * Fichier représentant un module.
     *
     * @var string
     */
    const TYPE_MODULE = "module";

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
     * @param string $className une chaine de caractère est recommandée.
     * @param string $methodName
     * @param boolean $static
     * @return boolean
     */
    public static function &isCallable($className, $methodName = "", $static = false) {
        $rslt = false;

        if (!empty($methodName)) {
            // Utilisation du buffer si possible
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
     * @param string $name Fichier demandé.
     * @return string chemin absolu ou nulle.
     */
    public static function &getAbsolutePath($name) {
        $rslt = null;

        if (self::isLoaded($name)) {
            $rslt = self::$loaded[$name];
        }
        return $rslt;
    }

    /**
     * Vérifie si le fichier demandé a été chargé.
     *
     * @param string $name Fichier demandé.
     * @return boolean true si c'est déjà chargé.
     */
    public static function isLoaded($name) {
        return isset(self::$loaded[$name]);
    }

    /**
     * Chargeur de fichier.
     *
     * @param string $name Nom de la classe ou du fichier.
     * @param string $ext Extension.
     * @return boolean true chargé.
     */
    private static function &load($name, $ext) {
        try {
            if (empty($name)) {
                throw new FailLoader("loader");
            }

            $loaded = self::isLoaded($name);

            // Si ce n'est pas déjà chargé
            if (!$loaded) {
                $ext = self::getExtension($ext, $name);
                $path = self::getFilePath($ext, $name);

                if (is_file($path)) {
                    $loaded = self::loadFilePath($ext, $name, $path);
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
                            throw new FailLoader("loader: " . $name);
                    }
                }
            }
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex, array(
                $name,
                $ext));
        }
        return $loaded;
    }

    /**
     * Retourne le type d'extension de fichier.
     *
     * @param string $ext
     * @return string
     */
    private static function &getExtension(&$ext, $name) {
        if (empty($ext)) {
            // Retrouve l'extension
            if (strpos($name, "\Blocks\Block") !== false) {
                $ext = self::TYPE_BLOCK;
            } else if (strpos($name, "Modules\Module") !== false) {
                $ext = self::TYPE_MODULE;
            } else {
                $ext = self::TYPE_CLASS;
            }
        }
        return $ext;
    }

    /**
     * Détermine le chemin vers le fichier.
     *
     * @param type $ext
     * @param type $name
     * @return string
     */
    private static function &getFilePath($ext, $name) {
        $path = "";

        switch ($ext) {
            case self::TYPE_CLASS:
            case self::TYPE_BLOCK:
            case self::TYPE_MODULE:
                $path = self::getFilePathFromNamespace($name);
                break;
            case self::TYPE_TRANSLATE:
                if (self::isCallable("CoreTranslate")) {
                    if ($name === DIRECTORY_SEPARATOR) {
                        $path = "lang" . DIRECTORY_SEPARATOR;
                    } else {
                        $path = $name . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
                    }

                    $path .= CoreTranslate::getInstance()->getCurrentLanguage();
                }

                $path .= "." . $ext;
                break;
            case self::TYPE_INCLUDE:
                $path = str_replace("_", DIRECTORY_SEPARATOR, $name) . "." . $ext;
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
     * @param string $name
     * @return string
     */
    private static function &getFilePathFromNamespace($name) {
        // Supprime le premier namespace
        $path = str_replace("TREngine\\", "", $name);

        // Conversion du namespace en dossier
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        return $path;
    }

    /**
     * Charge le fichier suivant son type, son nom et son chemin.
     *
     * @param string $ext
     * @param string $name
     * @param string $path
     * @return boolean
     */
    private static function &loadFilePath($ext, $name, $path) {
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
        self::$loaded[$name] = $path;
        $loaded = true;

        switch ($ext) {
            case self::TYPE_TRANSLATE:
                if (!empty($lang) && is_array($lang)) {
                    CoreTranslate::getInstance()->affectCache($lang);
                }
                break;
            case self::TYPE_INCLUDE:
                CoreMain::getInstance()->addInclude($name, $inc);
                break;
        }
        return $loaded;
    }

}
