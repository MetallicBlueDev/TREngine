<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Modèle pour un gestionnaire de fichier.
 *
 * @author Sébastien Villemain
 */
abstract class Cache_Model extends Core_Transaction {

    /**
     * Droit d'écriture CHMOD.
     * Sous forme de 4 octets.
     * Exemple : 0777.
     *
     * @var int
     */
    protected $chmod = 0777;

    protected function throwException($message) {
        throw new Fail_Cache("cache" . $message);
    }

    public function initialize(array &$transaction) {
        if (!empty($transaction)) {
            if (preg_match("/(ftp:\/\/)(.+)/", $transaction['host'], $matches)) {
                $transaction['host'] = $matches[2];
            }

            if (preg_match("/(.+)(\/)/", $transaction['host'], $matches)) {
                $transaction['host'] = $matches[1];
            }

            // Réglage de configuration
            $transaction['host'] = (empty($transaction['host'])) ? "127.0.0.1" : $transaction['host'];
            $transaction['port'] = (is_numeric($transaction['port'])) ? $transaction['port'] : 21;
            $transaction['user'] = (empty($transaction['user'])) ? "root" : $transaction['user'];
            $transaction['pass'] = (empty($transaction['pass'])) ? "" : $transaction['pass'];

            // Le dossier root sera redéfini après être identifié
            $transaction['root'] = (empty($transaction['root'])) ? DIRECTORY_SEPARATOR : $transaction['root'];
        }

        parent::initialize($transaction);
    }

    /**
     * Retourne le port.
     *
     * @return int
     */
    public function &getServerPort() {
        return $this->getTransactionValue("port");
    }

    /**
     * Retourne le chemin racine.
     *
     * @return string
     */
    public function &getServerRoot() {
        return $this->getTransactionValue("root");
    }

    /**
     * Affecte le chemin racine.
     *
     * @param string $newRoot
     */
    public function setServerRoot(&$newRoot) {
        $this->setTransactionValue("root", $newRoot);
    }

    /**
     * Ecriture du fichier cache.
     *
     * @param string $path chemin vers le fichier cache
     * @param string $content contenu du fichier cache
     * @param boolean $overWrite écrasement du fichier
     */
    public function writingCache($path, $content, $overWrite = true) {
        unset($path);
        unset($content);
        unset($overWrite);
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache($path, $updateTime = 0) {
        unset($path);
        unset($updateTime);
    }

    /**
     * Supprime tous fichiers trop vieux.
     *
     * @param string $dir chemin vers le fichier ou le dossier
     * @param string $timeLimit limite de temps
     */
    public function removeCache($dir = "", $timeLimit = 0) {
        unset($dir);
        unset($timeLimit);
    }

    /**
     * Retourne la liste des fichiers et dossiers présents.
     *
     * @param string $dirPath
     * @return array
     */
    public function &getFileNames($dirPath = "") {
        unset($dirPath);
        $names = array();
        return $names;
    }

    /**
     * Retourne la date de dernière modification du fichier.
     *
     * @param string $path
     * @return int
     */
    public function &getCacheMTime($path) {
        unset($path);
        $time = 0;
        return $time;
    }

}
