<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire d'acc�s
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Acces  {
	
	/**
	 * V�rifie si le client a les droits suffisant pour acceder au module
	 * 
	 * @param $mod String module ou page administrateur ou id du block sous forme block + Id
	 * @param $userIdAdmin String Id de l'administrateur a v�rifier
	 * @return boolean true le client vis� a la droit
	 */
	public static function moderate($zoneIdentifiant, $userIdAdmin = "") {
		// Rang 3 exig� !
		if (Core_Session::$userRang == 3) {
			// Recherche des droits admin
			$right = self::getAdminRight($userIdAdmin);
			
			if (substr($zoneIdentifiant, 0, 5) == "block") {
				$zone = "BLOCK";
				$identifiant = substr($zoneIdentifiant, 5, strlen($zoneIdentifiant));
			} else if (($pathPos = strrpos($zoneIdentifiant, "/")) !== false) {
				$module = substr($zoneIdentifiant, 0, $pathPos);
				$page = substr($zoneIdentifiant, $pathPos, strlen($zoneIdentifiant));
				
				if (Core_Loader::isCallable("Libs_Module") && Libs_Module::getInstance()->isModule($module, $page)) {
					$zone = "PAGE";
					$identifiant = $zoneIdentifiant;
				}
			} else {
				// Recherche des infos du module
				$zone = "MODULE";
				if (Core_Loader::isCallable("Libs_Module")) {
					$moduleInfo = Libs_Module::getInstance()->getInfoModule($zoneIdentifiant);
				} else {
					$moduleInfo = false;
				}
				$identifiant = $moduleInfo['mod_id'];
			}
			
			// Si les r�ponses retourn� sont correcte
			if (($right != false && count($right) > 0)
					&& (is_numeric($identifiant) || $zone == "PAGE")) {
				$nbRights = count($right);
				
				if ($nbRights > 0) {
					if ($right[0] == "all") {
						// Admin avec droit supr�me
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
	 * Retourne l'erreur d'acces li�e au module
	 * 
	 * @param $mod
	 * @return String
	 */
	public static function getModuleAccesError($mod) {
		// Recherche des infos du module
		if (Core_Loader::isCallable("Libs_Module")) {
			$moduleInfo = Libs_Module::getInstance()->getInfoModule();
		} else {
			$moduleInfo = false;
		}
		
		// Si on veut le type d'erreur pour un acces
		if ($moduleInfo['rang'] == -1) return ERROR_ACCES_OFF;
		else if ($moduleInfo['rang'] == 1 && Core_Session::$userRang == 0) return ERROR_ACCES_MEMBER;
		else if ($moduleInfo['rang'] > 1 && Core_Session::$userRang < $rang) return ERROR_ACCES_ADMIN;
		else return ERROR_ACCES_FORBIDDEN;
	}
	
	/**
	 * Autorise ou refuse l'acc�s a la ressource cible
	 * 
	 * @param $zoneIdentifiant String block+Id ou module/page.php ou module
	 * @param $zoneRang int
	 * @return boolean true acc�s autoris�
	 */
	public static function autorize($zoneIdentifiant = "", $zoneRang = "") {
		// Si ce n'est pas un block ou une page particuliere
		if (substr($zoneIdentifiant, 0, 5) != "block" && empty($zoneRang)) {
			// Recherche des infos du module
			if (Core_Loader::isCallable("Libs_Module")) {
				$moduleInfo = Libs_Module::getInstance()->getInfoModule($zoneIdentifiant);
			} else {
				$moduleInfo = false;
			}
			
			if ($moduleInfo != false) {
				$zoneIdentifiant = Libs_Module::$module;
				$zoneRang = $moduleInfo['rang'];
			} else {
				$zoneRang = false;
			}
		}
		
		if ($zoneRang !== false) {
			if ($zoneRang == 0) return true; // Acc�s public
			else if ($zoneRang > 0 && $zoneRang < 3 && Core_Session::$userRang >= $zoneRang) return true; // Acc�s membre ou admin
			else if ($zoneRang == 3 && self::moderate($zoneIdentifiant)) return true; // Acc�s admin avec droits
		}
		return false;
	}
	
	/**
	 * Retourne les droits de l'admin cibl�
	 * 
	 * @param $userIdAdmin String userId
	 * @return mixed array liste des droits ou false
	 */
	public static function getAdminRight($userIdAdmin = "") {
		if (!empty($userIdAdmin)) $userIdAdmin = Exec_Entities::secureText($userIdAdmin);
		else $userIdAdmin = Core_Session::$userId;
		
		Core_Sql::select(
			Core_Table::$ADMIN_USERS_TABLE,
			array("rights"),
			array("user_id = '" . $userIdAdmin . "'")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			$admin = Core_Sql::fetchArray();
			return explode("|", $admin['rights']);
		}
		return false;
	}
}


?>