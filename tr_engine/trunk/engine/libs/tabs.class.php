<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Classe de mise en forme d'onglets
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Tabs {
	
	/**
	 * Vérifie si c'est la 1ère instance
	 * 
	 * @var boolean
	 */
	private static $firstInstance = true;
	
	/**
	 * Nom du groupe d'onglets
	 * 
	 * @var String
	 */
	private $name = "";
	
	/**
	 * Groupe d'onglets (HTML)
	 * 
	 * @var String
	 */
	private $tabs = "";
	
	/**
	 * Groupe de contenu des onglets (HTML)
	 * @var unknown_type
	 */
	private $tabsContent = "";
	
	/**
	 * Id de l'onglet selectionné
	 * 
	 * @var String
	 */
	private $selected = "";
	
	/**
	 * Compteur d'onglet
	 * 
	 * @var int
	 */
	private $tabCounter = 0;
	
	/**
	 * Création d'un nouveau groupe d'onglet
	 * 
	 * @param $name String Nom du groupe d'onglet
	 */
	public function __construct($name) {
		if (self::$firstInstance) {
			Core_Html::getInstance()->addJavascriptFile("jquery.idTabs.js");
			Core_Html::getInstance()->addCssFile("jquery.idTabs.css");
			Core_Html::getInstance()->addJavascript("$().idTabs();");
			self::$firstInstance = false;
		}
		$this->selected = Core_Request::getString("selectedTab");
		$this->name = $name;
	}
	
	/**
	 * Ajouter un onglet et son contenu
	 * 
	 * @param $title String titre de l'onglet
	 * @param $htmlContent String contenu de l'onglet
	 */
	public function addTab($title, $htmlContent) {$this->selected = "idTab0";
		$idTab = "idTab" . $this->tabCounter++;
		$this->tabs .= "<li><a href=\"#" . $idTab . "\""
		. (($this->selected == $idTab) ? "class=\"selected\"" : "") . ">" . Exec_Entities::textDisplay($title) . "</a></li>";
		$this->tabsContent .= "<div id=\"" . $idTab . "\">" . $htmlContent . "</div>";
	}
	
	/**
	 * Retourne le rendu du form complet
	 * 
	 * @param $class String
	 * @return String
	 */
	public function render($class = "") {
		$content = "<div id=\"" . $this->name . "\""
		. " class=\"" . ((!empty($class)) ? $class : "tabs") . "\">"
		. "<ul class=\"idTabs\">"
		. $this->tabs
		. "</ul>"
		. $this->tabsContent
		. "</div>";
		return $content;
	}
}


?>