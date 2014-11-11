<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de module.
 *
 * @author Sébastien Villemain
 */
class Libs_Module {

    /**
     * Instance du gestionnaire de module.
     *
     * @var Libs_Module
     */
    private static $libsModule = null;

    /**
     * Nom du module courant.
     *
     * @var string
     */
    private $module = "";

    /**
     * Nom de la page courante.
     *
     * @var string
     */
    private $page = "";

    /**
     * Nom du viewer courant.
     *
     * @var string
     */
    private $view = "";

    /**
     * Tableau d'information sur les modules.
     *
     * @var array
     */
    private $modulesInfo = array();

    /**
     * Création du gestionnaire.
     */
    private function __construct() {
        $this->module = CoreRequest::getWord("mod");
        $this->page = CoreRequest::getWord("page");
        $this->view = CoreRequest::getWord("view");

        $defaultModule = CoreMain::getInstance()->getDefaultMod();
        $defaultPage = "index";
        $defaultView = "display";

        if (!empty($this->module) && empty($this->page)) {
            $this->page = $defaultPage;
        }

        // Erreur dans la configuration
        if (!$this->isModule()) {
            if (!empty($this->module) || !empty($this->page)) {
                // Afficher une erreur 404
                CoreLogger::addInformationMessage(ERROR_404);
            }

            $this->module = $defaultModule;
            $this->page = $defaultPage;
            $this->view = $defaultView;
        }

        if (empty($this->view)) {
            $this->view = $defaultView;
        }
    }

    /**
     * Création et récuperation de l'instance du module.
     *
     * @param string $module
     * @param string $page
     * @param string $view
     * @return Libs_Module
     */
    public static function &getInstance() {
        if (self::$libsModule === null) {
            self::$libsModule = new Libs_Module();
        }
        return self::$libsModule;
    }

    /**
     * Retourne un tableau contenant les modules disponibles.
     *
     * @return array => array("value" => valeur du module, "name" => nom du module).
     */
    public static function &getModuleList() {
        $moduleList = array();
        $modules = CoreCache::getInstance()->getNameList("modules");

        foreach ($modules as $module) {
            $moduleList[] = array(
                "value" => $module,
                "name" => "Module " . $module);
        }
        return $moduleList;
    }

    /**
     * Détermine si le module est en cours d'utilisation.
     *
     * @param string $moduleName
     * @return boolean true le module est actuellement sélectionné
     */
    public static function &isSelected($moduleName) {
        $selected = false;

        if (self::$libsModule !== null) {
            $selected = self::$libsModule->module === $moduleName;
        }
        return $selected;
    }

    /**
     * Retourne les informations du module cible.
     *
     * @param string $moduleName Le nom du module, par défaut le module courant.
     * @return Libs_ModuleData Informations sur le module.
     */
    public function &getInfoModule($moduleName = "") {
        $moduleInfo = null;

        // Nom du module cible
        $moduleName = empty($moduleName) ? $this->module : $moduleName;

        if (isset($this->modulesInfo[$moduleName])) {
            $moduleInfo = $this->modulesInfo[$moduleName];
        } else {
            $moduleData = array();

            // Recherche dans le cache
            $coreCache = CoreCache::getInstance(CoreCache::SECTION_MODULES);

            if (!$coreCache->cached($moduleName . ".php")) {
                $coreSql = CoreSql::getInstance();

                $coreSql->select(
                CoreTable::MODULES_TABLE, array(
                    "mod_id",
                    "name",
                    "rank",
                    "configs"), array(
                    "name =  '" . $moduleName . "'")
                );

                if ($coreSql->affectedRows() > 0) {
                    $moduleData = $coreSql->fetchArray()[0];

                    if (isset($moduleData['configs'])) {
                        $moduleData['configs'] = self::getModuleConfigs($moduleData['configs']);
                    }

                    // Mise en cache
                    $content = $coreCache->serializeData($moduleData);
                    $coreCache->writeCache($moduleName . ".php", $content);
                }
            } else {
                $moduleData = $coreCache->readCache($moduleName . ".php");
            }

            // Injection des informations du module
            $moduleInfo = new Libs_ModuleData($moduleData);
            $this->modulesInfo[$moduleName] = $moduleInfo;
        }
        return $moduleInfo;
    }

    /**
     * Charge le module courant.
     */
    public function launch() {
        $moduleInfo = $this->getInfoModule();

        // Vérification du niveau d'acces
        if (($moduleInfo->installed() && CoreAccess::autorize(CoreAccessType::getTypeFromToken($moduleInfo))) || (!$moduleInfo->installed() && CoreSession::getInstance()->getUserInfos()->hasAdminRank())) {
            if ($moduleInfo->isValid($this->page)) {

                if (CoreLoader::isCallable("Libs_Breadcrumb")) {
                    $libsBreadcrumb = Libs_Breadcrumb::getInstance();
                    $libsBreadcrumb->addTrail($moduleInfo->getName(), "?mod=" . $moduleInfo->getName());

                    // TODO A MODIFIER
                    // Juste une petite exception pour le module management qui est different
                    if ($moduleInfo->getName() !== "management") {
                        $libsBreadcrumb->addTrail($this->view, "?mod=" . $moduleInfo->getName() . "&view=" . $this->view);
                    }
                }

                $this->get($moduleInfo);
            }
        } else {
            CoreLogger::addErrorMessage(ERROR_ACCES_ZONE . " " . CoreAccess::getAccessErrorMessage($moduleInfo));
        }
    }

    /**
     * Retourne le jeu de configuration du module.
     *
     * @param string $moduleConfigs
     * @return array
     */
    private static function getModuleConfigs(&$moduleConfigs) {
        $moduleConfigs = explode("|", $moduleConfigs);

        foreach ($moduleConfigs as $config) {
            if (!empty($config)) {
                $values = explode("=", $config);

                if (count($values) > 1) {
                    // Chaine encodé avec urlencode
                    $moduleConfigs[$values[0]] = urldecode($values[1]);
                }
            }
        }
        return $moduleConfigs;
    }

    /**
     * Récupère le module.
     *
     * @param Libs_ModuleData $moduleInfo
     */
    private function get(&$moduleInfo) {
        $moduleClassName = "Module" . ucfirst($moduleInfo->getName()) . ucfirst($this->page);
        $loaded = CoreLoader::classLoader($moduleClassName);

        if ($loaded) {
            // Retourne un view valide sinon une chaine vide
            $this->view = $this->viewPage(array(
                $moduleClassName,
                ($moduleInfo->installed()) ? $this->view : "install"));

            // Affichage du module si possible
            if (!empty($this->view)) {
                CoreTranslate::getInstance()->translate("modules" . DIRECTORY_SEPARATOR . $moduleInfo->getName());

                $this->updateCount($moduleInfo->getId());

                /**
                 * @var Module_Model
                 */
                $moduleClass = new $moduleClassName();
                $moduleClass->setModuleData($moduleInfo);

                // Capture des données d'affichage
                ob_start();
                echo $moduleClass->{$this->view}();
                $moduleInfo->setBuffer(ob_get_contents());
                ob_end_clean();
            } else {
                CoreLogger::addErrorMessage(ERROR_MODULE_CODE . " (" . $moduleInfo->getName() . ")");
            }
        }
    }

    /**
     * Vérifie si le module existe.
     *
     * @param string $moduleName
     * @param string $page
     * @return boolean true le module existe.
     */
    public function isModule($moduleName = "", $page = "") {
        // Nom du module cible
        $moduleName = empty($moduleName) ? $this->module : $moduleName;

        // Nom de la page cible
        $page = empty($page) ? $this->page : $page;

        return is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . $page . ".module.php");
    }

    /**
     * Retourne le module compilé.
     *
     * @return string
     */
    public function &getModule() {
        $buffer = $this->getInfoModule()->getBuffer();

        // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
        if (Exec_Utils::inArray("rewriteBuffer", $this->getInfoModule()->getConfigs())) {
            $buffer = CoreUrlRewriting::getInstance()->rewriteBuffer($buffer);
        }
        return $buffer;
    }

    /**
     * Retourne un view valide sinon une chaine vide.
     *
     * @param array $pageInfo
     * @return string
     */
    private function &viewPage(array $pageInfo) {
        $invalid = false;

        if (CoreLoader::isCallable($pageInfo[0], $pageInfo[1])) {
            $userInfos = CoreSession::getInstance()->getUserInfos();

            if ($pageInfo[1] === "install" && ($this->getInfoModule()->installed() || !$userInfos->hasAdminRank())) {
                $invalid = true;
            } else if ($pageInfo[1] === "uninstall" && (!$this->getInfoModule()->installed() || !$userInfos->hasAdminRank())) {
                $invalid = true;
            } else if ($pageInfo[1] === "setting" && (!$this->getInfoModule()->installed() || !$userInfos->hasAdminRank())) {
                $invalid = true;
            }
        } else {
            $invalid = true;
        }

        $rslt = "";

        if ($invalid) {
            $default = "display";

            if ($pageInfo[1] !== $default) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default));
            }
        } else {
            $rslt = $pageInfo[1];
        }
        return $rslt;
    }

    /**
     * Mise à jour du compteur de visite du module courant.
     *
     * @param int $modId
     */
    private function updateCount($modId) {
        $coreSql = CoreSql::getInstance();

        $coreSql->addQuotedValue("count + 1");
        $coreSql->update(
        CoreTable::MODULES_TABLE, array(
            "count" => "count + 1"), array(
            "mod_id = '" . $modId . "'")
        );
    }

}
