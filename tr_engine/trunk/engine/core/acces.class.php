<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

class Core_Acces  {
	
	/**
	 * Vérifie si le client courant a accès au module courant
	 * 
	 * @param $mod String nom du module
	 * @return boolean true accès autorisé
	 */
	public static function mod($mod) {//return true; // TODO supprimer ceci
		// Recherche des infos du module
		$moduleInfo = Libs_Module::getInstance()->getInfoModule();
		
		if ($moduleInfo != false) {	
			if ($moduleInfo['rang'] == 3) return self::moderate($mod); // Accès admin avec droit
			else if ($moduleInfo['rang'] == 2 && Core_Session::$userRang > 1) return true; // Accès admin simple
			else if ($moduleInfo['rang'] == 1 && Core_Session::$userRang > 0) return true; // Accès membre
			else if ($moduleInfo['rang'] == 0) return true; // Accès publique
		}
		return false; // Aucun accès
	}
	
	/**
	 * Vérifie si le client a les droits suffisant pour acceder au module
	 * 
	 * @param $mod String module ou page administrateur
	 * @param $userIdAdmin String Id de l'administrateur a vérifier
	 * @return boolean true le client visé a la droit
	 */
	public static function moderate($mod, $userIdAdmin = "") {
		// Rang 3 exigé !
		if (Core_Session::$userRang == 3) {
			// Recherche des droits admin
			$right = Core_Session::getInstance()->getAdminRight($userIdAdmin);
			
			// Recherche des infos du module
			$moduleInfo = Libs_Module::getInstance()->getInfoModule();
			
			// Si les réponses retourné sont correcte
			if (($right != false && count($right) > 0)
					&& (isset($moduleInfo['modId']) || Libs_Module::getInstance()->isModule("management", $mod))) {
				$nbRights = count($right);
				
				if ($nbRights > 0) {
					if ($right[0] == "all") {
						// Admin avec droit suprême
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
	 * Retourne l'erreur d'acces liée au module
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