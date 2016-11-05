<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur un block.
 *
 * @author Sébastien Villemain
 */
class LibBlockData extends LibEntityData {

    /**
     * Position du block en lettre.
     *
     * @var string
     */
    private $sideName = "";

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
        $this->updateDataValue("mods", explode("|", $this->getStringValue("mods")));
        $this->updateDataValue("title", ExecEntities::textDisplay($this->getStringValue("title")));

        // Affecte la position du block en lettre.
        $this->sideName = LibBlock::getSideAsLetters($this->getSide());
    }

    /**
     * Retourne l'identifiant du block.
     *
     * @return int
     */
    public function &getId() {
        return $this->getIntValue("block_id");
    }

    /**
     * Retourne la position du block en lettre.
     *
     * @return string
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
        return $this->getIntValue("side");
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
     * Retourne le nom complet du template de block à utiliser.
     *
     * @return string
     */
    public function &getTemplateName() {
        $templateName = "block_" . $this->sideName;
        return $templateName;
    }

    /**
     * Retourne le titre du block.
     *
     * @return string
     */
    public function &getTitle() {
        return $this->getStringValue("title");
    }

    /**
     * Retourne le contenu du block.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getContent() {
        return $this->getStringValue("content");
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
        return $this->getIntValue("rank");
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
        $dataValue = ucfirst($this->getStringValue("type"));
        return $dataValue;
    }

    /**
     * Retourne le nom du dossier contenant le block.
     *
     * @return string
     */
    public function getFolderName() {
        return "Block" . $this->getType();
    }

    /**
     * Retourne le nom de classe représentant le block.
     *
     * @return string
     */
    public function getClassName() {
        return "Block" . $this->getType();
    }

    /**
     * Détermine si le block est installé.
     *
     * @return bool
     */
    public function installed() {
        return $this->hasValue("block_id");
    }

    /**
     * Vérifie si le block doit être activé.
     *
     * @param bool $checkModule
     * @return bool true le block doit être actif.
     */
    public function &canActive($checkModule = true) {
        $rslt = false;

        if (CoreAccess::autorize(CoreAccessType::getTypeFromToken($this))) {
            if ($checkModule) {
                if (CoreLoader::isCallable("LibModule")) {
                    foreach ($this->getTargetModules() as $modSelected) {
                        if ($modSelected === "all" || LibModule::isSelected($modSelected)) {
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
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getZone() {
        $zone = "BLOCK";
        return $zone;
    }

}
