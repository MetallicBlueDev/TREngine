<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreDataStorage;
use TREngine\Engine\Core\CoreAccessToken;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur une entité de bibliothèque.
 *
 * @author Sébastien Villemain
 */
abstract class LibEntityData extends CoreDataStorage implements CoreAccessToken {

    /**
     * Les données compilées.
     *
     * @var string
     */
    private $buffer = "";

    /**
     * Nouvelle information d'une entité.
     */
    protected function __construct() {
        parent::__construct();
    }

    /**
     * Retourne les données compilées.
     *
     * @return string
     */
    public function &getBuffer() {
        return $this->buffer;
    }

    /**
     * Affecte les données compilées.
     *
     * @param string $buffer
     */
    public function setBuffer($buffer) {
        $this->buffer = $buffer;
    }

    /**
     * Retourne le nom du dossier contenant l'entité.
     *
     * @return string
     */
    abstract public function getFolderName();

    /**
     * Retourne le nom de classe représentant l'entité.
     *
     * @return string
     */
    abstract public function getClassName();

    /**
     * Détermine si l'entité est valide.
     *
     * @return boolean
     */
    abstract public function isValid();

    /**
     * Détermine si l'entité est installée.
     *
     * @return boolean
     */
    abstract public function installed();
}
