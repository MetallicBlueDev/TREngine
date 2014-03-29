<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Modèle de classe pour un gestionnaire de fichier
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
     * @param $path String
     * @param $content String
     * @param $overWrite boolean
     */
    public function writingCache($path, $content, $overWrite = true) {

    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param $path String
     * @param $updateTime int
     */
    public function touchCache($path, $updateTime = 0) {

    }

    /**
     * Supprime un fichier ou supprime tout fichier trop vieux.
     *
     * @param $dir String
     * @param $timeLimit int
     */
    public function removeCache($dir = "", $timeLimit = 0) {

    }

    /**
     * Retourne le listing avec uniquement les fichiers présent.
     *
     * @param $dirPath String
     * @return array
     */
    public function &listNames($dirPath = "") {
        return array();
    }

    /**
     * Retourne l'état du gestionnaire.
     *
     * @return boolean true ready
     */
    public function isReady() {
        return false;
    }

}
