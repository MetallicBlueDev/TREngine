<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de email
 * 
 * @author S�bastien Villemain
 *
 */
class Exec_Mailer {
	
	/**
	 * V�rifie la validit� du mail
	 * 
	 * @param $address adresse email a v�rifier
	 * @return boolean true l'adresse email est valide
	 */
	public static function validMail($address) {
		return (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? true : false;
	}
}
?>