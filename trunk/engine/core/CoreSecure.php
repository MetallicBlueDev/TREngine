<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Système de sécurité.
 * Analyse rapidement les données reçues.
 * Configure les erreurs.
 * Capture la configuration.
 *
 * @author Sébastien Villemain
 */
class CoreSecure {

    /**
     * Instance de cette classe.
     *
     * @var CoreSecure
     */
    private static $secure = null;

    /**
     * Verrouillage de la sécurité (exception, erreur critique).
     *
     * @var boolean
     */
    private $locked = false;

    /**
     * Stats et debug mode.
     *
     * @var boolean
     */
    private $debuggingMode = false;

    /**
     * Routine de sécurisation.
     */
    private function __construct() {
        $this->checkError();
        $this->checkQueryString();
        $this->checkRequestReferer();
        $this->checkGPC();

        // Attention: il ne faut pas définir l'index avant CoreInfo mais avant CoreLoader
        if (!defined("TR_ENGINE_INDEX")) {
            $this->locked = true;
            define("TR_ENGINE_INDEX", true);
        }

        $this->debuggingMode = CoreRequest::getBoolean("debuggingMode", false, "GET");
    }

    /**
     * Retourne l'instance du gestionnaire de sécurité.
     *
     * @return CoreSecure
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$secure;
    }

    /**
     * Vérification de l'instance du gestionnaire de sécurité.
     */
    public static function checkInstance() {
        if (self::$secure === null) {
            self::$secure = new CoreSecure();

            // Si nous ne sommes pas passé par l'index
            if (self::$secure->locked()) {
                self::$secure->throwException("badUrl");
            }
        }
    }

    /**
     * Vérifie si le mode de statistique et de debug est actif.
     *
     * @return boolean
     */
    public static function &debuggingMode() {
        $rslt = false;

        if (self::$secure !== null) {
            $rslt = self::$secure->debuggingMode;
        }
        return $rslt;
    }

    /**
     * Détermine si il y a verrouillage de la sécurité (risque potentiel).
     *
     * @return boolean
     */
    public function &locked() {
        return $this->locked;
    }

    /**
     * Affiche un message d'erreur au client, mettra fin à l'exécution du moteur.
     * Cette fonction est activé si une erreur est détectée.
     *
     * @param string $customMessage Message d'erreur.
     * @param Exception $ex L'exception interne levée.
     * @param array $argv Argument suplementaire d'information sur l'erreur.
     */
    public function throwException($customMessage, Exception $ex = null, array $argv = array()) {
        $this->locked = true;

        if ($ex === null) {
            $ex = new FailEngine($customMessage);
        }

        // Préparation du template debug
        $libsMakeStyle = new LibsMakeStyle();
        $libsMakeStyle->assign("errorMessageTitle", $this->getErrorMessageTitle($customMessage));
        $libsMakeStyle->assign("errorMessage", $this->getDebugMessage($ex, $argv));

        // Affichage du template en debug si problème
        $libsMakeStyle->display("debug", true);

        // Arret du moteur
        exit();
    }

    /**
     * Retourne le type d'erreur courant sous forme de message.
     *
     * @param string $customMessage
     * @return string $errorMessageTitle
     */
    private function &getErrorMessageTitle($customMessage) {
        // Message d'erreur depuis une constante
        $errorMessageTitle = "ERROR_DEBUG_" . strtoupper($customMessage);

        if (defined($errorMessageTitle)) {
            $errorMessageTitle = Exec_Entities::entitiesUtf8(constant($errorMessageTitle));
        } else {
            $errorMessageTitle = "Stop loading";

            if ($this->debuggingMode) {
                $errorMessageTitle .= ": " . $customMessage;
            }
        }
        return $errorMessageTitle;
    }

    /**
     * Analyse l'erreur et prépare l'affichage de l'erreur.
     *
     * @param Exception $ex L'exception interne levée.
     * @param array $argv Argument supplémentaire d'information sur l'erreur.
     * @return array $errorMessage
     */
    private function &getDebugMessage(Exception $ex = null, array $argv = array()) {
        // Tableau avec les lignes d'erreurs
        $errorMessage = array();

        // Analyse de l'exception
        if ($ex !== null) {
            if ($this->debuggingMode) {
                if ($ex instanceof FailBase) {
                    $errorMessage[] = $ex->getFailInformation();
                } else {
                    $errorMessage[] = "Exception PHP (" . $ex->getCode() . ") : " . $ex->getMessage();
                }
            }

            foreach ($ex->getTrace() as $traceValue) {
                $errorLine = "";

                if (is_array($traceValue)) {
                    foreach ($traceValue as $key => $value) {
                        if ($key === "file" || $key === "function") {
                            $value = preg_replace("/([a-zA-Z0-9._]+).php/", "<b>\\1</b>.php", $value);
                            $errorLine .= " <b>" . $key . "</b> " . $value;
                        } else if ($key === "line" || $key == "class") {
                            $errorLine .= " in <b>" . $key . "</b> " . $value;
                        }
                    }
                }

                if (!empty($errorLine)) {
                    $errorMessage[] = $errorLine;
                }
            }
        }

        // Fusion des informations supplémentaires
        if (!empty($argv)) {
            $errorMessage[] = " ";
            $errorMessage[] = "<b>Additional information about the error:</b>";
            $errorMessage = array_merge($errorMessage, $argv);
        }

        if (CoreLoader::isCallable("CoreSession") && CoreLoader::isCallable("CoreSql")) {
            if (CoreSql::hasConnection()) {
                if (CoreSession::getInstance()->getUserInfos()->hasRegisteredRank()) {
                    $sqlErrors = CoreSql::getInstance()->getLastError();

                    if (!empty($sqlErrors)) {
                        $errorMessage[] = " ";
                        $errorMessage[] = "<b>Last Sql error message:</b>";
                        $errorMessage = array_merge($errorMessage, $sqlErrors);
                    }
                }
            }
        }

        if (CoreLoader::isCallable("CoreLogger")) {
            $loggerExceptions = CoreLogger::getExceptions();

            if (!empty($loggerExceptions)) {
                $errorMessage[] = " ";
                $errorMessage[] = "<b>Exceptions logged:</b>";
                $errorMessage = array_merge($errorMessage, $loggerExceptions);
            }
        }
        return $errorMessage;
    }

    /**
     * Réglages des sorties d'erreurs.
     */
    private function checkError() {
        error_reporting(defined("E_ALL") ? E_ALL : E_ERROR | E_WARNING | E_PARSE);
    }

    /**
     * Vérification des données reçues (Query string).
     */
    private function checkQueryString() {
        $queryString = strtolower(rawurldecode($_SERVER['QUERY_STRING']));

        $badStrings = array(
            "select",
            "union",
            "insert",
            "update",
            "and",
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

        foreach ($badStrings as $badStringValue) {
            if (strpos($queryString, $badStringValue)) {
                $this->throwException("badQueryString");
            }
        }
    }

    /**
     * Vérification des envois POST.
     */
    private function checkRequestReferer() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!empty($_SERVER['HTTP_REFERER'])) {
                if (!preg_match("/" . $_SERVER['HTTP_HOST'] . "/", $_SERVER['HTTP_REFERER'])) {
                    $this->throwException("badRequestReferer");
                }
            }
        }
    }

    /**
     * Fonction de substitution pour MAGIC_QUOTES_GPC.
     */
    private function checkGPC() {
        // Désactivation de MAGIC_QUOTES_GPC
        if (function_exists("set_magic_quotes_runtime") && function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
            set_magic_quotes_runtime(false);
        }

        $this->addSlashesForQuotes($_GET);
        $this->addSlashesForQuotes($_POST);
        $this->addSlashesForQuotes($_COOKIE);
    }

    /**
     * Ajoute un antislash pour chaque quote.
     *
     * @param array $key objet sans antislash
     */
    private function addSlashesForQuotes(&$key) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
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

}
