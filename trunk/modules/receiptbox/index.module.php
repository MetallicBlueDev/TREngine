<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Receiptbox_Index extends Libs_ModuleModel {
	
	public function display() {
		echo "Bienvenue sur la messagerie !";
	}
}


?>