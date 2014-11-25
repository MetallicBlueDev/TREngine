<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibBlock;
use TREngine\Engine\Lib\LibModule;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Représente un type d'accès à une zone spécifique.
 *
 * @author Sébastien Villemain
 */
class CoreAccessType implements CoreAccessToken {

    /**
     * Accès passe partout.
     *
     * @var string
     */
    const MAGIC_ACCESS = "all";

    /**
     * Cache d'accès typé.
     *
     * @var CoreAccessType[]
     */
    private static $cache = array();

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
     * @return CoreAccessType
     */
    public static function &getTypeFromDatabase(array &$rights) {
        $newAccess = new CoreAccessType($rights);
        return $newAccess;
    }

    /**
     * Retourne l'accès spécifique suivant la zone.
     *
     * @param CoreAccessToken $data
     * @return CoreAccessType
     */
    public static function &getTypeFromToken(CoreAccessToken $data) {
        $infos = array(
            "zone" => $data->getZone(),
            "rank" => $data->getRank(),
            "identifiant" => $data->getId(),
            "name" => $data->getName());
        return self::getTypeFromDatabase($infos);
    }

    /**
     * Retourne l'accès typé administrateur.
     *
     * @return CoreAccessType
     */
    public static function &getTypeFromAdmin() {
        if (!isset(self::$cache[self::MAGIC_ACCESS])) {
            $infos = array(
                "zone" => self::MAGIC_ACCESS,
                "rank" => self::MAGIC_ACCESS,
                "identifiant" => self::MAGIC_ACCESS);
            self::$cache[self::MAGIC_ACCESS] = self::getTypeFromDatabase($infos);
        }
        return self::$cache[self::MAGIC_ACCESS];
    }

    /**
     * Retourne le type de compte requit pour cet accès.
     *
     * @return int
     */
    public function &getRank() {
        $rank = isset($this->rights['rank']) ? (int) $this->rights['rank'] : CoreAccess::RANK_NONE;
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
     * Change la formule de la page.
     *
     * @param string $newPage
     */
    public function &setPage($newPage) {
        $this->rights['page'] = $newPage;
        unset($this->rights['validity']);
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
     * Name du type d'accès (par exemple home pour un module).
     *
     * @return string
     */
    public function &getName() {
        return $this->rights['name'];
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
     * @param CoreAccessType $otherAccessType
     * @return boolean
     */
    public function isAssignableFrom(CoreAccessType $otherAccessType) {
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
                        if (CoreLoader::isCallable("LibModule") && LibModule::getInstance()->isModule($this->getPage(), $this->getId())) {
                            $valid = true;
                        }
                    } else {
                        // Recherche d'informations sur le module
                        $moduleInfo = null;

                        if (CoreLoader::isCallable("LibModule")) {
                            $moduleInfo = LibModule::getInstance()->getInfoModule($this->getName());
                        }

                        if ($moduleInfo !== null && is_numeric($moduleInfo->getId())) {
                            $this->rights['page'] = $moduleInfo->getName();
                            $this->rights['identifiant'] = $moduleInfo->getId();
                            $valid = true;
                        }
                    }
                } else if ($this->isBlockZone()) {
                    // Recherche d'information sur le block
                    $blockInfo = null;

                    if (CoreLoader::isCallable("LibBlock")) {
                        $blockInfo = LibBlock::getInstance()->getInfoBlock($this->getId());
                    }

                    if ($blockInfo !== null && is_numeric($blockInfo->getId())) {
                        $this->rights['page'] = $blockInfo->getType();
                        $this->rights['identifiant'] = $blockInfo->getId();
                        $valid = true;
                    }
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
