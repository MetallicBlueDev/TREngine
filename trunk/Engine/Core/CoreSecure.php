<?php

namespace TREngine\Engine\Core;

use Exception;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Exec\ExecString;
use TREngine\Engine\Fail\FailBase;
use TREngine\Engine\Fail\FailEngine;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de la sécurité du noyaux.
 * Inclus un système de sécurité, une analyse rapidement les données reçues et une configuration les erreurs.
 *
 * @author Sébastien Villemain
 */
class CoreSecure {

    /**
     * Retourne la liste des caractères interdits.
     *
     * @var array
     */
    const BAD_QUERY_STRINGS = array(
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
        "document.write",
        "onmouse",
        "<script",
        "<iframe",
        "<applet",
        "<meta",
        "<style",
        "<form",
        "<img",
        "<body",
        "<link",
        "<comment",
        "..",
        "http://",
        "%3C%3F");

    /**
     * Instance de cette classe.
     *
     * @var CoreSecure
     */
    private static $secure = null;

    /**
     * Verrouillage de la sécurité (exception, erreur critique).
     *
     * @var bool
     */
    private $locked = false;

    /**
     * Stats et debug mode.
     *
     * @var bool
     */
    private $debuggingMode = false;

    /**
     * Routine de sécurisation.
     */
    private function __construct() {
        $this->configureOutput();
        $this->checkServerQueryString();
        $this->checkServerRequest();
        $this->checkGlobals();

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
    public static function &getInstance(): CoreSecure {
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
     * @return bool
     */
    public static function &debuggingMode(): bool {
        $rslt = false;

        if (self::$secure !== null) {
            $rslt = self::$secure->debuggingMode;
        }
        return $rslt;
    }

    /**
     * Détermine si il y a verrouillage de la sécurité (risque potentiel).
     *
     * @return bool
     */
    public function &locked(): bool {
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
    public function throwException(string $customMessage, Exception $ex = null, array $argv = array()) {
        $this->locked = true;

        if ($ex === null) {
            $ex = new FailEngine($customMessage);
        }

        // Préparation du template debug
        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assign("errorMessageTitle", $this->getErrorMessageTitle($customMessage));
        $libMakeStyle->assign("errorMessage", $this->getDebugMessage($ex, $argv));

        // Affichage du template en debug si problème
        $libMakeStyle->display("debug", true);

        // Arret du moteur
        exit();
    }

    /**
     * Retourne le type d'erreur courant sous forme de message.
     *
     * @param string $customMessage
     * @return string
     */
    private function &getErrorMessageTitle(string $customMessage): string {
        // Message d'erreur depuis une constante
        $errorMessageTitle = "ERROR_DEBUG_" . strtoupper($customMessage);

        if (defined($errorMessageTitle)) {
            $errorMessageTitle = ExecString::entitiesUtf8(constant($errorMessageTitle));
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
     * @return array
     */
    private function &getDebugMessage(Exception $ex = null, array $argv = array()): array {
        $errorMessages = array();

        $this->appendException($ex, $errorMessages);

        // Fusion des informations supplémentaires
        if (!empty($argv)) {
            $errorMessages[] = "";
            $errorMessages[] = "<span class=\"text_bold\">Additional information about the error:</span>";
            $errorMessages = array_merge($errorMessages, $argv);
        }

        $this->appendSqlErrors($errorMessages);
        $this->appendLoggerErrors($errorMessages);
        return $errorMessages;
    }

    /**
     * Ajoute des informations sur l'exception.
     *
     * @param Exception $ex
     * @param array $errorMessages
     */
    private function &appendException(Exception $ex, array &$errorMessages) {
        if ($ex !== null) {
            $this->appendExceptionMessage($ex, $errorMessages);
            $errorMessages[] = "";
            $this->appendExceptionTrace($ex, $errorMessages);
        }
    }

    /**
     * Ajoute une information générale sur l'exception.
     *
     * @param Exception $ex
     * @param array $errorMessages
     */
    private function &appendExceptionMessage(Exception $ex, array &$errorMessages) {
        if ($this->debuggingMode) {
            if ($ex instanceof FailBase) {
                $errorMessages[] = $ex->getFailInformation();
            } else {
                $errorMessages[] = "Exception PHP (" . $ex->getCode() . ") : " . $ex->getMessage();
            }
        }
    }

    /**
     * Ajoute la trace (pile d'appel) de l'exception.
     *
     * @param Exception $ex
     * @param array $errorMessages
     */
    private function &appendExceptionTrace(Exception $ex, array &$errorMessages) {
        foreach ($ex->getTrace() as $traceValue) {
            $errorLine = "";

            if (is_array($traceValue)) {
                foreach ($traceValue as $key => $value) {
                    if ($key === "file" || $key === "function") {
                        $value = preg_replace("/([a-zA-Z0-9._]+).php/", "<span class=\"text_bold\">\\1</span>.php", $value);
                        $errorLine .= " <span class=\"text_bold\">" . $key . "</span> " . $value;
                    } else if ($key === "line" || $key == "class") {
                        $errorLine .= " in <span class=\"text_bold\">" . $key . "</span> " . $value;
                    }
                }
            }

            if (!empty($errorLine)) {
                $errorMessages[] = $errorLine;
            }
        }
    }

    /**
     * Ajoute des informations sur les dernières erreurs SQL.
     *
     * @param array $errorMessages
     */
    private function &appendSqlErrors(array &$errorMessages) {
        if ($this->debuggingMode && CoreLoader::isCallable("CoreSql")) {
            if (CoreSql::hasConnection()) {
                $sqlErrors = CoreSql::getInstance()->getLastError();

                if (!empty($sqlErrors)) {
                    $errorMessages[] = "";
                    $errorMessages[] = "<span class=\"text_bold\">Last Sql error message:</span>";
                    $errorMessages = array_merge($errorMessages, $sqlErrors);
                }
            }
        }
    }

    /**
     * Ajoute des informations sur les erreurs collectées dans le journal.
     *
     * @param array $errorMessages
     */
    private function &appendLoggerErrors(array &$errorMessages) {
        if (CoreLoader::isCallable("CoreLogger")) {
            $loggerExceptions = CoreLogger::getExceptions();

            if (!empty($loggerExceptions)) {
                $errorMessages[] = "";
                $errorMessages[] = "<span class=\"text_bold\">Exceptions logged:</span>";
                $errorMessages = array_merge($errorMessages, $loggerExceptions);
            }
        }
    }

    /**
     * Réglages de la sortie d'erreurs.
     * Affichage de toutes les erreurs.
     */
    private function configureOutput() {
        error_reporting(defined("E_ALL") ? E_ALL : E_ERROR | E_WARNING | E_PARSE);
    }

    /**
     * Vérification des données reçues (depuis QUERY_STRING).
     */
    private function checkServerQueryString() {
        $queryString = strtolower(rawurldecode(self::getGlobalServer("QUERY_STRING")));

        foreach (self::BAD_QUERY_STRINGS as $badStringValue) {
            if (strpos($queryString, $badStringValue)) {
                $this->throwException("badQueryString");
            }
        }
    }

    /**
     * Vérification de la provenance des requêtes.
     */
    private function checkServerRequest() {
        if (self::getGlobalServer("REQUEST_METHOD") === "POST" && !empty(self::getGlobalServer("HTTP_REFERER"))) {
            // Vérification du demandeur de la méthode POST
            if (!preg_match("/" . self::getGlobalServer("HTTP_HOST") . "/", self::getGlobalServer("HTTP_REFERER"))) {
                $this->throwException("badRequestReferer");
            }
        }
    }

    /**
     * Vérification des variables globales.
     */
    private function checkGlobals() {
        $this->addSlashesForQuotes(self::getGlobalGet());
        $this->addSlashesForQuotes(self::getGlobalPost());
        $this->addSlashesForQuotes(self::getGlobalCookie());
    }

    /**
     * Ajoute un antislash pour chaque quote.
     *
     * @param mixed $key objet sans antislash
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

    /**
     * Classe autorisée à manipuler $_GET (lecture et écriture).
     *
     * @return array
     */
    private static function &getGlobalGet(): array {
        $globalGet = &${"_" . "GET"};
        return $globalGet;
    }

    /**
     * Classe autorisée à manipuler $_POST (lecture et écriture).
     *
     * @return array
     */
    private static function &getGlobalPost(): array {
        $globalPost = &${"_" . "POST"};
        return $globalPost;
    }

    /**
     * Classe autorisée à manipuler $_COOKIE (lecture et écriture).
     *
     * @return array
     */
    private static function &getGlobalCookie(): array {
        $globalCookie = &${"_" . "COOKIE"};
        return $globalCookie;
    }

    /**
     * Classe autorisée à manipuler $_SERVER (lecture seule).
     *
     * @param string $keyName
     * @return string
     */
    private static function getGlobalServer($keyName) {
        return ${"_" . "SERVER"}[$keyName];
    }

}
