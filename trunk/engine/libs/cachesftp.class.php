<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

// Chargement du parent
Core_Loader::classLoader("Libs_CacheModel");

/**
 *
 * @author Sébastien Villemain
 */
class Libs_CacheSftp extends Libs_CacheModel {
    // TODO classe a coder...
}
