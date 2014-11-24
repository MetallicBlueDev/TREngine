<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de menu style jdMenu by Jonathan Sharp
 *
 * @author Sébastien Villemain
 */
class Block_Menujd extends Block_Menu {

    public function display() {
        ExecJQuery::getJdMenu();

        $menus = $this->getMenu();
        if (CoreHtml::getInstance()->javascriptEnabled()) {
            $menus->addAttributs("class", "jd_menu" . (($this->getBlockData()->getSide() == 1 || $this->getBlockData()->getSide() == 2) ? " jd_menu_vertical" : ""));
        }

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libMakeStyle->assign("blockContent", $menus->render());
        $libMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>