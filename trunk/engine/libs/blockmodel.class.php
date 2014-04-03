<?php

/**
 * Block de base, hérité par tous les autres blocks.
 * Modèle pour le contenu d'un block.
 *
 * @author Sébastien Villemain
 */
abstract class Libs_BlockModel {

    /**
     * Identifiant du block.
     *
     * @var int
     */
    public $blockId = 0;

    /**
     * Position du block en chiffre.
     *
     * @var int
     */
    public $side = 0;

    /**
     * Position du block en lettre.
     *
     * @var string
     */
    public $sideName = "";

    /**
     * Nom complet du template de block a utiliser.
     *
     * @var string
     */
    public $templateName = "";

    /**
     * Titre du block.
     *
     * @var string
     */
    public $title = "";

    /**
     * Contenu du block.
     *
     * @var string
     */
    public $content = "";

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
        Core_Logger::addErrorMessage(ERROR_BLOCK_IMPLEMENT . ((!empty($this->title)) ? " (" . $this->title . ")" : ""));
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

}
