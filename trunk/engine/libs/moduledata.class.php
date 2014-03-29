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
     * @var String
     */
    private $moduleName = "";

    /**
     * Tableau d'information le module.
     *
     * @var array
     */
    private $data = array();

    /**
     * Nouvelle information de module.
     * 
     * @param String $moduleName
     * @param array $data
     */
    public function __construct($moduleName, array $data) {
        $this->moduleName = $moduleName;

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }
        $this->data = $data;
    }

    /**
     * Retourne le nom du module.
     *
     * @return String Le nom du module.
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
     * @param String $key
     * @param String $defaultValue
     * @return String
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

}
