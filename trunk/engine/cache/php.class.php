<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de fichier via PHP.
 *
 * @author Sébastien Villemain
 */
class Cache_Php extends CacheModel {

    protected function canUse() {
        // Gestionnaire natif; toujours diponible
        return true;
    }

    public function netConnected() {
        // Gestionnaire natif; toujours diponible
        return true;
    }

    public function &netSelect() {
        $rslt = true;
        return $rslt;
    }

    public function writeCache($path, $content, $overwrite = true) {
        if (!is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
            // Soit le fichier n'exite pas soit tout le dossier n'existe pas
            // On commence par vérifier et si besoin écrire le dossier
            $this->writeDirectory($path);
        }

        // Réécriture rapide sur un fichier
        $this->writeFile($path, $content, $overwrite);
    }

    public function touchCache($path, $updateTime = 0) {
        if (!touch(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path, $updateTime)) {
            CoreLogger::addException("Touch error on " . $path);
        }
    }

    public function removeCache($path, $timeLimit = 0) {
        if (!empty($path) && is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
            // C'est un fichier a supprimer
            $this->removeFile($path, $timeLimit);
        } else if (is_dir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
            // C'est un dossier a nettoyer
            $this->removeDirectory($path, $timeLimit);
        }
    }

    public function &getCacheMTime($path) {
        $mTime = filemtime(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path);
        return $mTime;
    }

    public function &getNameList($path) {
        $dirList = array();

        // Si le dossier est vide, on prend le dossier par défaut
        $path = !empty($path) ? TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path : TR_ENGINE_INDEXDIR;

        // Ouverture du dossier
        $handle = opendir($path);

        // Boucle sur les fichiers
        do {
            $file = readdir($handle);

            if (!empty($file)) {
                // Si c'est un fichier valide
                if ($file !== ".." && $file !== "." && $file !== "index.html" && $file !== "index.htm" && $file !== "index.php" && $file !== ".htaccess" && $file !== ".svn" && $file !== "checker.txt") {
                    $dirList[] = $file;
                }
            }
        } while (false !== $file);

        // Fermeture du dossier
        closedir($handle);

        // Rangement et mise à zéro du tableau
        sort($dirList);
        reset($dirList);
        return $dirList;
    }

    /**
     * Ecriture du fichier cache.
     *
     * @param string $pathFile chemin vers le fichier cache
     * @param string $content contenu du fichier cache
     * @param boolean $overwrite écrasement du fichier
     */
    private function writeFile($pathFile, $content, $overwrite = true) {
        $content = ($overwrite) ? self::getFileHeader($pathFile, $content) : $content;

        // Tentative d'écriture du fichier
        // Des problèmes on été constaté avec l'utilisation du chemin absolu TR_ENGINE_INDEXDIR
        $fp = fopen($pathFile, 'a');

        if ($fp) {
            // Verrouiller le fichier destination
            flock($fp, LOCK_EX);

            if ($overwrite) {
                // Tronque pour une réécriture complete
                ftruncate($fp, 0);
            }

            // Ecriture du fichier cache
            $nbBytesFile = strlen($content);
            $nbBytesCmd = fwrite($fp, $content, $nbBytesFile);

            // Vérification des bytes écris
            if ($nbBytesCmd !== $nbBytesFile) {
                @unlink(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $pathFile);

                CoreLogger::addException("Bad response for fwrite command. Path : " . $pathFile . ". "
                . "Server response : " . $nbBytesCmd . " bytes writed, " . $nbBytesFile . " bytes readed");
            }

            // Libere le verrou
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            // Recherche d'un fichier htaccess
            $strlen = strlen($pathFile);
            $isHtaccessFile = (substr($pathFile, -9, $strlen) === ".htaccess");

            // Si c'est un htaccess, on essai de corriger le problème
            if ($isHtaccessFile) {
                // On créée le même fichier en HTML
                $htaccessPath = substr($pathFile, 0, $strlen - 9);
                $this->writeFile($htaccessPath . "index.html", $content, $overwrite);

                // Puis on renomme
                rename($htaccessPath . "index.html", $htaccessPath . ".htaccess");
            }

            CoreLogger::addException("Bad response for fopen command. Path : " . $pathFile);
        }
    }

    /**
     * Ecriture des dossiers, reconstitution des dossiers.
     *
     * @param string $path chemin voulu
     */
    private function writeDirectory($path) {
        // Savoir si le path est un dossier ou un fichier
        $pathIsDir = self::isDirectoryPath($path);

        // Information sur les dossiers
        $dirs = explode(DIRECTORY_SEPARATOR, TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path);
        $nbDir = count($dirs);
        $currentPath = "";
        $count = 0;

        if ($nbDir > 0) {
            foreach ($dirs as $dir) {
                $count++;

                // Si le dernier élèment est un fichier ou simplement vide
                if (($count === $nbDir && !$pathIsDir) || empty($dir)) {
                    // Il vaut mieux continuer, plutot que de faire un arret avec break
                    continue; // on passe a la suite...
                }

                // Mise à jour du dossier courant
                $currentPath = ($count === 1) ? $dir : $currentPath . DIRECTORY_SEPARATOR . $dir;

                if (!is_dir($currentPath)) {
                    // Création du dossier
                    mkdir($currentPath, $this->chmod);
                    chmod($currentPath, $this->chmod);

                    // Vérification de l'existence du fichier
                    if (!is_dir($currentPath)) {
                        CoreLogger::addException("Bad response for mkdir|chmod command. Path : " . $currentPath);
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
     * Supprime le fichier cache.
     *
     * @param string $path
     * @param int $timeLimit
     */
    private function removeFile($path, $timeLimit) {
        // Vérification de la date d'expiration
        $deleteFile = false;

        // Vérification de la date
        if ($timeLimit > 0) {

            // Vérification de la date d'expiration
            if ($timeLimit > $this->getCacheMTime($path)) {
                // Fichier périmé, suppression
                $deleteFile = true;
            }
        } else {
            // Suppression du fichier directement
            $deleteFile = true;
        }

        if ($deleteFile) {
            $fp = @fopen(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path, 'a');

            if ($fp) {
                // Verrouiller le fichier destination
                flock($fp, LOCK_EX);

                // Libere le verrou
                flock($fp, LOCK_UN);
                fclose($fp);

                // Suppression
                unlink(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path);
            }

            if (is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
                CoreLogger::addException("Bad response for fopen|unlink command. Path : " . $path);
            }
        }
    }

    /**
     * Supprime le dossier.
     *
     * @param string $dirPath
     * @param int $timeLimit
     */
    private function removeDirectory($dirPath, $timeLimit) {
        // Ouverture du dossier
        $handle = opendir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $dirPath);

        // Boucle sur les fichiers
        do {
            $file = readdir($handle);

            if (!empty($file)) {
                // Si c'est un fichier valide
                if ($file !== ".." && $file !== "." && $file !== ".svn") {
                    // Vérification avant suppression
                    if ($timeLimit > 0) {
                        if (is_file($dirPath . DIRECTORY_SEPARATOR . $file)) {
                            // Si le fichier n'est pas périmé, on passe au suivant

                            if ($timeLimit < $this->getCacheMTime($dirPath . DIRECTORY_SEPARATOR . $file)) {
                                continue;
                            }
                        } else {
                            // C'est un dossier,
                            // on ne souhaite pas le supprimer dans ce mode de fonctionnement
                            continue;
                        }
                    }

                    // Suppression
                    if (is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $dirPath . DIRECTORY_SEPARATOR . $file)) {
                        // Suppression du fichier
                        $this->removeFile($dirPath . DIRECTORY_SEPARATOR . $file, $timeLimit);
                    } else {
                        // Suppression du dossier
                        $this->removeDirectory($dirPath . DIRECTORY_SEPARATOR . $file, 0);
                    }
                }
            }
        } while ($file !== false);

        // Fermeture du dossier
        closedir($handle);

        // Suppression du dernière dossier
        if ($timeLimit === 0) {
            rmdir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $dirPath);

            if (is_dir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $dirPath)) {
                CoreLogger::addException("Bad response for rmdir command. Path : " . $dirPath);
            }
        }
    }

}
