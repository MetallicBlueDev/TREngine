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
	private static $breadcrumb = false;
	
	/**
	 * Le fil d'Ariane
	 * 
	 * @var array
	 */
	private $breadcrumbTrail = array();
	
	public function __construct() {
		
	}
	
	/**
	 * Retoune et/ou crée l'instance Libs_Breadcrumb
	 * 
	 * @return Libs_Breadcrumb
	 */
	public static function &getInstance() {
		if (!$breadcrumb) {
			self::$breadcrumb = new self();
		}
		return self::$breadcrumb;
	}
	
	/**
	 * Ajoute un tracé au fil d'Ariane
	 * 
	 * @param $trail array or String
	 */
	public function addTrail($trail) {
		if (is_array($trail)) {
			foreach ($trail as $value) {
				if ($value != "") {
					$this->breadcrumbTrail[] = $value;
				}
			}
		} else {
			$this->breadcrumbTrail[] = $trail;
		}
	}
	
	/**
	 * Retourne le fil d'Ariane complet
	 * 
	 * @param $separator String séparateur de tracé
	 * @return String
	 */
	public function getBreadcrumbTrail($separator = " >> ") {
		$rslt = "";
		foreach($this->breadcrumbTrail as $trail) {
			if ($rslt != "") $rslt .= $separator . $trail;
			else $rslt .= $trail;
		}
		return $rslt;
	}
}


?>