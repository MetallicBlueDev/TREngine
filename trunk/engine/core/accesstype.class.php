<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Représente un type d'accès à une zone spécifique.
 *
 * @author Sébastien Villemain
 */
class Core_AccessType implements Core_AccessToken {

    /**
     * Accès passe partout.
     *
     * @var string
     */
    const MAGIC_ACCESS = "all";

    /**
     * Données complètes des droits.
     *
     * @var array
     */
    private $rights = array();

    /**
     * Nouveau type d'accès à vérifier.
     *
     * @param array $rights
     */
    private function __construct(array &$rights) {
        $this->rights = $rights;
    }

    /**
     * Retourne l'accès spécifique.
     *
     * @param array $rights
     * @return Core_AccessType
     */
    public static function &getTypeFromDatabase(array &$rights) {
        $newAccess = new Core_AccessType($rights);
        return $newAccess;
    }

    /**
     * Retourne l'accès spécifique suivant la zone.
     *
     * @param Core_AccessToken $data
     * @return Core_AccessType
     */
    public static function &getTypeFromExchange(Core_AccessToken $data) {
        $infos = array(
            "zone" => $data->getZone(),
            "rank" => $data->getRank(),
            "identifiant" => $data->getId());
        return self::getTypeFromDatabase($infos);
    }

    /**
     * Retourne le type de compte requit pour cet accès.
     *
     * @return int
     */
    public function &getRank() {
        $rank = isset($this->rights['rank']) ? $this->rights['rank'] : Core_Access::RANK_NONE;
        return $rank;
    }

    /**
     * Nom du type d'accès (par exemple BLOCK/MODULE).
     *
     * @return string
     */
    public function &getZone() {
        return $this->rights['zone'];
    }

    /**
     * Formule de la page (exemple le nom d'un module).
     *
     * @return string
     */
    public function &getPage() {
        return $this->rights['page'];
    }

    /**
     * Identifiant du type d'accès (par exemple 01 ou une page précise).
     *
     * @return string
     */
    public function &getId() {
        return $this->rights['identifiant'];
    }

    /**
     * Détermine si c'est un accès à un module.
     *
     * @return boolean
     */
    public function isModuleZone() {
        return ($this->getZone() === "MODULE");
    }

    /**
     * Détermine si c'est un accès à un block.
     *
     * @return boolean
     */
    public function isBlockZone() {
        return ($this->getZone() === "BLOCK");
    }

    /**
     * Détermine si c'est un accès personnalisé.
     *
     * @return boolean
     */
    public function isCustomZone() {
        return !empty($this->getZone()) && !$this->isBlockZone() && !$this->isModuleZone();
    }

    /**
     * Détermine si c'est un accès à une page spécifique.
     *
     * @return boolean
     */
    public function hasPageAccess() {
        return !empty($this->getPage());
    }

    /**
     * Détermine si le type d'accès est valide.
     *
     * @return boolean
     */
    public function &valid() {
        if (!$this->alreadyChecked()) {
            $this->checkValidity();
        }
        return $this->rights['validity'];
    }

    /**
     * Détermine si accès sont semblables.
     *
     * @param Core_AccessType $otherAccessType
     * @return boolean
     */
    public function isAssignableFrom(Core_AccessType $otherAccessType) {
        $rslt = false;

        if ($otherAccessType->getZone() === $this->getZone() || $otherAccessType->getZone() === self::MAGIC_ACCESS) {
            if ($otherAccessType->getId() === $this->getId() || $otherAccessType->getId() === self::MAGIC_ACCESS) {
                if (empty($this->getPage()) || ($this->getPage() === $otherAccessType->getPage() || $otherAccessType->getPage() === self::MAGIC_ACCESS)) {
                    $rslt = true;
                }
            }
        }
        return $rslt;
    }

    /**
     * Routine de vérification du type d'accès.
     */
    private function checkValidity() {
        $valid = false;

        if (!empty($this->getZone())) {
            if (!empty($this->getId())) {
                if ($this->isModuleZone()) {
                    if ($this->hasPageAccess()) {
                        if (Core_Loader::isCallable("Libs_Module") && Libs_Module::getInstance()->isModule($this->getPage(), $this->getId())) {
                            $valid = true;
                        }
                    } else {
                        // Recherche d'informations sur le module
                        $moduleInfo = null;

                        if (Core_Loader::isCallable("Libs_Module")) {
                            $moduleInfo = Libs_Module::getInstance()->getInfoModule($this->getId());
                        }

                        if ($moduleInfo !== null && is_numeric($moduleInfo->getId())) {
                            $this->rights['page'] = $moduleInfo->getName();
                            $this->rights['identifiant'] = $moduleInfo->getId();
                            $valid = true;
                        }
                    }
                } else if ($this->isBlockZone()) {
                    // TODO Recherche d'information sur le block
                    $valid = true;
                } else {
                    $valid = true;
                }
            }
        }

        $this->rights['validity'] = $valid;
    }

    /**
     * Détermine si l'accès a été vérifié.
     *
     * @return boolean
     */
    private function alreadyChecked() {
        return isset($this->rights['validity']);
    }

}
