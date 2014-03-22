<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Constructeur de fichier
 *
 * @author Sébastien Villemain
 *
 */
class Exec_JQuery {

	public static function getTreeView($identifier) {
		$coreHtml = Core_Html::getInstance();
		$coreHtml->addCssInculdeFile("jquery.treeview.css");
		$coreHtml->addJavascriptFile("jquery.treeview.js");
		$coreHtml->addJavascriptJquery("$('" . $identifier . "').treeview({animated: 'fast', collapsed: true, unique: true, persist: 'location'});");
	}

	public static function getJdMenu() {
		$coreHtml = Core_Html::getInstance();
		// Ajout du fichier de style
		$coreHtml->addCssInculdeFile("jquery.jdMenu.css");
		
		// Ajout des fichier javascript
		$coreHtml->addJavascriptFile("jquery.dimensions.js");
		$coreHtml->addJavascriptFile("jquery.positionBy.js");
		$coreHtml->addJavascriptFile("jquery.bgiframe.js");
		$coreHtml->addJavascriptFile("jquery.jdMenu.js");

		// Ajout du code d'execution
		$coreHtml->addJavascriptJquery("$('ul.jd_menu').jdMenu();");
	}

	public static function getIdTabs() {
		$coreHtml = Core_Html::getInstance();
		$coreHtml->addJavascriptFile("jquery.idTabs.js");
		$coreHtml->addCssInculdeFile("jquery.idTabs.css");
	}

	public static function getSlimbox() {
		$coreHtml = Core_Html::getInstance();
		$coreHtml->addJavascriptFile("jquery.slimbox.js");
		$coreHtml->addCssInculdeFile("jquery.slimbox.css");
	}
}
?>
