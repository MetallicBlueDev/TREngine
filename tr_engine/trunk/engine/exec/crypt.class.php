<?php

if (!defined("TR_ENGINE_INDEX")) {
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
	public static function cryptData($data, $salt = "", $method = "") {
		// Réglage de la méthode utilisé
		if (!$method) $method= "smd5";
		$method = strtolower($method);
		
		// Préparation du salt
		if (!$salt) $salt = md5(uniqid(rand(), true));
		
		switch($methode) {
			case 'smd5':
				// Si le crypt md5 est activé
				if (defined("CRYPT_MD5") && CRYPT_MD5) {
					return crypt($data,'$1$'. substr($salt, 0, 8).'$');
				}
				// Sinon utilisation du simple md5
				return self::cryptData($data, $salt, "md5");
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
				return self::cryptData($data, $salt);
		}
	}
	
	/**
	 * Encodeur de chaine
	 * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
	 * http://fr2.php.net/manual/fr/function.md5.php 
	 * 
	 * @param $plain_text
	 * @param $password
	 * @param $iv_len
	 * 
	 * @return String
	 */
	public static function md5Encrypt($plain_text, $password, $iv_len = 16) {
		$plain_text .= "\x13";
		$n = strlen($plain_text);
		if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
		
		$i = 0;
		$enc_text = self::getRandIv($iv_len);
		$iv = substr($password ^ $enc_text, 0, 512);
		while ($i < $n) {
			$block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
			$enc_text .= $block;
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return base64_encode($enc_text);
	}
	
	/**
	 * Décodeur de chaine
	 * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
	 * http://fr2.php.net/manual/fr/function.md5.php
	 * 
	 * @param $enc_text
	 * @param $password
	 * @param $iv_len
	 * 
	 * @return String
	 */
	public static function md5Decrypt($enc_text, $password, $iv_len = 16) {
		$enc_text = base64_decode($enc_text);
		$n = strlen($enc_text);
		
		$i = $iv_len;
		$plain_text = '';
		$iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
		while ($i < $n) {
			$block = substr($enc_text, $i, 16);
			$plain_text .= $block ^ pack('H*', md5($iv));
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return preg_replace('/\\x13\\x00*$/', '', $plain_text);
	}
	
	/**
	 * Genere une valeur
	 * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
	 * http://fr2.php.net/manual/fr/function.md5.php
	 * @param $iv_len
	 * @return String
	 */
	private static function getRandIv($iv_len) {
		$iv = '';
		while ($iv_len-- > 0) {
			$iv .= chr(mt_rand() & 0xff);
		}
		return $iv;
	}
}

?>