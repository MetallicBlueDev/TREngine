<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire des exceptions
 * 
 * @author Sébastien Villemain
 *
 */
class Core_Exception {
	
	/**
	 * Tableau contenant toutes les exceptions internes rencontrées
	 * Destiné au developpeur
	 * 
	 * @var array
	 */
	private static $exception = array();
	
	/**
	 * Tableau contenant toutes les erreurs mineurs rencontrées
	 * Destiné au client
	 * 
	 * @var array
	 */
	private static $minorError = array();
	
	/**
	 * Activer l'écrire dans une fichier log
	 * 
	 * @var boolean
	 */
	private static $writeLog = true;
	
	/**
	 * Activer ou désactiver le rapport d'erreur dans un log
	 * 
	 * @param boolean $active
	 */
	public static function setWriteLog($active) {
		if ($active) self::$writeLog = true;
		else self::$writeLog = false;
	}
	
	/**
	 * Ajoute une nouvelle exception
	 * 
	 * @param $msg
	 */
	public static function setException($msg) {
		$msg = strtolower($msg);
		$msg[0] = strtoupper($msg[0]);
		self::$exception[] = date('Y-m-d H:i:s') . " : " . $msg . ".";
	}
	
	/**
	 * Ajoute une nouvelle erreur mineur
	 * 
	 * @param $msg
	 */
	public static function setMinorError($msg) {
		self::$minorError[] = $msg;
	}
	
	/**
	 * Retourne le tableau d'erreur mineur
	 * 
	 * @return array string
	 */
	public static function getMinorError() {
		return self::$minorError;
	}
	
	/**
	 * Retourne le tableau d'exception
	 * 
	 * @return array string
	 */
	public static function getException() {
		return self::$exception;
	}
	
	/**
	 * Vérifie si une exception est détecté
	 * 
	 * @return boolean
	 */
	public static function exceptionDetected() {
		return (is_array(self::$exception) && count(self::$exception) > 0);
	}
	
	/**
	 * Vérifie si une erreur mineur est détecté
	 * 
	 * @return boolean
	 */
	public static function minorErrorDetected() {
		return (is_array(self::$minorError) && count(self::$minorError) > 0);
	}
	
	/**
	 * Capture les exceptions et les retournes en chaine de caractère
	 * 
	 * @param $var array
	 * @return String
	 */
	private static function linearize($var) {
		$content = "";
		foreach ($var as $msg) {
			$content .= $content . $msg . "\n";
		}
		return $content;
	}
	
	/**
	 * Affichage des exceptions courante
	 */
	public static function displayException() {
		$error = "";
		$color = "green";
		// Vérification de la présence des exceptions
		if (self::exceptionDetected()) {
			$color = "red";
			$nbStars = 45;
			for ($i = 0; $i < $nbStars; $i++) {
				if ($i == (int)($nbStars / 2)) {
					$nbException = count(self::$exception);
					$add = ($nbException > 1) ? "S" : "";
					$error .= $nbException . " DIFFERENT" . $add . " EXCEPTION" . $add;
				} else {
					$error .= "*";
				}
			}
			$exceptions = str_replace("\n", "<br />", self::linearize(self::$exception));
			$error .= "<br />" . $exceptions;
		} else {
			$error .= "No exception detected.";
		}
		
		echo "<div style=\"color: " . $color . ";\"><br />" . $error . "</div>\n"
		. "<div style=\"color: blue;\"><br />BenchMaker :<br />\n"
		. "Core : " . Exec_Marker::getTime("core")
		. "<br />Launcher : " . Exec_Marker::getTime("launcher")
		. "<br />All : " . Exec_Marker::getTime("all")
		. "</div>";
	}
	
	/**
	 * Ecriture du rapport dans un fichier log
	 */
	public static function logException() {
		if (self::$writeLog && self::exceptionDetected()) {			
			// Positionne dans le cache
			Core_CacheBuffer::setSectionName("log");
			// Ecriture a la suite du cache
			Core_CacheBuffer::writingCache("exception_" . date('Y-m-d') . ".log.php", self::linearize(self::$exception), false);
		}
	}
}
?>