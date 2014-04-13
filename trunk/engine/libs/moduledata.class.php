<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Information de base sur un module.
 *
 * @author Sébastien Villemain
 */
class Libs_ModuleData {

    /**
     * Nom du module courant.
     *
     * @var string
     */
    private $moduleName = "";

    /**
     * Tableau d'information du module.
     *
     * @var array
     */
    private $data = array();

    /**
     * Les données compilées du module.
     *
     * @var string
     */
    private $buffer = "";

    /**
     * Nouvelle information de module.
     *
     * @param string $moduleName
     * @param array $data
     */
    public function __construct($moduleName, array &$data) {
        $this->moduleName = $moduleName;

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        $this->data = $data;
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
        return $this->moduleName;
    }

    /**
     * Retourne l'identifiant du module.
     *
     * @return int
     */
    public function &getId() {
        return $this->data['mod_id'];
    }

    /**
     * Retourne le rang du module.
     *
     * @return int
     */
    public function &getRank() {
        return $this->data['rank'];
    }

    /**
     * Retourne la configuration du module.
     *
     * @return int
     */
    public function &getConfigs() {
        return $this->data['configs'];
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

        if (isset($this->data['configs'])) {
            if (isset($this->data['configs'][$key])) {
                $value = $this->data['configs'][$key];
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
        return isset($this->data['mod_id']);
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

}
