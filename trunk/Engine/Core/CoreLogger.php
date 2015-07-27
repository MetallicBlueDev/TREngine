<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecTimeMarker;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de messages.
 *
 * @author Sébastien Villemain
 */
class CoreLogger {

    /**
     * Exceptions destinées au développeur.
     *
     * @var array
     */
    private static $exceptions = array();

    /**
     * Messages destinées au client.
     *
     * @var array
     */
    private static $messages = array();

    /**
     * Tableau contenant toutes les lignes sql envoyées.
     *
     * @var array
     */
    private static $sqlRequest = array();

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
     * Retourne les messages pré-formatées.
     *
     * @return string
     */
    public static function displayMessages() {
        if (CoreLoader::isCallable("CoreMain")) {
            $hasMessages = !empty(self::$messages);
            $display = "none";
            $rslt = "";

            if ($hasMessages) {
                $display = "block";
                $rslt .= "<ul class=\"exception\">";

                foreach (self::$messages as $type => $infos) {
                    foreach ($infos as $message) {
                        $rslt .= "<li class=\"" . $type . "\"><div>" . $message . "</div></li>";
                    }
                }

                $rslt .= "</ul>";
            }

            // Réaction différente en fonction du type d'affichage demandée
            if (CoreMain::getInstance()->isDefaultLayout()) {
                echo "<div id=\"panel_message\" style=\"display: " . $display . ";\">" . $rslt . "</div>";
            } else if ($hasMessages) {
                if (CoreLoader::isCallable("CoreHtml")) {
                    if (CoreHtml::getInstance()->javascriptEnabled()) {
                        CoreHtml::getInstance()->addJavascript("displayMessage('" . addslashes($rslt) . "');");
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
     * Affichage des informations de debug.
     */
    public static function displayDebugInformations() {
        if (CoreLoader::isCallable("CoreMain") && CoreLoader::isCallable("CoreSession")) {
            if (CoreSession::getInstance()->getUserInfos()->hasRegisteredRank()) {
                echo "<div style=\"color: blue;\"><br />"
                . "***********************SQL REQUESTS (" . count(self::$sqlRequest) . ") :<br />";

                if (!empty(self::$sqlRequest)) {
                    echo str_replace("\n", "<br />", self::serializeData(self::$sqlRequest));
                } else {
                    echo "<span style=\"color: #2EFE2E;\">No sql request registred.</span>";
                }

                echo "<br /><br />***********************EXCEPTIONS (" . count(self::$exceptions) . ") :<br />";

                if (self::hasExceptions()) {
                    echo "<span style=\"color: red;\">"
                    . str_replace("\n", "<br />", self::serializeData(self::$exceptions))
                    . "</span>";
                } else {
                    echo "<span style=\"color: #2EFE2E;\">No exception registred.</span>";
                }

                echo "<br /><br />***********************BENCHMAKER :<br />"
                . "Core : " . ExecTimeMarker::getMeasurement("core") . " ms"
                . "<br />Launcher : " . ExecTimeMarker::getMeasurement("launcher") . " ms"
                . "<br />Main : " . ExecTimeMarker::getMeasurement("main") . " ms"
                . "<br /><span class=\"text_bold\">All : " . ExecTimeMarker::getMeasurement("all") . " ms</span>"
                . "</div>";
            }
        }
    }

    /**
     * Retourne les exceptions rencontrées.
     *
     * @return array
     */
    public static function &getExceptions() {
        return self::$exceptions;
    }

    /**
     * Ecriture du rapport d'erreur dans un fichier log.
     */
    public static function logException() {
        if (CoreLoader::isCallable("CoreCache")) {
            if (self::hasExceptions()) {
                // Ecriture à la suite du rapport
                CoreCache::getInstance(CoreCache::SECTION_LOGGER)->writeCache("exception_" . date('Y-m-d') . ".log.php", self::serializeData(self::$exceptions), false);
            }
        }
    }

    /**
     * Ajoute un nouveau message.
     *
     * @param string $msg
     * @param string $type Le type d'erreur (alert / note / info)
     */
    private static function addMessage($msg, $type = "alert") {
        switch ($type) {
            case 'alert':
            case 'note':
            case 'info':
                self::$messages[$type][] = $msg;
                break;
            default:
                self::addMessage($msg);
                break;
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

}
