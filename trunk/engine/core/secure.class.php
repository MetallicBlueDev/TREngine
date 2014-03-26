<?php
// Attention au double instances de Core_Secure...
if (preg_match("/secure.class.php/ie", $_SERVER['PHP_SELF'])) {
    if (!defined("TR_ENGINE_INDEX")) {
        new Core_Secure();
    }
}

/**
 * Système de sécurité.
 * Analyse rapidement les données reçues.
 * Configure les erreurs.
 * Capture la configuration.
 *
 * @author Sébastien Villemain
 */
class Core_Secure {

    /**
     * Instance de cette classe.
     *
     * @var Core_Secure
     */
    private static $secure = null;

    /**
     * Stats et debug mode.
     *
     * @var boolean
     */
    private static $debuggingMode = false;

    /**
     * Routine de sécurisation.
     */
    private function __construct() {
        $this->checkError();
        $this->checkQueryString();
        $this->checkRequestReferer();

        // Si nous ne sommes pas passé par l'index
        if (!defined("TR_ENGINE_INDEX")) {
            if (!class_exists("Core_Info")) {
                require("info.class.php");
            }

            define("TR_ENGINE_INDEX", true);
            $this->throwException("badUrl");
        }

        // A exécuter uniquement après avoir vérifié l'index et le numéro de version
        $this->checkGPC();
    }

    /**
     * Créé une instance de la classe si elle n'existe pas.
     * Retourne l'instance de la classe.
     *
     * @return Core_Secure
     */
    public static function &getInstance($debuggingMode = false) {
        if (self::$secure == null) {
            self::$debuggingMode = $debuggingMode;
            self::$secure = new self();
        }
        return self::$secure;
    }

    /**
     * Réglages des sorties d'erreurs.
     */
    private function checkError() {
        // Réglages des sorties d'erreur
        $errorReporting = E_ERROR | E_WARNING | E_PARSE;

        if (self::$debuggingMode) {
            $errorReporting = $errorReporting | E_DEPRECATED | E_STRICT;
        }
        error_reporting($errorReporting);
    }

    /**
     * Vérification des données reçues (Query String).
     */
    private function checkQueryString() {
        $queryString = strtolower(rawurldecode($_SERVER['QUERY_STRING']));
        $badString = array(
            "SELECT",
            "UNION",
            "INSERT",
            "UPDATE",
            "AND",
            "%20union%20",
            "/*",
            "*/union/*",
            "+union+",
            "load_file",
            "outfile",
            "document.cookie",
            "onmouse",
            "<script",
            "<iframe",
            "<applet",
            "<meta",
            "<style",
            "<form",
            "<img",
            "<body",
            "<link");

        foreach ($badString as $stringValue) {
            if (strpos($queryString, $stringValue)) {
                $this->throwException();
            }
        }
    }

    /**
     * Vérification des envois POST.
     */
    private function checkRequestReferer() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_SERVER['HTTP_REFERER'])) {
                if (!preg_match("/" . $_SERVER['HTTP_HOST'] . "/", $_SERVER['HTTP_REFERER'])) {
                    $this->throwException();
                }
            }
        }
    }

    /**
     * Fonction de substitution pour MAGIC_QUOTES_GPC.
     */
    private function checkGPC() {
        if (TR_ENGINE_PHP_VERSION < "5.3.0" && function_exists("set_magic_quotes_runtime")) {
            set_magic_quotes_runtime(0);
        }
        $this->addSlashesForQuotes($_GET);
        $this->addSlashesForQuotes($_POST);
        $this->addSlashesForQuotes($_COOKIE);
    }

    /**
     * Ajoute un antislash pour chaque quote.
     *
     * @param $key objet sans antislash
     */
    private function addSlashesForQuotes($key) {
        if (is_array($key)) {
            while (list($k, $v) = each($key)) {
                if (is_array($key[$k])) {
                    $this->addSlashesForQuotes($key[$k]);
                } else {
                    $key[$k] = addslashes($v);
                }
            }
            reset($key);
        } else {
            $key = addslashes($key);
        }
    }

    /**
     * Affiche un message d'erreur au client.
     * Cette fonction est activé si une erreur est détectée.
     *
     * @param $ie String or object L'exception interne levée.
     * @param $argv String or array argument suplementaire d'information sur l'erreur.
     */
    public function throwException($ie = "", $argv = "") {
        // Charge le loader si il faut
        if (!class_exists("Core_Loader")) {
            require(TR_ENGINE_DIR . "/engine/core/loader.class.php");
        }

        // Gestionnaires suivants obligatoires
        Core_Loader::classLoader("Exec_Entities");
        Core_Loader::classLoader("Core_Request");
        Core_Loader::classLoader("Exec_Cookie");
        Core_Loader::classLoader("Exec_Crypt");
        Core_Loader::classLoader("Core_Translate");
        Core_Translate::makeInstance();
        Core_Loader::classLoader("Core_Html");
        Core_Loader::classLoader("Libs_MakeStyle");

        // Préparation du template debug
        $libsMakeStyle = new Libs_MakeStyle();
        $libsMakeStyle->assign("errorMessageTitle", $this->getErrorMessageTitle($ie));
        $libsMakeStyle->assign("errorMessage", $this->getDebugMessage($ie, $argv));
        // Affichage du template en debug si problème
        $libsMakeStyle->displayDebug("debug.php");

        exit(); // Arret du moteur
    }

    /**
     * Analyse l'erreur et prépare l'affichage de l'erreur.
     *
     * @param $ie String or object L'exception interne levée.
     * @param $argv array argument suplementaire d'information sur l'erreur.
     * @return String $errorMessage
     */
    private function getDebugMessage($ie, $argv) {
        // Tableau avec les lignes d'erreurs
        $errorMessage = array();

        // Analyse de l'exception
        if (is_object($ie)) { // Pour une exception levé de type object
            $trace = $ie->getTrace();
            $nbTrace = count($trace);

            for ($i = $nbTrace; $i > 0; $i--) {
                $errorLine = "";
                if (is_array($trace[$i])) {
                    foreach ($trace[$i] as $key => $value) {
                        if ($key == "file") {
                            $value = preg_replace("/([a-zA-Z0-9._]+).php/", "<b>\\1</b>.php", $value);
                            $errorLine .= " <b>" . $key . "</b> " . $value;
                        } else if ($key == "line" || $key == "class") {
                            $errorLine .= " in <b>" . $key . "</b> " . $value;
                        }
                    }
                }
                if (!empty($errorLine)) {
                    $errorMessage[] = $errorLine;
                }
            }
        }
        if (!empty($argv)) { // Si des informations suplementaire sont disponibles
            if (is_array($argv)) {
                $errorMessage = array_merge($errorMessage, $argv);
            } else {
                $errorMessage[] = $argv;
            }
        }
        if (Core_Loader::isCallable("Core_Session") && Core_Loader::isCallable("Core_Sql")) {
            if (Core_Session::$userRank > 1) {
                $errorMessage = array_merge($errorMessage, (array) Core_Sql::getLastError());
            }
        }
        return $errorMessage;
    }

    /**
     * Retourne le type d'erreur courant sous forme de message.
     *
     * @param $cmd
     * @return String $errorMessageTitle
     */
    private function getErrorMessageTitle($ie) {
        // En fonction du type d'erreur
        $cmd = "";

        if (is_object($ie)) {
            $cmd = $ie->getMessage();
        } else {
            $cmd = $ie;
        }

        // Message d'erreur
        $errorMessageTitle = "ERROR_DEBUG_" . strtoupper($cmd);

        if (defined($errorMessageTitle)) {
            return Exec_Entities::entitiesUtf8(constant($errorMessageTitle));
        }
        return "Stop loading (Fatal error unknown).";
    }

    /**
     * Vérifie si le mode de statistique et de debug est actif
     *
     * @return boolean
     */
    public static function &isDebuggingMode() {
        return self::$debuggingMode;
    }

}

?>