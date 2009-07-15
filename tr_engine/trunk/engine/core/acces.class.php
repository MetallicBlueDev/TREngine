<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

class Core_Acces  {
	
	/**
	 * V�rifie si le client courant a acc�s au module courant
	 * 
	 * @param $mod String nom du module
	 * @return boolean true acc�s autoris�
	 */
	public static function mod($mod) {//return true; // TODO supprimer ceci
		// Recherche des infos du module
		$moduleInfo = Libs_Module::getInstance()->getInfoModule();
		
		if ($moduleInfo != false) {	
			if ($moduleInfo['rang'] == 3) return self::moderate($mod); // Acc�s admin avec droit
			else if ($moduleInfo['rang'] == 2 && Core_Session::$userRang > 1) return true; // Acc�s admin simple
			else if ($moduleInfo['rang'] == 1 && Core_Session::$userRang > 0) return true; // Acc�s membre
			else if ($moduleInfo['rang'] == 0) return true; // Acc�s publique
		}
		return false; // Aucun acc�s
	}
	
	/**
	 * V�rifie si le client a les droits suffisant pour acceder au module
	 * 
	 * @param $mod String module ou page administrateur
	 * @param $userIdAdmin String Id de l'administrateur a v�rifier
	 * @return boolean true le client vis� a la droit
	 */
	public static function moderate($mod, $userIdAdmin = "") {
		// Rang 3 exig� !
		if (Core_Session::$userRang == 3) {
			// Recherche des droits admin
			$right = Core_Session::getInstance()->getAdminRight($userIdAdmin);
			
			// Recherche des infos du module
			$moduleInfo = Libs_Module::getInstance()->getInfoModule();
			
			// Si les r�ponses retourn� sont correcte
			if (($right != false && count($right) > 0)
					&& (isset($moduleInfo['modId']) || Libs_Module::getInstance()->isModule("management", $mod))) {
				$nbRights = count($right);
				
				if ($nbRights > 0) {
					if ($right[0] == "all") {
						// Admin avec droit supr�me
						return true;
					} else {
						for ($i = 0; $i <= $nbRights; $i++) {
							if (is_numeric($right[$i]) && is_numeric($moduleInfo['modId']) && $right[$i] == $moduleInfo['modId']) {
								return true;
							} else if (!is_numeric($right[$i]) && !is_numeric($moduleInfo['modId']) && $right[$i] == $moduleInfo['modId']) {
								if (Libs_Module::getInstance()->isModule("management", $right[$i])) {
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
	public static function getError($mod) {
		// Recherche des infos du module
		$moduleInfo = Libs_Module::getInstance()->getInfoModule();
		
		// Si on veut le type d'erreur pour un acces
		if ($moduleInfo['rang'] == -1) return "ERROR_ACCES_OFF";
		else if ($moduleInfo['rang'] == 1 && Core_Session::$userRang == 0) return "ERROR_ACCES_MEMBER";
		else if ($moduleInfo['rang'] > 1 && Core_Session::$userRang < $rang) return "ERROR_ACCES_ADMIN";
		else return "ERROR_ACCES_FORBIDDEN";
	}
}


?>