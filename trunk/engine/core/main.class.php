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
     * Tableau d'information de configuration.
     */
    public static $coreConfig;

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
                self::$coreConfig[$key] = $value;
            } else {
                self::$coreConfig[$key] = Exec_Entities::stripSlashes($value);
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
     * @return mixed
     */
    public function &getConfigValue($key, $subKey = "") {
        $rslt = null;

        if (isset(self::$coreConfig[$key])) {
            $rslt = self::$coreConfig[$key];

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
     * Détermine l'état des inscriptions au site.
     *
     * @return boolean
     */
    public function isRegistrationAllowed() {
        return ($this->getConfigValue("registrationAllowed") == 1) ? true : false;
    }

    /**
     * Vérifie si le site est fermé.
     *
     * @return boolean
     */
    public function isClosed() {
        return ($this->getConfigValue("defaultSiteStatut") == "close") ? true : false;
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
        if (Core_Secure::isDebuggingMode())
            Exec_Marker::startTimer("launcher");

        // Vérification des bannissements
        Core_BlackBan::checkBlackBan();

        Core_Html::getInstance();

        // Configure les informations de page demandées
        $this->loadLayout();
        $this->loadModule();
        $this->loadMakeStyle();

        if (!Core_Secure::isDebuggingMode())
            $this->compressionOpen();

        // Comportement different en fonction du type de client
        if (!Core_BlackBan::isBlackUser()) {
            if (self::isFullScreen() && Core_Loader::isCallable("Libs_Block") && Core_Loader::isCallable("Libs_Module")) {
                if ($this->inMaintenance()) { // Affichage site fermé
                    // Charge le block login
                    Libs_Block::getInstance()->launchOneBlock(array(
                        "type = 'login'"));

                    // Affichage des données de la page de maintenance (fermeture)
                    $libsMakeStyle = new Libs_MakeStyle();
                    $libsMakeStyle->assign("closeText", ERROR_DEBUG_CLOSE);
                    $libsMakeStyle->display("close");
                } else {
                    // Chargement et construction du fil d'ariane
                    Libs_Breadcrumb::getInstance();

                    Libs_Module::getInstance()->launch();
                    Libs_Block::getInstance()->launchAllBlock();

                    Exec_Marker::stopTimer("main");
                    $libsMakeStyle = new Libs_MakeStyle();
                    $libsMakeStyle->display("index");
                }
            } else {
                // Affichage autonome des modules et blocks
                if ($this->isModuleLayout() && Core_Loader::isCallable("Libs_Module")) {
                    Libs_Module::getInstance()->launch();
                    echo Libs_Module::getInstance()->getModule();
                } else if ($this->isBlockLayout() && Core_Loader::isCallable("Libs_Block")) {
                    Libs_Block::getInstance()->launchOneBlock();
                    echo Libs_Block::getInstance()->getBlock();
                }
                // Execute la commande de récupération d'erreur
                Core_Logger::getMessages();
                // Javascript autonome
                Core_Html::getInstance()->selfJavascript();
            }

            // Validation du cache / Routine du cache
            Core_CacheBuffer::valideCacheBuffer();
            // Assemble tous les messages d'erreurs dans un fichier log
            Core_Logger::logException();
        } else {
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
        $installPath = TR_ENGINE_DIR . "/install/index.php";
        if (is_file($installPath)) {
            require($installPath);
        }
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

        // Charge la session
        $this->loadSession();
    }

    /**
     * Vérifie l'état de maintenance.
     *
     * @return boolean
     */
    private function inMaintenance() {
        return ($this->isClosed() && Core_Session::getInstance()->userRank < 2);
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
        while (ob_end_flush());
    }

    /**
     * Assignation et vérification de fonction layout.
     */
    private function loadLayout() {
        // Assignation et vérification de fonction layout
        $layout = strtolower(Core_Request::getWord("layout"));

        // Configuration du layout
        if ($layout != "default" && $layout != "modulepage" && $layout != "blockpage" && (($layout != "block" && $layout != "module") || (!Core_Html::getInstance()->isJavascriptEnabled()))) {
            $layout = "default";
        }

        $this->layout = $layout;
    }

    /**
     * Création de l'instance du module.
     */
    private function loadModule() {
        Libs_Module::getInstance(
        Core_Request::getWord("mod"), Core_Request::getWord("page"), Core_Request::getWord("view")
        );
    }

    /**
     * Assignation et vérification du template.
     */
    private function loadMakeStyle() {
        $template = (!Core_Session::getInstance()->userTemplate) ? self::$coreConfig['defaultTemplate'] : Core_Session::getInstance()->userTemplate;
        Libs_MakeStyle::getCurrentTemplate($template);
    }

    /**
     * Charge les outils de session.
     */
    private function loadSession() {
        // Analyse pour les statistiques
        Exec_Agent::getVisitorsStats();

        // Chargement des sessions
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
     * Charge et vérifie la configuration.
     */
    private function loadConfig() {
        // Chemin vers le fichier de configuration général
        $canUse = Core_Loader::includeLoader("configs_config");

        if ($canUse) {
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

                // Recherche du statut du site
                if (TR_ENGINE_STATUT == "close") {
                    Core_Secure::getInstance()->throwException("close");
                }

                if (!is_int($rawConfig['cacheTimeLimit']) || $rawConfig['cacheTimeLimit'] < 1) {
                    $rawConfig['cacheTimeLimit'] = 7;
                }

                $newConfig['cacheTimeLimit'] = (int) $rawConfig['cacheTimeLimit'];

                if (empty($rawConfig['cookiePrefix'])) {
                    $rawConfig['cookiePrefix'] = "tr";
                }

                $newConfig['cookiePrefix'] = $rawConfig['cookiePrefix'];


                if (!empty($rawConfig['cryptKey'])) {
                    $newConfig['cryptKey'] = $rawConfig['cryptKey'];
                }

                // Ajout a la configuration courante
                $this->main->addConfig($newConfig);

                unset(self::$coreConfig['configs_config']);
            }

            // Chargement de la configuration via la cache
            $newConfig = array();
            Core_CacheBuffer::setSectionName("tmp");

            // Si le cache est disponible
            if (Core_CacheBuffer::cached("configs.php")) {
                $newConfig = Core_CacheBuffer::getCache("configs.php");
            } else {
                $content = "";

                // Requête vers la base de données de configs
                Core_Sql::getInstance()->select(Core_Table::CONFIG_TABLE, array(
                    "name",
                    "value"));

                foreach (Core_Sql::getInstance()->fetchArray() as $row) {
                    $content .= Core_CacheBuffer::serializeData(array(
                        $row['name'] => $row['value']));
                    $newConfig[$row['name']] = Exec_Entities::stripSlashes($row['value']);
                }

                // Mise en cache
                Core_CacheBuffer::writingCache("configs.php", $content, true);
            }

            // Ajout a la configuration courante
            $this->main->addConfig($newConfig);
        }
    }

}
