<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Module de base, hérité par tous les autres modules.
 * Modèle pour le contenu d'un module.
 *
 * @author Sébastien Villemain
 */
abstract class Libs_ModuleModel {

    /**
     * Informations sur le module.
     *
     * @var Libs_ModuleData
     */
    private $data = null;

    /**
     * Fonction d'affichage par défaut.
     */
    public function display() {
        Core_Logger::addErrorMessage(ERROR_MODULE_IMPLEMENT . ((!empty($this->getModuleData()->getName())) ? " (" . $this->getModuleData()->getName() . ")" : ""));
    }

    /**
     * Installation du module courant.
     */
    public function install() {
        Core_Sql::getInstance()->insert(
        Core_Table::MODULES_TABLE, array(
            "name",
            "rank",
            "configs"), array(
            $this->getModuleData()->getName(),
            0,
            "")
        );
    }

    /**
     * Désinstallation du module courant.
     */
    public function uninstall() {
        Core_Sql::getInstance()->delete(
        Core_Table::MODULES_TABLE, array(
            "mod_id = '" . $this->getModuleData()->getId() . "'")
        );

        Core_Cache::getInstance(Core_Cache::SECTION_MODULES);

        Core_Cache::getInstance()->removeCache($this->getModuleData()->getName() . ".php");
        Core_Translate::removeCache("modules" . DIRECTORY_SEPARATOR . $this->getModuleData()->getName());
    }

    /**
     * Configuration du module courant.
     */
    public function setting() {
        // TODO mettre un forumlaire basique pour changer quelques configurations
    }

    /**
     * Affecte les données du module.
     *
     * @param Libs_ModuleData $data
     */
    public function setModuleData(&$data) {
        $this->data = $data;
    }

    /**
     * Retourne le données du module.
     *
     * @return Libs_ModuleData
     */
    public function &getModuleData() {
        if ($this->data === null) {
            $this->data = new Libs_ModuleData();
        }
        return $this->data;
    }

}
