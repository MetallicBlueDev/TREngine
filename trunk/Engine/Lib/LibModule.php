<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Module\ModuleModel;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreSession;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Exec\ExecUtils;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de module.
 *
 * @author Sébastien Villemain
 */
class LibModule {

    /**
     * Instance du gestionnaire de module.
     *
     * @var LibModule
     */
    private static $libModule = null;

    /**
     * Nom du module courant.
     *
     * @var string
     */
    private $module = "";

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

    }

    /**
     * Vérification de l'instance du gestionnaire des modules.
     */
    public static function checkInstance() {
        if (self::$libModule === null) {
            // Création d'un instance autonome
            self::$libModule = new LibModule();

            // Nom du module courant
            $moduleName = CoreRequest::getWord("mod");
            $defaultModuleName = CoreMain::getInstance()->getDefaultMod();

            if (empty($moduleName)) {
                $moduleName = $defaultModuleName;
            }

            // Nom de la page courante
            $page = CoreRequest::getWord("page");
            $defaultPage = "index";

            if (empty($page)) {
                $page = $defaultPage;
            }

            // Nom du viewer courant
            $view = CoreRequest::getWord("view");
            $defaultView = "display";

            if (empty($view)) {
                $view = $defaultView;
            }

            $infoModule = self::$libModule->getInfoModule($moduleName);
            $infoModule->setPage($page);
            $infoModule->setView($view);

            if (!$infoModule->isValid() && $moduleName !== $defaultModuleName) {
                // Afficher une erreur 404
                if (!empty($moduleName) || !empty($page)) {
                    CoreLogger::addInformationMessage(ERROR_404);
                }

                // Utilisation du module par défaut
                $moduleName = $defaultModuleName;
                $page = $defaultPage;
                $view = $defaultView;

                $infoModule = self::$libModule->getInfoModule($moduleName);
                $infoModule->setPage($page);
                $infoModule->setView($view);
            }

            self::$libModule->module = $infoModule->getName();
        }
    }

    /**
     * Création et récuperation de l'instance du module.
     *
     * @param string $module
     * @param string $page
     * @param string $view
     * @return LibModule
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$libModule;
    }

    /**
     * Retourne un tableau contenant les modules disponibles.
     *
     * @return array => array("value" => valeur du module, "name" => nom du module).
     */
    public static function &getModuleList() {
        $moduleList = array();
        $modules = CoreCache::getInstance()->getNameList("Modules");

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

        if (self::$libModule !== null) {
            $selected = self::$libModule->module === $moduleName;
        }
        return $selected;
    }

    /**
     * Retourne les informations du module cible.
     *
     * @param string $moduleName Le nom du module, par défaut le module courant.
     * @return LibModuleData Informations sur le module.
     */
    public function &getInfoModule($moduleName = "") {
        $moduleInfo = null;

        if (empty($moduleName)) {
            $moduleName = $this->module;
        } else {
            $moduleName = ucfirst($moduleName);
        }

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
            $moduleInfo = new LibModuleData($moduleData);
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
            if ($moduleInfo->isValid()) {
                CoreTranslate::getInstance()->translate("Modules" . DIRECTORY_SEPARATOR . $moduleInfo->getName());

                $libBreadcrumb = LibBreadcrumb::getInstance();
                $libBreadcrumb->addTrail($moduleInfo->getName(), "?mod=" . $moduleInfo->getName());

                // TODO A MODIFIER
                // Juste une petite exception pour le module management qui est different
                if ($moduleInfo->getName() !== "management") {
                    $libBreadcrumb->addTrail($moduleInfo->getView(), "?mod=" . $moduleInfo->getName() . "&view=" . $moduleInfo->getView());
                }

                $this->get($moduleInfo);
            }
        } else {
            CoreLogger::addErrorMessage(ERROR_ACCES_ZONE . " " . CoreAccess::getAccessErrorMessage($moduleInfo));
        }
    }

    /**
     * Retourne le module compilé.
     *
     * @return string
     */
    public function &getModule() {
        $buffer = $this->getInfoModule()->getBuffer();

        // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
        if (ExecUtils::inArray("rewriteBuffer", $this->getInfoModule()->getConfigs())) {
            $buffer = CoreUrlRewriting::getInstance()->rewriteBuffer($buffer);
        }
        return $buffer;
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
     * @param LibModuleData $moduleInfo
     */
    private function get(&$moduleInfo) {
        $moduleClassName = CoreLoader::getFullQualifiedClassName($moduleInfo->getClassName(), $moduleInfo->getName());
        $loaded = CoreLoader::classLoader($moduleClassName);

        if ($loaded) {
            // Vérification de la sous page
            $moduleInfo->setView($this->getValidViewPage(array(
                $moduleClassName,
                ($moduleInfo->installed()) ? $moduleInfo->getView() : "install")));

            // Affichage du module si possible
            if (!empty($moduleInfo->getView())) {
                $this->updateCount($moduleInfo->getId());

                /**
                 * @var ModuleModel
                 */
                $moduleClass = new $moduleClassName();
                $moduleClass->setModuleData($moduleInfo);

                // Capture des données d'affichage
                ob_start();
                echo $moduleClass->{$moduleInfo->getView()}();
                $moduleInfo->setBuffer(ob_get_clean());
            } else {
                CoreLogger::addErrorMessage(ERROR_MODULE_CODE . " (" . $moduleInfo->getName() . ")");
            }
        }
    }

    /**
     * Retourne un view valide sinon une chaine vide.
     *
     * @param array $pageInfo
     * @return string
     */
    private function &getValidViewPage(array $pageInfo) {
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
                $rslt = $this->getValidViewPage(array(
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
