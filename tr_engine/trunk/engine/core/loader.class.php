<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Chargeur de classe
 * Charge la classe si cela n'a pas déjà été fais
 * 
 * @author Sébastien Villemain
 *
 */
class Core_Loader {
	
	/**
	 * Tableau des classes chargées
	 */ 
	private static $loaded = array();
	
	/**
	 * Chargeur de classe
	 * 
	 * @param $class Nom de la classe
	 */
	public static function classLoader($class) {
		try {
			self::load($class, "class");
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}		
	}
	
	/**
	 * Chargeur de fichier include
	 * 
	 * @param $include Nom de l'include
	 */
	public static function includeLoader($include) {
		try {
			self::load($include, "inc");
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Chargeur de block
	 * 
	 * @param $block Nom ou type du block
	 */
	public static function blockLoader($block) {
		try {
			self::load($block, "block");
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Chargeur de module
	 * 
	 * @param $module Nom du module
	 */
	public static function moduleLoader($module) {
		try {
			self::load($module, "mod");
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Chargeur de fichier
	 * 
	 * @param $name Nom de la classe/ du fichier
	 * @param $ext Extension
	 */
	private static function load($name, $ext) {
		// Si ce n'est pas déjà chargé
		if (!self::isLoaded($name)) {
			// Retrouve le chemin via le nom
			$path = str_replace("_", "/", $name);
			
			// Repertoire principal
			if ($ext == "block") {
				$directory = "blocks";
			} else if ($ext == "mod") {
				$directory = "modules";
			} else {
				$directory = "engine";
			}
			
			// Chemin finale
			$path = TR_ENGINE_DIR . "/" . $directory . "/" . strtolower($path) . "." . $ext . ".php";
			
			if (is_file($path)) {
				require($path);
				self::$loaded[$name] = 1;
			} else { 
				throw new Exception("Loader");
			}
		}
	}
	
	/**
	 * Vérifie si le fichier demandé a été chargé
	 * 
	 * @param $name fichier demandé
	 * @return boolean true si c'est déjà chargé
	 */
	private static function isLoaded($name) {
		if (isset(self::$loaded[$name])) return true;
		else return false;
	}
}
?>