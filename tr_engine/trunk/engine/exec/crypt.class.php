<?php

if (!defined("ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Outil de cryptage
 * 
 * @author Sébastien Villemain
 *
 */
class Exec_Crypt {
	
	/**
	 * Creation d'un ID unique
	 * 
	 * @param $taille int
	 * @return String
	 */
	public static function creatId($taille = 32) {
		$randKey = "";
		$lettres = "abcdefghijklmnopqrstuvwxyz";
		$chiffres = "0123456789";
		
		srand(time());
		
		for ($i = 0; $i < $taille; $i++) {
			$lettres = (rand(0, 1) == 1) ? strtoupper($lettres) : strtolower($lettres);
			$randKey .= substr($lettres.$chiffres, (rand() % (strlen($lettres.$chiffres))), 1);
		}
		return $randKey;
	}
	
	/**
	 * Crypteur de donnée
	 * 
	 * @param $data String
	 * @param $method méthode de cryptage
	 * @return String
	 */
	public static function cryptData($data, $method = "") {
		// Réglage de la méthode utilisé
		if (!$method) $method= "smd5";
		$method = strtolower($method);
		
		// Préparation du salt
		$salt = md5(uniqid(rand(), true));
		
		switch($methode) {
			case 'smd5':
				// Si le crypt md5 est activé
				if (defined("CRYPT_MD5") && CRYPT_MD5) {
					return crypt($data,'$1$'. substr($salt, 0, 8).'$');
				}
				return false;
			case 'md5':
				return md5($data);
			case 'crypt':
				return crypt($data, substr($salt, 0, 2));
			case 'sha1':
				return sha1($data);
			case 'ssha':
				$salt = substr($salt, 0, 4);
				return '{SSHA}' . base64_encode(pack("H*", sha1($data . $salt)) . $salt);
			case 'my411':
				return '*'.sha1(pack("H*", sha1($data)));
			default:
				Core_Exception::setException("Unsupported crypt method. Method : " . $method);
				return self::cryptData($data, "md5");
		}
	}
}

?>