<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

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

        $libsMakeStyle = new Libs_MakeStyle();
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

        Exec_JQuery::getTreeView("#block" . $this->getBlockData()->getId());
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>