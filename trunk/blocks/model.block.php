<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

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
            $empty = array();
            $this->data = new Libs_BlockData($empty);
        }
        return $this->data;
    }

    /**
     * Retourne l'accès spécifique de ce module.
     *
     * @return Core_AccessType
     */
    public function &getAccessType() {
        return Core_AccessType::getTypeFromToken($this->getBlockData());
    }

}
