<?php

/**
 * Convertiseur de chaine de caractère en entities
 * 
 * @author Sebastien Villemain
 *
 */
class Exec_Entities {
	
	/**
	 * Transforme une chaine non-encodée, et la convertit en entitiées unicode &#xxx;
	 * pour que ça s'affiche correctement dans les navigateurs
	 * Thanks to ??? (sorry!) @ ???
	 * http://
	 * 
	 * @param string $source : la chaine
	 * @return string $encodedString : chaine et ses entitées
	 */
	public static function entitiesUtf8($source) {
		// Remplace les entités numériques
		$source = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $source);
		$source = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $source);
		
		// Remplace les entités litérales
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		$source = strtr($source, $trans_tbl);
		
		// Entitées UTF-8
		$source = utf8_encode($source);
		
		// array used to figure what number to decrement from character order value 
		// according to number of characters used to map unicode to ascii by utf-8
		$decrement[4] = 240;
		$decrement[3] = 224;
		$decrement[2] = 192;
		$decrement[1] = 0;
		
		// the number of bits to shift each charNum by
		$shift[1][0] = 0;
		$shift[2][0] = 6;
		$shift[2][1] = 0;
		$shift[3][0] = 12;
		$shift[3][1] = 6;
		$shift[3][2] = 0;
		$shift[4][0] = 18;
		$shift[4][1] = 12;
		$shift[4][2] = 6;
		$shift[4][3] = 0;
		
		$pos = 0;
		$len = strlen($source);
		$encodedString = '';
		while ($pos < $len) {
			$charPos = substr($source, $pos, 1);
			$asciiPos = ord($charPos);
			
			if ($asciiPos < 128) {
				$encodedString .= htmlentities($charPos);
				$pos++;
				continue;
			}
			
			if (($asciiPos >= 240) && ($asciiPos <= 255)) $i = 4; // 4 chars representing one unicode character
			else if (($asciiPos >= 224) && ($asciiPos <= 239)) $i = 3; // 3 chars representing one unicode character
			else if (($asciiPos >= 192) && ($asciiPos <= 223)) $i = 2; // 2 chars representing one unicode character
			else $i = 1; // 1 char (lower ascii)
			
			$thisLetter = substr($source, $pos, $i);
			$pos += $i;
			
			// process the string representing the letter to a unicode entity
			$thisLen = strlen($thisLetter);
			$decimalCode = 0;
			
			for ($thisPos = 0; $thisPos < $thisLen; $thisPos++) {
				$thisCharOrd = ord(substr($thisLetter, $thisPos, 1));
				
				if ($thisPos == 0) {
					$charNum = intval($thisCharOrd - $decrement[$thisLen]);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				} else {
					$charNum = intval($thisCharOrd - 128);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				}
			}
			
			$encodedLetter = '&#'. str_pad($decimalCode, ($thisLen==1) ? 3 : 5, '0', STR_PAD_LEFT).';';
			$encodedString .= $encodedLetter;
		}
		return $encodedString;
	}
	
	/**
	 * Ajout de slashes dans le texte
	 * 
	 * @param $text String
	 * @return String
	 */
	public static function addSlashes($text) {
		return addSlashes($text);
	}
	
	public static function stripSlashes($text) {
		return stripslashes($text);
	}
	
	public static function textDisplay($text) {
		$text = self::entitiesUtf8($text);
		//$text = self::stripSlashes($text);
		$text = Core_TextEditor::text($text);
		$text = Core_TextEditor::smilies($text);
		return $text;
	}
	
	public static function textDb($text) {
		//$text = addslashes($text);
		return $texte;
	}
}

?>