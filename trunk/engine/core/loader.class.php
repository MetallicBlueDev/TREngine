<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * Chargeur de classe.
 *
 * @author Sébastien Villemain
 */
class Core_Loader {

    /**
     * Tableau des classes chargées.
     *
     * @var array array("name" => "path")
     */
    private static $loaded = array();

    public static function affectRegister() {
        spl_autoload_register(array(
            'Core_Loader',
            'classLoader'));
    }

    /**
     * Chargeur de classe.
     *
     * @param string $class Nom de la classe.
     * @return boolean true chargé.
     */
    public static function &classLoader($class) {
        return self::load($class, "");
    }

    /**
     * Chargeur de fichier de traduction.
     *
     * @param string $plugin Module de traduction.
     * @return boolean true chargé.
     */
    public static function &langLoader($plugin) {
        return self::load($plugin, "lang");
    }

    /**
     * Chargeur de fichier include.
     *
     * @param string $include Nom de l'include.
     * @return boolean true chargé.
     */
    public static function &includeLoader($include) {
        return self::load($include, "inc");
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
        return (isLoaded($name) ? self::$loaded[$name] : null);
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
                throw new Exception("loader");
            }

            $loaded = self::isLoaded($name);

            // Si ce n'est pas déjà chargé
            if (!$loaded) {
                $path = "";

                // Retrouve l'extension
                if (empty($ext)) {
                    if (strpos($name, "Block_") !== false) {
                        $ext = "block";
                        $path = str_replace("Block_", "blocks_", $name);
                    } else if (strpos($name, "Module_") !== false) {
                        $ext = "module";
                        $path = str_replace("Module_", "modules_", $name);
                    } else {
                        $ext = "class";
                        $path = "engine_" . $name;
                    }
                } else {
                    switch ($ext) {
                        case 'lang':
                            if (self::isCallable("Core_Translate")) {
                                $path = $name . "_lang_" . Core_Translate::getInstance()->getCurrentLanguage();
                            }
                            break;
                        default:
                            $path = "engine_" . $name;
                            break;
                    }
                }

                $path = str_replace("_", "/", $path);
                $path = TR_ENGINE_DIR . "/" . strtolower($path) . "." . $ext . ".php";

                if (is_file($path)) {
                    if ($ext === "lang") {
                        $lang = array();
                    }

                    require($path);
                    self::$loaded[$name] = $path;
                    $loaded = true;

                    if ($ext === "lang") {
                        if (!empty($lang) && is_array($lang)) {
                            Core_Translate::getInstance()->affectCache($lang);
                        }
                    }
                } else {
                    switch ($ext) {
                        case 'block':
                            Core_Logger::addErrorMessage(ERROR_BLOCK_NO_FILE);
                            break;
                        case 'module':
                            Core_Logger::addErrorMessage(ERROR_MODULE_NO_FILE);
                            break;
                        default:
                            throw new Exception("loader");
                    }
                }
            }
        } catch (Exception $ex) {
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex, array(
                $name));
        }
        return $loaded;
    }

}
