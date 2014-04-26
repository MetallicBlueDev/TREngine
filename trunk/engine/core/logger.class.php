<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de messages.
 *
 * @author Sébastien Villemain
 */
class Core_Logger {

    /**
     * Instance de cette classe.
     *
     * @var Core_Secure
     */
    private static $coreLogger = null;

    /**
     * Exceptions destinées au développeur.
     *
     * @var array
     */
    private $exceptions = array();

    /**
     * Erreurs destinées au client.
     *
     * @var array
     */
    private $Errors = array();

    /**
     * Avertissement destinées au client.
     *
     * @var array
     */
    private $warnings = array();

    /**
     * Informations destinées au client.
     *
     * @var array
     */
    private $informations = array();

    /**
     * Tableau contenant toutes les lignes sql envoyées.
     *
     * @var array
     */
    private $sqlRequest = array();

    /**
     * Retourne l'instance du gestionnaire de message.
     *
     * @return Core_Logger
     */
    public static function &getInstance() {
        if (self::$coreLogger === null) {
            self::$coreLogger = new self();
        }
        return self::$coreLogger;
    }

    /**
     * Ajoute une nouvelle exception.
     *
     * @param $msg
     */
    public static function addException($msg) {
        $msg = strtolower($msg);
        $msg[0] = strtoupper($msg[0]);
        self::$exceptions[] = date('Y-m-d H:i:s') . " : " . $msg . ".";
    }

    /**
     * Ajoute une requête sql.
     *
     * @param $sql string
     */
    public static function addSqlRequest($sql) {
        self::$sqlRequest[] = $sql;
    }

    /**
     * Ajoute une alerte.
     *
     * @param string $msg
     */
    public static function addErrorMessage($msg) {
        self::addMessage($msg, "alert");
    }

    /**
     * Ajoute un avertissement.
     *
     * @param string $msg
     */
    public static function addWarningMessage($msg) {
        self::addMessage($msg, "note");
    }

    /**
     * Ajoute une information.
     *
     * @param string $msg
     */
    public static function addInformationMessage($msg) {
        self::addMessage($msg, "info");
    }

    /**
     * Ajoute un nouveau message.
     *
     * @param $msg string
     * @param $type string le type d'erreur (alert / note / info)
     */
    private static function addMessage($msg, $type = "alert") {
        switch ($type) {
            case 'alert':
                self::$Errors[] = $msg;
                break;
            case 'note':
                self::$warnings[] = $msg;
                break;
            case 'info':
                self::$informations[] = $msg;
                break;
            default:
                self::addMessage($msg);
                break;
        }
    }

    /**
     * Retourne les messages pré-formatées.
     *
     * @return string
     */
    public static function displayMessages() {
        if (Core_Loader::isCallable("Core_Main")) {
            $error = self::hasMessages();
            $display = "none";

            if ($error) {
                $display = "block";
                $rslt .= "<ul class=\"exception\">";
                foreach (self::$Errors as $alertError) {
                    $rslt .= "<li class=\"alert\"><div>" . $alertError . "</div></li>";
                }
                foreach (self::$warnings as $noteError) {
                    $rslt .= "<li class=\"note\"><div>" . $noteError . "</div></li>";
                }
                foreach (self::$informations as $infoError) {
                    $rslt .= "<li class=\"info\"><div>" . $infoError . "</div></li>";
                }
                $rslt .= "</ul>";
            }

            // Réaction différente en fonction du type d'affichage demandée
            if (Core_Main::getInstance()->isDefaultLayout()) {
                echo "<div id=\"block_message\" style=\"display: " . $display . ";\">" . $rslt . "</div>";
            } else if ($error) {
                if (Core_Loader::isCallable("Core_Html")) {
                    if (Core_Html::getInstance()->isJavascriptEnabled()) {
                        Core_Html::getInstance()->addJavascript("displayMessage('" . addslashes($rslt) . "');");
                        $rslt = "";
                    }
                }

                if (!empty($rslt)) {
                    echo $rslt;
                }
            }
        }
    }

    /**
     * Vérifie si une exception est détectée.
     *
     * @return boolean
     */
    private static function hasExceptions() {
        return (!empty(self::$exceptions));
    }

    /**
     * Vérifie si une erreur mineur est détectée.
     *
     * @return boolean
     */
    private static function hasMessages() {
        return (!empty(self::$Errors) || !empty(self::$warnings) || !empty(self::$informations));
    }

    /**
     * Capture les exceptions en chaine de caractères.
     *
     * @param $var array
     * @return string
     */
    private static function &serializeData($var) {
        $content = "";

        foreach ($var as $msg) {
            $content .= $msg . "\n";
        }
        return $content;
    }

    /**
     * Affichage des informations de debug.
     */
    public static function displayDebugInformations() {
        if (Core_Loader::isCallable("Core_Main") && Core_Loader::isCallable("Core_Session")) {
            if (Core_Session::getInstance()->userRank > 1) {
                echo "<div style=\"color: blue;\"><br />"
                . "***********************SQL REQUESTS (" . count(self::$sqlRequest) . ") :<br />";
                if (!empty(self::$sqlRequest)) {
                    echo str_replace("\n", "<br />", self::serializeData(self::$sqlRequest));
                } else {
                    echo "<span style=\"color: green;\">No sql request registred.</span>";
                }

                echo "<br /><br />***********************EXCEPTIONS (" . count(self::$exceptions) . ") :<br />";

                if (self::hasExceptions()) {
                    echo "<span style=\"color: red;\">"
                    . str_replace("\n", "<br />", self::serializeData(self::$exceptions))
                    . "</span>";
                } else {
                    echo "<span style=\"color: green;\">No exception registred.</span>";
                }

                echo "<br /><br />***********************BENCHMAKER :<br />"
                . "Core : " . Exec_Marker::getTime("core") . " ms"
                . "<br />Launcher : " . Exec_Marker::getTime("launcher") . " ms"
                . "<br />All : " . Exec_Marker::getTime("all") . " ms"
                . "</div>";
            }
        }
    }

    /**
     * Ecriture du rapport dans un fichier log.
     */
    public static function logException() {
        if (Core_Loader::isCallable("Core_CacheBuffer")) {
            // Activer ou désactiver le rapport d'erreur dans un log.
            // Activer l'"criture dans un fichier log.
            if (self::$writeLog && self::hasExceptions()) {
                // Positionne dans le cache
                Core_CacheBuffer::setSectionName("log");
                // Ecriture a la suite du cache
                Core_CacheBuffer::writingCache("exception_" . date('Y-m-d') . ".log.php", self::serializeData(self::$exceptions), false);
            }
        }
    }

}
