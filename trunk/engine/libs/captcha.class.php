<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * G�n�rateur de captcha, anti-robot, anti-spam
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Captcha {
	
	/**
	 * V�rifie l'�tat initialisation de la classe
	 * 
	 * @var boolean
	 */
	private static $iniRand = false;
	
	/**
	 * Active le script captcha
	 * 
	 * @var boolean
	 */
	private $enabled = false;
	
	/**
	 * Un object utilis� suivant le type
	 * 
	 * @var Object
	 */
	private $object = "";
	
	/**
	 * R�ponse correcte a donner
	 * 
	 * @var String
	 */
	private $response = "";
	
	/**
	 * Nom du champs input anti robot
	 * 
	 * @var String
	 */
	private $inputRobotName = "";
	
	/**
	 * Question pos� li� a la r�ponse courante
	 * 
	 * @var String
	 */
	private $question = "";
	
	/**
	 * Configuration d'un nouveau captcha
	 * 
	 * @param $object Object (Libs_Form Object par exemple)
	 */
	public function __construct(&$object = null) {
		// Mode du captcha
		$captchaMode = Core_Main::$coreConfig['captchaMode'];
		$captchaMode = ($captchaMode == "off" || $captchaMode == "auto" || $captchaMode == "manu") ? $captchaMode : "auto";
		// Decide de l'activation
		if ($captchaMode == "off") $this->enabled = false;
		else if ($captchaMode == "auto" && Core_Session::$userRang > 0)  $this->enabled = false;
		else if ($captchaMode == "manu" && Core_Session::$userRang > 1) $this->enabled = false;
		else $this->enabled = true;
		
		if ($this->enabled) {
			$this->object = ($object != null && is_object($object)) ? $object : null;
		}
	}
	
	/**
	 * Initialise le compteur de donn�e al�atoire
	 */
	private function initRand() {
		mt_srand((double)microtime()*1000000);
		self::$iniRand = true;
	}
	
	/**
	 * Retourne un valeur al�atoire
	 * 
	 * @param $mini int
	 * @param $max int
	 * @return int
	 */
	private function randInt($mini, $max) {
		if (!self::$iniRand) {
			$this->initRand();
		}
		return mt_rand($mini, $max);
	}
	
	/**
	 * Cr�� un calcul simple
	 */
	private function makeSimpleCalculation() {
		// Nombre al�atoire
		$numberOne = $this->randInt(0, 9);
		$numberTwo = $this->randInt(1, 12);
		
		// Choix de l'operateur de fa�on al�atoire
		$operateur = ($numberTwo >= $numberOne) ? array("+", "*") : array("-", "+", "*");
		$operateur = $operateur[array_rand($operateur)];
		
		// Calcul de la r�ponse
		eval('$this->response = strval(' . $numberOne . $operateur . $numberTwo . ');');
		
		// Affichage al�atoire de l'op�rateur
		if ($this->randInt(0, 1) == 1) {
			// Affichage de l'op�rateur en lettre
			switch($operateur) {
				case '*': $operateur = "fois"; break;
				case '-': $operateur = "moins"; break;
				case '+': $operateur = "plus"; break;
				default: $operateur = "plus"; break;
			}
		} else {
			// Affichage de l'op�rateur en symbole
			$operateur = ($operateur == "*" && $this->randInt(0, 1) == 1) ? "x" : $operateur;
		}
		$this->question = CAPTCHA_MAKE_SIMPLE_CALCULATION . " " . $numberOne . " " . $operateur  . " " . $numberTwo . " ?";
	}
	
	/**
	 * Ecrire un certain nombre de lettre de l'alphabet
	 */
	private function makeLetters() {
		// Nombre al�atoire
		$number = $this->randInt(1, 6);
		$this->question = CAPTCHA_MAKE_LETTERS . " " . $number;
		$this->response = substr("abcdef", 0, $number);
	}
	
	/**
	 * Ecrire la lettre de l'alphabet correspondant au chiffre
	 */
	private function makeLetter() {
		// Nombre al�atoire
		$number = $this->randInt(1, 6);
		$this->question = CAPTCHA_MAKE_LETTER . " " . $number;
		$this->response = substr("abcdef", $number - 1, 1);
	}
	
	/**
	 * Ecrire un certain nombre de chiffre
	 */
	private function makeNumbers() {
		// Nombre al�atoire
		$number = $this->randInt(1, 6);
		$this->question = CAPTCHA_MAKE_NUMBERS . " " . $number;
		$this->response = substr("012345", 0, $number + 1);
	}
	
	/**
	 * Convertir en lettre un mois demand� en chiffre et inversement
	 */
	private function makeNumberMonth() {
		// Nombre al�atoire
		$number = $this->randInt(1, 12);
		
		// Recherche du mois par rapport au chiffre
		switch($number) {
			case '1': $month = JANUARY; break;
			case '2': $month = FEBRUARY; break;
			case '3': $month = MARCH; break;
			case '4': $month = APRIL; break;
			case '5': $month = MAY; break;
			case '6': $month = JUNE; break;
			case '7': $month = JULY; break;
			case '8': $month = AUGUST; break;
			case '9': $month = SEPTEMBER; break;
			case '10': $month = OCTOBER; break;
			case '11': $month = NOVEMBER; break;
			case '12': $month = DECEMBER; break;
			default: $month = JANUARY; break;
		}
		
		if ($this->randInt(0, 1) == 0) {
			// Ecrire en lettre un mois de l'ann�e
			$this->question = CAPTCHA_MAKE_NUMBER_TO_MONTH . " " . $number . " ?";
			$this->response = $month;
		} else {
			// Ecrire en chiffre un mois de l'ann�e
			$this->question = CAPTCHA_MAKE_MONTH_TO_NUMBER . " " . $month . " ?";
			$this->response = $number;
		}
	}
	
	/**
	 * G�n�re une image
	 */
	private function makePicture() {// TODO a v�rifier
		$this->response = Exec_Crypt::createId($this->randInt(3, 6));
		$this->question = CAPTCHA_MAKE_PICTURE_CODE . ": " . "<img src=\"engine/libs/imagegenerator.php?mode=code&amp;code=" . $this->response . "\" alt=\"\" />\n";
	}
	
	/**
	 * Creation du captcha
	 * Captcha cr��e dans l'objet valide sinon retourne en code HTML
	 * 
	 * @return String le code HTML a incruster dans la page ou une chaine vide si un objet valide est utilis�
	 */
	public function &create() {
		$rslt = "";
		if ($this->enabled) {
			$this->inputRobotName = Exec_Crypt::createIdLettres($this->randInt(5, 9));
			$mini = (extension_loaded('gd')) ? 0 : 1;
			$mode = $this->randInt($mini, 5);
			
			switch($mode) {
				case '0': $this->makePicture(); break;
				case '1': $this->makeSimpleCalculation(); break;
				case '2': $this->makeNumberMonth(); break;
				case '3': $this->makeLetter(); break;
				case '4': $this->makeLetters(); break;
				case '5': $this->makeNumbers(); break;
				default: $this->makeLetter(); break;
			}
			
			if ($this->object != null) {
				if ($this->object instanceOf Libs_Form) { // A v�rifier
					$this->object->addInputText("cles", $this->question, "input captcha");
					$this->object->addInputHidden($this->inputRobotName, "");
				}
			} else {
				$rslt = $this->question . " <input name=\"cles\" type=\"text\" value=\"\" />"
				. "<input name=\"" . $this->inputRobotName . "\" type=\"hidden\" value=\"\" />";
			}
		}
		Exec_Cookie::createCookie("captcha", addslashes(serialize($this)));
		return $rslt;
	}
	
	/**
	 * V�rifie la validit� du captcha courant
	 * 
	 * @return boolean
	 */
	public function verif() {
		$code = Core_Request::getString("cles", "", "POST");
		$inputRobot = Core_Request::getString($this->inputRobotName, "", "POST");
		// V�rification du formulaire
		if (empty($inputRobot) && $code == $this->response) {
			return true;
		}
		Core_Exception::addNoteError(CAPTCHA_INVALID);
		return false;
	}
	
	/**
	 * V�rifie la validit� du captcha
	 * 
	 * @param $object Libs_Captcha
	 * @return boolean
	 */
	public static function check($object = "") {
		if (!is_object($object)) {
			$object = unserialize(stripslashes(Exec_Cookie::getCookie("captcha")));
		}
		if (is_object($object)) {
			return $object->verif();
		}
		Core_Exception::addNoteError(CAPTCHA_INVALID);
		return false;
	}
}
?>