<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreDataStorage;
use TREngine\Engine\Core\CoreAccessToken;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur un module.
 *
 * @author Sébastien Villemain
 */
class LibModuleData extends CoreDataStorage implements CoreAccessToken {

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
        $this->page = $page;
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
        return $this->getDataValue("name");
    }

    /**
     * Retourne le nom de classe.
     *
     * @return string
     */
    public function &getClassName() {
        return "Module" . ucfirst($this->getPage());
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
     * @return boolean
     */
    public function isValid() {
        return is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "Modules" . DIRECTORY_SEPARATOR . ucfirst($this->getName()) . DIRECTORY_SEPARATOR . "Module" . $this->getPage() . ".php");
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
