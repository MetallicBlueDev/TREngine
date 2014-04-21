<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../engine/core/secure.class.php");
    new Core_Secure();
}

/**
 * Block de menu style jdMenu by Jonathan Sharp
 *
 * @author Sébastien Villemain
 */
class Block_Menujd extends Block_Menu {

    public function display() {
        Exec_JQuery::getJdMenu();

        $menus = $this->getMenu();
        if (Core_Html::getInstance()->isJavascriptEnabled()) {
            $menus->addAttributs("class", "jd_menu" . (($this->getBlockData()->getSide() == 1 || $this->getBlockData()->getSide() == 2) ? " jd_menu_vertical" : ""));
        }

        $libsMakeStyle = new Libs_MakeStyle();
        $libsMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libsMakeStyle->assign("blockContent", $menus->render());
        $libsMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>