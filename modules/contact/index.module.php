<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

class Module_Contact_Index extends Module_Model {

    public function display() {
        echo "Si vous souhaitez me contacter, le plus simple est de m'envoyer un e-mail (<a href=\"mailto:villemain.s_AT_gmail.com\">gmail.com</a>)"
        . "<br />Retrouvez mes coordonn&#233;es compl&#232;te sur <a href=\"?mod=profilcv\">mon profil</a>.";
    }

    public function setting() {
        return "Pas de setting...";
    }

}

?>