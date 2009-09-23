<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

class Exec_Marker {
	
	/**
	 * Marqueur de début
	 * 
	 * @var array
	 */
	private static $startTime = array();
	
	/**
	 * Marqueur de fin
	 * 
	 * @var array
	 */
	private static $finishTime = array();
	
	/**
	 * Marque le temps de début de génération
	 */
	public static function startTimer($name) {
		self::$startTime[$name] = self::maker();
	}
	
	/**
	 * Marque le temps de fin de génération
	 */
	public static function stopTimer($name) {
		self::$finishTime[$name] = self::maker();
	}
	
	/**
	 * Retourne le temps courant en micro seconde
	 * 
	 * @return float
	 */
	private static function maker() {
		return microtime(true);
	}
	
	/**
	 * Retourne le temps qui a été mis pour généré la page
	 * 
	 * @param $virgule int chiffre après la virgule
	 * @return int le temps de génération
	 */
	public static function &getTime($name, $virgule = 4) {
		if (isset(self::$startTime[$name]) 
				&& isset(self::$finishTime[$name])) {
					$rslt = self::$finishTime[$name] - self::$startTime[$name];
					$rslt = round($rslt, $virgule);
					return $rslt;
		} else {
			return 0;
		}
	}
}


?>