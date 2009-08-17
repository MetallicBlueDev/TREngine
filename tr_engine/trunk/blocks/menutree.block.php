<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../engine/core/secure.class.php");
	new Core_Secure();
}

// Résolution de la dépendance du block menu
Core_Loader::blockLoader("menu");

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
		
		Core_Html::getInstance()->addCssFile("jquery.treeview.css");
		Core_Html::getInstance()->addJavaScriptFile("jquery.treeview.js");
		Core_Html::getInstance()->addJavaScriptJquery(
"$('#block" . $this->blockId . "').treeview({
	animated: 'fast',
	collapsed: true,
	unique: true,
	persist: 'location',
	toggle: function() {
		window.console && console.log('%o was toggled', this);
	}
});"
		);
		
	}
}


?>