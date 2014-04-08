<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de la communication SQL
 *
 * @author Sébastien Villemain
 */
class Base_Mysqli extends Base_Mysql {

}

?>