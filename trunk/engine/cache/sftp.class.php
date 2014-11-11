<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de fichier via FTP sécurisée.
 *
 * @author Sébastien Villemain
 */
class Cache_Sftp extends Cache_Model {

    protected function canUse() {
        // TODO classe a coder..
        return false;
    }

}
