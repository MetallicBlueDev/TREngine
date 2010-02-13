<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire d'accès
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Acces  {
	
	/**
	 * Vérifie si le client a les droits suffisant pour acceder au module
	 * 
	 * @param $zoneIdentifiant String module ou page administrateur ou id du block sous forme block + Id
	 * @param $userIdAdmin String Id de l'administrateur a vérifier
	 * @return boolean true le client visé a la droit
	 */
	public static function moderate($zoneIdentifiant, $userIdAdmin = "") {
		// Rang 3 exigé !
		if (Core_Session::$userRang == 3) {
			// Recherche des droits admin
			$right = self::getAdminRight($userIdAdmin);
			
			// Si les réponses retourné sont correcte
			if (count($right) > 0 && self::accessType($zoneIdentifiant, $zone, $identifiant)) {
				$nbRights = count($right);
				
				if ($nbRights > 0) {
					if ($right[0] == "all") {
						// Admin avec droit suprême
						return true;
					} else {
						for ($i = 0; $i <= $nbRights; $i++) {
							$currentRight = $right[$i];
							
							if ($zone == "MODULE") {
								if (is_numeric($currentRight) && $identifiant == $currentRight) {
									return true;
								}
							} else if ($zone == "BLOCK" && substr($currentRight, 0, 5) == "block") {
								$currentRightId = substr($currentRight, 5, strlen($currentRight));
								if (is_numeric($currentRightId) && $identifiant == $currentRightId) {
									return true;
								}
							} else if ($zone == "PAGE" && $currentRight == $identifiant) {
								if (Libs_Module::getInstance()->isModule("management", $currentRight)) {
									return true;
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
	 * @return String
	 */
	public static function &getModuleAccesError($mod) {
		// Recherche des infos du module
		$moduleInfo = array();
		if (Core_Loader::isCallable("Libs_Module")) {
			$moduleInfo = Libs_Module::getInstance()->getInfoModule();
		}
		
		$error = ERROR_ACCES_FORBIDDEN;
		// Si on veut le type d'erreur pour un acces
		if (!empty($moduleInfo)) {
			if ($moduleInfo['rang'] == -1) $error = ERROR_ACCES_OFF;
			else if ($moduleInfo['rang'] == 1 && Core_Session::$userRang == 0) $error = ERROR_ACCES_MEMBER;
			else if ($moduleInfo['rang'] > 1 && Core_Session::$userRang < $rang) $error = ERROR_ACCES_ADMIN;
		}
		return $error;
	}
	
	/**
	 * Autorise ou refuse l'accès a la ressource cible
	 * 
	 * @param $zoneIdentifiant String block+Id ou module/page.php ou module
	 * @param $zoneRang int
	 * @return boolean true accès autorisé
	 */
	public static function &autorize($zoneIdentifiant = "", $zoneRang = 0) {
		$access = false;
		// Si ce n'est pas un block ou une page particuliere
		if (substr($zoneIdentifiant, 0, 5) != "block" && empty($zoneRang)) {
			// Recherche des infos du module
			$moduleInfo = array();
			if (Core_Loader::isCallable("Libs_Module")) {
				$moduleInfo = Libs_Module::getInstance()->getInfoModule($zoneIdentifiant);
			}
			
			if (!empty($moduleInfo)) {
				$zoneIdentifiant = Libs_Module::$module;
				$zoneRang = $moduleInfo['rang'];
			} else {
				return $access; // Aucun information = aucun acces
			}
		}
		
		if ($zoneRang !== false) {
			if ($zoneRang == 0) $access = true; // Accès public
			else if ($zoneRang > 0 && $zoneRang < 3 && Core_Session::$userRang >= $zoneRang) $access = true; // Accès membre ou admin
			else if ($zoneRang == 3 && self::moderate($zoneIdentifiant)) $access = true; // Accès admin avec droits
		}
		return $access;
	}
	
	/**
	 * Retourne les droits de l'admin ciblé
	 * 
	 * @param $userIdAdmin String userId
	 * @return array liste des droits
	 */
	public static function &getAdminRight($userIdAdmin = "") {
		if (!empty($userIdAdmin)) $userIdAdmin = Exec_Entities::secureText($userIdAdmin);
		else $userIdAdmin = Core_Session::$userId;
		
		Core_Sql::select(
			Core_Table::$USERS_ADMIN_TABLE,
			array("rights"),
			array("user_id = '" . $userIdAdmin . "'")
		);
		
		$rights = array();
		if (Core_Sql::affectedRows() > 0) {
			$admin = Core_Sql::fetchArray();
			$rights = explode("|", $admin['rights']);
		}
		return $rights;
	}
	
	/**
	 * Identifie le type d'acces lié a l'identifiant entré
	 * 
	 * @param $zoneIdentifiant String module ou page administrateur ou id du block sous forme block + Id 
	 * @param $zone String la zone type trouvée (BLOCK/PAGE/MODULE)
	 * @param $identifiant String l'identifiant lié au type trouvé
	 * @return boolean true identifiant valide
	 */
	public static function &accessType(&$zoneIdentifiant, &$zone, &$identifiant) {
		$access = false;
		if (substr($zoneIdentifiant, 0, 5) == "block") {
			$zone = "BLOCK";
			$identifiant = substr($zoneIdentifiant, 5, strlen($zoneIdentifiant));
			$access = true;
		} else if (($pathPos = strrpos($zoneIdentifiant, "/")) !== false) {
			$module = substr($zoneIdentifiant, 0, $pathPos);
			$page = substr($zoneIdentifiant, $pathPos, strlen($zoneIdentifiant));
			
			if (Core_Loader::isCallable("Libs_Module") && Libs_Module::getInstance()->isModule($module, $page)) {
				$zone = "PAGE";
				$identifiant = $zoneIdentifiant;
				$access = true;
			}
		} else if (!is_numeric($zoneIdentifiant)) {
			// Recherche d'informations sur le module
			$moduleInfo = array();
			if (Core_Loader::isCallable("Libs_Module")) {
				$moduleInfo = Libs_Module::getInstance()->getInfoModule($zoneIdentifiant);
			}
			
			if (!empty($moduleInfo)) {
				if (is_numeric($moduleInfo['mod_id'])) {
					$zone = "MODULE";
					$identifiant = $moduleInfo['mod_id'];
					$access = true;
				}
			}
		}
		return $access;
	}
}

?>