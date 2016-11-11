<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Collecteur de données pour mise en mémoire tampon.
 *
 * @author Sébastien Villemain
 */
abstract class CoreDataStorage {

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
    protected function &getStorage(): array {
        return $this->data;
    }

    /**
     * Détermine si instance a été réalisée.
     *
     * @return bool
     */
    protected function initialized(): bool {
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
     * Retourne la valeur de la clé sous forme de chaine de caractères.
     *
     * @param string $keyName
     * @param string $defaultValue
     * @return string
     */
    protected function &getStringValue(string $keyName, string $defaultValue = null): string {
        $value = (string) $this->getDataValue($keyName, $defaultValue);
        return $value;
    }

    /**
     * Retourne la valeur de la clé sous forme d'entier.
     *
     * @param string $keyName
     * @param int $defaultValue
     * @return int
     */
    protected function &getIntValue(string $keyName, int $defaultValue = null): int {
        $value = (int) $this->getDataValue($keyName, $defaultValue);
        return $value;
    }

    /**
     * Détermine si la clé existe.
     *
     * @param string $keyName
     * @return bool
     */
    protected function exist(string $keyName): bool {
        return isset($this->data[$keyName]);
    }

    /**
     * Détermine si une valeur a été affectée.
     *
     * @param string $keyName
     * @return bool
     */
    protected function hasValue(string $keyName): bool {
        return $this->exist($keyName) && !empty($this->data[$keyName]);
    }

    /**
     * Change la valeur de clé.
     *
     * @param string $keyName
     * @param mixed $value
     */
    protected function setDataValue(string $keyName, $value) {
        $this->data[$keyName] = $value;
    }

    /**
     * Change la valeur de clé uniquement si elle existe déjà.
     *
     * @param string $keyName
     * @param mixed $value
     */
    protected function updateDataValue(string $keyName, $value) {
        if ($this->exist($keyName)) {
            $this->setDataValue($keyName, $value);
        }
    }

}
