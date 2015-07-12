<?php

namespace TREngine\Engine\Lib;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur un module.
 *
 * @author Sébastien Villemain
 */
class LibModuleData extends LibEntityData {

    /**
     * La page sélectionnée.
     *
     * @var string
     */
    private $page = null;

    /**
     * La sous page.
     *
     * @var string
     */
    private $view = null;

    /**
     * Nouvelle information de module.
     *
     * @param array $data
     */
    public function __construct(array &$data, $initializeConfig = false) {
        parent::__construct();

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        if ($initializeConfig) {
            $rawConfigs = null;

            if (isset($data['configs'])) {
                $rawConfigs = $data['configs'];
            }
            $data['configs'] = self::getModuleConfigs($rawConfigs);
        }

        $this->newStorage($data);
    }

    /**
     * Retourne la page sélectionnée.
     *
     * @return string
     */
    public function &getPage() {
        return $this->page;
    }

    /**
     * Affecte la page sélectionnée.
     *
     * @param string $page
     */
    public function setPage($page) {
        $this->page = ucfirst($page);
    }

    /**
     * Retourne la sous-page sélectionnée.
     *
     * @return string
     */
    public function &getView() {
        return $this->view;
    }

    /**
     * Affecte la sous-page sélectionnée.
     *
     * @param string $view
     */
    public function setView($view) {
        $this->view = $view;
    }

    /**
     * Retourne le nom du module.
     *
     * @return string Le nom du module.
     */
    public function &getName() {
        $dataValue = ucfirst($this->getDataValue("name"));
        return $dataValue;
    }

    /**
     * Retourne le nom du dossier contenant le module.
     *
     * @return string
     */
    public function getFolderName() {
        return "Module" . $this->getName();
    }

    /**
     * Retourne le nom de classe représentant le module.
     *
     * @return string
     */
    public function getClassName() {
        return "Module" . $this->getPage();
    }

    /**
     * Retourne l'identifiant du module.
     *
     * @return int
     */
    public function &getId() {
        return $this->getIntValue("mod_id");
    }

    /**
     * Retourne le rang du module.
     *
     * @return int
     */
    public function &getRank() {
        return $this->getIntValue("rank");
    }

    /**
     * Retourne la configuration du module.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
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
     * Détermine si le module est installé.
     *
     * @return boolean
     */
    public function installed() {
        return $this->hasValue("mod_id");
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

    /**
     * Retourne le jeu de configuration du module.
     *
     * @param string $moduleConfigs
     * @return array
     */
    private static function getModuleConfigs(&$moduleConfigs) {
        $moduleConfigs = explode("|", $moduleConfigs);

        foreach ($moduleConfigs as $config) {
            if (!empty($config)) {
                $values = explode("=", $config);

                if (count($values) > 1) {
                    // Chaine encodé avec urlencode
                    $moduleConfigs[$values[0]] = urldecode($values[1]);
                }
            }
        }

        if ($moduleConfigs === null) {
            $moduleConfigs = array();
        }
        return $moduleConfigs;
    }

}
