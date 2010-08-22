<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Home_Index extends Module_Model {
	
	public function display() {
		echo "Bonjour, bienvenue sur <a href=\"index.php\">Trancer-Studio.net</a>.<br />";
	}
	
	public function setting() {
		return "Pas de setting...";
	}
}


?>