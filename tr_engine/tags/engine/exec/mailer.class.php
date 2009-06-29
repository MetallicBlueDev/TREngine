<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de email
 * 
 * @author Sébastien Villemain
 *
 */
class Exec_Mailer {
	
	/**
	 * Vérifie la validité du mail
	 * 
	 * @param $address adresse email a vérifier
	 * @return boolean true l'adresse email est valide
	 */
	public static function validMail($address) {
		return (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? true : false;
	}
}
?>