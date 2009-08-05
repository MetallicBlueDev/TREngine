<?php

// Résolution de la dépendance du block menu
Core_Loader::blockLoader("menu");

class Block_Menutree extends Block_Menu {
	
	public function display() {		
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("blockTitle", $this->title);
		$libsMakeStyle->assign("blockContent", "vide pour le moment...");
		$libsMakeStyle->display($this->templateName);
	}
}


?>