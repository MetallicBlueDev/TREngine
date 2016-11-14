<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de requêtes vers le protocole HTTP.
 * Classe autorisée à manipuler les Superglobals.
 *
 * @author Sébastien Villemain
 */
class CoreRequest {

    /**
     * Mémoire tampon: tableau array des requêtes.
     *
     * @var array
     */
    private static $buffer = array();

    /**
     * Retourne la variable demandée de type booléenne.
     *
     * @param string $name Nom de la variable
     * @param bool $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return bool
     */
    public static function &getBoolean(string $name, bool $default = false, string $hash = "default"): bool {
        return self::getVars($name, "BOOL", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type entier.
     *
     * @param string $name Nom de la variable
     * @param int $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return int
     */
    public static function &getInteger(string $name, int $default = 0, string $hash = "default"): int {
        return self::getVars($name, "INT", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type float.
     *
     * @param string $name Nom de la variable
     * @param float $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return float
     */
    public static function &getFloat(string $name, float $default = 0.0, string $hash = "default"): float {
        return self::getVars($name, "FLOAT", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type string valide base64.
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    public static function &getBase64(string $name, string $default = "", string $hash = "default"): string {
        return self::getVars($name, "BASE64", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type string.
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    public static function &getWord(string $name, string $default = "", string $hash = "default"): string {
        return self::getVars($name, "WORD", $default, $hash);
    }

    /**
     * Retourne la variable demandée de type string.
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    public static function &getString(string $name, string $default = "", string $hash = "default"): string {
        return self::getVars($name, "STRING", $default, $hash);
    }

    /**
     * Retourne le type de méthode utlisée pour la requête.
     *
     * @return string
     */
    public static function &getRequestMethod(): string {
        return self::getString("REQUEST_METHOD", "", "SERVER");
    }

    /**
     * Retourne le contenu de la requête demandée.
     *
     * @param string $hash
     * @param bool $useDefault
     * @return array
     */
    private static function &getRequest(string $hash, bool $useDefault): array {
        $hash = strtoupper($hash);
        $input = array();

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
                if (!$useDefault) {
                    CoreSecure::getInstance()->throwException("requestHash", null, array(
                        $hash));
                }

                $hash = self::getRequestMethod();

                if (!empty($hash)) {
                    $input = self::getRequest($hash, false);
                }
        }
        return $input;
    }

    /**
     * Récupère, analyse et vérifie une variable URL.
     *
     * @param string $name Nom de la variable
     * @param string $type Type de donnée
     * @param mixed $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return mixed
     */
    private static function &getVars(string $name, string $type, $default = "", string $hash = "default") {
        $rslt = null;

        if (isset(self::$buffer[$name])) {
            $rslt = self::$buffer[$name];
        } else {
            // Recherche de la méthode courante
            $input = self::getRequest($hash, true);

            if (isset($input[$name]) && $input[$name] !== null) {
                $rslt = self::protect($input[$name], $type);
                self::$buffer[$name] = $rslt;
            } else {
                $rslt = $default;
            }
        }
        return $rslt;
    }

    /**
     * Vérifie le contenu des données importées.
     *
     * @param mixed $content
     * @param string $type
     * @return mixed
     */
    private static function &protect($content, string $type) {
        $type = strtoupper($type);

        switch ($type) {
            case 'INT':
            case 'INTEGER':
                $matches = array();
                preg_match('/-?[0-9]+/', (string) $content, $matches);
                $content = (int) $matches[0];
                break;

            case 'FLOAT':
            case 'DOUBLE':
                $matches = array();
                preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $content, $matches);
                $content = (float) $matches[0];
                break;

            case 'BOOL':
            case 'BOOLEAN':
                $content = (string) $content;
                $content = ($content === "1" || $content === "true") ? "1" : "0";
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
                CoreLogger::addException("CoreRequest : data type unknown");
                $content = self::protect($content, "STRING");
                break;
        }
        return $content;
    }

}
