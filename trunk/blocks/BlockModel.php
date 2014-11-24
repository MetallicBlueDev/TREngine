<?php

namespace TREngine\Blocks;

use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Libs\LibsBlockData;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de base, hérité par tous les autres blocks.
 * Modèle pour le contenu d'un block.
 *
 * @author Sébastien Villemain
 */
abstract class BlockModel {

    /**
     * Informations sur le block.
     *
     * @var LibsBlockData
     */
    private $data = null;

    /**
     * Affichage par défaut.
     */
    public function display() {
        CoreLogger::addErrorMessage(ERROR_BLOCK_IMPLEMENT . ((!empty($this->getBlockData()->getTitle())) ? " (" . $this->getBlockData()->getTitle() . ")" : ""));
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
     * @param LibsBlockData $data
     */
    public function setBlockData(&$data) {
        $this->data = $data;
    }

    /**
     * Retourne le données du block.
     *
     * @return LibsBlockData
     */
    public function &getBlockData() {
        if ($this->data === null) {
            $empty = array();
            $this->data = new LibsBlockData($empty);
        }
        return $this->data;
    }

    /**
     * Retourne l'accès spécifique de ce module.
     *
     * @return CoreAccessType
     */
    public function &getAccessType() {
        return CoreAccessType::getTypeFromToken($this->getBlockData());
    }

}
