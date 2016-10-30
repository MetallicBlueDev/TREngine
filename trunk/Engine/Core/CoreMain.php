<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibBlock;
use TREngine\Engine\Lib\LibModule;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Exec\ExecMailer;
use TREngine\Engine\Exec\ExecTimeMarker;
use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Classe principal du moteur.
 *
 * @author Sébastien Villemain
 */
class CoreMain {

    /**
     * Instance principal du moteur.
     *
     * @var CoreMain
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

    /**
     * Informations sur l'agent.
     *
     * @var CoreAgentData
     */
    private $agentInfos = null;

    private function __construct() {
        // NE RIEN FAIRE
    }

    /**
     * Retourne l'instance principal du moteur.
     *
     * @return CoreMain
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$coreMain;
    }

    /**
     * Vérification de l'instance principal du moteur.
     */
    public static function checkInstance() {
        if (self::$coreMain === null) {
            if (CoreSecure::debuggingMode()) {
                ExecTimeMarker::startMeasurement("core");
            }

            self::$coreMain = new CoreMain();
            self::$coreMain->prepare();

            if (CoreSecure::debuggingMode()) {
                ExecTimeMarker::stopMeasurement("core");
            }
        }
    }

    /**
     * Retourne les données de l'agent actuel.
     *
     * @return CoreAgentData
     */
    public function getAgentInfos() {
        if ($this->agentInfos === null) {
            $this->agentInfos = new CoreAgentData();
        }
        return $this->agentInfos;
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
                $this->configs[$key] = ExecEntities::stripSlashes($value);
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
     * Retourne la configuration du cache.
     *
     * @return array
     */
    public function &getConfigCache() {
        return $this->getConfigValue("configs_cache");
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
     * @return bool
     */
    public function doUrlRewriting() {
        return ($this->getConfigValue("urlRewriting") === "1") ? true : false;
    }

    /**
     * Vérifie l'état de maintenance.
     *
     * @return bool
     */
    public function doDumb() {
        return (!$this->doOpening() && !CoreSession::getInstance()->getUserInfos()->hasAdminRank());
    }

    /**
     * Vérifie l'état du site (ouvert/fermé).
     *
     * @return bool
     */
    public function doOpening() {
        return ($this->getDefaultSiteStatut() === "open");
    }

    /**
     * Détermine l'état des inscriptions au site.
     *
     * @return bool
     */
    public function registrationAllowed() {
        return ($this->getConfigValue("registrationAllowed") === "1") ? true : false;
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
     * Retourne la durée de validité du cache des sessions.
     *
     * @return int
     */
    public function &getSessionTimeLimit() {
        return $this->getConfigValue("sessionTimeLimit");
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
            return CoreRequest::getString("SERVER_NAME", "", "SERVER");
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
     * @return bool true c'est en plein écran.
     */
    public function isDefaultLayout() {
        return (($this->layout === "default") ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé module.
     *
     * @return bool true c'est un affichage de module uniquement.
     */
    public function isModuleLayout() {
        return (($this->layout === "module" || $this->layout === "modulepage") ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé block.
     *
     * @return bool true c'est un affichage de block uniquement.
     */
    public function isBlockLayout() {
        return (($this->layout === "block" || $this->layout == "blockpage") ? true : false);
    }

    /**
     * Démarrage TR ENGINE.
     */
    public function start() {
        if (CoreSecure::debuggingMode()) {
            ExecTimeMarker::startMeasurement("launcher");
        }

        CoreTranslate::checkInstance();

        // Vérification des bannissements
        $coreSession = CoreSession::getInstance();
        $coreSession->checkBanishment();

        CoreHtml::checkInstance();

        // Configure les informations de page demandées
        $this->checkLayout();
        $this->checkMakeStyle();

        if (!CoreSecure::debuggingMode()) {
            $this->compressionOpen();
        }

        if ($coreSession->bannedSession()) {
            // Isoloire du bannissement
            $coreSession->displayBanishment();
        } else {
            // Vérification du type d'affichage
            if ($this->isDefaultLayout()) {
                $this->displayDefaultLayout();
            } else {
                // Affichage autonome des modules et blocks
                if ($this->isModuleLayout()) {
                    $this->displayModuleLayout();
                } else if ($this->isBlockLayout()) {
                    $this->displayBlockLayout();
                }

                // Execute la commande de récupération d'erreur
                CoreLogger::displayMessages();

                // Javascript autonome
                CoreHtml::getInstance()->selfJavascript();
            }

            // Validation et routine du cache
            CoreCache::getInstance()->workspaceCache();

            if (CoreSecure::debuggingMode()) {
                // Assemble tous les messages d'erreurs dans un fichier log
                CoreLogger::logException();
            }
        }

        ExecTimeMarker::stopMeasurement("main");

        if (CoreSecure::debuggingMode()) {
            ExecTimeMarker::stopMeasurement("launcher");
        } else {
            $this->compressionClose();
        }
    }

    /**
     * Recherche de nouveau composant.
     *
     * @return bool true nouveau composant détecté.
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
//        $installPath = TR_ENGINE_INDEXDIR . "/install/index.php";
//        if (is_file($installPath)) {
//            require $installPath;
//        }
    }

    /**
     * Affichage classique du site.
     */
    private function displayDefaultLayout() {
        if ($this->doDumb()) {
            // Mode maintenance: possibilité de s'identifier
            LibBlock::getInstance()->launchBlockByType("Login");

            // Affichage des données de la page de maintenance (fermeture)
            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assign("closeText", ERROR_DEBUG_CLOSE);
            $libMakeStyle->assign("closeReason", $this->getDefaultSiteCloseReason());
            $libMakeStyle->display("close");
        } else {
            // Mode normal: exécution général
            LibModule::getInstance()->launch();
            LibBlock::getInstance()->launchAllBlock();

            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->display("main");
        }
    }

    /**
     * Affichage du module uniquement.
     */
    private function displayModuleLayout() {
        $libModule = LibModule::getInstance();
        $libModule->launch();

        echo $libModule->getModule();
    }

    /**
     * Affichage du block uniquement.
     */
    private function displayBlockLayout() {
        $libBlock = LibBlock::getInstance();
        $libBlock->launchBlockRequested();

        echo $libBlock->getBlock();
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
        $layout = strtolower(CoreRequest::getWord("layout"));

        // Configuration du layout
        if ($layout !== "default" && $layout !== "modulepage" && $layout !== "blockpage" && (($layout !== "block" && $layout !== "module") || (!CoreHtml::getInstance()->javascriptEnabled()))) {
            $layout = "default";
        }

        $this->layout = $layout;
    }

    /**
     * Vérification et assignation du template.
     */
    private function checkMakeStyle() {
        $templateName = CoreSession::getInstance()->getUserInfos()->getTemplate();

        // Tentative d'utilisation du template du client
        if (!LibMakeStyle::isTemplateDir($templateName)) {
            $templateName = null;
        }

        // Tentative d'utilisation du template du site
        if (empty($templateName)) {
            $templateName = $this->getDefaultTemplate();
        }

        LibMakeStyle::setTemplateDir($templateName);
    }

    /**
     * Lance le tampon de sortie.
     * Entête & tamporisation de sortie.
     */
    private function compressionOpen() {
        header("Vary: Cookie, Accept-Encoding");
        // HTTP_ACCEPT_ENCODING => gzip
        if (extension_loaded('zlib') && ini_get('zlib.output_compression') !== "1" && function_exists("ob_gzhandler") && !$this->doUrlRewriting()) {
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
        if (!$this->loadCache()) {
            CoreSecure::getInstance()->throwException("cachePath", null, array(
                CoreLoader::getIncludeAbsolutePath("configs_cache")));
        }

        if (!$this->loadSql()) {
            CoreSecure::getInstance()->throwException("sqlPath", null, array(
                CoreLoader::getIncludeAbsolutePath("configs_database")));
        }

        if (!$this->loadConfig()) {
            CoreSecure::getInstance()->throwException("configPath", null, array(
                CoreLoader::getIncludeAbsolutePath("configs_config")));
        }

        // Chargement de la session
        CoreSession::checkInstance();
    }

    /**
     * Charge le gestionnaire de cache.
     *
     * @return bool true chargé
     */
    private function loadCache() {
        $canUse = CoreLoader::isCallable("CoreCache");

        if (!$canUse) {
            // Chemin vers le fichier de configuration du cache
            if (CoreLoader::includeLoader("configs_cache")) {
                // Démarrage de l'instance CoreCache
                CoreCache::checkInstance();

                $canUse = true;
            }
        }
        return $canUse;
    }

    /**
     * Charge le gestionnaire Sql.
     *
     * @return bool true chargé
     */
    private function loadSql() {
        $canUse = CoreLoader::isCallable("CoreSql");

        if (!$canUse) {
            // Chemin vers le fichier de configuration de la base de données
            if (CoreLoader::includeLoader("configs_database")) {
                // Démarrage de l'instance CoreSql
                CoreSql::checkInstance();

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
        $canUse = CoreLoader::includeLoader("configs_config");

        if ($canUse) {
            // Tentative d'utilisation de la configuration
            $rawConfig = $this->getConfigValue("configs_config");

            if (!empty($rawConfig)) {
                $newConfig = array();

                // Vérification de l'adresse email du webmaster
                if (!ExecMailer::isValidMail($rawConfig["TR_ENGINE_MAIL"])) {
                    CoreLogger::addException("Default mail isn't valide");
                }

                define("TR_ENGINE_MAIL", $rawConfig["TR_ENGINE_MAIL"]);

                // Vérification du statut
                $rawConfig["TR_ENGINE_STATUT"] = strtolower($rawConfig["TR_ENGINE_STATUT"]);

                if ($rawConfig["TR_ENGINE_STATUT"] !== "close" && $rawConfig["TR_ENGINE_STATUT"] !== "open") {
                    $rawConfig["TR_ENGINE_STATUT"] = "open";
                }

                define("TR_ENGINE_STATUT", $rawConfig["TR_ENGINE_STATUT"]);

                if (TR_ENGINE_STATUT == "close") {
                    CoreSecure::getInstance()->throwException("close");
                }

                // Vérification de la durée de validité du cache
                if (!is_int($rawConfig['sessionTimeLimit']) || $rawConfig['sessionTimeLimit'] < 1) {
                    $rawConfig['sessionTimeLimit'] = 7;
                }

                $newConfig['sessionTimeLimit'] = (int) $rawConfig['sessionTimeLimit'];

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
                $coreCache = CoreCache::getInstance(CoreCache::SECTION_TMP);

                // Si le cache est disponible
                if ($coreCache->cached("configs.php")) {
                    $newConfig = $coreCache->readCache("configs.php");
                } else {
                    $content = "";
                    $coreSql = CoreSql::getInstance();

                    // Requête vers la base de données de configs
                    $coreSql->select(CoreTable::CONFIG_TABLE, array(
                        "name",
                        "value"));

                    foreach ($coreSql->fetchArray() as $row) {
                        $content .= $coreCache->serializeData(array(
                            $row['name'] => $row['value']));
                        $newConfig[$row['name']] = ExecEntities::stripSlashes($row['value']);
                    }

                    // Mise en cache
                    $coreCache->writeCache("configs.php", $content);
                }

                // Ajout a la configuration courante
                $this->addConfig($newConfig);
            }
        }
        return $canUse;
    }

}
