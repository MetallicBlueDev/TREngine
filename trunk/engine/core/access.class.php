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
     * @param boolean $forceSpecificRank
     * @return boolean
     */
    public static function &autorize(Core_AccessType &$accessType, $forceSpecificRank = false) {
        $rslt = false;

        if ($accessType->valid()) {
            $userInfos = Core_Session::getInstance()->getUserInfos();

            if ($userInfos->getRank() >= $accessType->getRank()) {
                if ($accessType->getRank() === self::RANK_SPECIFIC_RIGHT || $forceSpecificRank) {
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
