<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../engine/core/secure.class.php");
	new Core_Secure();
}

// Résolution de la dépendance du block menu
Core_Loader::classLoader("Block_Menu");

/**
 * Block de menu style jdMenu by Jonathan Sharp
 * 
 * @author Sebastien Villemain
 *
 */
class Block_Menujd extends Block_Menu {
	
	public function display() {
		$this->configure();
		$menus = $this->getMenu();
		$menus->addAttributs("class", "jd_menu" . (($this->side == 1 || $this->side == 2) ? " jd_menu_vertical" : ""));
		
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("blockTitle", $this->title);
		$libsMakeStyle->assign("blockContent", $menus->render());
		$libsMakeStyle->display($this->templateName);
	}
	
	private function configure() {		
		// Ajout du fichier de style
		Core_Html::getInstance()->addCssFile("jquery.jdMenu.css");
		// Ajout des fichier javascript
		Core_Html::getInstance()->addJavascriptFile("jquery.dimensions.js");
		Core_Html::getInstance()->addJavascriptFile("jquery.positionBy.js");
		Core_Html::getInstance()->addJavascriptFile("jquery.bgiframe.js");
		Core_Html::getInstance()->addJavascriptFile("jquery.jdMenu.js");
		
		// Ajout du code d'excution
		Core_Html::getInstance()->addJavascriptJquery("$('ul.jd_menu').jdMenu();");
	}
}


?>