<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Classe de mise en forme d'un formulaire.
 * Avec mise en cache automatique.
 * 
 * @author Sébastien Villemain
 */
class Libs_Form {
	
	/**
	 * Nom du formulaire.
	 * 
	 * @var String
	 */
	private $name = "";
	
	/**
	 * Url pour le tag d'action.
	 * 
	 * @var String
	 */
	private $urlAction = "";
	
	/**
	 * Titre du formulaire.
	 * 
	 * @var String
	 */
	private $title = "";
	
	/**
	 * Description du formulaire.
	 * 
	 * @var String
	 */
	private $description = "";
	
	/**
	 * Donnée contenu dans le formulaire.
	 * 
	 * @var String
	 */
	private $inputData = "";
	
	/**
	 * Fermeture de la balise fieldset.
	 * 
	 * @var boolean
	 */
	private $doFieldset = true;
	
	/**
	 * Formulaire présent dans le cache.
	 * 
	 * @var boolean
	 */
	private $cached = false;
	
	/**
	 * Variable mise en cache.
	 * 
	 * @var array
	 */
	private $cacheVars = array();
	
	/**
	 * Position dans le tableau des variables cache.
	 * 
	 * @var int
	 */
	private $cacheVarsIndex = 0;
	
	/**
	 * Nouveau formulaire.
	 * 
	 * @param $name String identifiant du formlaire : attention a ne pas prendre un nom déjà utilisé !
	 * @param $urlAction String
	 */
	public function __construct($name, $urlAction = "") {
		$this->name = $name;
		$this->urlAction = !empty($urlAction) ? $urlAction : "index.php";
		
		Core_CacheBuffer::setSectionName("form");
		$this->cached = Core_CacheBuffer::cached($name . ".php");
	}
	
	/**
	 * Mettre un title au début du formulaire.
	 * 
	 * @param $title String
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * Mettre une description au début du formulaire.
	 * 
	 * @param $description String
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Ajout d'une valeur en cache.
	 * 
	 * @param $value String
	 */
	private function addCacheVar($value) {
		$this->cacheVars[$this->cacheVarsIndex++] = $value;
	}
	
	/**
	 * Retourne la dernière variable de cache.
	 * 
	 * @return String
	 */
	private function getLastCacheVar() {
		if ($this->cached) return "";
		// Le nom de la "vars" est relatif a Core_CacheBuffer::getCache()
		return "$" . "vars[" . ($this->cacheVarsIndex - 1) . "]";
	}
	
	/**
	 * Ajoute un fieldset.
	 * 
	 * @param $class String
	 */
	public function addFieldset($title = "", $description = "") {
		$title = $this->getTitle($title);
		$description = $this->getDescription($description);
		
		if (!$this->cached) {
			if (!$this->doFieldset) $this->doFieldset = true;
			else $this->inputData .= "</fieldset>";
			
			$this->inputData .= "<fieldset>" 
			. $title . $description;
		}
	}
	
	/**
	 * Ajouter un champs de type texte.
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	public function addInputText($name, $description = "", $defaultValue = "", $options = "", $class = "") {
		$this->addInput($name, $name, $description, "text", $defaultValue, $options, $class);		
	}
	
	/**
	 * Ajouter un champs caché.
	 * 
	 * @param $name String
	 * @param $defaultValue String
	 * @param $options String
	 */
	public function addInputHidden($name, $defaultValue, $options = "") {
		$this->addInput($name, $name, "", "hidden", $defaultValue, $options, "");		
	}
	
	/**
	 * Ajouter un bouton d'envoie
	 * 
	 * @param $name String
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	public function addInputSubmit($name, $defaultValue, $options = "", $class = "") {
		$this->addInput($name, $name, "", "submit", $defaultValue, $options, $class);		
	}
	
	/**
	 * Ajouter un champs de type bouton radio.
	 * 
	 * @param $name String
	 * @param $id String
	 * @param $description String
	 * @param $checked boolean
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	public function addInputRadio($id, $name, $description = "", $checked = false, $defaultValue = "", $options = "", $class = "") {
		if (empty($class)) $class = "radio";
		if ($checked) $options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
		$this->addInput($id, $name, $description, "radio", $defaultValue, $options, $class);		
	}
	
	/**
	 * Ajouter un champs de type bouton a cocher.
	 *
	 * @param $name String
	 * @param $id String
	 * @param $description String
	 * @param $checked boolean
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	public function addInputCheckbox($id, $name, $description = "", $checked = false, $defaultValue = "", $options = "", $class = "") {
		if (empty($class)) $class = "checkbox";
		if ($checked) $options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
		$this->addInput($id, $name, $description, "checkbox", $defaultValue, $options, $class);
	}

	/**
	 * Ajouter un bouton.
	 *
	 * @param $name String
	 * @param $description String
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	public function addInputButton($name, $description = "", $defaultValue = "", $options = "", $class = "") {
		$this->addInput($name, $name, $description, "button", $defaultValue, $options, $class);
	}
	
	/**
	 * Ajouter un champs de type mot de passe.
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $options String
	 * @param $class String
	 */
	public function addInputPassword($name, $description = "", $options = "", $class = "") {
		$this->addInput($name, $name, $description, "password", "", $options, $class);		
	}
	
	/**
	 * Ajouter un champs.
	 * 
	 * @param $id String
	 * @param $name String
	 * @param $description String
	 * @param $type String
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	private function addInput($id, $name, $description, $type, $defaultValue = "", $options = "", $class = "") {
		if (empty($class)) $class = "input";
		
		$idDescription = $this->getId($name);
		$description = $this->getLabel($id, $description);
		$id = $this->getId($id, "input");
		
		$this->addCacheVar($name);
		$name = $this->getLastCacheVar();
		
		$this->addCacheVar($type);
		$type = $this->getLastCacheVar();
		
		$this->addCacheVar($class);
		$class = $this->getLastCacheVar();
		
		$this->addCacheVar($defaultValue);
		$defaultValue = $this->getLastCacheVar();
		
		$this->addCacheVar($options);
		$options = $this->getLastCacheVar();
		
		if (!$this->cached) {
			$this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
			. " <input id=\"" . $id . "\" name=\"" . $name . "\" type=\"" . $type . "\""
			. " class=\"" . $class . "\" value=\"" . $defaultValue . "\" " . $options . " />"
			. "</p>";
		}
	}
	
	/**
	 * Ajouter un textarea.
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $defaultValue String
	 * @param $options String
	 * @param $class String
	 */
	public function addTextarea($name, $description, $defaultValue = "", $options = "", $class = "") {
		if (empty($class)) $class = "textarea";
		
		$idDescription = $this->getId($name);
		$description = $this->getLabel($name, $description);
		$id = $this->getId($name, "input");
		
		$this->addCacheVar($name);
		$name = $this->getLastCacheVar();
		
		$this->addCacheVar($class);
		$class = $this->getLastCacheVar();
		
		$this->addCacheVar($options);
		$options = $this->getLastCacheVar();
		
		$defaultValue = Exec_Entities::stripSlashes($defaultValue);
		$this->addCacheVar($defaultValue);
		$defaultValue = $this->getLastCacheVar();
		
		if (!$this->cached) {
			$this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
			. " <textarea id=\"" . $id . "\" name=\"" . $name . "\""
			. " class=\"" . $class . "\" " . $options . " >"
			. $defaultValue . "</textarea></p>";
		}
	}
	
	/**
	 * Ajouter un liste déroulante (balise ouvrante seulement).
	 * 
	 * @param $name String
	 * @param $description String
	 * @param $options String
	 * @param $class String
	 */
	public function addSelectOpenTag($name, $description, $options = "", $class = "") {
		if (empty($class)) $class = "select";
		
		$idDescription = $this->getId($name);
		$description = $this->getLabel($name, $description);
		$id = $this->getId($name, "input");
		
		$this->addCacheVar($name);
		$name = $this->getLastCacheVar();
		
		$this->addCacheVar($class);
		$class = $this->getLastCacheVar();
		
		$this->addCacheVar($options);
		$options = $this->getLastCacheVar();
		
		if (!$this->cached) {
			$this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
			. " <select id=\"" . $id . "\" name=\"" . $name . "\""
			. " class=\"" . $class . "\" " . $options . ">";
		}
	}
	
	/**
	 * Ajouter un élément dans la liste déroulante ouverte.
	 * 
	 * @param $value String
	 * @param $description String
	 * @param $selected boolean
	 * @param $options String
	 */
	public function addSelectItemTag($value, $description = "", $selected = false, $options = "") {
		if ($selected) $options = "selected=\"selected\"" . ((!empty($options)) ? " " . $options : "");
		
		$description = ((!empty($description)) ? Exec_Entities::textDisplay($description) : $value);
		
		$this->addCacheVar($value);
		$value = $this->getLastCacheVar();
		
		$this->addCacheVar($options);
		$options = $this->getLastCacheVar();
		
		$this->addCacheVar($description);
		$description = $this->getLastCacheVar();
		
		if (!$this->cached) {
			$this->inputData .= " <option value=\"" . $value . "\" " . $options . ">"
			. $description . "</option>";
		}
	}
	
	/**
	 * Fermeture de la liste déroulante.
	 */
	public function addSelectCloseTag() {
		if (!$this->cached) $this->inputData .= "</select></p>";
	}
	/**
	 * Ajoute du code HTML en plus dans le fieldset courant.
	 * 
	 * @param $html String
	 */
	public function addHtmlInFieldset($html) {
		$this->addCacheVar($html);
		
		if (!$this->cached) $this->inputData .= "<p>" . $this->getLastCacheVar() . "</p>";
	}
	
	/**
	 * Ajoute un espace.
	 */
	public function addSpace() {
		if (!$this->cached) $this->inputData .= "<p><br /></p>";
	}
	
	/**
	 * Ajoute du code HTML en plus après avoir fermé le fieldset courant.
	 * 
	 * @param $html String
	 */
	public function addHtmlOutFieldset($html) {
		$this->addCacheVar($html);
		
		if (!$this->cached) {
			if ($this->doFieldset) {
				$this->inputData .= "</fieldset>";
				$this->doFieldset = false;
			}
			$this->inputData .= "<p>" . $this->getLastCacheVar() . "</p>";
		}
	}
	
	/**
	 * Retourne le code HTML pour le label.
	 * 
	 * @param $name String
	 * @param $description String
	 * @return String
	 */
	private function getLabel($name, $description) {
		$id = $this->getId($name, "input");
		$description = Exec_Entities::textDisplay($description);
		$this->addCacheVar($description);
		
		if ($this->cached) return "";
		return "<label for=\"" . $id . "\">" . $this->getLastCacheVar() . "</label>";
	}
	
	/**
	 * Retourne le code HTML pour le titre.
	 * 
	 * @param $title String
	 * @return String
	 */
	private function getTitle($title) {
		$title = Exec_Entities::textDisplay($title);
		$this->addCacheVar($title);
		
		if ($this->cached) return "";
		return "<legend><b>" . $this->getLastCacheVar() . "</b></legend>";
	}
	
	/**
	 * Retourne le code HTML pour la description.
	 * 
	 * @param $description String
	 * @return String
	 */
	private function getDescription($description) {
		$id = $this->getId("description");
		$description = Exec_Entities::textDisplay($description);
		$this->addCacheVar($description);
		
		if ($this->cached) return "";
		return "<p class=\"" . $id . "\">" . $this->getLastCacheVar() . "</p>";
	}
	
	/**
	 * Retourne l'identifiant du champs.
	 * 
	 * @param $name String
	 * @param $options String
	 * @return String
	 */
	private function getId($name, $options = "") {
		if (!empty($options)) $options = "-" . $options;
		$id = "form-" . $this->name . "-" . $name . $options;
		$this->addCacheVar($id);
		
		if ($this->cached) return "";
		return $this->getLastCacheVar();
	}
	
	/**
	 * Retourne le rendu du formulaire complet.
	 * Attention, ceci procédera a une sauvegarde et une lecture du cache.
	 * 
	 * @param $class String
	 * @return String
	 */
	public function &render($class = "") {
		if (empty($class)) $class = "form";
		
		$this->addCacheVar($this->urlAction);
		$url = $this->getLastCacheVar();
		
		$this->addCacheVar($this->name);
		$name = $this->getLastCacheVar();
		
		$this->addCacheVar($class);
		$class = $this->getLastCacheVar();
		
		$title = $this->getTitle($this->title);
		$description = $this->getDescription($this->description);
		
		$content = "";
		Core_CacheBuffer::setSectionName("form");
		if ($this->cached) { // Récupèration des données mise en cache
			$content = Core_CacheBuffer::getCache($this->name . ".php", $this->cacheVars);
		} else { // Préparation puis mise en cache
			$data = "<form action=\"" . $url . "\" method=\"post\" id=\"form-" . $name . "\" name=\"" . $name . "\""
			. " class=\"" . $class . "\"><fieldset>" . $title . $description . $this->inputData
			. (($this->doFieldset) ? "</fieldset>" : "") . "</form>";
			
			// Enregistrement dans le cache
			$data = Core_CacheBuffer::serializeData($data);
			Core_CacheBuffer::writingCache($this->name . ".php", $data);
			
			// Lecture pour l'affichage
			$vars = $this->cacheVars;
			eval(" \$content = $data; "); // Ne pas ajouter de quote : les données sont déjà serializé
		}
		return $content;
	}
}

?>