<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur un block.
 *
 * @author Sébastien Villemain
 */
class Libs_BlockData extends Core_DataStorage implements Core_AccessToken {

    /**
     * Position du block en lettre.
     *
     * @var string
     */
    private $sideName = "";

    /**
     * Les données compilées du block.
     *
     * @var string
     */
    private $buffer = "";

    /**
     * Nouvelle information de block.
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
        $this->updateDataValue("mods", explode("|", $this->getDataValue("mods")));
        $this->updateDataValue("title", Exec_Entities::textDisplay($this->getDataValue("title")));
    }

    /**
     * Retourne les données compilées du block.
     *
     * @return string
     */
    public function &getBuffer() {
        return $this->buffer;
    }

    /**
     * Affecte les données compilées du block.
     *
     * @param string $buffer
     */
    public function setBuffer($buffer) {
        $this->buffer = $buffer;
    }

    /**
     * Retourne l'identifiant du block.
     *
     * @return int
     */
    public function &getId() {
        return $this->getDataValue("block_id");
    }

    /**
     * Retourne la position du block en lettre.
     *
     * @return int
     */
    public function &getName() {
        return $this->getSideName();
    }

    /**
     * Retourne la position du block en chiffre.
     *
     * @return int
     */
    public function &getSide() {
        return $this->getDataValue("side");
    }

    /**
     * Retourne la position du block en lettre.
     *
     * @return string
     */
    public function &getSideName() {
        return $this->sideName;
    }

    /**
     * Affecte la position du block en lettre.
     *
     * @param string $sideName
     */
    public function setSideName($sideName) {
        $this->sideName = $sideName;
    }

    /**
     * Retourne le nom complet du template de block à utiliser.
     *
     * @return string
     */
    public function &getTemplateName() {
        return "block_" . $this->sideName;
    }

    /**
     * Retourne le titre du block.
     *
     * @return string
     */
    public function &getTitle() {
        return $this->getDataValue("title");
    }

    /**
     * Retourne le contenu du block.
     *
     * @return string
     */
    public function &getContent() {
        return $this->getDataValue("content");
    }

    /**
     * Affecte le contenu du block.
     *
     * @param string $content
     */
    public function setContent($content) {
        $this->setDataValue("content", $content);
    }

    /**
     * Retourne le rang pour accèder au block.
     *
     * @return int
     */
    public function &getRank() {
        return $this->getDataValue("rank");
    }

    /**
     * Retourne les modules cibles.
     *
     * @return array
     */
    public function &getTargetModules() {
        $rslt = $this->getDataValue("mods");

        if (empty($rslt)) {
            $rslt = array(
                "all");
        }
        return $rslt;
    }

    /**
     * Retourne le type de block.
     *
     * @return string
     */
    public function &getType() {
        return $this->getDataValue("type");
    }

    /**
     * Vérifie si le block est valide (si le block existe).
     *
     * @return boolean true block valide
     */
    public function isValid() {
        return is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "blocks" . DIRECTORY_SEPARATOR . $this->getType() . ".block.php");
    }

    /**
     * Vérifie si le block doit être activé.
     *
     * @param boolean $checkModule
     * @return boolean true le block doit être actif.
     */
    public function &canActive($checkModule = true) {
        $rslt = false;

        if (Core_Access::autorize(Core_AccessType::getTypeFromToken($this))) {
            if ($checkModule) {
                if (Core_Loader::isCallable("Libs_Module")) {
                    foreach ($this->getTargetModules() as $modSelected) {
                        if ($modSelected === "all" || Libs_Module::isSelected($modSelected)) {
                            $rslt = true;
                            break;
                        }
                    }
                }
            } else {
                $rslt = true;
            }
        }
        return $rslt;
    }

    /**
     * Retourne le zone d'échange.
     *
     * @return string
     */
    public function &getZone() {
        $zone = "BLOCK";
        return $zone;
    }

}
