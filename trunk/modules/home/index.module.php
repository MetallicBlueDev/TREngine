<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Home_Index extends Module_Model {
	
	public function display() {
		echo "Contenu du module home, bienvenue !";
	}
	
	public function setting() {
		return "home setting!!";
	}
}


?>