<?php

namespace TREngine\Engine\Module;

use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Lib\LibModuleData;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Module de base, hérité par tous les autres modules.
 * Modèle pour le contenu d'un module.
 *
 * @author Sébastien Villemain
 */
abstract class ModuleModel {

    /**
     * Informations sur le module.
     *
     * @var LibModuleData
     */
    private $data = null;

    /**
     * Fonction d'affichage par défaut.
     */
    public function display() {
        CoreLogger::addErrorMessage(ERROR_MODULE_IMPLEMENT . ((!empty($this->getModuleData()->getName())) ? " (" . $this->getModuleData()->getName() . ")" : ""));
    }

    /**
     * Installation du module courant.
     */
    public function install() {
        CoreSql::getInstance()->insert(
        CoreTable::MODULES_TABLE, array(
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
        CoreSql::getInstance()->delete(
        CoreTable::MODULES_TABLE, array(
            "mod_id = '" . $this->getModuleData()->getId() . "'")
        );

        CoreCache::getInstance(CoreCache::SECTION_MODULES)->removeCache($this->getModuleData()->getName() . ".php");
        CoreTranslate::removeCache("modules" . DIRECTORY_SEPARATOR . $this->getModuleData()->getName());
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
     * @param LibModuleData $data
     */
    public function setModuleData(&$data) {
        $this->data = $data;
    }

    /**
     * Retourne le données du module.
     *
     * @return LibModuleData
     */
    public function &getModuleData() {
        if ($this->data === null) {
            $empty = array();
            $this->data = new LibModuleData($empty);
        }
        return $this->data;
    }

    /**
     * Retourne l'accès spécifique de ce module.
     *
     * @return CoreAccessType
     */
    public function &getAccessType() {
        return CoreAccessType::getTypeFromToken($this->getModuleData());
    }

}