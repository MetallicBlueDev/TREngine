<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de menu style Menu treeview by
 *
 * @author Sébastien Villemain
 */
class Block_Menutree extends Block_Menu {

    public function display() {
        $this->configure();
        $menus = $this->getMenu();
        $menus->addAttributs("class", "treeview");

        $libsMakeStyle = new LibsMakeStyle();
        $libsMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libsMakeStyle->assign("blockContent", $menus->render());
        $libsMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    private function configure() {
        // Configure le style pour la classe
        $this->getBlockData()->setContent(strtolower($this->getBlockData()->getContent()));

        switch ($this->getBlockData()->getContent()) {
            case "black":
            case "red":
            case "gray":
            case "famfamfam":
                break;
            default:
                $this->getBlockData()->setContent("");
        }

        ExecJQuery::getTreeView("#block" . $this->getBlockData()->getId());
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>