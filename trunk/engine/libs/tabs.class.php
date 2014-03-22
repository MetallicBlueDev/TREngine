<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Classe de mise en forme d'onglets
 * 
 * @author Sébastien Villemain
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
		$this->name = $name;
		$this->selected = Core_Request::getString("selectedTab");
		if (self::$firstInstance) {
			Core_Loader::classLoader("Exec_JQuery");
			Exec_JQuery::getIdTabs();
			self::$firstInstance = false;
		}
		if (empty($this->selected) && !Core_Html::getInstance()->isJavascriptEnabled()) {
			$this->selected = $this->name . "idTab0";
		}
	}
	
	/**
	 * Ajouter un onglet et son contenu
	 * 
	 * @param $title String titre de l'onglet
	 * @param $htmlContent String contenu de l'onglet
	 */
	public function addTab($title, $htmlContent) {
		// Id de l'onget courant
		$idTab = $this->name . "idTab" . $this->tabCounter++;
		// Création de l'onget
		$this->tabs .= "<li><a href=\"";
		if (Core_Html::getInstance()->isJavascriptEnabled()) {
			// Une simple balise pour le javascript
			$this->tabs .= "#" . $idTab;
		} else {
			// Un lien complet sans le javascript
			$queryString = Core_Request::getString("QUERY_STRING", "", "SERVER");
			$queryString = str_replace("selectedTab=" . $this->selected, "", $queryString);
			$queryString = (substr($queryString, -1) != "&") ? $queryString . "&" : $queryString;
			$this->tabs .= Core_Html::getLink("index.php?" . $queryString . "selectedTab=" . $idTab);
		}
		$this->tabs .= "\""
		. (($this->selected == $idTab) ? "class=\"selected\"" : "display=\"none;\"") . ">" . Exec_Entities::textDisplay($title) . "</a></li>";
		// Si le javascript est actif ou que nous sommes dans l'onget courant
		if (Core_Html::getInstance()->isJavascriptEnabled() || $this->selected == $idTab) {
			$this->tabsContent .= "<div id=\"" . $idTab . "\">" . $htmlContent . "</div>";
		}
	}
	
	/**
	 * Retourne le rendu du form complet
	 * 
	 * @param $class String
	 * @return String
	 */
	public function &render($class = "") {
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