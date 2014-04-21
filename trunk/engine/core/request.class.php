<?php

/**
 * Gestionnaire de requêtes URL.
 *
 * @author Sébastien Villemain
 */
class Core_Request {

    /**
     * Tableau buffer des requêtes.
     *
     * @var array
     */
    private static $buffer = array();

    /**
     * Retourne la variable demandée de type int
     *
     * @param $name string Nom de la variable
     * @param $default int Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return int
     */
    public static function &getInt($name, $default = 0, $hash = "default") {
        return self::getVars($name, "INT", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type float
     *
     * @param $name string Nom de la variable
     * @param $default float Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return float
     */
    public static function &getFloat($name, $default = 0.0, $hash = "default") {
        return self::getVars($name, "FLOAT", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type double
     *
     * @param $name string Nom de la variable
     * @param $default double Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return double
     */
    public static function &getDouble($name, $default = 0.0, $hash = "default") {
        return self::getVars($name, "DOUBLE", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type string valide base64
     *
     * @param $name string Nom de la variable
     * @param $default string Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return string
     */
    public static function &getBase64($name, $default = "", $hash = "default") {
        return self::getVars($name, "BASE64", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type string
     *
     * @param $name string Nom de la variable
     * @param $default string Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return string
     */
    public static function &getWord($name, $default = "", $hash = "default") {
        return self::getVars($name, "WORD", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type string
     *
     * @param $name string Nom de la variable
     * @param $default string Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return string
     */
    public static function &getString($name, $default = "", $hash = "default") {
        return self::getVars($name, "STRING", $default, $hash);
    }

    /**
     * Retourne le type de méthode utlisé pour la requête.
     *
     * @return type
     */
    public static function getRequestMethod() {
        return self::getRequest();
    }

    /**
     * Retourne le contenu de la requête demandée.
     *
     * @param $hash string
     * @return array
     */
    private static function &getRequest($hash = "default") {
        $hash = strtoupper($hash);
        $input = "";

        switch ($hash) {
            case 'GET':
                $input = &$_GET;
                break;
            case 'POST':
                $input = &$_POST;
                break;
            case 'FILES':
                $input = &$_FILES;
                break;
            case 'COOKIE':
                $input = &$_COOKIE;
                break;
            case 'ENV':
                $input = &$_ENV;
                break;
            case 'SERVER':
                $input = &$_SERVER;
                break;
            default:
                $hash = $_SERVER['REQUEST_METHOD'];

                if (!empty($hash)) {
                    $input = &self::getRequest($hash);
                }
        }
        return $input;
    }

    /**
     * Vérifie le contenu des données importées.
     *
     * @param $content object
     * @param $type string
     * @return object
     */
    private static function &protect($content, $type) {
        $type = strtoupper($type);

        switch ($type) {
            case 'INT':
            case 'INTEGER':
                $matches = array();
                preg_match('/-?[0-9]+/', (string) $content, $matches);
                $content = @(int) $matches[0];
                break;

            case 'FLOAT':
            case 'DOUBLE':
                $matches = array();
                preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $content, $matches);
                $content = @(float) $matches[0];
                break;

            case 'BOOL':
            case 'BOOLEAN':
                $content = (string) $content;
                $content = ($content == "1" || $content == "true") ? "1" : "0";
                $content = (bool) $content;
                break;

            case 'BASE64':
                $content = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $content);
                break;

            case 'WORD':
                $content = (string) preg_replace('/[^A-Z_]/i', '', $content);
                $content = self::protect($content, "STRING");
                break;

            case 'STRING':
                $content = trim($content);
                if (preg_match('/(\.\.|http:|ftp:)/', $content)) {
                    $content = "";
                }
                break;
            default:
                Core_Logger::addException("Core_Request : data type unknown");
                $content = self::protect($content, "STRING");
                break;
        }
        return $content;
    }

    /**
     * Récupère, analyse et vérifie une variable URL.
     *
     * @param $name string Nom de la variable
     * @param $type string Type de donnée
     * @param $default object Donnée par défaut
     * @param $hash string Provenance de la variable
     * @return object
     */
    private static function &getVars($name, $type, $default = "", $hash = "default") {
        $rslt = null;

        if (isset(self::$buffer[$name])) {
            $rslt = self::$buffer[$name];
        } else {
            // Recherche de la méthode courante
            $input = self::getRequest($hash);

            if (isset($input[$name]) && $input[$name] !== null) {
                $rslt = self::protect($input[$name], $type);
                self::$buffer[$name] = $rslt;
            } else {
                $rslt = $default;
            }
        }
        return $rslt;
    }

}
