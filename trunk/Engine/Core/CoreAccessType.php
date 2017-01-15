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
    const FULL_ACCESS = "all";

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
     * Retourne l'accès depuis la table d'information.
     *
     * @param array $rights
     * @return CoreAccessType
     */
    public static function &getTypeFromDatas(array &$rights): CoreAccessType {
        $newAccess = new CoreAccessType($rights);
        return $newAccess;
    }

    /**
     * Retourne l'accès spécifique suivant la zone.
     *
     * @param CoreAccessToken $data
     * @return CoreAccessType
     */
    public static function &getTypeFromToken(CoreAccessToken $data): CoreAccessType {
        $infos = array(
            "zone" => $data->getZone(),
            "rank" => $data->getRank(),
            "identifiant" => $data->getId(),
            "name" => $data->getName());
        return self::getTypeFromDatas($infos);
    }

    /**
     * Retourne l'accès typé administrateur.
     *
     * @return CoreAccessType
     */
    public static function &getTypeFromAdmin(): CoreAccessType {
        if (!isset(self::$cache[self::FULL_ACCESS])) {
            $infos = array(
                "zone" => self::FULL_ACCESS,
                "rank" => self::FULL_ACCESS,
                "identifiant" => self::FULL_ACCESS);
            self::$cache[self::FULL_ACCESS] = self::getTypeFromDatas($infos);
        }
        return self::$cache[self::FULL_ACCESS];
    }

    /**
     * Retourne le type de compte requit pour cet accès.
     *
     * @return int
     */
    public function &getRank(): int {
        $rank = isset($this->rights['rank']) ? (int) $this->rights['rank'] : CoreAccess::RANK_NONE;
        return $rank;
    }

    /**
     * Nom du type d'accès (par exemple BLOCK/MODULE).
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getZone(): string {
        return $this->rights['zone'];
    }

    /**
     * Formule de la page (exemple le nom d'un module).
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getPage(): string {
        return $this->rights['page'];
    }

    /**
     * Change la formule de la page.
     *
     * @param string $newPage
     */
    public function &setPage(string $newPage) {
        $this->rights['page'] = $newPage;
        unset($this->rights['validity']);
    }

    /**
     * Identifiant du type d'accès (par exemple 01 ou une page précise).
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getId(): string {
        return $this->rights['identifiant'];
    }

    /**
     * Nom du type d'accès (par exemple home pour un module).
     *
     * @return string
     */
    public function &getName(): string {
        return $this->rights['name'];
    }

    /**
     * Détermine si c'est un accès à un module.
     *
     * @return bool
     */
    public function isModuleZone(): bool {
        return ($this->getZone() === "MODULE");
    }

    /**
     * Détermine si c'est un accès à un block.
     *
     * @return bool
     */
    public function isBlockZone(): bool {
        return ($this->getZone() === "BLOCK");
    }

    /**
     * Détermine si c'est un accès personnalisé.
     *
     * @return bool
     */
    public function isCustomZone(): bool {
        return !empty($this->getZone()) && !$this->isBlockZone() && !$this->isModuleZone();
    }

    /**
     * Détermine si c'est un accès à une page spécifique.
     *
     * @return bool
     */
    public function hasPageAccess(): bool {
        return !empty($this->getPage());
    }

    /**
     * Détermine si le type d'accès est valide.
     *
     * @return bool
     */
    public function &valid(): bool {
        if (!$this->alreadyChecked()) {
            $this->checkValidity();
        }
        return $this->rights['validity'];
    }

    /**
     * Détermine si accès sont semblables.
     *
     * @param CoreAccessType $otherAccessType
     * @return bool
     */
    public function isAssignableFrom(CoreAccessType $otherAccessType): bool {
        $rslt = false;

        if ($otherAccessType->getZone() === $this->getZone() || $otherAccessType->getZone() === self::FULL_ACCESS) {
            if ($otherAccessType->getId() === $this->getId() || $otherAccessType->getId() === self::FULL_ACCESS) {
                if (empty($this->getPage()) || ($this->getPage() === $otherAccessType->getPage() || $otherAccessType->getPage() === self::FULL_ACCESS)) {
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

        if (!empty($this->getZone()) && !empty($this->getId())) {
            if ($this->isModuleZone()) {
                $valid = $this->canOpenModule();
            } else if ($this->isBlockZone()) {
                $valid = $this->canOpenBlock();
            } else {
                $valid = true;
            }
        }

        $this->rights['validity'] = $valid;
    }

    /**
     * Détermine si il est possible d'ouvrir le module.
     *
     * @return bool
     */
    private function &canOpenModule(): bool {
        $valid = false;

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
        return $valid;
    }

    /**
     * Détermine si il est possible d'ouvrir le block.
     *
     * @return bool
     */
    private function &canOpenBlock(): bool {
        $valid = false;
        $blockInfo = null;

        // Recherche d'information sur le block
        if (CoreLoader::isCallable("LibBlock")) {
            $blockInfo = LibBlock::getInstance()->getInfoBlock($this->getId());
        }

        if ($blockInfo !== null && is_numeric($blockInfo->getId())) {
            $this->rights['page'] = $blockInfo->getType();
            $this->rights['identifiant'] = $blockInfo->getId();
            $valid = true;
        }
        return $valid;
    }

    /**
     * Détermine si l'accès a été vérifié.
     *
     * @return bool
     */
    private function alreadyChecked(): bool {
        return isset($this->rights['validity']);
    }

}
