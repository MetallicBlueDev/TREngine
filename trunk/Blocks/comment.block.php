<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block login, accès rapide à une connexion, à une déconnexion et à son compte.
 *
 * @author Sébastien Villemain
 */
class Block_Comment extends BlockModel {

    private $displayOnModule = array();

    private function configure() {
        list($displayOnModule) = explode('|', $this->getBlockData()->getContent());
        $this->displayOnModule = explode('>:>', $displayOnModule); // on r�cup�re une chaine sous forme monModule>:>monModule2
    }

    private function &render() {
        $content = "";
        return $content;
    }

    public function display() {
        $this->configure();
        // Si le module courant fait partie de la liste des affichages
        if (ExecUtils::inArray(LibModule::getInstance()->getInfoModule()->getName(), $this->displayOnModule)) {
            // Si la position est interieur au module (moduletop ou modulebottom)
            if ($this->getBlockData()->getSide() == 5 || $this->getBlockData()->getSide() == 6) {
                echo $this->render();
            }
        }
    }

    public function install() {

    }

    public function uninstall() {

    }

}

?>