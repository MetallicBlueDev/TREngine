<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Block de base, hérité par tous les autres blocks.
 * Modèle pour le contenu d'un block.
 *
 * @author Sébastien Villemain
 */
abstract class Block_Model {

    /**
     * Informations sur le block.
     *
     * @var Libs_BlockData
     */
    private $data = null;

    /**
     * Rank pour acceder au block.
     *
     * @var int
     */
    public $rank = "";

    /**
     * Affichage par défaut.
     */
    public function display() {
        Core_Logger::addErrorMessage(ERROR_BLOCK_IMPLEMENT . ((!empty($this->getBlockData()->getTitle())) ? " (" . $this->getBlockData()->getTitle() . ")" : ""));
    }

    /**
     * Procédure d'installation du block.
     */
    public function install() {

    }

    /**
     * Procédure de désinstallation du block.
     */
    public function uninstall() {

    }

    /**
     * Affecte les données du block.
     *
     * @param Libs_BlockData $data
     */
    public function setBlockData(&$data) {
        $this->data = $data;
    }

    /**
     * Retourne le données du block.
     *
     * @return Libs_BlockData
     */
    public function &getBlockData() {
        if ($this->data === null) {
            $this->data = new Libs_BlockData(array());
        }
        return $this->data;
    }

}
