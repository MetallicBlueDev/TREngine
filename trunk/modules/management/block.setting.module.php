<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../../engine/core/secure.class.php");
    new Core_Secure();
}

class Module_Management_Block extends Libs_ModuleModel {

    public function setting() {
        Core_Loader::classLoader("Libs_Block");
        $localView = Core_Request::getWord("localView");

        // Affichage et traitement
        $content = "";
        switch ($localView) {
            case "sendMoveUp":
                $this->sendMoveUp();
                $content .= $this->tabHome();
                break;
            case "sendMoveDown":
                $this->sendMoveDown();
                $content .= $this->tabHome();
                break;
            case "sendDelete":
                $this->sendDelete();
                $content .= $this->tabHome();
                break;
            case "sendCopy":
                $this->sendCopy();
                $content .= $this->tabEdit(Core_Sql::getInstance()->insertId());
                break;
            case "tabEdit":
                $content .= $this->tabEdit();
                break;
            case "tabAdd":
                $content .= $this->tabAdd();
                break;
            default:
                $content .= $this->tabHome();
        }

        if (Core_Main::isFullScreen()) {
            return "<div id=\"block_main_setting\">"
            . $content . "</div>";
        }
        return $content;
    }

    private function tabHome() {
        Core_Loader::classLoader("Libs_Rack");
        $firstLine = array(
            array(
                35,
                BLOCK_TITLE),
            array(
                20,
                BLOCK_TYPE),
            array(
                10,
                BLOCK_SIDE),
            array(
                5,
                BLOCK_POSITION),
            array(
                10,
                BLOCK_ACCESS),
            array(
                20,
                BLOCK_VIEW_MODULE_PAGE)
        );
        $rack = new Libs_Rack($firstLine);

        Core_Sql::getInstance()->select(
        Core_Table::$BLOCKS_TABLE, array(
            "block_id",
            "side",
            "position",
            "title",
            "content",
            "type",
            "rank",
            "mods"), array(), array(
            "position")
        );
        if (Core_Sql::getInstance()->affectedRows() > 0) {
            while ($row = Core_Sql::getInstance()->fetchArray()) {
                // Parametre de la ligne
                $title = "<a href=\"" . Core_Html::getLink("?mod=management&manage=block&localView=tabEdit&blockId=" . $row['block_id']) . "\">" . $row['title'] . "</a>";
                $type = $row['type'];
                $side = Libs_Block::getLitteralSide($row['side']);
                $position = Core_Html::getLinkForBlock("?mod=management&manage=block&localView=sendMoveUp&blockId=" . $row['block_id'], "?mod=management&manage=block&localView=sendMoveUp&blockId=" . $row['block_id'], "#block_main_setting", "^"
                );
                $position .= $row['position'];
                $position .= Core_Html::getLinkForBlock("?mod=management&manage=block&localView=sendMoveDown&blockId=" . $row['block_id'], "?mod=management&manage=block&localView=sendMoveDown&blockId=" . $row['block_id'], "#block_main_setting", "v"
                );
                $rank = Core_Access::getLitteralRank($row['rank']);
                $mods = ($row['mods'] == "all") ? BLOCK_ALL_PAGE : BLOCK_VARIES_PAGE;
                // Ajout de la ligne au tableau
                $rack->addLine(array(
                    $title,
                    $type,
                    $side,
                    $position,
                    $rank,
                    $mods));
            }
        }

        Module_Management_Index::addAddButtonInToolbar("localView=tabAdd");
        return $rack->render();
    }

    private function sendMoveUp() {
        $blockId = Core_Request::getInt("blockId", -1);

        if ($blockId > -1) { // Si l'id semble valide
            Core_Sql::getInstance()->select(
            Core_Table::$BLOCKS_TABLE, array(
                "side",
                "position"), array(
                "block_id = '" . $blockId . "'")
            );
            if (Core_Sql::getInstance()->affectedRows() > 0) { // Si le block existe
                $blockMove = Core_Sql::getInstance()->fetchArray(); // Récuperation des informations sur le block

                if ($blockMove['position'] > 0) {
                    // Requête de Sélection des autres blocks
                    Core_Sql::getInstance()->select(
                    Core_Table::$BLOCKS_TABLE, array(
                        "block_id",
                        "position"), array(
                        "side = '" . $blockMove['side'] . "' AND",
                        "(position = '" . $blockMove['position'] . "' OR position = '"
                        . ($blockMove['position'] - 1) . "')")
                    );
                    if (Core_Sql::getInstance()->affectedRows() > 0) {
                        Core_Sql::getInstance()->addArrayBuffer("blockMoveUp");
                        // Mise à jour de position
                        while ($row = Core_Sql::getInstance()->fetchBuffer("blockMoveUp")) {
                            $row['position'] = ($row['block_id'] == $blockId) ? $row['position'] - 1 : $row['position'] + 1;

                            Core_Sql::getInstance()->update(
                            Core_Table::$BLOCKS_TABLE, array(
                                "position" => $row['position']), array(
                                "block_id = '" . $row['block_id'] . "'")
                            );
                        }
                        Core_Logger::addInformationMessage(DATA_SAVED);
                    }
                }
            } else {
                Core_Logger::addInformationMessage(DATA_INVALID);
            }
        } else {
            Core_Logger::addInformationMessage(DATA_INVALID);
        }
    }

    private function sendMoveDown() {
        $blockId = Core_Request::getInt("blockId", -1);

        if ($blockId > -1) { // Si l'id semble valide
            Core_Sql::getInstance()->select(
            Core_Table::$BLOCKS_TABLE, array(
                "side",
                "position"), array(
                "block_id = '" . $blockId . "'")
            );
            if (Core_Sql::getInstance()->affectedRows() > 0) { // Si le block existe
                $blockMove = Core_Sql::getInstance()->fetchArray(); // Récuperation des informations sur le block
                // Sélection du block le plus bas
                Core_Sql::getInstance()->select(
                Core_Table::$BLOCKS_TABLE, array(
                    "block_id",
                    "position"), array(
                    "side = '" . $blockMove['side'] . "'"), array(
                    "position DESC"), "1"
                );

                if (Core_Sql::getInstance()->affectedRows() > 0) {
                    $blockDown = Core_Sql::getInstance()->fetchArray();

                    if ($blockMove['position'] < $blockDown['position']) {
                        // Requête de Sélection des autres blocks
                        Core_Sql::getInstance()->select(
                        Core_Table::$BLOCKS_TABLE, array(
                            "block_id",
                            "position"), array(
                            "side = '" . $blockMove['side'] . "' AND",
                            "(position = '" . $blockMove['position'] . "' OR position = '"
                            . ($blockMove['position'] + 1) . "')")
                        );
                        if (Core_Sql::getInstance()->affectedRows() > 0) {
                            Core_Sql::getInstance()->addArrayBuffer("blockMoveDown");
                            // Mise à jour de position
                            while ($row = Core_Sql::getInstance()->fetchBuffer("blockMoveDown")) {
                                $row['position'] = ($row['block_id'] == $blockId) ? $row['position'] + 1 : $row['position'] - 1;

                                Core_Sql::getInstance()->update(
                                Core_Table::$BLOCKS_TABLE, array(
                                    "position" => $row['position']), array(
                                    "block_id = '" . $row['block_id'] . "'")
                                );
                            }
                            Core_Logger::addInformationMessage(DATA_SAVED);
                        }
                    }
                }
            } else {
                Core_Logger::addInformationMessage(DATA_INVALID);
            }
        } else {
            Core_Logger::addInformationMessage(DATA_INVALID);
        }
    }

    private function tabEdit($blockId = -1) {
        if ($blockId < 0) {
            $blockId = Core_Request::getInt("blockId", -1);
        }

        if ($blockId > -1) { // Si l'id semble valide
            Core_Sql::getInstance()->select(
            Core_Table::$BLOCKS_TABLE, array(
                "side",
                "position",
                "title",
                "content",
                "type",
                "rank",
                "mods"), array(
                "block_id = '" . $blockId . "'")
            );
            if (Core_Sql::getInstance()->affectedRows() > 0) { // Si le block existe
                $block = Core_Sql::getInstance()->fetchArray();
                Libs_Breadcrumb::getInstance()->addTrail($block['title'], "?mod=management&manage=block&localView=tabEdit&blockId=" . $blockId);

                Core_Loader::classLoader("Libs_Form");
                $form = new Libs_Form("management-block-blockedit");
                $form->setTitle(BLOCK_EDIT_TITLE);
                $form->setDescription(BLOCK_EDIT_DESCRIPTION);
                $form->addSpace();

                $form->addHtmlInFieldset("ID : #" . $blockId);
                $form->addInputText("blockTitle", BLOCK_TITLE, $block['title']);

                $blockList = Libs_Block::listBlocks();
                $form->addSelectOpenTag("blockType", BLOCK_TYPE);
                $form->addSelectItemTag($block['type'], "", true);
                foreach ($blockList as $blockType) {
                    if ($blockType == $block['type'])
                        continue;
                    $form->addSelectItemTag($blockType);
                }
                $form->addSelectCloseTag();

                $sideList = Libs_Block::listSide();
                $form->addSelectOpenTag("blockSide", BLOCK_SIDE);
                $currentSideName = "";
                foreach ($sideList as $blockSide) {
                    if ($blockSide['numeric'] == $block['side']) {
                        $currentSideName = $blockSide['letters'];
                        continue;
                    }
                    $form->addSelectItemTag($blockSide['numeric'], $blockSide['numeric'] . " " . $blockSide['letters']);
                }
                $form->addSelectItemTag($block['side'], $block['side'] . " " . $currentSideName, true);
                $form->addSelectCloseTag();
                // TODO rafraichir la liste des ordres (position) suivant la liste des positions (side)
                $form->addInputText("blockTitle", BLOCK_POSITION, $block['position']);

                $rankList = Core_Access::listRanks();
                $form->addSelectOpenTag("blockRank", BLOCK_ACCESS);
                $currentRankName = "";
                foreach ($rankList as $blockRank) {
                    if ($blockRank['numeric'] == $block['rank']) {
                        $currentRankName = $blockRank['letters'];
                        continue;
                    }
                    $form->addSelectItemTag($blockRank['numeric'], $blockRank['numeric'] . " " . $blockRank['letters']);
                }
                $form->addSelectItemTag($block['rank'], $block['rank'] . " " . $currentRankName, true);
                $form->addSelectCloseTag();
                // TODO faire une liste cliquable avec un bouton radio "toutes les pages" et "aucune page" (= rank -1)
                $form->addInputText("blockTitle", BLOCK_VIEW_MODULE_PAGE, $block['mods']);

                $form->addInputText("blockTitle", "content", $block['content']);

                $position .= Core_Html::getLinkForBlock("?mod=management&manage=block&localView=movedown&blockId=" . $row['block_id'], "?mod=management&manage=block&localView=movedown&blockId=" . $row['block_id'], "#block_main_setting", "v"
                );
                Module_Management_Index::addDeleteButtonInToolbar("localView=sendDelete&blockId=" . $blockId);
                Module_Management_Index::addCopyButtonInToolbar("localView=sendCopy&blockId=" . $blockId);
                Module_Management_Index::addEditButtonInToolbar("localView=tabAdd", PREVIEW);
                Module_Management_Index::addAddButtonInToolbar("localView=tabAdd");
                return $form->render();
            } else {
                Core_Logger::addInformationMessage(DATA_INVALID);
            }
        } else {
            Core_Logger::addInformationMessage(DATA_INVALID);
        }
        return "";
    }

    private function sendDelete() {
        $blockId = Core_Request::getInt("blockId", -1);

        if ($blockId > -1) { // Si l'id semble valide
            Core_Sql::getInstance()->select(
            Core_Table::$BLOCKS_TABLE, array(
                "type"), array(
                "block_id = '" . $blockId . "'")
            );
            if (Core_Sql::getInstance()->affectedRows() > 0) { // Si le block existe
                $block = Core_Sql::getInstance()->fetchArray();

                $blockClassName = "Block_" . ucfirst($block['type']);
                $loaded = Core_Loader::classLoader($blockClassName);

                if ($loaded) {
                    if (Core_Loader::isCallable($blockClassName, "uninstall")) {
                        $BlockClass = new $blockClassName();
                        $BlockClass->uninstall();
                    }
                }

                Core_Sql::getInstance()->delete(
                Core_Table::$BLOCKS_TABLE, array(
                    "block_id = '" . $blockId . "'")
                );
                Core_Translate::removeCache("blocks/" . $block['type']);
                Core_Logger::addInformationMessage(DATA_DELETED);
            } else {
                Core_Logger::addInformationMessage(DATA_INVALID);
            }
        } else {
            Core_Logger::addInformationMessage(DATA_INVALID);
        }
    }

    private function sendCopy() {
        $blockId = Core_Request::getInt("blockId", -1);

        if ($blockId > -1) { // Si l'id semble valide
            $keys = array(
                "side",
                "position",
                "title",
                "content",
                "type",
                "rank",
                "mods");
            Core_Sql::getInstance()->select(
            Core_Table::$BLOCKS_TABLE, $keys, array(
                "block_id = '" . $blockId . "'")
            );
            if (Core_Sql::getInstance()->affectedRows() > 0) { // Si le block existe
                $block = Core_Sql::getInstance()->fetchArray();
                $block['title'] = $block['title'] . " Copy";
                Core_Sql::getInstance()->insert(
                Core_Table::$BLOCKS_TABLE, $keys, $block
                );
                Core_Logger::addInformationMessage(DATA_COPIED);
            } else {
                Core_Logger::addInformationMessage(DATA_INVALID);
            }
        } else {
            Core_Logger::addInformationMessage(DATA_INVALID);
        }
    }

    private function tabAdd() {
        Libs_Breadcrumb::getInstance()->addTrail(ADD, "?mod=management&manage=block&localView=tabAdd");
    }

    private function sendAdd() {

    }

}

?>