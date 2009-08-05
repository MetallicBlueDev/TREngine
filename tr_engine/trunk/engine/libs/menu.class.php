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
	private $menuIdentifier = "";
	
	/**
	 * Menu complet et son arborescence
	 * 
	 * @var array
	 */
	private $items = array();
	
	/**
	 * Construction du menu
	 * 
	 * @param $menuIdentifier Identifiant du menu par exemple "block22"
	 * @param $sql array
	 */
	public function __construct($menuIdentifier, $sql = array()) {
		$this->menuIdentifier = $menuIdentifier;
		
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
		$data = Core_CacheBuffer::getCache($this->menuIdentifier . ".php");
		$this->items = unserialize(Exec_Entities::stripSlashes($data));
	}
	
	/**
	 * Vérifie la présence du cache
	 * 
	 * @return boolean
	 */
	private function isCached() {
		return (Core_CacheBuffer::cached($this->menuIdentifier . ".php"));
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
			Core_Sql::addBuffer($this->menuIdentifier, "menu_id");
			$menus = Core_Sql::getBuffer($this->menuIdentifier);
			
			foreach($menus as $key => $menu) {
				$parentRoute = "";
				$parentTree = array();
				
				// Get parent information
				$parentId = $menus[$key]->parent_id;
				if (isset($menus[$parentId]) && isset($menus[$parentId]->route) && isset($menus[$parentId]->tree)) {
					$parentRoute = $menus[$parentId]->route . "/";
					$parentTree  = $menus[$parentId]->tree;
				}
				
				// Create tree
				$parentTree[] = $menus[$key]->menu_id;
				$menus[$key]->tree = $parentTree;
			
				// Create route
				$menus[$key]->route = $parentRoute . str_replace(" ", "-", $menus[$key]->content);
			}
			
			Core_CacheBuffer::writingCache(
				$this->menuIdentifier . ".php", 
				"$" . Core_CacheBuffer::getSectionName() . " = \"" . Exec_Entities::addSlashes(serialize($menus)) . "\""
			);
			$this->items = $menus;
		}
	}
	
	// TODO a supprimer
	private function get() {
		// establish the hierarchy of the menu
		$children = array();
		
		// first pass - collect children
		$cacheIndex = array();
		foreach ($this->items as $index => $item) {
			if ($item['rang'] <= Core_Session::$userRang)  {
				$list = (isset($children[$item['parent']])) ? $children[$item['parent']] : array();
				$list[] = $item;
				$children[$item['parent']] = $list;
			}
			$cacheIndex[$item['menu_id']] = $index;
		}
		
		// second pass - collect 'open' menus
		$itemId = Core_Request::getInt("itemid", 0);
		$open = array($itemId);
		$count = 20; // maximum levels - to prevent runaway loop
		
		while (--$count) {
			if (isset($cacheIndex[$itemId])) {				
				if (isset($this->items[$cacheIndex[$id]]) && $this->items[$cacheIndex[$id]]['parent'] > 0) {
					$open[] = $this->items[$cacheIndex[$id]]['parent'];
				} else {
					break;
				}
			}
		}
		
		$this->recurse(0, 0, $children, $open);
	}
	
	// TODO a supprimer
	private function recurse($niveau, $level, &$children, &$open) {
		if (isset($children[0])) {
			$niveau = min($level, count($indents) - 1);
			// Séparateur de menu haut
			//echo "\n" . $indents[$niveau][0];
			
			foreach ($children[0] as $row) {
				// Ouverture balise
				//echo "\n" . $indents[$niveau][1];
				
				// Contenu TXT
				//echo mosGetMenuLink($row, $level, $params, $open);
				
				// show menu with menu expanded - submenus visible
				if (in_array($row->id, $open)) {
					$this->recurse($row['menu_id'], $level + 1, $children, $open);
				}
				// Fermeture balise
				//echo $indents[$niveau][2];
			}
			// Séparateur de menu bas
			//echo "\n" . $indents[$niveau][3];
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


?>