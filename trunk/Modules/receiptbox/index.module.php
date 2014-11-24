<?php

namespace TREngine\Modules;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleReceiptboxIndex extends ModuleModel {

    public function display() {
        echo "Bienvenue sur la messagerie !";
    }

}
