<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class Module_Receiptbox_Index extends ModuleModel {

    public function display() {
        echo "Bienvenue sur la messagerie !";
    }

}

?>