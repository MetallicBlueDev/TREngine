<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
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
    protected $ftp = array(
        "host" => "",
        "port" => "",
        "user" => "",
        "pass" => "",
        "root" => ""
    );

    /**
     * Ecriture du fichier cache.
     *
     * @param string $path
     * @param string $content
     * @param boolean $overWrite
     */
    public function writingCache($path, $content, $overWrite = true) {
        unset($path);
        unset($content);
        unset($overWrite);
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path
     * @param int $updateTime
     */
    public function touchCache($path, $updateTime = 0) {
        unset($path);
        unset($updateTime);
    }

    /**
     * Supprime un fichier ou supprime tout fichier trop vieux.
     *
     * @param string $dir
     * @param int $timeLimit
     */
    public function removeCache($dir = "", $timeLimit = 0) {
        unset($dir);
        unset($timeLimit);
    }

    /**
     * Retourne le listing avec uniquement les fichiers présent.
     *
     * @param string $dirPath
     * @return array
     */
    public function &listNames($dirPath = "") {
        unset($dirPath);
        return array();
    }

    /**
     * Détermine si le gestion est utilisable.
     *
     * @return boolean true ready
     */
    public function canUse() {
        return false;
    }

}
