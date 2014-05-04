<?php
if (!defined("TR_ENGINE_INDEX")) {
	require(".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
	Core_Secure::checkInstance();
}

class Module_Management_User extends Libs_ModuleModel {
	public function setting() {
		return "setting!!";
	}
}

?>