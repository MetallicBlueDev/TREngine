<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreCache;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de création des fichiers du moteur.
 *
 * @author Sébastien Villemain
 */
class ExecFileBuilder {

    /**
     * Génére un nouveau fichier de configuration.
     *
     * @param string $mail
     * @param string $statut
     * @param int $cacheTimeLimit
     * @param string $cookiePrefix
     * @param string $cryptKey
     */
    public static function buildConfigFile($mail, $statut, $cacheTimeLimit, $cookiePrefix, $cryptKey) {
        // Vérification du mail
        if (empty($mail) && define(TR_ENGINE_MAIL)) {
            $mail = TR_ENGINE_MAIL;
        }

        // Vérification de la durée du cache
        if (!is_int($cacheTimeLimit) || $cacheTimeLimit < 0) {
            $cacheTimeLimit = 7;
        }

        $content = "<?php \n"
        . "// ----------------------------------------------------------------------- //\n"
        . "// Informations générales\n"
        . "//\n"
        . "// Webmaster email address\n"
        . "$" . "inc['TR_ENGINE_MAIL'] = \"" . $mail . "\";\n"
        . "//\n"
        . "// Status of the site (open | close)\n"
        . "$" . "inc['TR_ENGINE_STATUT'] = \"" . (($statut == "close") ? "close" : "open") . "\";\n"
        . "// ----------------------------------------------------------------------- //\n"
        . "// -------------------------------------------------------------------------//\n"
        . "// Data sessions\n"
        . "//\n"
        . "// Duration in days of the validity of sessions files cached.\n"
        . "$" . "inc['cacheTimeLimit'] = \"" . $cacheTimeLimit . "\";\n"
        . "//\n"
        . "// Cookies names prefix\n"
        . "$" . "inc['cookiePrefix'] = \"" . $cookiePrefix . "\";\n"
        . "//\n"
        . "// Unique decryption key (generated randomly during installation)\n"
        . "$" . "inc['cryptKey'] = \"" . $cryptKey . "\";\n"
        . "// -------------------------------------------------------------------------//\n"
        . "?>\n";

        CoreCache::getInstance(CoreCache::SECTION_CONFIGS)->writeCache("config.inc.php", $content);
    }

    /**
     * Génére un nouveau fichier de configuration FTP
     *
     * @param $host string
     * @param $port int
     * @param $user string
     * @param $pass string
     * @param $root string
     * @param $type string
     */
    public static function buildFtpFile($host, $port, $user, $pass, $root, $type) {
        // Vérification du port
        if (!is_int($port))
            $port = 21;

        // Vérification du mode de ftp
        if (CoreLoader::isCallable("CoreCache")) {
            $type = CoreCache::getInstance(CoreCache::SECTION_CONFIGS)->getTransactionType();
        }

        $content = "<?php \n"
        . "// -------------------------------------------------------------------------//\n"
        . "// FTP settings\n"
        . "//\n"
        . "// Address of the FTP host\n"
        . "$" . "ftp['host'] = \"" . $host . "\";\n"
        . "//\n"
        . "// Port number of host\n"
        . "$" . "ftp['port'] = " . $port . ";\n"
        . "//\n"
        . "// Username FTP\n"
        . "$" . "ftp['user'] = \"" . $user . "\";\n"
        . "//\n"
        . "// Password User FTP\n"
        . "$" . "ftp['pass'] = \"" . $pass . "\";\n"
        . "//\n"
        . "// FTP Root Path\n"
        . "$" . "ftp['root'] = \"" . $root . "\";\n"
        . "//\n"
        . "// FTP mode (native = php | ftp | sftp)\n"
        . "$" . "ftp['type'] = \"" . $type . "\";\n"
        . "// -------------------------------------------------------------------------//\n"
        . "?>\n";

        if (CoreLoader::isCallable("CoreCache")) {
            CoreCache::getInstance(CoreCache::SECTION_CONFIGS)->writeCache("cache.inc.php", $content);
        }
    }

    /**
     * Génére un nouveau fichier de configuration pour la base de données
     *
     * @param $host string
     * @param $user string
     * @param $pass string
     * @param $name string
     * @param $type string
     * @param $prefix string
     */
    public static function buildDatabaseFile($host, $user, $pass, $name, $type, $prefix) {
        // Vérification du type de base de données
        if (CoreLoader::isCallable("CoreSql")) {
            $bases = CoreSql::getBaseList();
            $type = (isset($bases[$type])) ? $type : "mysql";
        }

        $content = "<?php \n"
        . "// -------------------------------------------------------------------------//\n"
        . "// Database settings\n"
        . "//\n"
        . "// Address of the host base\n"
        . "$" . "db['host'] = \"" . $host . "\";\n"
        . "//\n"
        . "// Name of database user\n"
        . "$" . "db['user'] = \"" . $user . "\";\n"
        . "//\n"
        . "// Password of the database\n"
        . "$" . "db['pass'] = \"" . $pass . "\";\n"
        . "//\n"
        . "// Database name\n"
        . "$" . "db['name'] = \"" . $name . "\";\n"
        . "//\n"
        . "// Database type\n"
        . "$" . "db['type'] = \"" . $type . "\";\n"
        . "//\n"
        . "// Prefix tables\n"
        . "$" . "db['prefix'] = \"" . $prefix . "\";\n"
        . "// -------------------------------------------------------------------------//\n"
        . "?>\n";

        if (CoreLoader::isCallable("CoreCache")) {
            CoreCache::getInstance(CoreCache::SECTION_CONFIGS)->writeCache("database.inc.php", $content);
        }
    }

}
