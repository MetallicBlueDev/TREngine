<?php

class Block_Menu extends Block_Base {
	
	public function display($configs) {
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("blockTitle", $this->title);
		$libsMakeStyle->assign("blockContent", $this->content);
		$libsMakeStyle->display($this->templateName);
	}
}


?>