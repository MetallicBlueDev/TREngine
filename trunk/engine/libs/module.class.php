<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

Core_Loader::classLoader("Libs_ModuleData");
Core_Loader::classLoader("Libs_ModuleModel");

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
     * @var String
     */
    private $module = "";

    /**
     * Nom de la page courante.
     *
     * @var String
     */
    private $page = "";

    /**
     * Nom du viewer courant.
     *
     * @var String
     */
    private $view = "";

    /**
     * Module compilé.
     *
     * @var String
     */
    private $moduleCompiled = "";

    /**
     * Tableau d'information sur les modules.
     *
     * @var array
     */
    private $modules = array();

    private function __construct($module, $page, $view) {
        $this->module = $module;
        $this->page = $page;
        $this->view = $view;

        $defaultModule = Core_Main::$coreConfig['defaultMod'];
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
     * @return Libs_Module
     */
    public static function &getInstance($module = "", $page = "", $view = "") {
        if (self::$libsModule == null) {
            self::$libsModule = new self($module, $page, $view);
        }
        return self::$libsModule;
    }

    /**
     * Retourne un tableau contenant les modules disponibles.
     *
     * @return array => array("value" => valeur du module, "name" => nom du module).
     */
    public static function &listModules() {
        $moduleList = array();
        $modules = Core_CacheBuffer::listNames("modules");

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
     * @param String $moduleName
     * @return boolean true le module est actuellement sélectionné
     */
    public static function isSelected($moduleName) {
        $selected = false;

        if (self::$libsModule != null) {
            $selected = self::$libsModule->module == $moduleName;
        }
        return $selected;
    }

    /**
     * Retourne les informations du module cible.
     *
     * @param String $moduleName  le nom du module, par défaut le module courant.
     * @return Libs_ModuleData informations sur le module.
     */
    public function &getInfoModule($moduleName = "") {
        $moduleInfo = null;

        // Nom du module cible
        $moduleName = empty($moduleName) ? $this->module : $moduleName;

        // Retourne le buffer
        if (isset($this->modules[$moduleName])) {
            $moduleInfo = $this->modules[$moduleName];
        } else {
            $moduleData = array();

            // Recherche dans le cache
            Core_CacheBuffer::setSectionName("modules");

            if (!Core_CacheBuffer::cached($moduleName . ".php")) {
                Core_Sql::select(
                Core_Table::$MODULES_TABLE, array(
                    "mod_id",
                    "rank",
                    "configs"), array(
                    "name =  '" . $moduleName . "'")
                );

                if (Core_Sql::affectedRows() > 0) {
                    $moduleData = Core_Sql::fetchArray();
                    $configs = explode("|", $moduleData['configs']);

                    foreach ($configs as $value) {
                        $value = explode("=", $value);
                        $configs[$value[0]] = urldecode($value[1]); // Chaine encodé en urlencode
                    }

                    $moduleData['configs'] = $configs;

                    // Mise en cache
                    $content = Core_CacheBuffer::serializeData($moduleData);
                    Core_CacheBuffer::writingCache($moduleName . ".php", $content);
                }
            } else {
                $moduleData = Core_CacheBuffer::getCache($moduleName . ".php");
            }

            // Injection des informations du module
            $moduleInfo = new Libs_ModuleData($moduleName, &$moduleData);
            $this->modules[$moduleName] = $moduleInfo;
        }
        return $moduleInfo;
    }

    /**
     * Charge le module courant.
     */
    public function launch() {
        // Vérification du niveau d'acces
        if (($this->installed() && Core_Access::autorize($this->module)) || (!$this->installed() && Core_Session::$userRank > 1)) {
            if (empty($this->moduleCompiled) && $this->isModule()) {
                Core_Translate::translate("modules/" . $this->module);

                if (Core_Loader::isCallable("Libs_Breadcrumb")) {
                    Libs_Breadcrumb::getInstance()->addTrail($this->module, "?mod=" . $this->module);

                    // TODO A MODIFIER
                    // Juste une petite exception pour le module management qui est different
                    if ($this->module != "management") {
                        Libs_Breadcrumb::getInstance()->addTrail($this->view, "?mod=" . $this->module . "&view=" . $this->view);
                    }
                }
                $this->get();
            }
        } else {
            Core_Logger::addErrorMessage(ERROR_ACCES_ZONE . " " . Core_Access::getModuleAccesError($this->module));
        }
    }

    /**
     * Vérifie si le module existe.
     *
     * @param String $moduleName
     * @param String $page
     * @return boolean true le module existe.
     */
    public function isModule($moduleName = "", $page = "") {
        // Nom du module cible
        $moduleName = empty($moduleName) ? $this->module : $moduleName;

        // Nom de la page cible
        $page = empty($page) ? $this->page : $page;

        return is_file(TR_ENGINE_DIR . "/modules/" . $moduleName . "/" . $page . ".module.php");
    }

    /**
     * Retourne le module compilé.
     *
     * @return String
     */
    public function &getModule($rewriteBuffer = false) {
        $buffer = $this->moduleCompiled;

        // Tamporisation de sortie
        if (Core_Main::doUrlRewriting() && ($rewriteBuffer || Exec_Utils::inArray("rewriteBuffer", $this->getInfoModule()->getConfigs()))) {
            $buffer = Core_UrlRewriting::rewriteBuffer($buffer);
        }
        // Relachement des tampon
        return $buffer;
    }

    /**
     * Retourne un view valide sinon une chaine vide.
     *
     * @param array $pageInfo
     * @param boolean $setAlternative
     * @return String
     */
    private function &viewPage(array $pageInfo, $setAlternative = true) {
        $rslt = "";
        $default = "display";

        if (Core_Loader::isCallable($pageInfo[0], $pageInfo[1])) {
            if ($pageInfo[1] == "install" && ($this->installed() || Core_Session::$userRank < 2)) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default), false);
            } else if ($pageInfo[1] == "uninstall" && (!$this->installed() || !Core_Access::moderate($this->module))) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default), false);
            } else if ($pageInfo[1] == "setting" && (!$this->installed() || !Core_Access::moderate($this->module))) {
                $rslt = $this->viewPage(array(
                    $pageInfo[0],
                    $default), false);
            } else {
                $rslt = $pageInfo[1];
            }
        } else if ($setAlternative && $pageInfo[1] != $default) {
            $rslt = $this->viewPage(array(
                $pageInfo[0],
                $default), false);
        }
        return $rslt;
    }

    /**
     * Récupère le module.
     *
     * @param String $viewPage
     */
    private function get() {
        $moduleClassName = "Module_" . ucfirst($this->module) . "_" . ucfirst($this->page);
        $loaded = Core_Loader::classLoader($moduleClassName);

        if ($loaded) {
            // Retourne un view valide sinon une chaine vide
            $this->view = $this->viewPage(array(
                $moduleClassName,
                ($this->installed()) ? $this->view : "install"), false);

            // Affichage du module si possible
            if (!empty($this->view)) {
                $this->updateCount();
                $ModuleClass = new $moduleClassName();
                $ModuleClass->data = $this->getInfoModule();

                // Capture des données d'affichage
                ob_start();
                echo $ModuleClass->{$this->view}();
                $this->moduleCompiled = ob_get_contents();
                ob_end_clean();
            } else {
                Core_Logger::addErrorMessage(ERROR_MODULE_CODE . " (" . $this->module . ")");
            }
        }
    }

    /**
     * Vérifie que le module est installé.
     *
     * @param String $moduleName
     * @return boolean
     */
    private function installed($moduleName = "") {
        return (isset($this->getInfoModule($moduleName)['mod_id']));
    }

    /**
     * Mise à jour du compteur de visite du module courant.
     */
    private function updateCount() {
        Core_Sql::addQuoted("", "count + 1");
        Core_Sql::update(
        Core_Table::$MODULES_TABLE, array(
            "count" => "count + 1"), array(
            "mod_id = '" . $this->getInfoModule()->getId() . "'")
        );
    }

}
