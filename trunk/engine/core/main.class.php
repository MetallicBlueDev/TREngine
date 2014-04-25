<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Classe principal du moteur.
 *
 * @author Sébastien Villemain
 */
class Core_Main {

    /**
     * Instance principal du moteur.
     *
     * @var Core_Main
     */
    private static $coreMain = null;

    /**
     * Tableau de configuration.
     */
    private $configs;

    /**
     * Mode de mise en page courante.
     * default : affichage normale et complet
     * module : affichage uniquement du module si javascript activé
     * block : affichage uniquement du block si javascript activé
     * modulepage : affichage uniquement du module forcé
     * blockpage : affichage uniquement du block forcé
     *
     * @var string
     */
    private $layout = "default";

    private function __construct() {
        // NE RIEN FAIRE
    }

    /**
     * Retourne l'instance principal du moteur.
     *
     * @return Core_Main
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$coreMain;
    }

    /**
     * Vérifie l'instance principal du moteur.
     */
    public static function checkInstance() {
        if (self::$coreMain === null) {
            if (Core_Secure::isDebuggingMode()) {
                Exec_Marker::startTimer("core");
            }

            self::$coreMain = new self();
            self::$coreMain->prepare();

            if (Core_Secure::isDebuggingMode()) {
                Exec_Marker::stopTimer("core");
            }
        }
    }

    /**
     * Ajoute les données à la configuration.
     *
     * @param array
     */
    public function addConfig(array $configuration) {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $this->configs[$key] = $value;
            } else {
                $this->configs[$key] = Exec_Entities::stripSlashes($value);
            }
        }
    }

    /**
     * Ajoute les données d'inclusion à la configuration.
     *
     * @param string $name
     * @param array $include
     */
    public function addInclude($name, array $include) {
        $this->addConfig(array(
            $name => $include));
    }

    /**
     * Retourne le contenu de la configuration.
     *
     * @param string $key
     * @param string $subKey
     * @return string
     */
    public function &getConfigValue($key, $subKey = "") {
        $rslt = null;

        if (isset($this->configs[$key])) {
            $rslt = $this->configs[$key];

            if (isset($rslt[$subKey])) {
                $rslt = $rslt[$subKey];
            }
        }
        return $rslt;
    }

    /**
     * Retourne la configuration ftp.
     *
     * @return array
     */
    public function &getConfigFtp() {
        return $this->getConfigValue("configs_ftp");
    }

    /**
     * Retourne la configuration de la base de données.
     *
     * @return array
     */
    public function &getConfigDatabase() {
        return $this->getConfigValue("configs_database");
    }

    /**
     * Détermine si l'url rewriting est activé.
     *
     * @return boolean
     */
    public function doUrlRewriting() {
        return ($this->getConfigValue("urlRewriting") == 1) ? true : false;
    }

    /**
     * Vérifie l'état de maintenance.
     *
     * @return boolean
     */
    public function doDumb() {
        return (!$this->doOpening() && Core_Session::getInstance()->userRank < 2);
    }

    /**
     * Vérifie l'état du site (ouvert/fermé).
     *
     * @return boolean
     */
    public function doOpening() {
        return ($this->getDefaultSiteStatut() == "open");
    }

    /**
     * Détermine l'état des inscriptions au site.
     *
     * @return boolean
     */
    public function registrationAllowed() {
        return ($this->getConfigValue("registrationAllowed") == 1) ? true : false;
    }

    /**
     * Retourne le préfixe des cookies.
     *
     * @return string
     */
    public function &getCookiePrefix() {
        return $this->getConfigValue("cookiePrefix");
    }

    /**
     * Retourne la durée de validité du cache.
     *
     * @return int
     */
    public function &getCacheTimeLimit() {
        return $this->getConfigValue("cacheTimeLimit");
    }

    /**
     * Retourne la clé de cryptage.
     *
     * @return string
     */
    public function &getCryptKey() {
        return $this->getConfigValue("cryptKey");
    }

    /**
     * Retourne le mode du captcha.
     *
     * @return string
     */
    public function &getCaptchaMode() {
        return $this->getConfigValue("captchaMode");
    }

    /**
     * Retourne l'adresse email de l'administrateur.
     *
     * @return string
     */
    public function &getDefaultAdministratorMail() {
        return $this->getDefaultConfigValue("defaultAdministratorMail", function() {
            return TR_ENGINE_MAIL;
        });
    }

    /**
     * Retourne le nom du site.
     *
     * @return string
     */
    public function &getDefaultSiteName() {
        return $this->getDefaultConfigValue("defaultSiteName", function() {
            return Core_Request::getString("SERVER_NAME", "", "SERVER");
        });
    }

    /**
     * Retourne le slogan du site.
     *
     * @return string
     */
    public function &getDefaultSiteSlogan() {
        return $this->getDefaultConfigValue("defaultSiteSlogan", function() {
            return "TR ENGINE";
        });
    }

    /**
     * Retourne le status du site.
     *
     * @return string
     */
    public function &getDefaultSiteStatut() {
        return $this->getDefaultConfigValue("defaultSiteStatut", function() {
            return "open";
        });
    }

    /**
     * Retourne la raison de la fermeture du site.
     *
     * @return string
     */
    public function &getDefaultSiteCloseReason() {
        return $this->getDefaultConfigValue("defaultSiteCloseReason", function() {
            return " ";
        });
    }

    /**
     * Retourne la description du site.
     *
     * @return string
     */
    public function &getDefaultDescription() {
        return $this->getDefaultConfigValue("defaultDescription", function() {
            return "TR ENGINE";
        });
    }

    /**
     * Retourne les mots clés du site.
     *
     * @return string
     */
    public function &getDefaultKeyWords() {
        return $this->getDefaultConfigValue("defaultKeyWords", function() {
            return "TR ENGINE";
        });
    }

    /**
     * Retourne la langue par défaut.
     *
     * @return string
     */
    public function &getDefaultLanguage() {
        return $this->getDefaultConfigValue("defaultLanguage", function() {
            return "english";
        });
    }

    /**
     * Retourne le template par défaut.
     *
     * @return string
     */
    public function &getDefaultTemplate() {
        return $this->getDefaultConfigValue("defaultTemplate", function() {
            return " ";
        });
    }

    /**
     * Retourne le nom du module par défaut.
     *
     * @return string
     */
    public function &getDefaultMod() {
        return $this->getDefaultConfigValue("defaultMod", function() {
            return "home";
        });
    }

    /**
     * Détermine si l'affichage se fait en écran complet (affichage classique).
     *
     * @return boolean true c'est en plein écran.
     */
    public function isDefaultLayout() {
        return (($this->layout == "default") ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé module.
     *
     * @return boolean true c'est un affichage de module uniquement.
     */
    public function isModuleLayout() {
        return (($this->layout == "module" || $this->layout == "modulepage") ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé block.
     *
     * @return boolean true c'est un affichage de block uniquement.
     */
    public function isBlockLayout() {
        return (($this->layout == "block" || $this->layout == "blockpage") ? true : false);
    }

    /**
     * Démarrage TR ENGINE.
     */
    public function start() {
        if (Core_Secure::isDebuggingMode()) {
            Exec_Marker::startTimer("launcher");
        }

        // Vérification des bannissements
        Core_BlackBan::checkBlackBan();

        Core_Html::checkInstance();

        // Configure les informations de page demandées
        $this->checkLayout();
        $this->checkModule();
        $this->checkMakeStyle();

        if (!Core_Secure::isDebuggingMode()) {
            $this->compressionOpen();
        }

        // Vérification du bannissement
        if (!Core_BlackBan::isBlackUser()) {
            // Vérification du type d'affichage
            if ($this->isFullScreen() && Core_Loader::isCallable("Libs_Block") && Core_Loader::isCallable("Libs_Module")) {
                // Affichage classique du site
                if ($this->doDumb()) {
                    // Mode maintenance: possibilité de s'identifier
                    Libs_Block::getInstance()->launchOneBlock(array(
                        "type = 'login'"));

                    Exec_Marker::stopTimer("main");

                    // Affichage des données de la page de maintenance (fermeture)
                    $libsMakeStyle = new Libs_MakeStyle();
                    $libsMakeStyle->assign("closeText", ERROR_DEBUG_CLOSE);
                    $libsMakeStyle->display("close");
                } else {
                    // Mode normal: construction du fil d'ariane
                    Libs_Breadcrumb::checkInstance();

                    Libs_Module::getInstance()->launch();
                    Libs_Block::getInstance()->launchAllBlock();

                    Exec_Marker::stopTimer("main");

                    $libsMakeStyle = new Libs_MakeStyle();
                    $libsMakeStyle->display("index");
                }
            } else {
                // Affichage autonome des modules et blocks
                if ($this->isModuleLayout() && Core_Loader::isCallable("Libs_Module")) {
                    $libsModule = Libs_Module::getInstance();

                    // Affichage du module uniquement
                    $libsModule->launch();

                    Exec_Marker::stopTimer("main");

                    echo $libsModule->getModule();
                } else if ($this->isBlockLayout() && Core_Loader::isCallable("Libs_Block")) {
                    $libsBlock = Libs_Block::getInstance();

                    // Affichage du block uniquement
                    $libsBlock->launchOneBlock();

                    Exec_Marker::stopTimer("main");

                    echo $libsBlock->getBlock();
                }

                // Execute la commande de récupération d'erreur
                Core_Logger::displayMessages();

                // Javascript autonome
                Core_Html::getInstance()->selfJavascript();
            }

            // Validation du cache / Routine du cache
            Core_CacheBuffer::valideCacheBuffer();

            // Assemble tous les messages d'erreurs dans un fichier log
            Core_Logger::logException();
        } else {
            Exec_Marker::stopTimer("main");

            Core_BlackBan::displayBlackPage();
        }

        if (Core_Secure::isDebuggingMode()) {
            Exec_Marker::stopTimer("launcher");
        } else {
            $this->compressionClose();
        }
    }

    /**
     * Recherche de nouveau composant.
     *
     * @return boolean true nouveau composant détecté.
     */
    public function newComponentDetected() {
        // TODO détection de nouveau module a coder
        return false;
    }

    /**
     * Démarrage de l'installeur.
     */
    public function install() {
        // TODO installation a coder
//        $installPath = TR_ENGINE_DIR . "/install/index.php";
//        if (is_file($installPath)) {
//            require($installPath);
//        }
    }

    /**
     * Retourne la valeur par défaut de la configuration.
     *
     * @param string $keyName
     * @param string $callback
     * @return string
     */
    private function &getDefaultConfigValue($keyName, $callback) {
        $value = $this->getConfigValue($keyName);

        if (empty($value)) {
            $value = $callback();
            $this->addConfig(array(
                $keyName => $value));
        }
        return $value;
    }

    /**
     * Vérification et assignation du layout.
     */
    private function checkLayout() {
        // Assignation et vérification de fonction layout
        $layout = strtolower(Core_Request::getWord("layout"));

        // Configuration du layout
        if ($layout != "default" && $layout != "modulepage" && $layout != "blockpage" && (($layout != "block" && $layout != "module") || (!Core_Html::getInstance()->isJavascriptEnabled()))) {
            $layout = "default";
        }

        $this->layout = $layout;
    }

    /**
     * Vérification et assignation des informations sur le module.
     */
    private function checkModule() {
        Libs_Module::checkInstance(
        Core_Request::getWord("mod"), Core_Request::getWord("page"), Core_Request::getWord("view")
        );
    }

    /**
     * Vérification et assignation du template.
     */
    private function checkMakeStyle() {
        $templateName = Core_Session::getInstance()->userTemplate;

        // Tentative d'utilisation du template du client
        if (!Libs_MakeStyle::setCurrentTemplate($templateName)) {
            $templateName = null;
        }

        if (empty($templateName)) {
            $templateName = $this->getDefaultTemplate();

            // Tentative d'utilisation du template du site
            Libs_MakeStyle::setCurrentTemplate($templateName);
        }
    }

    /**
     * Lance le tampon de sortie.
     * Entête & tamporisation de sortie.
     */
    private function compressionOpen() {
        header("Vary: Cookie, Accept-Encoding");

        if (extension_loaded('zlib') && !ini_get('zlib.output_compression') && function_exists("ob_gzhandler") && !$this->doUrlRewriting()) {
            ob_start("ob_gzhandler");
        } else {
            ob_start();
        }
    }

    /**
     * Relachement des tampons de sortie.
     */
    private function compressionClose() {
        $canContinue = false;

        do {
            $canContinue = ob_end_flush();
        } while ($canContinue);
    }

    /**
     * Préparation TR ENGINE.
     * Procédure de préparation du moteur.
     * Une étape avant le démarrage réel.
     */
    private function prepare() {
        if (!$this->loadCacheBuffer()) {
            Core_Secure::getInstance()->throwException("ftpPath", null, array(
                Core_Loader::getAbsolutePath("configs_ftp")));
        }

        if (!$this->loadSql()) {
            Core_Secure::getInstance()->throwException("sqlPath", null, array(
                Core_Loader::getAbsolutePath("configs_database")));
        }

        if (!$this->loadConfig()) {
            Core_Secure::getInstance()->throwException("configPath", null, array(
                Core_Loader::getAbsolutePath("configs_config")));
        }

        // Analyse pour les statistiques
        Exec_Agent::executeAnalysis();

        // Chargement de la session
        Core_Session::checkInstance();
    }

    /**
     * Charge le gestionnaire de cache et les données FTP.
     *
     * @return boolean true chargé
     */
    private function loadCacheBuffer() {
        $canUse = Core_Loader::isCallable("Core_CacheBuffer");

        if (!$canUse) {
            // Mode natif PHP actif
            Core_CacheBuffer::setModeActived(array(
                "php"));

            // Chemin du fichier de configuration ftp
            if (Core_Loader::includeLoader("configs_ftp")) {
                $mode = strtolower($this->getConfigValue("configs_ftp", "type"));

                if (!empty($mode)) {
                    Core_CacheBuffer::setModeActived(array(
                        $mode));
                }

                $canUse = true;
            }
        }
        return $canUse;
    }

    /**
     * Charge le gestionnaire Sql.
     *
     * @return boolean true chargé
     */
    private function loadSql() {
        $canUse = Core_Loader::isCallable("Core_Sql");

        if (!$canUse) {
            // Chemin vers le fichier de configuration de la base de données
            if (Core_Loader::includeLoader("configs_database")) {
                // Démarrage de l'instance Core_Sql
                Core_Sql::checkInstance();

                $canUse = true;
            }
        }
        return $canUse;
    }

    /**
     * Charge la configuration générale.
     */
    private function loadConfig() {
        // Chemin vers le fichier de configuration du moteur
        $canUse = Core_Loader::includeLoader("configs_config");

        if ($canUse) {
            // Tentative d'utilisation de la configuration
            $rawConfig = $this->getConfigValue("configs_config");

            if (!empty($rawConfig)) {
                $newConfig = array();

                // Vérification de l'adresse email du webmaster
                if (!Exec_Mailer::validMail($rawConfig["TR_ENGINE_MAIL"])) {
                    Core_Logger::addException("Default mail isn't valide");
                }

                define("TR_ENGINE_MAIL", $rawConfig["TR_ENGINE_MAIL"]);

                // Vérification du statut
                $rawConfig["TR_ENGINE_STATUT"] = strtolower($rawConfig["TR_ENGINE_STATUT"]);

                if ($rawConfig["TR_ENGINE_STATUT"] !== "close" && $rawConfig["TR_ENGINE_STATUT"] !== "open") {
                    $rawConfig["TR_ENGINE_STATUT"] = "open";
                }

                define("TR_ENGINE_STATUT", $rawConfig["TR_ENGINE_STATUT"]);

                if (TR_ENGINE_STATUT == "close") {
                    Core_Secure::getInstance()->throwException("close");
                }

                // Vérification de la durée de validité du cache
                if (!is_int($rawConfig['cacheTimeLimit']) || $rawConfig['cacheTimeLimit'] < 1) {
                    $rawConfig['cacheTimeLimit'] = 7;
                }

                $newConfig['cacheTimeLimit'] = (int) $rawConfig['cacheTimeLimit'];

                // Vérification du préfixage des cookies
                if (empty($rawConfig['cookiePrefix'])) {
                    $rawConfig['cookiePrefix'] = "tr";
                }

                $newConfig['cookiePrefix'] = $rawConfig['cookiePrefix'];

                // Vérification de la clé de cryptage
                if (!empty($rawConfig['cryptKey'])) {
                    $newConfig['cryptKey'] = $rawConfig['cryptKey'];
                }

                // Ajout à la configuration courante
                $this->addConfig($newConfig);
            } else {
                // Il n'est pas normale de n'avoir aucune information
                $canUse = false;
            }

            // Nettoyage des clés temporaires
            unset($this->configs['configs_config']);

            // Si tout semble en ordre, nous continuons le chargement
            if ($canUse) {
                // Chargement de la configuration via la cache
                $newConfig = array();
                Core_CacheBuffer::setSectionName("tmp");

                // Si le cache est disponible
                if (Core_CacheBuffer::cached("configs.php")) {
                    $newConfig = Core_CacheBuffer::getCache("configs.php");
                } else {
                    $content = "";
                    $coreSql = Core_Sql::getInstance();

                    // Requête vers la base de données de configs
                    $coreSql->select(Core_Table::CONFIG_TABLE, array(
                        "name",
                        "value"));

                    foreach ($coreSql->fetchArray() as $row) {
                        $content .= Core_CacheBuffer::serializeData(array(
                            $row['name'] => $row['value']));
                        $newConfig[$row['name']] = Exec_Entities::stripSlashes($row['value']);
                    }

                    // Mise en cache
                    Core_CacheBuffer::writingCache("configs.php", $content, true);
                }

                // Ajout a la configuration courante
                $this->addConfig($newConfig);
            }
        }
        return $canUse;
    }

}
