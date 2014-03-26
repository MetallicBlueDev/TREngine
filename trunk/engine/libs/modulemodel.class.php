<?php

/**
 * Module de base, hérité par tous les autres modules.
 * Modèle pour le contenu d'un module.
 *
 * @author Sébastien Villemain
 */
abstract class Libs_ModuleModel {

    /**
     * Configuration du module
     *
     * @var array
     */
    protected $configs = array();

    /**
     * Id du module
     *
     * @var int
     */
    protected $modId = 0;

    /**
     * Nom du module courant
     *
     * @var String
     */
    protected $module = "";

    /**
     * Nom de la page courante
     *
     * @var String
     */
    protected $page = "";

    /**
     * Rank du module
     *
     * @var int
     */
    protected $rank = "";

    /**
     * Nom du viewer courant.
     *
     * @var String
     */
    protected $view = "";

    /**
     * Fonction d'affichage par défaut.
     */
    public function display() {
        Core_Logger::addErrorMessage(ERROR_MODULE_IMPLEMENT . ((!empty($this->module)) ? " (" . $this->module . ")" : ""));
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
            Libs_Module::$module,
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
            "mod_id = '" . $this->modId . "'")
        );
        Core_CacheBuffer::setSectionName("modules");
        Core_CacheBuffer::removeCache(Libs_Module::$module . ".php");
        Core_Translate::removeCache("modules/" . self::$module);
    }

    /**
     * Configuration du module courant.
     */
    public function setting() {
        // TODO mettre un forumlaire basique pour changer quelques configurations
    }

}
