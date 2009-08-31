<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Classe de mise en forme d'un formulaire
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Form {
	
	/**
	 * Nom du form
	 * 
	 * @var String
	 */
	private $name = "";
	
	/**
	 * Url pour le tag d'action
	 * 
	 * @var String
	 */
	private $urlAction = "";
	
	/**
	 * Titre du form
	 * 
	 * @var String
	 */
	private $title = "";
	
	/**
	 * Description du form
	 * 
	 * @var String
	 */
	private $description = "";
	
	/**
	 * Donnée contenu dans le formulaire
	 * 
	 * @var String
	 */
	private $inputData = "";
	
	/**
	 * Fermeture de la balise fieldset
	 * 
	 * @var boolean
	 */
	private $doFieldset = false;
	
	/**
	 * Nouveau formulaire
	 * 
	 * @param $name String
	 * @param $urlAction String
	 */
	public function __construct($name, $urlAction = "") {
		$this->name = $name;
		$this->urlAction = !empty($urlAction) ? $urlAction : "index.php";
	}
	
	/**
	 * Mettre un title au début du form
	 * 
	 * @param $title String
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * Mettre une description au début du form
	 * 
	 * @param $description String
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Ajoute un fieldset
	 * 
	 * @param $class String
	 */
	public function addFieldset($class = "input") {
		if (!$this->doFieldset) {
			$this->doFieldset = true;
		} else {
			$this->inputData .= "</fieldset>";
		}
		$this->inputData .= "<fieldset class=\"" . $class . "\">";
	}
	
	/**
	 * Ajouter un champs de type texte
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $class String
	 * @param $options String
	 */
	public function addInputText($name, $description = "", $class = "", $options = "") {
		if (empty($class)) $class = "input";
		$this->addInput($name, $description, "text", $class, $options);		
	}
	
	/**
	 * Ajouter un champs caché
	 * 
	 * @param $name String
	 * @param $value String
	 * @param $options String
	 */
	public function addInputHidden($name, $value, $options = "") {
		$options = "value=\"" . $value . "\"" . ((!empty($options)) ? " " . $options : "");
		$this->addInput($name, "", "hidden", "", $options);		
	}
	
	/**
	 * Ajouter un bouton d'envoie
	 * 
	 * @param $name String
	 * @param $options String
	 */
	public function addInputSubmit($name, $class = "", $options) {
		if (empty($class)) $class = "input";
		$this->addInput($name, "", "submit", $class, $options);		
	}
	
	/**
	 * Ajouter un champs de type bouton radio
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $class String
	 * @param $options String
	 */
	public function addInputRadio($name, $description = "", $class = "", $options = "") {
		if (empty($class)) $class = "radio";
		$this->addInput($name, $description, "radio", $class, $options);		
	}
	
	/**
	 * Ajouter un champs de type bouton a cocher
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $checked boolean
	 * @param $class String
	 * @param $options String
	 */
	public function addInputCheckbox($name, $description = "", $checked = false, $class = "", $options = "") {
		if (empty($class)) $class = "checkbox";
		if ($checked) {
			$options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
		}
		$this->addInput($name, $description, "checkbox", $class, $options);		
	}
	
	/**
	 * Ajouter un champs de type mot de passe
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $class String
	 * @param $options String
	 */
	public function addInputPassword($name, $description = "", $class = "", $options = "") {
		if (empty($class)) $class = "input";
		$this->addInput($name, $description, "password", $class, $options);		
	}
	
	/**
	 * Ajouter un champs
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $type String
	 * @param $class String
	 * @param $options String
	 */
	public function addInput($name, $description, $type, $class = "", $options = "") {
		$data = ((!empty($description)) ? "<p id=\"" . $this->getId($name) . "\">" . $this->getLabel($name, $description) : "")
		. "<input id=\"" . $this->getId($name, "input") . "\" name=\"" . $name . "\" type=\"" . $type . "\""
		. ((!empty($class)) ? " class=\"" . $class . "\"" : "")
		. ((!empty($options)) ? " " . $options : "") . " />"
		. ((!empty($description)) ? "</p>" : "");
		$this->inputData .= $data;
	}
	
	/**
	 * Ajouter un textarea
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $class String
	 * @param $options String
	 * @param $defaultText String
	 */
	public function addTextarea($name, $description, $class = "", $options = "", $defaultText = "") {
		if (empty($class)) $class = "textarea";
		$data = "<p id=\"" . $this->getId($name) . "\">"
		. $this->getLabel($name, $description)
		. "<textarea id=\"" . $this->getId($name, "input") . "\" name=\"" . $name . "\""
		. ((!empty($class)) ? " class=\"" . $class . "\"" : "")
		. ((!empty($options)) ? " " . $options : "") . ">"
		. ((!empty($defaultText)) ? Exec_Entities::stripSlashes($defaultText) : "")
		. "</textarea></p>";
		$this->inputData .= $data;
	}
	
	/**
	 * Ajouter un liste déroulante (balise ouvrante seulement)
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $class String
	 * @param $options String
	 */
	public function addSelectOpenTag($name, $description, $class = "", $options = "") {
		if (empty($class)) $class = "select";
		$data = "<p id=\"" . $this->getId($name) . "\">"
		. $this->getLabel($name, $description)
		. "<select id=\"" . $this->getId($name, "input") . "\" name=\"" . $name . "\""
		. ((!empty($class)) ? " class=\"" . $class . "\"" : "")
		. ((!empty($options)) ? " " . $options : "")
		. ">";
		$this->inputData .= $data;
	}
	
	/**
	 * Ajouter un element dans la liste déroulante ouverte
	 * 
	 * @param $value String
	 * @param $description String
	 * @param $selected boolean
	 * @param $options String
	 */
	public function addSelectItemTag($value, $description = "", $selected = false, $options = "") {
		$data .= " <option value=\"" . $value . "\""
		. (($selected) ? " selected=\"selected\"" : "")
		. ((!empty($options)) ? " " . $options : ""). ">"
		. ((!empty($description)) ? Exec_Entities::textDisplay($description) : $value)
		. "</option>";
		$this->inputData .= $data;
	}
	
	/**
	 * Fermeture de la liste déroulante
	 */
	public function addSelectCloseTag() {
		$this->inputData .= "</select></p>";
	}
	/**
	 * Ajoute du code HTML en plus dans le fieldset courant
	 * 
	 * @param $html String
	 */
	public function addHtmlInFieldset($html) {
		$this->inputData .= $html;
	}
	
	/**
	 * Ajoute du code HTML en plus après avoir fermé le fieldset courant
	 * 
	 * @param $html String
	 */
	public function addHtmlOutFieldset($html) {
		if ($this->doFieldset) {
			$this->inputData .= "</fieldset>";
			$this->doFieldset = false;
		}
		$this->inputData .= $html;
	}
	
	/**
	 * Retourne le label
	 * 
	 * @param $name String
	 * @param $description String
	 * @return String
	 */
	private function getLabel($name, $description) {
		return "<label for=\"" . $this->getId($name, "input") . "\">" . Exec_Entities::textDisplay($description) . "</label>";
	}
	
	/**
	 * Retourne l'id du champs
	 * 
	 * @param $name String
	 * @return String
	 */
	private function getId($name, $options = "") {
		if (!empty($options)) $options = "-" . $options;
		return "form-" . $this->name . "-" . $name . $options;
	}
	
	/**
	 * Retourne le rendu du form complet
	 * 
	 * @param $class String
	 * @return String
	 */
	public function render($class = "") {
		// Définition du form
		$content = "<form action=\"" . $this->urlAction . "\" method=\"post\" id=\"form-" . $this->name . "\" name=\"" . $this->name . "\""
		. " class=\"" . ((!empty($class)) ? $class : "form") . "\"><fieldset>"
		. ((!empty($this->title)) ? "<legend>" . Exec_Entities::textDisplay($this->title) . "</legend>" : "")
		. ((!empty($this->description)) ? "<p id=\"" . $this->getId("description") . "\">" . Exec_Entities::textDisplay($this->description) . "</p>" : "")
		. $this->inputData
		. (($this->doFieldset) ? "</fieldset>" : "")
		. "</form>";
		return $content;
	}
}

?>