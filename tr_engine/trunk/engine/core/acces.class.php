<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

class Core_Acces  {
	
	/**
	 * Memoire cache de module
	 * 
	 * @var array
	 */
	private static $modules = array();
	
	/**
	 * Vérifie si le client courant a accès au module courant
	 * 
	 * @param $mod
	 * @return boolean true accès autorisé
	 */
	public static function mod($mod) {
		Core_Sql::getInstance()->select(
			Core_Table::$MODULES_TABLE,
			array("rang", "configs"),
			array("name = '" . $mod . "'")
		);
		
		if ($sql->AffectedRows > 0) {
			list($rang, $configs) = $sql->fetchArray();
			Core_Main::$moduleConfig = $configs;
			self::$modules[$mod] = $rang;
			
			if ($rang == 3) return self::moderate($mod); // Accès admin avec droit
			else if ($rang == 2 && Core_Session::$userRang > 1) return true; // Accès admin simple
			else if ($rang == 1 && Core_Session::$userRang > 0) return true; // Accès membre
			else if ($rang == 0) return true; // Accès publique
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
		// Si aucun Id précisé, on le prend sur le client courant
		if (!$userIdAdmin) $userIdAdmin = Core_Session::$userId;
		$userIdAdmin = Exec_Entities::secureText($userIdAdmin);
		
		// Rang 3 exigé !
		if (Core_Session::$userRang == 3 && $userIdAdmin != "") {
			$sql = Core_Sql::getInstance();
			
			$sql->select(
				Core_Table::$ADMIN_USERS_TABLE,
				array("rights"),
				array("user_id = '" . $userIdAdmin . "'")
			);
			list($rights) = $sql->fetchArray();
			$adminRows = $sql->affectedRows();
			
			$sql->select(
				Core_Table::$MODULES_TABLE,
				array("mod_id"),
				array("name = '" . $mod . "'")
			);
			list($modId) = $sql->fetchArray();
			$modRows = $sql->affectedRows();
			
			// Si c'est une page bien particulière
			if ($adminRows == 1
					&& $modRows == 0 
					&& is_file(TR_ENGINE_DIR . "/modules/management/" . $mod . ".php")) {
				$modRows++;
			}
			
			// Si les réponses retourné sont correcte
			if ($adminRows == 1 && $modRows == 1) {
				$right = explode("|", $rights);
				$nbRights = count($right);
				
				if ($nbRights > 0) {
					if ($right[0] == "all") {
						// Admin avec droit suprême
						return true;
					} else {
						for ($i = 0; $i <= $nbRights; $i++) {
							if (is_numeric($right[$i]) && is_numeric($modId) && $right[$i] == $modId) {
								return true;
							} else if (!is_numeric($right[$i]) && !is_numeric($modId) && $right[$i] == $modId) {
								if (is_file(TR_ENGINE_DIR . "/modules/management/" . $right[$i] . ".php")) {
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
		// Récuperation du rangs
		$rang = self::getRang($mod);
		
		// Si on veut le type d'erreur pour un acces
		if ($rang == -1) return "ERROR_ACCES_OFF";
		else if ($rang == 1 && Core_Session::$userRang == 0) return "ERROR_ACCES_MEMBER";
		else if ($rang > 1 && Core_Session::$userRang < $rang) return "ERROR_ACCES_ADMIN";
		else return "ERROR_ACCES_FORBIDDEN";
	}
	
	/**
	 * Retourne le rang du module
	 * 
	 * @param $mod String
	 * @return int
	 */
	public static function getRang($mod) {
		if (!self::$modules[$mod]) {
			Core_Sql::getInstance()->select(
				Core_Table::$MODULES_TABLE,
				array("rang"),
				array("name = '" . $mod . "'")
			);
			if ($sql->AffectedRows > 0) {
				list($rang, $configs) = $sql->fetchArray();
				self::$modules[$mod] = $rang;
			}
		}
		return self::$modules[$mod];
	}
}


?>