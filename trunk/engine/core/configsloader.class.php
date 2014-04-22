<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * Chargeur de configuration
 *
 * @author Sébastien Villemain
 */
class Core_ConfigsLoader {

    /**
     * Lance le chargeur de configs
     */
    public function __construct() {
        if (Core_Loader::isCallable("Core_Main")) {
            if ($this->loadCacheBuffer() && $this->loadSql()) {
                $this->loadConfig();
            } else {
                Core_Secure::getInstance()->throwException("Config error: Core_CacheBuffer and Core_Sql aren't loaded");
            }
        } else {
            Core_Secure::getInstance()->throwException("Config error: Core_Main isn't loaded");
        }
    }

    /**
     * Charge et vérifie la configuration
     */
    private function loadConfig() {
        // Chemin vers le fichier de configuration générale
        $configPath = TR_ENGINE_DIR . "/configs/config.inc.php";

        if (is_file($configPath)) {
            require($configPath);

            $configuration = array();

            // Vérification de l'adresse email du webmaster
            if (Exec_Mailer::validMail($config["TR_ENGINE_MAIL"])) {
                define("TR_ENGINE_MAIL", $config["TR_ENGINE_MAIL"]);
            } else {
                Core_Logger::addException("Default mail isn't valide");
            }

            // Vérification du statut
            $config["TR_ENGINE_STATUT"] = strtolower($config["TR_ENGINE_STATUT"]);
            if ($config["TR_ENGINE_STATUT"] == "close")
                define("TR_ENGINE_STATUT", "close");
            else
                define("TR_ENGINE_STATUT", "open");

            // Recherche du statut du site
            if (TR_ENGINE_STATUT == "close") {
                Core_Secure::getInstance()->throwException("close");
            }

            if (is_int($config['cacheTimeLimit']) && $config['cacheTimeLimit'] >= 1)
                $configuration['cacheTimeLimit'] = $config['cacheTimeLimit'];
            else
                $configuration['cacheTimeLimit'] = 7;

            if (!empty($config['cookiePrefix']))
                $configuration['cookiePrefix'] = $config['cookiePrefix'];
            else
                $configuration['cookiePrefix'] = "tr";

            if (!empty($config['cryptKey']))
                $configuration['cryptKey'] = $config['cryptKey'];

            // Ajout a la configuration courante
            Core_Main::addToConfiguration($configuration);

            // Chargement de la configuration via la cache
            $configuration = array();
            Core_CacheBuffer::setSectionName("tmp");

            // Si le cache est disponible
            if (Core_CacheBuffer::cached("configs.php")) {
                $configuration = Core_CacheBuffer::getCache("configs.php");
            } else {
                $content = "";
                // Requête vers la base de données de configs
                Core_Sql::getInstance()->select(Core_Table::$CONFIG_TABLE, array(
                    "name",
                    "value"));
                while ($row = Core_Sql::getInstance()->fetchArray()) {
                    $content .= Core_CacheBuffer::serializeData(array(
                        $row['name'] => $row['value']));
                    $configuration[$row['name']] = Exec_Entities::stripSlashes($row['value']);
                }

                // Mise en cache
                Core_CacheBuffer::writingCache("configs.php", $content, true);
            }

            // Ajout a la configuration courante
            Core_Main::addToConfiguration($configuration);
        } else {
            Core_Secure::getInstance()->throwException("configPath", null, array(
                $configPath));
        }
    }

    /**
     * Charge le gestionnaire Sql
     *
     * @return boolean true chargé
     */
    private function loadSql() {
        if (!Core_Loader::isCallable("Core_Sql")) {
            // Chemin vers le fichier de configuration de la base de données
            $databasePath = TR_ENGINE_DIR . "/configs/database.inc.php";

            if (is_file($databasePath)) {
                require($databasePath);

                // Vérification du type de base de données
                if (!is_file(TR_ENGINE_DIR . "/engine/base/" . $db['type'] . ".class.php")) {
                    Core_Secure::getInstance()->throwException("sqlType", null, array(
                        $db['type']));
                }

                // Démarrage de l'instance Core_Sql
                Core_Sql::checkInstance($db);
            } else {
                Core_Secure::getInstance()->throwException("sqlPath", null, array(
                    $databasePath));
            }
            return Core_Loader::isCallable("Core_Sql");
        }
        return true;
    }

    /**
     * Charge le gestionnaire de cache et les données FTP
     *
     * @return boolean true chargé
     */
    private function loadCacheBuffer() {
        if (!Core_Loader::isCallable("Core_CacheBuffer")) {
            // Mode natif PHP actif
            $modes = array(
                "php");

            // Chemin du fichier de configuration ftp
            $ftpPath = TR_ENGINE_DIR . "/configs/ftp.inc.php";

            if (is_file($ftpPath)) {
                require($ftpPath);

                // Préparation de l'activation du mode
                $mode = strtolower($ftp['type']);
                if ($mode != "php")
                    $modes[] = $mode;

                // Ajout des données FTP
                Core_CacheBuffer::setFtp($ftp);
            }
            // Activation des modes
            Core_CacheBuffer::setModeActived($modes);
            return Core_Loader::isCallable("Core_CacheBuffer");
        }
        return true;
    }

}
