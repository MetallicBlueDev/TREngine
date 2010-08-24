<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../engine/core/secure.class.php");
	new Core_Secure();
}

// Résolution de la dépendance du block menu
Core_Loader::classLoader("Block_Menu");

/**
 * Block de menu style Menu treeview by 
 * 
 * @author Sebastien Villemain
 *
 */
class Block_Menutree extends Block_Menu {
	
	public function display() {
		$this->configure();
		$menus = $this->getMenu();
		$menus->addAttributs("class", "treeview");
		
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("blockTitle", $this->title);
		$libsMakeStyle->assign("blockContent", $menus->render());
		$libsMakeStyle->display($this->templateName);
	}
	
	private function configure() {
		// Configure le style pour la classe
		$this->content = strtolower($this->content);
		switch($this->content) {
			case "black": 
			case "red":
			case "gray":
			case "famfamfam":
				break;
			default:
				$this->content = "";
		}
		
		Core_Loader::classLoader("Exec_JQuery");
		Exec_JQuery::getTreeView("#block" . $this->blockId);
	}

	public function install() {
	}

	public function uninstall() {
		parent::uninstall();
	}
}


?>