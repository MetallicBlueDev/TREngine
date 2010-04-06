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
	 * Tableau contenant toutes les erreurs mineurs de type warning / alerte rencontrées
	 * Destiné au client
	 * 
	 * @var array
	 */
	private static $alertError = array();
	
	/**
	 * Tableau contenant toutes les erreurs mineurs de type informative rencontrées
	 * Destiné au client
	 * 
	 * @var array
	 */
	private static $noteError = array();
	
	/**
	 * Tableau contenant toutes les erreurs de type informative rencontrées
	 * Destiné au client
	 * 
	 * @var array
	 */
	private static $infoError = array();
	
	/**
	 * Activer l'écrire dans une fichier log
	 * 
	 * @var boolean
	 */
	private static $writeLog = true;
	
	/**
	 * Tableau contenant toutes les lignes sql envoyées
	 * 
	 * @var array
	 */
	private static $sqlRequest = array();
	
	/**
	 * Activer ou désactiver le rapport d'erreur dans un log
	 * 
	 * @param boolean $active
	 */
	public static function setWriteLog($active) {
		self::$writeLog = ($active) ? true : false;
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
	 * Ajoute une requête sql
	 * 
	 * @param $sql String
	 */
	public static function setSqlRequest($sql) {
		self::$sqlRequest[] = $sql;
	}
	
	/**
	 * Ajouter une erreur mineur de type alerte
	 * 
	 * @param $msg String
	 */
	public static function addAlertError($msg) {
		self::addMinorError($msg, "alert");
	}
	
	/**
	 * Ajouter une erreur mineur de type note
	 * 
	 * @param $msg String
	 */
	public static function addNoteError($msg) {
		self::addMinorError($msg, "note");
	}
	
	/**
	 * Ajouter une erreur informative
	 * 
	 * @param $msg String
	 */
	public static function addInfoError($msg) {
		self::addMinorError($msg, "info");
	}
	
	/**
	 * Ajoute une nouvelle erreur mineur
	 * 
	 * @param $msg String
	 * @param $type String le type d'erreur (alert / note / info)
	 */
	public static function addMinorError($msg, $type = "alert") {
		switch ($type) {
			case 'alert':
				self::$alertError[] = $msg;
				break;
			case 'note':
				self::$noteError[] = $msg;
				break;
			case 'info':
				self::$infoError[] = $msg;
				break;
			default:
				self::addMinorError($msg);
				break;
		}		
	}
	
	/**
	 * Retourne une liste contenant les erreurs mineurs
	 * 
	 * @return String
	 */
	public static function &getMinorError() {
		$rslt = "";
		
		if (Core_Loader::isCallable("Core_Main")) {
			$error = self::minorErrorDetected();
			$display = "none";
			
			if ($error) {
				$display = "block";
				$rslt .= "<ul class=\"exception\">";
				foreach(self::$alertError as $alertError) {
					$rslt .= "<li class=\"alert\"><div>" . $alertError . "</div></li>";
				}
				foreach(self::$noteError as $noteError) {
					$rslt .= "<li class=\"note\"><div>" . $noteError . "</div></li>";
				}
				foreach(self::$infoError as $infoError) {
					$rslt .= "<li class=\"info\"><div>" . $infoError . "</div></li>";
				}
				$rslt .= "</ul>";
			}
			
			// Réaction différente en fonction du type d'affichage demandée
			if (Core_Main::isFullScreen()) {
				$rslt = "<div id=\"block_message\" style=\"display: " . $display . ";\">" . $rslt . "</div>";
			} else if ($error) {
				if (Core_Loader::isCallable("Core_Html")) {
					if (Core_Html::getInstance()->isJavascriptEnabled()) {
						Core_Html::getInstance()->addJavascript("displayMessage('" . addslashes($rslt) . "');");
						$rslt = "";
					}
				}
			}
		}
		return $rslt;
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
		return (!empty(self::$exception));
	}
	
	/**
	 * Vérifie si une erreur mineur est détecté
	 * 
	 * @return boolean
	 */
	public static function minorErrorDetected() {
		return (!empty(self::$alertError) || !empty(self::$noteError) || !empty(self::$infoError));
	}
	
	/**
	 * Capture les exceptions en chaine de caractère
	 * 
	 * @param $var array
	 * @return String
	 */
	private static function &serializeData($var) {
		$content = "";
		foreach ($var as $msg) {
			$content .= $msg . "\n";
		}
		return $content;
	}
	
	/**
	 * Affichage des exceptions courante
	 */
	public static function displayException() {
		if (Core_Loader::isCallable("Core_Main") && Core_Loader::isCallable("Core_Session")) {
			if (Core_Session::$userRank > 1) {
				echo "<div style=\"color: blue;\"><br />"
				. "***********************SQL REQUESTS (" . count(self::$sqlRequest) . ") :<br />";
				if (!empty(self::$sqlRequest)) {
					echo str_replace("\n", "<br />", self::serializeData(self::$sqlRequest));
				} else {
					echo "<span style=\"color: green;\">No sql request registred.</span>";
				}
				
				echo "<br /><br />***********************EXCEPTIONS (" . count(self::$exception) . ") :<br />";
				
				if (self::exceptionDetected()) {
					echo "<span style=\"color: red;\">"
					. str_replace("\n", "<br />", self::serializeData(self::$exception))
					. "</span>";
				} else {
					echo "<span style=\"color: green;\">No exception registred.</span>";
				}
				
				echo "<br /><br />***********************BENCHMAKER :<br />"
				. "Core : " . Exec_Marker::getTime("core") . " ms"
				. "<br />Launcher : " . Exec_Marker::getTime("launcher") . " ms"
				. "<br />All : " . Exec_Marker::getTime("all") . " ms"
				. "</div>";
			}
		}
	}
	
	/**
	 * Ecriture du rapport dans un fichier log
	 */
	public static function logException() {
		if (Core_Loader::isCallable("Core_CacheBuffer")) {
			if (self::$writeLog && self::exceptionDetected()) {			
				// Positionne dans le cache
				Core_CacheBuffer::setSectionName("log");
				// Ecriture a la suite du cache
				Core_CacheBuffer::writingCache("exception_" . date('Y-m-d') . ".log.php", self::serializeData(self::$exception), false);
			}
		}
	}
}
?>