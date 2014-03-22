<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../engine/core/secure.class.php");
    new Core_Secure();
}

// Résolution de la dépendance du block menu
Core_Loader::classLoader("Block_Menu");

/**
 * Block de menu style jdMenu by Jonathan Sharp
 *
 * @author Sébastien Villemain
 *
 */
class Block_Menujd extends Block_Menu {

    public function display() {
        Core_Loader::classLoader("Exec_JQuery");
        Exec_JQuery::getJdMenu();

        $menus = $this->getMenu();
        if (Core_Html::getInstance()->isJavascriptEnabled()) {
            $menus->addAttributs("class", "jd_menu" . (($this->side == 1 || $this->side == 2) ? " jd_menu_vertical" : ""));
        }

        $libsMakeStyle = new Libs_MakeStyle();
        $libsMakeStyle->assign("blockTitle", $this->title);
        $libsMakeStyle->assign("blockContent", $menus->render());
        $libsMakeStyle->display($this->templateName);
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>