<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Constructeur de fichier
 *
 * @author Sébastien Villemain
 */
class Exec_FileBuilder {

    /**
     * Génére un nouveau fichier de configuration générale
     *
     * @param $TR_ENGINE_MAIL string
     * @param $TR_ENGINE_STATUT string
     * @param $cacheTimeLimit int
     * @param $cookiePrefix string
     * @param $cryptKey string
     */
    public static function buildConfigFile($TR_ENGINE_MAIL, $TR_ENGINE_STATUT, $cacheTimeLimit, $cookiePrefix, $cryptKey) {
        // Vérification du mail
        if (empty($TR_ENGINE_MAIL) && define(TR_ENGINE_MAIL))
            $TR_ENGINE_MAIL = TR_ENGINE_MAIL;

        // Vérification de la durée du cache
        if (!is_int($cacheTimeLimit) || $cacheTimeLimit < 0)
            $cacheTimeLimit = 7;

        $content = "<?php \n"
        . "// ----------------------------------------------------------------------- //\n"
        . "// Generals informations\n"
        . "//\n"
        . "// Webmaster mail\n"
        . "$" . "config['TR_ENGINE_MAIL'] = \"" . $TR_ENGINE_MAIL . "\";\n"
        . "//\n"
        . "// Site states (open | close)\n"
        . "$" . "config['TR_ENGINE_STATUT'] = \"" . (($TR_ENGINE_STATUT == "close") ? "close" : "open") . "\";\n"
        . "// ----------------------------------------------------------------------- //\n"
        . "// Sessions settings\n"
        . "// Duration in days of validity of session files caching\n"
        . "$" . "config['cacheTimeLimit'] = " . $cacheTimeLimit . ";\n"
        . "//\n"
        . "// Prefix the names of cookies\n"
        . "$" . "config['cookiePrefix'] = \"" . $cookiePrefix . "\";\n"
        . "//\n"
        . "// Unique decryption key (generated randomly during installation)\n"
        . "$" . "config['cryptKey'] = \"" . $cryptKey . "\";\n"
        . "// -------------------------------------------------------------------------//\n"
        . "?>\n";

        if (Core_Loader::isCallable("Core_CacheBuffer")) {
            Core_CacheBuffer::setSectionName(Core_CacheBuffer::SECTION_CONFIGS);
            Core_CacheBuffer::writingCache("configs.inc.php", $content, true);
        }
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
        if (Core_Loader::isCallable("Core_CacheBuffer")) {
            $mode = Core_CacheBuffer::getModeActived();
            $type = (isset($mode[$type])) ? $type : "";
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

        if (Core_Loader::isCallable("Core_CacheBuffer")) {
            Core_CacheBuffer::setSectionName("configs");
            Core_CacheBuffer::writingCache("ftp.inc.php", $content, true);
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
        if (Core_Loader::isCallable("Core_Sql")) {
            $bases = Core_Sql::listBases();
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

        if (Core_Loader::isCallable("Core_CacheBuffer")) {
            Core_CacheBuffer::setSectionName("configs");
            Core_CacheBuffer::writingCache("database.inc.php", $content, true);
        }
    }

}

?>