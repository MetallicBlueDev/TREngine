<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Management_Setting extends Module_Model {
	public function setting() {
		return "setting!!";
	}
}

?>