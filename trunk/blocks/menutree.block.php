<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../engine/core/secure.class.php");
    new Core_Secure();
}

// R�solution de la dépendance du block menu
Core_Loader::classLoader("Block_Menu");

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

        Core_Loader::classLoader("Exec_JQuery");
        Exec_JQuery::getTreeView("#block" . $this->getBlockData()->getId());
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>