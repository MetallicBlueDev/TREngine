<?php

/**
 * Gestionnaire de données en mémoire.
 *
 * @author Sébastien Villemain
 */
abstract class Core_DataStorage {

    /**
     * Données en cache.
     *
     * @var array
     */
    private $data = array();

    /**
     * Nouveau modèle de données.
     */
    protected function __construct() {
        $this->data = null;
    }

    /**
     * Initialise les données.
     *
     * @param array $data
     */
    protected function newStorage(array &$data) {
        $this->data = $data;
    }

    /**
     * Retourne toutes les information en mémoire.
     *
     * @return array
     */
    protected function &getStorage() {
        return $this->data;
    }

    /**
     * Détermine si instance a été réalisée.
     *
     * @return boolean
     */
    protected function initialized() {
        return $this->data !== null;
    }

    /**
     * Retourne la valeur de la clé.
     *
     * @return mixed
     */
    protected function &getDataValue($keyName, $defaultValue = null) {
        $value = null;

        if ($this->exist($keyName)) {
            $value = $this->data[$keyName];
        } else {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * Détermine si la clé existe.
     *
     * @param string $keyName
     * @return boolean
     */
    protected function exist($keyName) {
        return isset($this->data[$keyName]);
    }

    /**
     * Détermine si une valeur a été affectée.
     *
     * @param string $keyName
     * @return boolean
     */
    protected function hasValue($keyName) {
        return $this->exist($keyName) && !empty($this->data[$keyName]);
    }

    /**
     * Change la valeur de clé.
     *
     * @param string $keyName
     * @param mixed $value
     */
    protected function setDataValue($keyName, $value) {
        $this->data[$keyName] = $value;
    }

    /**
     * Change la valeur de clé uniquement si elle existe déjà.
     *
     * @param string $keyName
     * @param mixed $value
     */
    protected function updateDataValue($keyName, $value) {
        if ($this->exist($keyName)) {
            $this->setDataValue($keyName, $value);
        }
    }

}
