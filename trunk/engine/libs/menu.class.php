<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de menu
 * 
 * @author Sébastien Villemain
 */
class Libs_Menu {
	
	/**
	 * Identifiant du menu
	 * 
	 * @var String
	 */
	private $identifier = "";
	
	/**
	 * Menu complet et son arborescence
	 * 
	 * @var array
	 */
	private $items = array();
	
	/**
	 * Cles de l'element actuellement actif
	 * 
	 * @var int
	 */
	private $itemActive = 0;
	
	/**
	 * Attributs de l'element principal
	 * 
	 * @var String
	 */
	private $attribtus = "";
	
	/**
	 * Construction du menu
	 * 
	 * @param $identifier Identifiant du menu par exemple "block22"
	 * @param $sql array
	 */
	public function __construct($identifier, $sql = array()) {
		$this->identifier = $identifier;
		$this->itemActive = Core_Request::getInt("item", 0);
		
		Core_CacheBuffer::setSectionName("menus");
		if ($this->isCached()) {
			$this->loadFromCache();
		} else if (count($sql) >= 3) {
			$this->loadFromDb($sql);
		}
	}
	
	/**
	 * Chargement du menu via le cache
	 */
	private function loadFromCache() {
		$data = Core_CacheBuffer::getCache($this->identifier . ".php");
		$this->items = unserialize(Exec_Entities::stripSlashes($data));
	}
	
	/**
	 * Vérifie la présence du cache
	 * 
	 * @return boolean
	 */
	private function isCached() {
		return (Core_CacheBuffer::cached($this->identifier . ".php"));
	}
	
	/**
	 * Chargement du menu depuis la base
	 * 
	 * @param $sql array parametre de Sélection
	 */
	private function loadFromDb($sql) {
		Core_Sql::select(
			$sql['table'],
			$sql['select'],
			$sql['where'],
			$sql['orderby'],
			$sql['limit']
		);
		
		if (Core_Sql::affectedRows() > 0) {
			// Création d'un buffer
			Core_Sql::addBuffer($this->identifier, "menu_id");
			$menus = Core_Sql::getBuffer($this->identifier);
			
			// Ajoute et monte tout les items
			foreach($menus as $key => $item) {
				// Création du chemin route
				if ($item->parent_id > 0) {
					$item->route = $menus[$item->parent_id]->route;
				}
				$item->route[] = $key;
				$this->items[$key] = new Libs_MenuElement($item, $this->items);
			}
			
			Core_CacheBuffer::writingCache(
				$this->identifier . ".php", 
				Core_CacheBuffer::serializeData(serialize($this->items))
			);
		}
	}
	
	/**
	 * Ajoute un attribut a l'element UL principal
	 * 
	 * @param $name String
	 * @param $value String
	 */
	public function addAttributs($name, $value) {
		$this->attribtus .= " " . $name . "=\"" . $value . "\"";
	}
	
	/**
	 * Création d'un rendu complet du menu
	 * 
	 * @param $callback
	 * @return String
	 */
	public function &render($callback = "Block_Menu::getLine") {
		// Creation du tableau route
		$route = array();
		if (is_object($this->items[$this->itemActive])) {
			$route = $this->items[$this->itemActive]->data->route;
		}
		
		// Début de rendu
		$out = "<ul id=\"" . $this->identifier . "\"" . $this->attribtus . ">";
		foreach($this->items as $key => $item) {
			if ($item->data->parent_id == 0 && Core_Access::autorize($this->identifier, $item->data->rank)) {
				// Ajout du tableau route dans l'element principal
				if ($key == $route[0]) $item->setRoute($route);
				// Création du rendu
				$out .= $this->items[$key]->toString($callback, $this->classParent, $this->classActive);
			}
		}
		$out .= "</ul>";
		return $out;
	}
}

/**
 * Membre d'un menu
 * 
 * @author Sébastien Villemain
 */
class Libs_MenuElement {
	
	/**
	 * Item info du menu
	 * 
	 * @var array - object
	 */
	public $data = array();
	
	/**
	 * Attributs de l'element
	 * 
	 * @var array
	 */
	private $attributs = array();
	
	/**
	 * Enfant de l'element
	 * 
	 * @var array
	 */
	private $child = array();
	
	/**
	 * Balise relative a l'element
	 * 
	 * @var array
	 */
	private $tags = array();
	
	/**
	 * Tableau route
	 * 
	 * @var array
	 */
	private $route = array();
	
	/**
	 * Construction de l'element du menu
	 * 
	 * @param $item array - object
	 * @param $items array - object
	 */
	public function __construct($item, &$items) {
		// Ajout des infos de l'item
		$this->data = $item;
		$this->addTags("li");
		
		// Enfant trouvé
		if ($item->parent_id > 0) {
			// Ajout de l'enfant
			$this->addAttributs("class", "item" . $item->menu_id);
			$items[$item->parent_id]->addChild($this);
		} else if ($item->parent_id == 0) {
			$this->addAttributs("class", "parent");
		}
	}
	
	/**
	 * Ajoute un attribut a la liste
	 * 
	 * @param $name String nom de l'attribut
	 * @param $value String valeur de l'attribut
	 */
	public function addAttributs($name, $value) {
		if (!isset($this->attributs[$name])) {
			$this->attributs[$name] = $value;
		} else {
			// Conversion en tableau si besoin
			if (!is_array($this->attributs[$name])) {
				$firstValue = $this->attributs[$name];
				$this->attributs[$name] = array();
				$this->attributs[$name][] = $firstValue;
			}
			// Vérification des valeurs déjà enregistrées
			if (Exec_Utils::inArray($value, $this->attributs[$name]) == false) {
				if ($value == "parent") {
					array_unshift($this->attributs[$name], $value);
				} else if ($value == "active") {
					if ($this->attributs[$name][0] == "parent") {
						// Remplace parent par active
						$this->attributs[$name][0] = $value;
						// Ajoute a nouveau parent en 1er
						array_unshift($this->attributs[$name], "parent");
					} else {
						array_unshift($this->attributs[$name], $value);
					}
				} else {
					$this->attributs[$name][] = $value;
				}
			}
		}
	}
	
	/**
	 * Supprime un attributs
	 * 
	 * @param $name String nom de l'attribut
	 */
	public function removeAttributs($name = "") {
		if (!empty($name)) {
			unset($this->attributs[$name]);
		} else {
			foreach($this->attributs as $key => $attributs) {
				unset($this->attributs[$key]);
			}
		}
	}
	
	/**
	 * Mise en forme des attributs
	 * 
	 * @param $attributs array
	 * @return String
	 */
	public function &gétattributs($attributs = "") {
		if (empty($attributs)) {
			$attributs = $this->attributs;
		}
		$rslt = "";
		foreach($attributs as $attributsName => $value) {
			if (!is_int($attributsName)) {
				$rslt .= " " . $attributsName . "=\"";
			}
			if (is_array($value)) {
				$rslt .= $this->gétattributs($value);
			} else {
				if (!empty($rslt) && is_int($attributsName)) $rslt .= " ";
				$rslt .= htmlspecialchars($value);			
			}
			if (!is_int($attributsName)) {
				$rslt .= "\"";
			}
		}
		return $rslt;
	}
	
	/**
	 * Ajout de balise tag pour l'element
	 * 
	 * @param $tag String
	 */
	public function addTags($tag) {
		$this->tags[] = $tag;
	}
	
	/**
	 * Mise en place du tableau route
	 * 
	 * @param $route array
	 */
	public function setRoute($route) {
		$this->route = $route;
	}
	
	/**
	 * Ajoute un enfant a l'item courant
	 * 
	 * @param $child Libs_MenuElement or array - object
	 * @param $items array - object
	 */
	public function &addChild(&$child, &$items = array()) {
		// Création de l'enfant si besoin
		if (!is_object($child)) {
			$child = new Libs_MenuElement($child, $items);
		}
		// Ajout du tag UL si c'est un nouveau parent
		if (empty($this->child)) {
			$this->addTags("ul");
		}
		// Ajoute la classe parent
		$this->addAttributs("class", "parent");
		// Ajoute la classe element
		if ($this->data->parent_id > 0) {
			$this->addAttributs("class", "item" . $this->data->menu_id);
		}
		$this->child[$child->data->menu_id] = &$child;
	}
	
	/**
	 * Supprime un enfant
	 * 
	 * @param $child Libs_MenuElement or array - object
	 * @param $items array - object
	 */
	public function removeChild(&$child = "", &$items = array()) {
		if (empty($child)) {
			foreach($this->child as $key => $child) {
				unset($this->child[$key]);
			}
		} else {
			if (!is_object($child)) {
				$child = &$items[$child->menu_id];
			}
			unset($this->child[$child->data->menu_id]);
		}
	}
	
	/**
	 * Convertie la classe en chaine de caratère
	 * 
	 * @param $callback
	 */
	public function &toString($callback = "") {		
		// Mise en forme du texte via la callback
		$text = $this->data->content;
		if (!empty($callback) && !empty($text)) {
			$text = Core_Loader::callback($callback, $text);
		}
		
		// Ajout de la classe active
		if (isset($this->route) && Exec_Utils::inArray($this->data->menu_id, $this->route)) {
			$this->addAttributs("class", "active");
			Libs_Breadcrumb::getInstance()->addTrail($text);
		}
		
		// Préparation des données
		$out = "";
		$end = "";
		$attributs = $this->gétattributs();
		$text = "<span>" . $text . "</span>";		
		
		// Extraction des balises de débuts et de fin et ajout du texte
		foreach($this->tags as $tag) {
			$out .= "<" . $tag . $attributs . ">" . $text;
			$text = "";
			$end = $end . "</" . $tag . ">";
		}
		
		// Constuction des branches
		if (!empty($this->child)) {			
			foreach($this->child as $child) {
				$child->route = $this->route;
				$out .= $child->toString($callback);
			}
		}
		
		// Ajout des balises de fin
		$out .= $end;
		return $out;
	}
	
	/**
	 * Destructeur propre
	 */
	public function __destruct() {
		$this->item = array();
		$this->removeAttributs();
		$this->removeChild();
	}
}


?>