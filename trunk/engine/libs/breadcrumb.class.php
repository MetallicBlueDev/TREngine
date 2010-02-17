<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Editeur du fil d'Ariane
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Breadcrumb {
	
	/**
	 * Instance de la classe
	 * 
	 * @var Libs_Breadcrumb
	 */
	private static $breadcrumb = null;
	
	/**
	 * Le fil d'Ariane
	 * 
	 * @var array
	 */
	private $breadcrumbTrail = array();
	
	public function __construct() {
		// Ajoute la page principal
		$this->addTrail(Core_Main::$coreConfig['defaultSiteName'], "index.php");
	}
	
	/**
	 * Retoune et/ou crée l'instance Libs_Breadcrumb
	 * 
	 * @return Libs_Breadcrumb
	 */
	public static function &getInstance() {
		if (self::$breadcrumb == null) {
			self::$breadcrumb = new self();
		}
		return self::$breadcrumb;
	}
	
	/**
	 * Ajoute un tracé au fil d'Ariane
	 * 
	 * @param $trail String
	 * @param $link String
	 */
	public function addTrail($trail, $link = "") {
		if (!empty($trail)) {
			$constant = "TRAIL_" . strtoupper($trail);
			if (defined($constant)) $trail = constant($constant);
			if (!empty($link)) {
				$trail = "<a href=\"" . $link . "\">" . $trail . "</a>";
			}
			$this->breadcrumbTrail[] = $trail;
		}
	}
	
	/**
	 * Retourne le fil d'Ariane complet
	 * 
	 * @param $separator String séparateur de tracé
	 * @return String
	 */
	public function &getBreadcrumbTrail($separator = " >> ") {
		$rslt = "";
		foreach($this->breadcrumbTrail as $trail) {
			if (!empty($rslt)) $rslt .= $separator;
			$rslt .= $trail;
		}
		return $rslt;
	}
}


?>