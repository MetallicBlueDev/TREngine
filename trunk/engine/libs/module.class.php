<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

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
        $this->module = Core_Request::getWord("mod");
        $this->page = Core_Request::getWord("page");
        $this->view = Core_Request::getWord("view");

        $defaultModule = Core_Main::getInstance()->getDefaultMod();
        $defaultPage = "index";
        $defaultView = "display";

        if (!empty($this->module) && empty($this->page)) {
            $this->page = $defaultPage;
        }

        // Erreur dans la configuration
        if (!$this->isModule()) {
            if (!empty($this->module) || !empty($this->page)) {
                // Afficher une erreur 404
                Core_Logger::addInformationMessage(ERROR_404);
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
            self::$libsModule = new self();
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
        $modules = Core_Cache::getInstance()->getNameList("modules");

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

        // Retourne le buffer
        if (isset($this->modulesInfo[$moduleName])) {
            $moduleInfo = $this->modulesInfo[$moduleName];
        } else {
            $moduleData = array();

            // Recherche dans le cache
            $coreCache = Core_Cache::getInstance(Core_Cache::SECTION_MODULES);

            if (!$coreCache->cached($moduleName . ".php")) {
                $coreSql = Core_Sql::getInstance();

                $coreSql->select(
                Core_Table::MODULES_TABLE, array(
                    "mod_id",
                    "rank",
                    "configs"), array(
                    "name =  '" . $moduleName . "'")
                );

                if ($coreSql->affectedRows() > 0) {
                    $moduleData = $coreSql->fetchArray();

                    if (isset($moduleData['configs'])) {
                        $moduleData['configs'] = explode("|", $moduleData['configs']);

                        foreach ($moduleData['configs'] as $value) {
                            $value = explode("=", $value);

                            // Chaine encodé avec urlencode
                            $moduleData['configs'][$value[0]] = urldecode($value[1]);
                        }
                    }

                    // Mise en cache
                    $content = $coreCache->serializeData($moduleData);
                    $coreCache->writeCache($moduleName . ".php", $content);
                }
            } else {
                $moduleData = $coreCache->readCache($moduleName . ".php");
            }

            // Injection des informations du module
            $moduleInfo = new Libs_ModuleData($moduleName, $moduleData);
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
        if (($moduleInfo->installed() && Core_Access::autorize($moduleInfo->getName())) || (!$moduleInfo->installed() && Core_Session::getInstance()->userRank > 1)) {
            if ($moduleInfo->isValid($this->page)) {

                if (Core_Loader::isCallable("Libs_Breadcrumb")) {
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
            Core_Logger::addErrorMessage(ERROR_ACCES_ZONE . " " . Core_Access::getModuleAccesError($moduleInfo->getName()));
        }
    }

    /**
     * Récupère le module.
     *
     * @param Libs_ModuleData $moduleInfo
     */
    private function get(&$moduleInfo) {
        $moduleClassName = "Module_" . ucfirst($moduleInfo->getName()) . "_" . ucfirst($this->page);
        $loaded = Core_Loader::classLoader($moduleClassName);

        if ($loaded) {
            // Retourne un view valide sinon une chaine vide
            $this->view = $this->viewPage(array(
                $moduleClassName,
                ($moduleInfo->installed()) ? $this->view : "install"), false);

            // Affichage du module si possible
            if (!empty($this->view)) {
                Core_Translate::getInstance()->translate("modules/" . $moduleInfo->getName());

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
                Core_Logger::addErrorMessage(ERROR_MODULE_CODE . " (" . $moduleInfo->getName() . ")");
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

        return is_file(TR_ENGINE_DIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . $page . ".module.php");
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
            $buffer = Core_UrlRewriting::getInstance()->rewriteBuffer($buffer);
        }
        return $buffer;
    }

    /**
     * Retourne un view valide sinon une chaine vide.
     *
     * @param array $pageInfo
     * @param boolean $setAlternative
     * @return string
     */
    private function &viewPage(array $pageInfo, $setAlternative = true) {
        $rslt = "";
        $default = "display";

        if (Core_Loader::isCallable($pageInfo[0], $pageInfo[1])) {
            if ($pageInfo[1] === "install" && ($this->getInfoModule()->installed() || Core_Session::getInstance()->userRank < 2)) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default), false);
            } else if ($pageInfo[1] === "uninstall" && (!$this->getInfoModule()->installed() || !Core_Access::moderate($this->module))) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default), false);
            } else if ($pageInfo[1] === "setting" && (!$this->getInfoModule()->installed() || !Core_Access::moderate($this->module))) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default), false);
            } else {
                $rslt = $pageInfo[1];
            }
        } else if ($setAlternative && $pageInfo[1] !== $default) {
            $rslt = $this->viewPage(array(
                $pageInfo[0],
                $default), false);
        }
        return $rslt;
    }

    /**
     * Mise à jour du compteur de visite du module courant.
     *
     * @param int $modId
     */
    private function updateCount($modId) {
        $coreSql = Core_Sql::getInstance();

        $coreSql->addQuoted("", "count + 1");
        $coreSql->update(
        Core_Table::MODULES_TABLE, array(
            "count" => "count + 1"), array(
            "mod_id = '" . $$modId . "'")
        );
    }

}
