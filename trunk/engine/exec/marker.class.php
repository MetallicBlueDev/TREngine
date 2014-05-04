<?php
if (!defined("TR_ENGINE_INDEX")) {
	require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
	Core_Secure::checkInstance();
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
	 * Retourne le temps qui a été mis pour généré la page en ms
	 * 
	 * @return int le temps de génération en milliseconde (ms)
	 */
	public static function &getTime($name) {
		if (isset(self::$startTime[$name]) 
				&& isset(self::$finishTime[$name])) {
			$rslt = self::$finishTime[$name] - self::$startTime[$name];
			$rslt = round($rslt, 4) * 1000;
			return $rslt;
		}
		return 0;
	}
}


?>