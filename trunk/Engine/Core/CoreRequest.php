<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de requêtes vers le protocole HTTP.
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
                $input = self::getGlobalGet();
                break;
            case 'POST':
                $input = self::getGlobalPost();
                break;
            case 'FILES':
                $input = self::getGlobalFiles();
                break;
            case 'COOKIE':
                $input = self::getGlobalCookie();
                break;
            case 'ENV':
                $input = self::getGlobalEnv();
                break;
            case 'SERVER':
                $input = self::getGlobalServer();
                break;
            default:
                $input = self::getDefaultRequest($hash, $useDefault);
        }
        return $input;
    }

    /**
     * Retourne le contenu de la requête par défaut.
     *
     * @param string $hash
     * @param bool $useDefault
     * @return array
     */
    private static function &getDefaultRequest(string $hash, bool $useDefault): array {
        if (!$useDefault) {
            CoreSecure::getInstance()->throwException("requestHash", null, array(
                $hash));
        }

        $hash = self::getRequestMethod();

        if (!empty($hash)) {
            $input = self::getRequest($hash, false);
        }
        return $input;
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
                $content = self::protectInt($content);
                break;
            case 'FLOAT':
            case 'DOUBLE':
                $content = self::protectFloat($content);
                break;
            case 'BOOL':
            case 'BOOLEAN':
                $content = self::protectBool($content);
                break;
            case 'BASE64':
                $content = self::protectBase64($content);
                break;
            case 'WORD':
                $content = self::protectWord($content);
                break;
            case 'STRING':
                $content = self::protectString($content);
                break;
            default:
                CoreLogger::addException("CoreRequest : data type unknown");
                $content = self::protectString($content);
                break;
        }
        return $content;
    }

    /**
     * Force le typage en entier.
     *
     * @param mixed $content
     * @return int
     */
    private static function &protectInt($content): int {
        $matches = array();
        preg_match('/-?[0-9]+/', (string) $content, $matches);
        $content = (int) $matches[0];
        return $content;
    }

    /**
     * Force le typage en float.
     *
     * @param mixed $content
     * @return float
     */
    private static function &protectFloat($content): float {
        $matches = array();
        preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $content, $matches);
        $content = (float) $matches[0];
        return $content;
    }

    /**
     * Force le typage en valeur booléenne.
     *
     * @param mixed $content
     * @return bool
     */
    private static function &protectBool($content): bool {
        $content = (string) $content;
        $content = ($content === "1" || $content === "true") ? "1" : "0";
        $content = (bool) $content;
        return $content;
    }

    /**
     * Force le typage en chaine de caractère en base 64.
     *
     * @param mixed $content
     * @return string
     */
    private static function &protectBase64($content): string {
        $content = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $content);
        return $content;
    }

    /**
     * Force le typage en chaine de caractère sur un mot.
     *
     * @param mixed $content
     * @return string
     */
    private static function &protectWord($content): string {
        $content = (string) preg_replace('/[^A-Z_]/i', '', $content);
        return self::protectString($content);
    }

    /**
     * Force le typage en chaine de caractère.
     *
     * @param mixed $content
     * @return string
     */
    private static function &protectString($content): string {
        $content = trim((string) $content);

        if (preg_match('/(\.\.|http:|ftp:)/', $content)) {
            $content = "";
        }
        return $content;
    }

    /**
     * Classe autorisée à manipuler les $_GET (lecture seule).
     *
     * @return array
     */
    private static function &getGlobalGet(): array {
        $globalVars = ${"_" . "GET"};
        return $globalVars;
    }

    /**
     * Classe autorisée à manipuler les $_POST (lecture seule).
     *
     * @return array
     */
    private static function &getGlobalPost(): array {
        $globalVars = ${"_" . "POST"};
        return $globalVars;
    }

    /**
     * Classe autorisée à manipuler les $_FILES (lecture seule).
     *
     * @return array
     */
    private static function &getGlobalFiles(): array {
        $globalVars = ${"_" . "FILES"};
        return $globalVars;
    }

    /**
     * Classe autorisée à manipuler les $_COOKIE (lecture seule).
     *
     * @return array
     */
    private static function &getGlobalCookie(): array {
        $globalVars = ${"_" . "COOKIE"};
        return $globalVars;
    }

    /**
     * Classe autorisée à manipuler les $_ENV (lecture seule).
     *
     * @return array
     */
    private static function &getGlobalEnv(): array {
        $globalVars = ${"_" . "ENV"};
        return $globalVars;
    }

    /**
     * Classe autorisée à manipuler les $_SERVER (lecture seule).
     *
     * @return array
     */
    private static function &getGlobalServer(): array {
        $globalVars = ${"_" . "SERVER"};
        return $globalVars;
    }

}
