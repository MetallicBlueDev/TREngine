<?php

/**
 * Fonction optimisé et utilitaire
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Utils {
	
	/**
	 * Indique si une valeur appartient à un tableau
	 * in_array optimized function
	 * 
	 * @author robin at robinnixon dot com
	 * http://www.php.net/manual/fr/function.in-array.php#92460
	 * 
	 * @param $needle mixed
	 * @param $haystack array
	 * @return boolean si needle est trouvé
	 */
	public static function inArray($needle, $haystack) {
		$top = sizeof($haystack) -1;
		$bot = 0;
		
		while($top >= $bot) {
			$p = floor(($top + $bot) / 2);
			if ($haystack[$p] < $needle) $bot = $p + 1;
			else if ($haystack[$p] > $needle) $top = $p - 1;
			else return true;
		}
		return false;
	}
	
	/**
	 * Indique si une valeur appartient à un tableau (tableau multiple)
	 * Tableau a dimension multiple
	 * in_array multi array function
	 * 
	 * @param $needle mixed
	 * @param $haystack array
	 * @return boolean si needle est trouvé
	 */
	public static function inMultiArray($needle, $haystack) {
		foreach ($haystack as $value) {
			if (is_array($value)) { 
				if (self::inMultiArray($needle, $value)) return true;
			} else {
				if ($value == $needle) return true;
			}
		}
	}
}

?>