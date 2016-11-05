<?php

namespace TREngine\Engine\Block;

use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Lib\LibBlockData;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

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
     * @var LibBlockData
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
     * @param LibBlockData $data
     */
    public function setBlockData(LibBlockData &$data) {
        $this->data = $data;
    }

    /**
     * Retourne le données du block.
     *
     * @return LibBlockData
     */
    public function &getBlockData(): LibBlockData {
        if ($this->data === null) {
            $empty = array();
            $this->data = new LibBlockData($empty);
        }
        return $this->data;
    }

    /**
     * Retourne l'accès spécifique de ce module.
     *
     * @return CoreAccessType
     */
    public function &getAccessType(): CoreAccessType {
        return CoreAccessType::getTypeFromToken($this->getBlockData());
    }

}
