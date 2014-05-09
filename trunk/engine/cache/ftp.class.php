<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}
// TODO il faut verifier entierement les paths de la classe

/**
 * Gestionnaire de fichier via FTP.
 *
 * @author Sébastien Villemain
 */
class Cache_Ftp extends Cache_Model {

    /**
     * Le timeout de la connexion.
     *
     * @var int
     */
    private $timeOut = 10;

    public function canUse() {
        $rslt = extension_loaded("ftp") && function_exists('ftp_connect');

        if (!$rslt) {
            Core_Logger::addException("Socket function not found");
        }
        return false;
    }

    public function netConnect() {
        // Si aucune connexion engagé
        if ($this->connId === null) {
            // Connexion au serveur
            $this->connId = ftp_connect($this->getTransactionHost(), $this->getServerPort(), $this->timeOut);

            if ($this->connId === false) {
                Core_Logger::addException("Could not connect to host " . $this->getTransactionHost() . " on port " . $this->getServerPort());
                $this->connId = null;
            } else {
                // Force le timeout, si possible
                $this->setTimeOut();
            }
        }
    }

    public function netDeconnect() {
        if ($this->netConnected()) {
            if (ftp_close($this->connId)) {
                Core_Logger::addException("Unable to close connection");
            }
        }
    }

    public function &netSelect() {
        $rslt = false;

        // Envoi de l'identifiant
        if (ftp_login($this->connId, $this->getTransactionUser(), $this->getTransactionPass())) {
            // Configuration du chemin FTP
            $this->rootConfig();
            $rslt = true;
        } else {
            Core_Logger::addException("Unable to login.");
        }
        return $rslt;
    }

    public function writeCache($path, $content, $overwrite = true) {
        if (!is_file($this->getRootPath($path))) {
            // Soit le fichier n'exite pas, soit tout le dossier n'existe pas
            // On commence par vérifier et si besoin écrire le dossier
            $this->writeDirectory($path);
        }

        // Réécriture rapide sur un fichier
        $this->writeFile($path, $content, $overwrite);
    }

    public function touchCache($path, $updateTime = 0) {
        // TODO mise a jour de la date de modif a coder
        parent::touchCache($path, $updateTime);
    }

    public function removeCache($dir = "", $timeLimit = 0) {
        if (!empty($dir) && is_file($this->getRootPath($dir))) {
            // C'est un fichier a supprimer
            $this->removeFile($dir, $timeLimit);
        } else if (is_dir($this->getRootPath($dir))) {
            // C'est un dossier a nettoyer
            $this->removeDirectory($dir, $timeLimit);
        }
    }

    public function &getNameList($path = "") {
        $dirList = array();

        if ($this->netConnected()) {
            // Demarrage du mode passif
            if ($this->setPassiveMode()) {
                // Recherche la liste
                $dirList = ftp_nlist($this->connId, $this->getRootPath($path));

                // Si aucune erreur, on nettoie
                if (!is_bool($dirList)) {
                    $dirList = preg_replace('#^' . preg_quote($this->getRootPath($path), '#') . '[/\\\\]?#', '', $dirList);
                }
            }
        }

        // On supprime les mauvaises clés
        $dirListKeys = array_merge(
        array_keys($dirList, ".."), array_keys($dirList, "."), array_keys($dirList, "index.html"), array_keys($dirList, "index.htm"), array_keys($dirList, "index.php"), array_keys($dirList, ".htaccess"), array_keys($dirList, ".svn"), array_keys($dirList, "checker.txt")
        );

        if (is_array($dirListKeys)) {
            foreach ($dirListKeys as $key) {
                unset($dirList[$key]);
            }
        }

        sort($dirList);
        reset($dirList);
        return $dirList;
    }

    public function &getCacheMTime($path) {
        $mTime = 0;

        if ($this->netConnected()) {
            $mTime = ftp_mdtm($this->connId, $this->getRootPath($path));

            if ($mTime === -1) { // Une erreur est survenue
                Core_Logger::addException("Bad response for ftp_mdtm command. Path : " . $path
                . " Turn off the native command.");
            }
        }
        return $mTime;
    }

    /**
     * Configure le timeout sur le serveur.
     *
     * @return boolean true le timeout a été configuré sur le serveur
     */
    private function &setTimeOut() {
        $rslt = ftp_set_option($this->connId, FTP_TIMEOUT_SEC, $this->timeout);
        return $rslt;
    }

    /**
     * Retourne le chemin complet FTP.
     *
     * @param string $path chemin local
     * @return string
     */
    private function &getRootPath($path) {
        // TODO mettre en place le root path !!
        return $path;
    }

    /**
     * Configuration du dossier root du FTP.
     */
    private function rootConfig() {
        // Si aucun root n'est précisé
        if ($this->getServerRoot() === DIRECTORY_SEPARATOR) {
            // On commence la recherche
            $pathFound = "";
            $listNames = $this->getNameList();

            if (is_array($listNames)) {
                // On décompose les dossiers
                $listNamesSearch = explode(DIRECTORY_SEPARATOR, TR_ENGINE_DIR);

                // On recherche des correspondances
                foreach ($listNames as $dirName) {
                    foreach ($listNamesSearch as $dirNameSearch) {
                        // On construit le lien
                        if (($dirNameSearch === $dirName) || !empty($pathFound)) {
                            $pathFound = (!empty($pathFound)) ? $pathFound . DIRECTORY_SEPARATOR . $dirNameSearch : $dirNameSearch;
                        } else {
                            $pathRebuild = (!empty($pathRebuild)) ? $pathRebuild . DIRECTORY_SEPARATOR . $dirNameSearch : $dirNameSearch;
                        }
                    }

                    // On verifie si c'est bon et on arrete si c'est trouvé
                    if (!empty($pathFound) && is_file(DIRECTORY_SEPARATOR . $pathRebuild . DIRECTORY_SEPARATOR . $pathFound . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php")) {
                        break;
                    } else {
                        // Resets
                        $pathFound = $pathRebuild = "";
                    }
                }
            }
        }

        // Vérification du root path
        if (is_file(DIRECTORY_SEPARATOR . $pathRebuild . DIRECTORY_SEPARATOR . $pathFound . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php")) {
            $this->setServerRoot($pathFound);
        } else if (empty($this->getServerRoot())) {
            Core_Logger::addException("Unable to configure root path.");
        }
    }

    /**
     * Modification des droits CHMOD.
     *
     * @param string $path : chemin du dossier
     * @param octal $mode : droit à attribuer en OCTAL (en octal: 0777 -> 777)
     */
    private function chmod($path, $mode) {
        if (ftp_site($this->connId, "CHMOD " . $mode . " " . $this->getRootPath($path))) {
            Core_Logger::addException("Bad response for ftp_site CHMOD command. Path : " . $path);
        }
    }

    /**
     * Création d'un fichier sur le serveur FTP.
     *
     * @param string $path
     * @param string $content
     * @param boolean $overwrite
     */
    private function writeFile($path, $content, $overwrite = true) {
        $content = ($overwrite) ? Core_Cache::getHeader($path, $content) : $content;
//$path : local => chemin valide local jusqu'au fichier, remote => chemin valide FTP (avec le root donc) jusqu'au fichier
        if ($this->netConnected()) {
            // Demarrage du mode passif
            if ($this->setPassiveMode()) {
                // Tentative de création du fichier
                $buffer = @fopen($this->getRootPath($path), "a"); // TODO il faut mettre un path local ici !
                fwrite($buffer, $content);
                rewind($buffer);

                // Ecriture du fichier
                if (!ftp_fget($this->connId, $buffer, $this->getRootPath($path), FTP_ASCII)) {// TODO il faut mettre une path remote ici !
                    Core_Logger::addException("Bad response for ftp_fget command. Path : " . $path);
                }

                fclose($buffer);
            }
        }
    }

    /**
     * Création d'un dossier sur le serveur FTP.
     *
     * @param string $path : chemin valide à créer
     */
    private function writeDirectory($path) {
        // Savoir si le path est un dossier ou un fichier
        $pathIsDir = Core_Cache::isDir($path);

        // Information sur les dossiers
        $dirs = explode(DIRECTORY_SEPARATOR, $this->getPath($path));
        $nbDir = count($dirs);
        $currentPath = "";
        $count = 0;

        if ($nbDir > 0) {
            foreach ($dirs as $dir) {
                $count++;

                // Si le dernier élèment est un fichier ou simplement vide
                if (($count === $nbDir && !$pathIsDir) || empty($dir)) {
                    break; // on passe a la suite...
                }

                // Mise à jour du dossier courant
                $currentPath = ($count === 1) ? $currentPath = $dir : $currentPath . DIRECTORY_SEPARATOR . $dir;

                if (!is_dir($currentPath)) {
                    // Création du dossier
                    if ($this->netConnected()) {
                        if (!ftp_mkdir($this->connId, $path)) {
                            Core_Logger::addException("Bad response for ftp_mkdir command. Path : " . $path);
                        }

                        // Ajuste les droits CHMOD
                        $this->chmod($path, $this->chmod);
                    }

                    // Des petites fichiers bonus...
                    if ($dir === "tmp") {
                        $this->writeFile($currentPath . DIRECTORY_SEPARATOR . "index.php", "header(\"Location: .." . DIRECTORY_SEPARATOR . "index.php\");");
                    } else {
                        $this->writeFile($currentPath . DIRECTORY_SEPARATOR . ".htaccess", "deny from all");
                    }
                }
            }
        }
    }

    /**
     * Suppression d'un fichier sur le serveur FTP.
     *
     * @param string $path : chemin valide à supprimer
     * @param int $timeLimit
     */
    private function removeFile($path, $timeLimit) {
        // Vérification de la date d'expiration
        $deleteFile = false;

        // Vérification de la date
        if ($timeLimit > 0) {
            // Vérification de la date d'expiration
            if ($timeLimit > $this->getMTime($path)) {
                // Fichier périmé, suppression
                $deleteFile = true;
            }
        } else {
            // Suppression du fichier directement
            $deleteFile = true;
        }

        if ($deleteFile && $this->netConnected()) {
            // On efface le fichier, si c'est un fichier
            if (!ftp_delete($this->connId, $this->getRootPath($path))) {
                Core_Logger::addException("Bad response for ftp_delete command. Path : " . $path);
            }
        }
    }

    /**
     * Suppression d'un dossier sur le serveur FTP.
     *
     * @param string $path : chemin valide à supprimer
     * @param int $timeLimit
     */
    private function removeDirectory($path, $timeLimit) {
        // Récuperation des éléments présents
        $dirList = $this->getNameList($path);

        if (empty($dirList)) {
            foreach ($dirList as $dirPath) {
                // Vérification avant suppression
                if ($timeLimit > 0) {
                    if (is_file($this->getRootPath($path . DIRECTORY_SEPARATOR . $dirPath))) {
                        // Si le fichier n'est pas périmé, on passe au suivant
                        if ($timeLimit < $this->getMTime($path . DIRECTORY_SEPARATOR . $dirPath)) {
                            continue;
                        }
                    } else {
                        // C'est un dossier,
                        // on ne souhaite pas le supprimer dans ce mode de fonctionnement
                        continue;
                    }
                }

                if (is_file($this->getRootPath($path . DIRECTORY_SEPARATOR . $dirPath))) {
                    // Suppression du fichier
                    $this->removeFile($path . DIRECTORY_SEPARATOR . $dirPath, $timeLimit);
                } else {
                    // Suppression du dossier
                    $this->removeDirectory($path . DIRECTORY_SEPARATOR . $dirPath, $timeLimit);
                }
            }
        }

        // Suppression du dernière dossier
        if ($timeLimit === 0 && $this->netConnected()) {
            if (!ftp_rmdir($this->connId, $this->getRootPath($path))) {
                Core_Logger::addException("Bad response for ftp_rmdir command. Path : " . $path);
            }
        }
    }

    /**
     * Démarre le mode passif du serveur FTP.
     *
     * @return boolean true si aucune erreur
     */
    private function &setPassiveMode() {
        $rslt = false;

        if ($this->netConnected()) {
            if (ftp_pasv($this->connId, true)) {
                $rslt = true;
            }
        }
        return $rslt;
    }

}
