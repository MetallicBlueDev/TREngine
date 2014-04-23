<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de bannissement du moteur
 *
 * @author Sébastien Villemain
 */
class Core_BlackBan {

    /**
     * Cherche si le client est bannis
     *
     * @return boolean true le client est bannis
     */
    public static function isBlackUser() {
        return ((!empty(Core_Session::getInstance()->userIpBan)) ? true : false);
    }

    /**
     * Affichage de l'isoloire BLACKBAN
     */
    public static function displayBlackPage() {
        Core_Sql::getInstance()->select(
        Core_Table::BANNED_TABLE, array(
            "reason"), array(
            "ip = '" . Core_Session::getInstance()->userIpBan . "'")
        );

        if (Core_Sql::getInstance()->affectedRows() > 0) {
            $mail = (!empty(Core_Main::$coreConfig['defaultAdministratorMail'])) ? Core_Main::$coreConfig['defaultAdministratorMail'] : TR_ENGINE_MAIL;
            $name = (!empty(Core_Main::$coreConfig['defaultSiteName'])) ? Core_Main::$coreConfig['defaultSiteName'] : "";

            $mail = Exec_Mailer::protectedDisplay($mail, $name);

            $libsMakeStyle = new Libs_MakeStyle();
            $libsMakeStyle->assign("mail", $mail);
            $libsMakeStyle->assign("reason", Exec_Entities::textDisplay($reason));
            $libsMakeStyle->assign("ip", Core_Session::getInstance()->userIpBan);
            $libsMakeStyle->display("banishment");
        }
    }

    public static function checkBlackBan() {
        self::checkOldBlackBan();
        self::checkBan();
    }

    /**
     * Nettoyage des adresses IP périmées de la base de données
     */
    private static function checkOldBlackBan() {
        $deleteOldBlackBan = false;

        Core_CacheBuffer::setSectionName("tmp");
        // Vérification du fichier cache
        if (!Core_CacheBuffer::cached("deleteOldBlackBan.txt")) {
            $deleteOldBlackBan = true;
            Core_CacheBuffer::writingCache("deleteOldBlackBan.txt", "1");
        } else if ((time() - 2 * 24 * 60 * 60) < Core_CacheBuffer::cacheMTime("deleteOldBlackBan.txt")) {
            $deleteOldBlackBan = false;
            Core_CacheBuffer::touchCache("deleteOldBlackBan.txt");
        }

        if ($deleteOldBlackBan) {
            // Suppression des bannissements par ip trop vieux / 2 jours
            Core_Sql::getInstance()->delete(
            Core_Table::BANNED_TABLE, array(
                "ip != ''",
                "&& (name = 'Hacker' || name = '')",
                "&& type = '0'",
                "&& DATE_ADD(date, INTERVAL 2 DAY) > CURDATE()"
            )
            );
        }
    }

    /**
     * Vérification des bannissements
     */
    private static function checkBan() {
        $userIp = Exec_Agent::$userIp;
        // Recherche de bannissement de session
        if (!empty(Core_Session::getInstance()->userIpBan)) {
            // Si l'ip n'est plus du tout valide
            if (Core_Session::getInstance()->userIpBan != $userIp && !preg_match("/" . Core_Session::getInstance()->userIpBan . "/", $userIp)) {
                // On verifie qu'il est bien dans la base (au cas ou il y aurait un débannissement)
                Core_Sql::getInstance()->select(
                Core_Table::BANNED_TABLE, array(
                    "ban_id"), array(
                    "ip = '" . Core_Session::getInstance()->userIpBan . "'")
                );

                // Il est fiché, on s'occupe bien de lui
                if (Core_Sql::getInstance()->affectedRows() > 0) {
                    // Extrait l'id du bannissement
                    list($banId) = Core_Sql::getInstance()->fetchArray();

                    // Mise à jour de l'ip
                    Core_Sql::getInstance()->update(
                    Core_Table::BANNED_TABLE, array(
                        "ip" => $userIp), array(
                        "ban_id = '" . $banId . "'")
                    );
                    Core_Session::getInstance()->userIpBan = $userIp;
                } else {
                    Core_Session::getInstance()->cancelIpBan();
                }
            }
        } else {
            // Sinon on recherche dans la base les bannis; leurs ip et leurs pseudo
            Core_Sql::getInstance()->select(
            Core_Table::BANNED_TABLE, array(
                "ip",
                "name"), array(), array(
                "ban_id")
            );

            while (list($blackBanIp, $blackBanName) = Core_Sql::getInstance()->fetchArray()) {
                $banIp = explode(".", $blackBanIp);

                // Filtre pour la vérification
                if (isset($banIp[3]) && !empty($banIp[3])) {
                    $banList = $blackBanIp;
                    $searchIp = $userIp;
                } else {
                    $banList = $banIp[0] . $banIp[1] . $banIp[2];
                    $uIp = explode(".", $userIp);
                    $searchIp = $uIp[0] . $uIp[1] . $uIp[2];
                }

                // Vérification du client
                if ($searchIp == $banList) {
                    // IP bannis !
                    Core_Session::getInstance()->userIpBan = $blackBanIp;
                } else if (!empty(Core_Session::getInstance()->userName) && Core_Session::getInstance()->userName = $blackBanName) {
                    // Pseudo bannis !
                    Core_Session::getInstance()->userIpBan = $blackBanIp;
                } else {
                    Core_Session::getInstance()->userIpBan = "";
                }

                // La vérification a déjà aboutie, on arrête
                if (!empty(Core_Session::getInstance()->userIpBan))
                    break;
            }
        }
    }

}

?>