<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de menu
 * 
 * @author Sebastien Villemain
 *
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
	 * Construction du menu
	 * 
	 * @param $identifier Identifiant du menu par exemple "block22"
	 * @param $sql array
	 */
	public function __construct($identifier, $sql = array()) {
		$this->identifier = $identifier;
		
		Core_CacheBuffer::setSectionName("menus");
		if ($this->isCached()) {
			$this->loadFromCache();
		} else if (count($sql) >= 3) {
			$this->loadFromDb($sql);
		}
		
		$this->structure();
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
	 * @param $sql array parametre de selection
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
				$this->items[$key] = new Libs_MenuElement($item, $this->items);
			}
			
			Core_CacheBuffer::writingCache(
				$this->identifier . ".php", 
				"$" . Core_CacheBuffer::getSectionName() . " = \"" . Exec_Entities::addSlashes(serialize($this->items)) . "\""
			);
		}
	}
	
	private function structure() {
		$outPut = "";
		$count = count($this->items);
		foreach($this->items as $key => $item) {
			if ($item->data->parent_id == 0) {
				$outPut .= $this->toString($item);
			}
		}
	}
	
	/**
	 * Return a well-formed XML string based on SimpleXML element
	 *
	 * @return string
	 */
	// TODO a utiliser pour finalisation 
	function toString($whitespace=true)
	{
		//Start a new line, indent by the number indicated in $this->level, add a <, and add the name of the tag
		if ($whitespace) {
			$out = "\n".str_repeat("\t", $this->_level).'<'.$this->_name;
		} else {
			$out = '<'.$this->_name;
		}

		//For each attribute, add attr="value"
		foreach($this->_attributes as $attr => $value) {
			$out .= ' '.$attr.'="'.htmlspecialchars($value).'"';
		}

		//If there are no children and it contains no data, end it off with a />
		if (empty($this->_children) && empty($this->_data)) {
			$out .= " />";
		}
		else //Otherwise...
		{
			//If there are children
			if(!empty($this->_children))
			{
				//Close off the start tag
				$out .= '>';

				//For each child, call the asXML function (this will ensure that all children are added recursively)
				foreach($this->_children as $child)
					$out .= $child->toString($whitespace);

				//Add the newline and indentation to go along with the close tag
				if ($whitespace) {
					$out .= "\n".str_repeat("\t", $this->_level);
				}
			}

			//If there is data, close off the start tag and add the data
			elseif(!empty($this->_data))
				$out .= '>'.htmlspecialchars($this->_data);

			//Add the end tag
			$out .= '</'.$this->_name.'>';
		}

		//Return the final output
		return $out;
	}
}

/**
 * Membre d'un menu
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_MenuElement {
	
	/**
	 * Item info du menu
	 * 
	 * @var array - object
	 */
	public $data = array();
	
	private $attributs = array();
	
	private $child = array();
	
	/**
	 * Construction de l'element du menu
	 * 
	 * @param $item array - object
	 */
	public function __construct($item, &$items) {
		// Ajout des infos de l'item
		$this->data = $item;
		
		// Recherche d'enfant
		if ($item->parent_id > 0) {
			$this->addAttributs("class", "item" . $item->menu_id);
			$items[$item->parent_id]->addChild($this);
		} else {
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
		if ($name != "") {
			$this->attributs[$name] = $value;
		}
	}
	
	/**
	 * Supprime un attributs
	 * 
	 * @param $nameString nom de l'attribut
	 */
	public function removeAttributs($name = "") {
		if ($name != "") {
			unset($this->attributs[$name]);
		} else {
			foreach($this->attributs as $key => $attributs) {
				unset($this->attributs[$key]);
			}
		}
	}
	
	/**
	 * Ajoute un enfant a l'item courant
	 * 
	 * @param $child Libs_MenuElement or array - object
	 * @param $items array - object
	 */
	public function &addChild(&$child, &$items = array()) {
		if (!is_object($child)) {
			$child = new Libs_MenuElement($child, $items);
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
	
	public function __destruct() {
		$this->item = array();
		$this->removeAttributs();
		$this->removeChild();
	}
}


?>