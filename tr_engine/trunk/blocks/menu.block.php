<?php

class Block_Menu extends Block_Model {
	
	/**
	 * TAG pour les options
	 * 
	 * @var String
	 */
	private static $optionsTag = "__OPTIONS__";
	
	/**
	 * Id du block menu actif
	 * 
	 * @var int
	 */
	private $blockIdFocused = 0;
	
	public function display() {
		
		$this->getMenu();
		
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("blockTitle", $this->title);
		$libsMakeStyle->assign("blockContent", "");
		$libsMakeStyle->display($this->templateName);
	}
	
	/**
	 * Configure l'id du block menu actif
	 */
	private function init() {
		if (is_numeric($this->content)) {
			$this->blockIdFocused = $this->content;
		} else {
			$this->blockIdFocused = $this->blockId;
		}
	}
	
	public function getMenu() {
		$this->init();
		
		Core_Loader::classLoader("Libs_Menu");
		$menus = new Libs_Menu(
			"block" . $this->blockIdFocused,
			array(
				"table" => Core_Table::$MENUS_TABLES,
				"select" => array("menu_id", "block_id", "parent_id", "content", "sublevel", "position", "rang"),
				"where" => array("block_id = '" . $this->blockIdFocused . "'"),
				"orderby" => array("sublevel", "parent_id", "position"),
				"limit" => array()
			)
		);
	}
	
	/**
	 * Retourne une ligne de menu propre sous forme HTML
	 * 
	 * Exemple :
	 * Link example__OPTIONS__B.I.U.A.?mod=home__OPTIONS__
	 * 
	 * @param $line String
	 * @return String
	 */
	public function getLine($line) {
		$outPut = "";
		if (preg_match("/(.+)" . self::$optionsTag . "(.*?)" . self::$optionsTag . "/", $line, $matches)) {
			// Conversion du texte
			$text = Exec_Entities::textDisplay($matches[1]);
			
			// Recherche des options et style
			$options = explode(".", $matches[2]);
			foreach($options as $key => $value) {
				if ($value == "B") $bold = 1;
				if ($value == "I") $italic = 1;
				if ($value == "U") $underline = 1;
				if ($value == "BIG") $big = 1;
				if ($value == "SMALL") $small = 1;
				if ($value == "A") $link = $matches[2][$key+1];
				if ($value == "POPUP") $popup = 1;
			}
			
			// Application des options et styles
			if ($bold) $text = "<b>" . $text . "</b>";
			if ($italic) $text = "<i>" . $text . "</i>";
			if ($underline) $text = "<u>" . $text . "</u>";
			if ($big) $text = "<big>" . $text . "</big>";
			if ($small) $text = "<small>" . $text . "</small>";
			if ($link != "") $text = "<a href=\"" . (($popup) ? "javascript:window.open('" . $link . "')" : $link) . "\" alt=\"\" title=\"\">" . $text . "</a>";
			
			$outPut = $text;
		} else {
			// Aucun style appliqué
			// Conversion du texte
			$outPut = Exec_Entities::textDisplay($line);
		}
		return $outPut;
	}
	
	/**
	 * Retourne une ligne avec les TAGS
	 * 
	 * @param $text String Texte du menu
	 * @param $options array Options choisis
	 * @return String
	 */
	public function setLine($text, $options = array()) {
		$optionsString = "";
		
		// Formate les options
		foreach($options as $key => $value) {
			// Les options sont uniquement en majuscule
			$key = strtoupper($key);
			
			if (($key == "B")) $optionsString .= "B";
			if (($key == "I")) $optionsString .= "I";
			if (($key == "I")) $optionsString .= "U";
			if (($key == "BIG")) $optionsString .= "BIG";
			if (($key == "SMALL")) $optionsString .= "SMALL";
			if (($key == "I")) $optionsString .= "A." . $value;
			if (($key == "POPUP")) $optionsString .= "POPUP";
		}
		
		// Termine le tag des options
		if ($optionsString != "") $optionsString = self::$optionsTag . $optionsString . self::$optionsTag;
		
		return $text . $optionsString;
	}
}


?>