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

new Core_Info();

/**
 * Recherche d'information rapide.
 * NOTE IMPORTANTE : cette classe doit être compatible PHP 4.
 *
 * @author Sébastien Villemain
 */
class Core_Info {

    /**
     * Capture de la configuration courante.
     */
    function __construct() {

        /**
         * Chemin jusqu'à la racine.
         *
         * @var String
         */
        define("TR_ENGINE_DIR", $this->getBaseDir());

        /**
         * Adresse URL complète jusqu'à TR ENGINE.
         *
         * @var String
         */
        define("TR_ENGINE_URL", $this->getUrlAddress());

        /**
         * Le système d'exploitation qui exécute TR ENGINE.
         *
         * @var String
         */
        define("TR_ENGINE_PHP_OS", $this->getPhpOs());

        /**
         * Numéro de version du moteur.
         *
         * Controle de révision
         * xx -> version courante
         * xx -> fonctionnalitées ajoutées
         * xx -> bugs ou failles critiques corrigés
         * xx -> bug mineur
         *
         * @var String
         */
        define("TR_ENGINE_VERSION", "0.5.0.0");
    }

    /**
     * Retourne le chemin jusqu'à la racine.
     *
     * @return String
     */
    private function getBaseDir() {
        $baseDir = "";

        // Recherche du chemin absolu depuis n'importe quel fichier
        if (defined("TR_ENGINE_INDEX")) {
            // Nous sommes dans l'index
            $baseDir = str_replace('\\', '/', getcwd());
        } else {
            // Chemin de base
            $baseName = str_replace($_SERVER['SCRIPT_NAME'], "", $_SERVER['SCRIPT_FILENAME']);
            $workingDirectory = getcwd();

            if (!empty($workingDirectory)) {
                // Chemin jusqu'au fichier
                $currentPath = str_replace('\\', '/', $workingDirectory);
                // On isole le chemin en plus jusqu'au fichier
                $path = str_replace($baseName, "", $currentPath);
            }

            $path = substr($path, 1); // Suppression du slash

            if (!empty($path)) { // Recherche du chemin complet
                // Vérification en se reperant sur l'emplacement du fichier de configuration
                while (!is_file($baseName . "/" . $path . "/configs/config.inc.php")) {
                    // On remonte d'un cran
                    $path = dirname($path);
                    // La recherche n'aboutira pas
                    if ($path == ".") {
                        break;
                    }
                }
            }

            // Verification du résultat
            if (!empty($path) && is_file($baseName . "/" . $path . "/configs/config.inc.php")) {
                $baseDir = $baseName . "/" . $path;
            } else if (is_file($baseName . "/configs/config.inc.php")) {
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
     * @return String
     */
    private function getUrlAddress() {
        // Recherche de l'URL courante
        $urlTmp = $_SERVER["REQUEST_URI"];

        if (substr($urlTmp, -1) == "/") {
            $urlTmp = substr($urlTmp, 0, strlen($urlTmp) - 1);
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
        return ((empty($urlFinal)) ? @$_SERVER["SERVER_NAME"] : @$_SERVER["SERVER_NAME"] . "/" . $urlFinal);
    }

    /**
     * Retourne la plateforme sur lequel est PHP.
     *
     * @return String
     */
    private function getPhpOs() {
        return strtoupper(substr(PHP_OS, 0, 3));
    }

}

?>