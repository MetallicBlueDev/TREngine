<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de menu style jdMenu by Jonathan Sharp
 *
 * @author Sébastien Villemain
 */
class Block_Menujd extends Block_Menu {

    public function display() {
        Exec_JQuery::getJdMenu();

        $menus = $this->getMenu();
        if (CoreHtml::getInstance()->javascriptEnabled()) {
            $menus->addAttributs("class", "jd_menu" . (($this->getBlockData()->getSide() == 1 || $this->getBlockData()->getSide() == 2) ? " jd_menu_vertical" : ""));
        }

        $libsMakeStyle = new LibsMakeStyle();
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