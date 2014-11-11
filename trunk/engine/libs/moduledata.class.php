<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur un module.
 *
 * @author Sébastien Villemain
 */
class Libs_ModuleData extends Core_DataStorage implements Core_AccessToken {

    /**
     * Les données compilées du module.
     *
     * @var string
     */
    private $buffer = "";

    /**
     * Nouvelle information de module.
     *
     * @param array $data
     */
    public function __construct(array &$data) {
        parent::__construct();

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        $this->newStorage($data);
    }

    /**
     * Retourne les données compilées du module.
     *
     * @return string
     */
    public function &getBuffer() {
        return $this->buffer;
    }

    /**
     * Affecte les données compilées du module.
     *
     * @param string $buffer
     */
    public function setBuffer($buffer) {
        $this->buffer = $buffer;
    }

    /**
     * Retourne le nom du module.
     *
     * @return string Le nom du module.
     */
    public function &getName() {
        return $this->getDataValue("name");
    }

    /**
     * Retourne l'identifiant du module.
     *
     * @return int
     */
    public function &getId() {
        return $this->getDataValue("mod_id");
    }

    /**
     * Retourne le rang du module.
     *
     * @return int
     */
    public function &getRank() {
        return $this->getDataValue("rank");
    }

    /**
     * Retourne la configuration du module.
     *
     * @return int
     */
    public function &getConfigs() {
        return $this->getDataValue("configs");
    }

    /**
     * Retourne la valeur de la clé de configuration du module.
     *
     * @param string $key
     * @param string $defaultValue
     * @return string
     */
    public function &getConfigValue($key, $defaultValue = "") {
        $value = null;

        if ($this->hasValue("configs")) {
            if (isset($this->hasValue("configs")[$key])) {
                $value = $this->getDataValue("configs")[$key];
            }
        }

        if ($value === null || empty($value)) {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * Vérifie que le module est installé.
     *
     * @return boolean
     */
    public function installed() {
        return $this->hasValue("mod_id");
    }

    /**
     * Vérifie si le module est valide (si le module existe).
     *
     * @param string $page
     * @return boolean
     */
    public function isValid($page = "") {
        return Libs_Module::getInstance()->isModule($this->getName(), $page);
    }

    /**
     * Retourne le zone d'échange.
     *
     * @return string
     */
    public function &getZone() {
        $zone = "MODULE";
        return $zone;
    }

}
