<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de bannissement du moteur
 * 
 * @author Sebastien Villemain
 *
 */
class Core_BlackBan {
	
	/**
	 * Cherche si le client est bannis
	 * 
	 * @return boolean true le client est bannis
	 */
	public static function isBlackUser() {
		return ((Core_Session::$userIpBan != "") ? true : false);
	}
	
	/**
	 * Affichage de l'isoloire BLACKBAN
	 */
	public static function displayBlackPage() {		
		Core_Sql::select(
			Core_Table::$BANNED_TABLE,
			array("reason"),
			array("ip = '" . Core_Session::$userIpBan . "'")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			$mail = (Core_Main::$coreConfig['defaultAdministratorMail'] != "") ? Core_Main::$coreConfig['defaultAdministratorMail'] : TR_ENGINE_MAIL;
			$name = (Core_Main::$coreConfig['defaultSiteName'] != "") ? Core_Main::$coreConfig['defaultSiteName'] : "";
			
			Core_Loader::classLoader("Exec_Mailer");
			$mail = Exec_Mailer::protectedDisplay($mail, $name);
			
			$libsMakeStyle = new Libs_MakeStyle();
			$libsMakeStyle->assign("mail", $mail);
			$libsMakeStyle->assign("name", $name);
			$libsMakeStyle->assign("reason", Exec_Entities::textDisplay($reason));
			$libsMakeStyle->assign("slogan", (Core_Main::$coreConfig['defaultSiteSlogan'] != "") ? Core_Main::$coreConfig['defaultSiteSlogan'] : "");
			$libsMakeStyle->display("blackban.tpl");
		}
		
	}
	
	/**
	 * Reset du statue de bannis
	 */
	private static function deleteBlackIp() {
		Core_Session::getInstance()->deleteUserIpBan();
	}
	
	public static function checkBlackBan() {
		self::checkOldBlackBan();
		self::checkBan();
	}
	
	/**
	 * Nettoyage des adresses IP périmées de la base de donnée
	 */
	private static function checkOldBlackBan() {
		$deleteOldBlackBan = false;
		
		Core_CacheBuffer::setSectionName("tmp");		
		// Vérification du fichier cache
		if (!Core_CacheBuffer::cached("deleteOldBlackBan.txt")) {
			$deleteOldBlackBan = true;
			Core_CacheBuffer::writingCache("deleteOldBlackBan.txt", "1");
		} else if ((time() - 2*24*60*60) < Core_CacheBuffer::cacheMTime("deleteOldBlackBan.txt")) {
			$deleteOldBlackBan = true;
			Core_CacheBuffer::touchCache("deleteOldBlackBan.txt");
		}
		
		if ($deleteOldBlackBan) {
			// Suppression des bannissements par ip trop vieux / 2 jours 
			Core_Sql::delete(
				Core_Table::$BANNED_TABLE,
				array("ip != ''", 
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
		
		if (Core_Session::$userIpBan != "") {
			// Si l'ip n'est plus du tout valide
			if (Core_Session::$userIpBan != $userIp 
					&& !preg_match("/" . Core_Session::$userIpBan . "/", $userIp)) {
				// On verifie qu'il est bien dans la base (au cas ou il y aurait un débannissement)
				Core_Sql::select(
					Core_Table::$BANNED_TABLE,
					array("ban_id"),
					array("ip = '" . Core_Session::$userIpBan . "'")
				);
				
				// Il est fiché, on s'occupe bien de lui
				if (Core_Sql::affectedRows() > 0) {
					// Extrait l'id du bannissement
					list($banId) = Core_Sql::fetchArray();
					
					// Mise à jour de l'ip
					Core_Sql::update(
						Core_Table::$BANNED_TABLE,
						array("ip = '" . $userIp . "'"),
						array("ban_id = '" . $banId . "'")
					);
					Core_Session::$userIpBan = $userIp;
				} else {
					self::deleteBlackIp();
				}
			}
		} else {
			// Sinon on recherche dans la base les bannis l'ip et le pseudo
			Core_Sql::select(
				Core_Table::$BANNED_TABLE,
				array("ip", "name"),
				array(),
				array("ban_id")					
			);
			
			while (list($blackBanIp, $blackBanName) = Core_Sql::fetchArray()) {
				$banIp = explode(".", $blackBanIp);
				
				// Filtre pour la vérification
				if (isset($banIp[3]) && $banIp[3] != "") {
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
					Core_Session::$userIpBan = $blackBanIp;
				} else if (Core_Session::$userName != "" && Core_Session::$userName = $blackBanName) {
					// Pseudo bannis !
					Core_Session::$userIpBan = $blackBanIp;
				} else {
					Core_Session::$userIpBan = "";
				} 
				
				// La vérification a déjà aboutie, on arrête
				if (Core_Session::$userIpBan != "") break;
			}
		}
	}
}

?>