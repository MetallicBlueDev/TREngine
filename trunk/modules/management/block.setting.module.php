<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Management_Block extends Module_Model {
	public function setting() {
		Core_Loader::classLoader("Libs_Block");
		
		// Traitement
		$localView = Core_Request::getWord("localView");
		switch($localView) {
			case "moveup":
				$this->sendMove("up");
				break;
			case "movedown":
				$this->sendMove("down");
				break;
		}
		
		$content = "";
		if (Core_Main::isFullScreen()) {
			$content .= "<div id=\"block_main_setting\">";
		}
		// Affichage
		switch($localView) {
			case "edit":
				$content .= $this->tabEdit();
				break;
			default:
				$content .= $this->tabHome();
				break;
		}
		if (Core_Main::isFullScreen()) {
			$content .= "</div>";
		}
		return $content;
	}
	
	private function tabHome() {
		Core_Loader::classLoader("Libs_Rack");
		$firstLine = array(
			array(20, BLOCK_TYPE),
			array(35, BLOCK_TITLE),
			array(10, BLOCK_SIDE),
			array(5, BLOCK_POSITION),
			array(10, BLOCK_ACCESS),
			array(20, BLOCK_VIEW_MODULE_PAGE)
		);
		$rack = new Libs_Rack($firstLine);
		
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE, 
			array("block_id", "side", "position", "title", "content", "type", "rang", "mods"),
			array(),
			array("position")
		);
		if (Core_Sql::affectedRows() > 0) {
			while ($row = Core_Sql::fetchArray()) {
				// Parametre de la ligne
				$type = $row['type'];
				$title = "<a href=\"" . Core_Html::getLink("?mod=management&manage=block&localView=edit&blockId=" . $row['block_id']) . "\">" . $row['title'] . "</a>";
				$side = Libs_Block::getLitteralSide($row['side']);
				$position = Core_Html::getLinkForBlock("?mod=management&manage=block&localView=moveup&blockId=" . $row['block_id'],
					"?mod=management&manage=block&localView=moveup&blockId=" . $row['block_id'],
					"#block_main_setting",
					"^"
				);
				$position .= $row['position'];
				$position .= Core_Html::getLinkForBlock("?mod=management&manage=block&localView=movedown&blockId=" . $row['block_id'],
					"?mod=management&manage=block&localView=movedown&blockId=" . $row['block_id'],
					"#block_main_setting",
					"v"
				);
				$rang = Core_Access::getLitteralRang($row['rang']);
				$mods = ($row['mods'] == "all") ? BLOCK_ALL_PAGE : BLOCK_VARIES_PAGE;
				// Ajout de la ligne au tableau
				$rack->addLine(array($type, $title, $side, $position, $rang, $mods));
			}
		}
		return $rack->render();
	}
	
	private function sendMove($move) {
		$blockId = Core_Request::getInt("blockId", -1);
		
		if ($blockId > -1) { // Si l'id semble valide
			Core_Sql::select(
				Core_Table::$BLOCKS_TABLE,
				array("side", "position"),
				array("block_id = '" . $blockId . "'")
			);
			if (Core_Sql::affectedRows() > 0) { // Si le block existe
				$blockMove = Core_Sql::fetchArray(); // Récuperation des informations sur le block
				
				if (($move != "up") || ($blockMove['position'] > 0 && $move == "up")) {
					// Requête de selection des autres blocks
					$where = "position = '" . $blockMove['position'] . "' OR position = '"
					. (($move == "up") ? ($blockMove['position'] - 1) : ($blockMove['position'] + 1)) . "'";
					Core_Sql::select(
						Core_Table::$BLOCKS_TABLE,
						array("block_id", "position"),
						array("side = '" . $blockMove['side'] . "' AND", $where)
					);
					if (Core_Sql::affectedRows() > 0) {
						// Mise à jour de position
						while ($row = Core_Sql::fetchArray()) {
							if ($move == "up") {
								$row['position'] = ($row['block_id'] == $blockId) ? $row['position'] - 1 : $row['position'] + 1;
							} else {
								$row['position'] = ($row['block_id'] == $blockId) ? $row['position'] + 1 : $row['position'] - 1;
							}
							// Vérification de la position la plus haute
							$row['position'] = ($row['position'] >= 0) ? $row['position'] : 0;
							Core_Sql::update(
								Core_Table::$BLOCKS_TABLE,
								array("position" => $row['position']),
								array("block_id = '" . $row['block_id'] . "'")
							);
						}
						Core_Exception::addInfoError(DATA_SAVED);
					}
				}
			}
		}
	}
	
	private function tabEdit() {
		$blockId = Core_Request::getInt("blockId", -1);
		
		if ($blockId > -1) { // Si l'id semble valide
			Core_Sql::select(
				Core_Table::$BLOCKS_TABLE,
				array("side", "position", "title", "content", "type", "rang", "mods"),
				array("block_id = '" . $blockId . "'")
			);
			if (Core_Sql::affectedRows() > 0) { // Si le block existe
				$block = Core_Sql::fetchArray();
				Core_Loader::classLoader("Libs_Form");
				
				$form = new Libs_Form("blockedit");
				$form->setTitle(BLOCK_EDIT_TITLE);
				$form->setDescription("ID #" . $blockId);
				$form->addSpace();
				$form->addInputText("blockTitle", BLOCK_TITLE, $block['title']);
				
				$form->addInputText("blockTitle", BLOCK_TYPE, $block['type']);
				$form->addInputText("blockTitle", BLOCK_SIDE, $block['side']);
				$form->addInputText("blockTitle", BLOCK_POSITION, $block['position']);
				$form->addInputText("blockTitle", BLOCK_ACCESS, $block['rang']);
				
				$form->addInputText("blockTitle", BLOCK_VIEW_MODULE_PAGE, $block['mods']);
				
				$form->addInputText("blockTitle", "content", $block['content']);
				
				$position .= Core_Html::getLinkForBlock("?mod=management&manage=block&localView=movedown&blockId=" . $row['block_id'],
					"?mod=management&manage=block&localView=movedown&blockId=" . $row['block_id'],
					"#block_main_setting",
					"v"
				);
				return $form->render();
			}
		}
		return "";
	}
}

?>