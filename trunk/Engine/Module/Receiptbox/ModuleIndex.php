<?php

namespace TREngine\Engine\Module\Receiptbox;

use TREngine\Engine\Module\ModuleModel;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleIndex extends ModuleModel {

    public function display() {
        echo "Bienvenue sur la messagerie !";
    }

}
