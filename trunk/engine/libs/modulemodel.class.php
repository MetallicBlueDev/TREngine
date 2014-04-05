<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
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
    public $data = null;

    /**
     * Fonction d'affichage par défaut.
     */
    public function display() {
        Core_Logger::addErrorMessage(ERROR_MODULE_IMPLEMENT . ((!empty($this->data->getName())) ? " (" . $this->data->getName() . ")" : ""));
    }

    /**
     * Installation du module courant.
     */
    public function install() {
        Core_Sql::insert(
        Core_Table::$MODULES_TABLE, array(
            "name",
            "rank",
            "configs"), array(
            $this->data->getName(),
            0,
            "")
        );
    }

    /**
     * Désinstallation du module courant.
     */
    public function uninstall() {
        Core_Sql::delete(
        Core_Table::$MODULES_TABLE, array(
            "mod_id = '" . $this->data->getId() . "'")
        );
        Core_CacheBuffer::setSectionName("modules");
        Core_CacheBuffer::removeCache($this->data->getName() . ".php");
        Core_Translate::getInstance()->removeCache("modules/" . $this->data->getName());
    }

    /**
     * Configuration du module courant.
     */
    public function setting() {
        // TODO mettre un forumlaire basique pour changer quelques configurations
    }

}
