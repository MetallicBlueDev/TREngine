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
    private static $types = array();

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
                            if (is_numeric($currentRight) && $coreAccessType->getIdentifiant() === $currentRight) {
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
     * Retourne l'erreur d'acces liée au module
     *
     * @param $mod
     * @return string
     */
    public static function &getModuleAccesError($mod) {
        // Recherche des infos du module
        $moduleRank = -2;
        if (Core_Loader::isCallable("Libs_Module")) {
            $moduleRank = Libs_Module::getInstance()->getInfoModule($mod)->getRank();
        }

        $error = ERROR_ACCES_FORBIDDEN;
        // Si on veut le type d'erreur pour un acces
        if ($moduleRank > -2) {
            if ($moduleRank === -1)
                $error = ERROR_ACCES_OFF;
            else if ($moduleRank === 1 && Core_Session::getInstance()->userRank === 0)
                $error = ERROR_ACCES_MEMBER;
            else if ($moduleRank > 1 && Core_Session::getInstance()->userRank < $rank)
                $error = ERROR_ACCES_ADMIN;
        }
        return $error;
    }

    /**
     * Autorise ou refuse l'accès a la ressource cible
     *
     * @param $zoneIdentifiant string block+Id ou module/page.php ou module
     * @param $zoneRank int
     * @return boolean true accès autorisé
     */
    public static function &autorize($zoneIdentifiant, $zoneRank = -2) {
        $access = false;
        // Si ce n'est pas un block ou une page particuliere
        if (substr($zoneIdentifiant, 0, 5) !== "block" && $zoneRank < -1) {
            // Recherche des infos du module
            $moduleInfo = array();
            if (Core_Loader::isCallable("Libs_Module")) {
                $moduleInfo = Libs_Module::getInstance()->getInfoModule($zoneIdentifiant);
            }

            if (!empty($moduleInfo)) {
                $zoneIdentifiant = $moduleInfo['name'];
                $zoneRank = $moduleInfo['rank'];
            }
        }

        if ($zoneRank === 0)
            $access = true; // Accès public
        else if ($zoneRank > 0 && $zoneRank < 3 && Core_Session::getInstance()->userRank >= $zoneRank)
            $access = true; // Accès membre ou admin
        else if ($zoneRank === 3 && self::moderate($zoneIdentifiant))
            $access = true; // Accès admin avec droits
        return $access;
    }

    /**
     * Retourne les droits de l'admin ciblé
     *
     * @param $userIdAdmin string userId
     * @return array liste des droits
     */
    public static function &getAdminRight($userIdAdmin = "") {
        if (!empty($userIdAdmin))
            $userIdAdmin = Exec_Entities::secureText($userIdAdmin);
        else
            $userIdAdmin = Core_Session::getInstance()->getUserInfos()->getId();

        $admin = array();
        $admin = Core_Sql::getInstance()->getBuffer("getAdminRight");
        if (empty($admin)) { // Si la requête n'est pas en cache
            Core_Sql::getInstance()->select(
            Core_Table::USERS_ADMIN_TABLE, array(
                "rights"), array(
                "user_id = '" . $userIdAdmin . "'")
            );

            if (Core_Sql::getInstance()->affectedRows() > 0) {
                Core_Sql::getInstance()->addArrayBuffer("getAdminRight");
                $admin = Core_Sql::getInstance()->getBuffer("getAdminRight");
            }
        }

        $rights = array();
        if (!empty($admin)) {
            $rights = explode("|", $admin[0]['rights']);
        }
        return $rights;
    }

    /**
     * Retourne l'identification du type d'accès.
     *
     * @param string $zoneIdentifiant valeur du type d'accès.
     * @return Core_AccessType
     */
    public static function &getAccessType($zoneIdentifiant) {
        if (!isset(self::$types[$zoneIdentifiant])) {
            self::$types[$zoneIdentifiant] = new Core_AccessType($zoneIdentifiant);
        }
        return self::$types[$zoneIdentifiant];
    }

    public static function &autorize(Core_AccessType $accessType) {
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

        for ($i = self::RANK_NONE; $i <= self::RANK_SPECIFIC_RIGHT; $i++) {
            $rankList[] = array(
                "numeric" => $i,
                "letters" => self::getRankAsLitteral($i));
        }
        return $rankList;
    }

}
