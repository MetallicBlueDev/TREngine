<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire des accès et autorisation.
 *
 * @author Sébastien Villemain
 */
class Core_Access {

    /**
     * Accès non défini.
     *
     * @var int
     */
    const RANK_NONE = 0;

    /**
     * Accès publique.
     *
     * @var int
     */
    const RANK_PUBLIC = 1;

    /**
     * Accès aux membres.
     *
     * @var int
     */
    const RANK_REGITRED = 2;

    /**
     * Accès aux administrateurs.
     *
     * @var int
     */
    const RANK_ADMIN = 3;

    /**
     * Accès avec droit spécifique.
     *
     * @var int
     */
    const RANK_SPECIFIC_RIGHT = 4;

    /**
     * Liste des rangs valides.
     *
     * @var array array("name" => 0)
     */
    private static $rankRegistred = array(
        "ACCESS_NONE" => self::RANK_NONE,
        "ACCESS_PUBLIC" => self::RANK_PUBLIC,
        "ACCESS_REGISTRED" => self::RANK_REGITRED,
        "ACCESS_ADMIN" => self::RANK_ADMIN,
        "ACCESS_SPECIFIC_RIGHT" => self::RANK_SPECIFIC_RIGHT);

    /**
     * Vérifie si le client a les droits suffisant pour acceder au module
     *
     * @param $zoneIdentifiant string module ou page administrateur (module/page) ou id du block sous forme block + Id
     * @param $userIdAdmin string Id de l'administrateur a vérifier
     * @return boolean true le client visé a la droit
     */
    public static function moderate($zoneIdentifiant, $userIdAdmin = "") {
        if (Core_Session::getInstance()->getUserInfos()->hasAdminRank()) {
            // Recherche des droits administrateur
            $rights = self::getAdminRight($userIdAdmin);
            $nbRights = count($rights);
            $coreAccessType = self::getAccessType($zoneIdentifiant);

            // Si les réponses retourné sont correcte
            if ($nbRights > 0 && $coreAccessType->valid()) {
                // Vérification des droits
                if ($rights[0] === "all") { // Admin avec droit suprême
                    return true;
                } else {
                    // Analyse des droits, un par un
                    for ($i = 0; $i <= $nbRights; $i++) {
                        // Droit courant étudié
                        $currentRight = $rights[$i];

                        // Si c'est un droit de module
                        if ($coreAccessType->isModuleZone()) {
                            if (is_numeric($currentRight) && $coreAccessType->getId() === $currentRight) {
                                return true;
                            }
                        } else { // Si c'est un droit spécial
                            // Affectation des variables pour le droit courant
                            $zoneIdentifiantRight = $currentRight;
                            $zoneRight = "";
                            $identifiantRight = "";

                            // Vérification de la validité du droit
                            if (self::getAccessType($zoneIdentifiantRight, $zoneRight, $identifiantRight)) {
                                // Vérification suivant le type de droit
                                if ($zone === "BLOCK") {
                                    if ($zoneRight === "BLOCK" && is_numeric($identifiantRight)) {
                                        if ($identifiant === $identifiantRight) {
                                            return true;
                                        }
                                    }
                                } else if ($zone === "PAGE") {
                                    if ($zoneRight === "PAGE" && $zoneIdentifiant === $zoneIdentifiantRight && $identifiant === $identifiantRight) {
                                        if (Libs_Module::getInstance()->isModule($zoneIdentifiant, $identifiant)) {
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Retourne l'erreur d'accès liée au jeton.
     *
     * @param Core_AccessToken $token
     * @return string
     */
    public static function &getAccessErrorMessage(Core_AccessToken $token) {
        $error = ERROR_ACCES_FORBIDDEN;

        if ($token->getRank() === -1) {
            $error = ERROR_ACCES_OFF;
        } else {
            $userInfos = Core_Session::getInstance()->getUserInfos();

            if ($token->getRank() === 1 && !$userInfos->hasRegisteredRank()) {
                $error = ERROR_ACCES_MEMBER;
            } else if ($token->getRank() > 1 && $userInfos->getRank() < $token->getRank()) {
                $error = ERROR_ACCES_ADMIN;
            }
        }
        return $error;
    }

    /**
     * Autorise ou refuse l'accès à la ressource cible.
     *
     * @param Core_AccessType $accessType
     * @return boolean
     */
    public static function &autorize(Core_AccessType &$accessType) {
        $rslt = false;

        if ($accessType->valid()) {
            $userInfos = Core_Session::getInstance()->getUserInfos();

            if ($userInfos->getRank() >= $accessType->getRank()) {
                if ($accessType->getRank() === self::RANK_SPECIFIC_RIGHT) {
                    foreach ($userInfos->getRights() as $userAccessType) {
                        if (!$userAccessType->valid()) {
                            continue;
                        }

                        if ($accessType->isAssignableFrom($userAccessType)) {
                            $rslt = true;
                            break;
                        }
                    }
                } else {
                    $rslt = true;
                }
            }
        }
        return $rslt;
    }

    /**
     * Retourne le type d'acces avec la traduction.
     *
     * @param int $rank
     * @return string accès traduit (si possible).
     */
    public static function &getRankAsLitteral($rank) {
        if (!is_numeric($rank)) {
            Core_Secure::getInstance()->throwException("accessRank", null, array(
                "Invalid rank value: " . $rank));
        }

        $rankLitteral = array_search($rank, self::$rankRegistred, true);

        if ($rankLitteral === false) {
            Core_Secure::getInstance()->throwException("accessRank", null, array(
                "Numeric rank: " . $rank));
        }

        $rankLitteral = defined($rankLitteral) ? constant($rankLitteral) : $rankLitteral;
        return $rankLitteral;
    }

    /**
     * Liste des niveaux d'accès disponibles.
     *
     * @return array array("numeric" => identifiant int, "letters" => nom du niveau)
     */
    public static function &getRankList() {
        $rankList = array();

        foreach (self::$rankRegistred as $rank) {
            $rankList[] = array(
                "numeric" => $rank,
                "letters" => self::getRankAsLitteral($rank));
        }
        return $rankList;
    }

}
