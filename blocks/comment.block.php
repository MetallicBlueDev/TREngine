<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../engine/core/secure.class.php");
    new Core_Secure();
}

/**
 * Block login, accès rapide à une connexion, à une déconnexion et à son compte.
 *
 * @author Sebastien Villemain
 */
class Block_Comment extends Block_Model {

    private $displayOnModule = array();

    private function configure() {
        list($displayOnModule) = explode('|', $this->content);
        $this->displayOnModule = explode('>:>', $displayOnModule); // on r�cup�re une chaine sous forme monModule>:>monModule2
    }

    private function &render() {
        $content = "";
        return $content;
    }

    public function display() {
        $this->configure();
        // Si le module courant fait partie de la liste des affichages
        if (Core_Loader::isCallable("Libs_Module") && Exec_Utils::inArray(Libs_Module::$module, $this->displayOnModule)) {
            // Si la position est interieur au module (moduletop ou modulebottom)
            if ($this->side == 5 || $this->side == 6) {
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