<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Management_Block extends Module_Model {
	public function setting() {
		Core_Loader::classLoader("Libs_Rack");
		Core_Loader::classLoader("Libs_Block");
		
		$localView = Core_Request::getWord("localView", "", "POST");
		
		if (!empty($localView)) {
			switch($localView) {
				case "sendGeneral":
					$this->sendGeneral();
					break;
				case "sendSystem":
					$this->sendSystem();
					break;
			}
		} else {
			return $this->tabHome();
		}
		return "";
	}
	
	private function tabHome() {
		$firstLine = array(
			array(20, BLOCK_TYPE),
			array(35, BLOCK_TITLE),
			array(10, BLOCK_SIDE),
			array(5, BLOCK_POSITION),
			array(10, BLOCK_ACCESS),
			array(20, BLOCK_VIEW_MODULE_PAGE)
		);
		$rack = new Libs_Rack($firstLine);
		
		Core_Sql::select(Core_Table::$BLOCKS_TABLE, array("block_id", "side", "position", "title", "content", "type", "rang", "mods"));
		if (Core_Sql::affectedRows() > 0) {
			while ($row = Core_Sql::fetchArray()) {
				// Parametre de la ligne
				$type = $row['type'];
				$title = "<a href=\"?mod=management&manage=block&localView=edit&blockid=" . $row['block_id'] . "\">" . $row['title'] . "</a>";
				$side = Libs_Block::getLitteralSide($row['side']);
				$position = "<a href=\"?mod=management&manage=block&localView=moveup\">^</a>" . $row['position']
				. "<a href=\"?mod=management&manage=block&localView=movedown\">v</a>";
				$rang = Core_Access::getLitteralRang($row['rang']);
				$mods = ($row['mods'] == "all") ? BLOCK_ALL_PAGE : BLOCK_VARIES_PAGE;
				// Ajout de la ligne au tableau
				$rack->addLine(array($type, $title, $side, $position, $rang, $mods));
			}
		}
		return $rack->render();
	}
}

?>