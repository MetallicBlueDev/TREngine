<?php

namespace TREngine\Engine\Modules\Receiptbox;

use TREngine\Engine\Modules\ModuleModel;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleIndex extends ModuleModel {

    public function display() {
        echo "Bienvenue sur la messagerie !";
    }

}
