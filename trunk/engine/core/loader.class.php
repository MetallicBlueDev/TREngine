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
     */
    private static $loaded = array();

    /**
     * Chargeur de classe.
     *
     * @param $class String Nom de la classe.
     * @return boolean true chargé.
     */
    public static function classLoader($class) {
        $classLoaded = false;

        try {
            $classLoaded = self::load($class);
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie, $class);
        }
        return $classLoaded;
    }

    /**
     * Chargeur de fichier include.
     *
     * @param $include String Nom de l'include.
     * @return boolean true chargé.
     */
    public static function includeLoader($include) {
        try {
            return self::load($include, "inc");
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie, $include);
        }
    }

    /**
     * Chargeur de fichier.
     *
     * @param $name String Nom de la classe ou du fichier.
     * @param $ext String Extension.
     * @return boolean true chargé.
     */
    private static function load($name, $ext = "") {
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
                $path = "engine_" . $name;
            }

            $path = str_replace("_", "/", $path);
            $path = TR_ENGINE_DIR . "/" . strtolower($path) . "." . $ext . ".php";

            if (is_file($path)) {
                require($path);
                self::$loaded[$name] = true;
                $loaded = true;
            } else {
                switch ($ext) {
                    case 'block':
                        Core_Logger::addErrorMessage(ERROR_BLOCK_NO_FILE);
                        break;
                    case 'module':
                        Core_Logger::addErrorMessage(ERROR_MODULE_NO_FILE);
                        break;
                    default:
                        throw new Exception("Loader");
                }
                $loaded = false;
            }
        }
        return $loaded;
    }

    /**
     * Vérifie si le fichier demandé a été chargé.
     *
     * @param $name String fichier demandé.
     * @return boolean true si c'est déjà chargé.
     */
    private static function isLoaded($name) {
        return isset(self::$loaded[$name]);
    }

    /**
     * Vérifie la disponibilité de la classe et de ca methode éventuellement.
     *
     * @param $className String or Object une chaine de caractère est recommandée.
     * @param $methodName String
     * @param $static boolean
     * @return boolean
     */
    public static function isCallable($className, $methodName = "", $static = false) {
        $rslt = false;

        if (is_object($className)) {
            $className = get_class($className);
        }

        if (!empty($methodName)) {
            // Utilisation du buffer si possible
            if (self::isLoaded($className)) {
                // Pour les pages plus complexes comme le module management
                $pos = 0;

                if (($pos = strpos($className, ".")) !== false) { // exemple : Module_Management_Security.setting
                    $className = substr($className, 0, $pos);
                }

                // Défini le comportement de l'appel
                if ($static) {
                    $rslt = @is_callable("{$className}::{$methodName}");
                } else {
                    $rslt = @is_callable(array(
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
     * @param $callback String or array Nom de la callback.
     * @return callback resultat.
     */
    public static function callback($callback) {
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

}

?>