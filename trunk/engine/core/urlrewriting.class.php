<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire URL REWRITING
 * 
 * @author Sébastien Villemain
 *
 */
class Core_UrlRewriting {
	
	/**
	 * Vérifie si l'url rewriting a été activé
	 * 
	 * @return boolean true c'est activé
	 */
	private static function &isActived() {
		if (Core_Main::doUrlRewriting() && self::testPassed()) {
			return true;
		}
		return false;
	}
	
	/**
	 * Vérifie si la classe peut être activé
	 * Alias de isActived()
	 * 
	 * @return boolean
	 */
	public static function &test() {
		return self::isActived();
	}
	
	/**
	 * Vérifie si les tests ont été passés avec succès
	 * 
	 * @return boolean
	 */
	private static function &testPassed() {
		// TODO vérifie si fichier tmp de test est OK
		// si pas OK et pas de fichier tmp pour signaler la désactivation
		// on tente de mettre urlRewriting a 0 puis on créé le fichier tmp de désactivation
		// si déjà fichier tmp de décastivation ajouter erreur dans Core_Exception
		return false;
	}
	
	public static function &rewriteLink($link) {
		return $link;
	}
	
	public static function &rewriteBuffer($buffer) {
		return $buffer;
	}
}
?>