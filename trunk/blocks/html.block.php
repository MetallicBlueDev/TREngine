<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Block pour contenu HTML
 *
 * @author Sébastien Villemain
 */
class Block_Html extends Libs_BlockModel {

}

?>