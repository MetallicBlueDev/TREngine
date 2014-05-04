<?php
/*
 * NOTE IMPORTANTE : tout ce fichier doit être compatible PHP 4.
 */
if (preg_match("/info.class.php/ie", $_SERVER['PHP_SELF'])) {
    exit();
}

/**
 * Version php sous forme x.x.x.x (exemple : 5.2.9.2).
 */
define("TR_ENGINE_PHP_VERSION", preg_replace("/[^0-9.]/", "", (preg_replace("/(_|-|[+])/", ".", phpversion()))));

// Si une version PHP OO moderne n'est pas détecté
if (TR_ENGINE_PHP_VERSION < "5.0.0") {
    echo"<b>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</b>"
    . "<br /><br />MINIMAL PHP VERSION : 5.0.0"
    . "<br />YOUR PHP VERSION : " . TR_ENGINE_PHP_VERSION;
    exit();
}

// Initalisation de toutes les autres informations de base
Core_Info::initialize();

/**
 * Recherche d'information rapide.
 *
 * @author Sébastien Villemain
 */
class Core_Info {

    private static $initialized = false;

    private function __construct() {

    }

    public static function initialize() {
        if (!self::$initialized) {
            self::$initialized = true;
            $info = new Core_Info();

            /**
             * Chemin jusqu'à la racine.
             *
             * @var string
             */
            define("TR_ENGINE_DIR", $info->getBaseDir());

            /**
             * Adresse URL complète jusqu'à TR ENGINE.
             *
             * @var string
             */
            define("TR_ENGINE_URL", $info->getUrlAddress());

            /**
             * Le système d'exploitation qui exécute TR ENGINE.
             *
             * @var string
             */
            define("TR_ENGINE_PHP_OS", $info->getPhpOs());

            /**
             * Numéro de version du moteur.
             *
             * Controle de révision
             * xx -> version courante
             * xx -> fonctionnalitées ajoutées
             * xx -> bugs ou failles critiques corrigés
             * xx -> bug mineur
             *
             * @var string
             */
            define("TR_ENGINE_VERSION", "0.6.0.0");
        }
    }

    /**
     * Retourne le chemin jusqu'à la racine.
     *
     * @return string
     */
    private function getBaseDir() {
        $baseDir = "";

        // Recherche du chemin absolu depuis n'importe quel fichier
        if (defined("TR_ENGINE_INDEX")) {
            // Nous sommes dans l'index
            $baseDir = getcwd();
        } else {
            // Chemin de base
            $baseName = str_replace($_SERVER['SCRIPT_NAME'], "", $_SERVER['SCRIPT_FILENAME']);
            $baseName = str_replace("/", DIRECTORY_SEPARATOR, $baseName);
            $workingDirectory = getcwd();

            if (!empty($workingDirectory)) {
                // On isole le chemin en plus jusqu'au fichier
                $path = str_replace($baseName, "", $workingDirectory);

                if (!empty($path)) {
                    // Suppression du slash
                    if ($path[0] == DIRECTORY_SEPARATOR) {
                        $path = substr($path, 1);
                    }

                    // Vérification en se reperant sur l'emplacement du fichier de configuration
                    while (!is_file($baseName . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.inc.php")) {
                        // On remonte d'un cran
                        $path = dirname($path);

                        // La recherche n'aboutira pas
                        if ($path == ".") {
                            break;
                        }
                    }
                }
            }

            // Verification du résultat
            if (!empty($path) && is_file($baseName . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.inc.php")) {
                $baseDir = $baseName . DIRECTORY_SEPARATOR . $path;
            } else if (is_file($baseName . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.inc.php")) {
                $baseDir = $baseName;
            } else {
                $baseDir = $baseName;
            }
        }
        return $baseDir;
    }

    /**
     * Retourne l'adresse URL complète jusqu'à TR ENGINE.
     *
     * @return string
     */
    private function getUrlAddress() {
        // Recherche de l'URL courante
        $urlTmp = $_SERVER["REQUEST_URI"];

        if (substr($urlTmp, -1) == "/") {
            $urlTmp = substr($urlTmp, 0, -1);
        }

        if ($urlTmp[0] == "/") {
            $urlTmp = substr($urlTmp, 1);
        }

        $urlTmp = explode("/", $urlTmp);

        // Recherche du dossier courant
        $urlBase = explode("/", TR_ENGINE_DIR);

        // Construction du lien
        $urlFinal = "";
        for ($i = count($urlTmp) - 1; $i >= 0; $i--) {
            for ($j = count($urlBase) - 1; $j >= 0; $j--) {
                if (empty($urlBase[$j])) {
                    continue;
                }

                if ($urlTmp[$i] != $urlBase[$j]) {
                    break;
                }

                if (empty($urlFinal)) {
                    $urlFinal = $urlTmp[$i];
                } else {
                    $urlFinal = $urlTmp[$i] . "/" . $urlFinal;
                }
                $urlBase[$j] = "";
            }
        }
        return ((empty($urlFinal)) ? $_SERVER["SERVER_NAME"] : $_SERVER["SERVER_NAME"] . "/" . $urlFinal);
    }

    /**
     * Retourne la plateforme sur lequel est PHP.
     *
     * @return string
     */
    private function getPhpOs() {
        return strtoupper(substr(PHP_OS, 0, 3));
    }

}
