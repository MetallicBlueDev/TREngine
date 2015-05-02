<?php

namespace TREngine\Engine\Module\ModuleContact;

use TREngine\Engine\Module\ModuleModel;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleIndex extends ModuleModel {

    public function display() {
        echo "Si vous souhaitez me contacter, le plus simple est de m'envoyer un e-mail (<a href=\"mailto:villemain.s_AT_gmail.com\">gmail.com</a>)"
        . "<br />Retrouvez mes coordonn&#233;es compl&#232;te sur <a href=\"?mod=profilcv\">mon profil</a>.";
    }

    public function setting() {
        return "Pas de setting...";
    }

}
