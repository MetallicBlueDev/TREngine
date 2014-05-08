<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Modèle de classe pour un gestionnaire de fichier.
 *
 * @author Sébastien Villemain
 */
abstract class Libs_CacheModel {

    /**
     * Droit d'écriture CHMOD.
     * Sous forme de 4 octets.
     * Exemple : 0777.
     *
     * @var int
     */
    protected $chmod = 0777;

    /**
     * Configuration du FTP.
     *
     * @var array
     */
    private $ftp = array(
        "host" => "",
        "port" => "",
        "user" => "",
        "pass" => "",
        "root" => ""
    );

    /**
     * Nouveau modèle de cache.
     */
    protected function __construct() {
        $this->ftp = null;
    }

    /**
     * Paramètre la connexion.
     *
     * @param array $ftp
     */
    public function initializeCache(array &$ftp) {
        if ($this->ftp === null) {
            if (empty($ftp)) {
                $ftp = array();
            } else {
                if (preg_match("/(ftp:\/\/)(.+)/", $ftp['host'], $matches)) {
                    $ftp['host'] = $matches[2];
                }

                if (preg_match("/(.+)(\/)/", $ftp['host'], $matches)) {
                    $ftp['host'] = $matches[1];
                }

                // Réglage de configuration
                $ftp['host'] = (empty($ftp['host'])) ? "127.0.0.1" : $ftp['host'];
                $ftp['port'] = (is_numeric($ftp['port'])) ? $ftp['port'] : 21;
                $ftp['user'] = (empty($ftp['user'])) ? "root" : $ftp['user'];
                $ftp['pass'] = (empty($ftp['pass'])) ? "" : $ftp['pass'];

                // Le dossier root sera redéfini après être identifié
                $ftp['root'] = (empty($ftp['root'])) ? DIRECTORY_SEPARATOR : $ftp['root'];
            }

            $this->ftp = $ftp;
        }
    }

    public function __destruct() {

    }

    /**
     * Retourne l'adresse de l'hôte.
     *
     * @return string
     */
    public function &getFtpHost() {
        return $this->ftp['host'];
    }

    /**
     * Retourne le port.
     *
     * @return int
     */
    public function &getFtpPort() {
        return $this->ftp['port'];
    }

    /**
     * Retourne l'identifiant du connexion.
     *
     * @return string
     */
    public function &getFtpUser() {
        return $this->ftp['user'];
    }

    /**
     * Retourne le mot de passe de connexion.
     *
     * @return string
     */
    public function &getFtpPass() {
        return $this->ftp['pass'];
    }

    /**
     * Retourne le chemin racine.
     *
     * @return string
     */
    public function &getFtpRoot() {
        return $this->ftp['root'];
    }

    /**
     * Affecte le chemin racine.
     *
     * @param string $newRoot
     */
    public function setFtpRoot(&$newRoot) {
        $this->ftp['root'] = $newRoot;
    }

    /**
     * Détermine si le gestionnaire est utilisable.
     *
     * @return boolean true ready
     */
    public function canUse() {
        return false;
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
