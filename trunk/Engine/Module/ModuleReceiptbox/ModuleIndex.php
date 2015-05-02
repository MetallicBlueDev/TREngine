<?php

namespace TREngine\Engine\Module\ModuleReceiptbox;

use TREngine\Engine\Module\ModuleModel;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleIndex extends ModuleModel {

    public function display() {
        echo "Bienvenue sur la messagerie !";
    }

}
