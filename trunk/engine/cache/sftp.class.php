<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de fichier via FTP sécurisée.
 *
 * @author Sébastien Villemain
 */
class Cache_Sftp extends Cache_Model {
    // TODO classe a coder...
}
