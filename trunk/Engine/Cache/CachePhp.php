<?php

namespace TREngine\Engine\Cache;

use TREngine\Engine\Core\CoreLogger;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de fichier via PHP.
 *
 * @author Sébastien Villemain
 */
class CachePhp extends CacheModel {

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    protected function canUse(): bool {
        // Gestionnaire natif; toujours diponible
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function netConnected(): bool {
        // Gestionnaire natif; toujours diponible
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function &netSelect(): bool {
        $rslt = true;
        return $rslt;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path
     * @param mixed $content
     * @param bool $overwrite
     */
    public function writeCache(string $path, $content, bool $overwrite = true) {
        if (!is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
            // Soit le fichier n'exite pas soit tout le dossier n'existe pas
            // On commence par vérifier et si besoin écrire le dossier
            $this->writeDirectory($path);
        }

        // Réécriture rapide sur un fichier
        $this->writeFile($path, $content, $overwrite);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path
     * @param int $updateTime
     */
    public function touchCache(string $path, int $updateTime = 0) {
        if (!touch(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path, $updateTime)) {
            CoreLogger::addException("Touch error on " . $path);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path
     * @param int $timeLimit
     */
    public function removeCache(string $path, int $timeLimit = 0) {
        if (!empty($path) && is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
            // C'est un fichier a supprimer
            $this->removeFile($path, $timeLimit);
        } else if (is_dir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path)) {
            // C'est un dossier a nettoyer
            $this->removeDirectory($path, $timeLimit);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path
     * @return int
     */
    public function &getCacheMTime(string $path): int {
        $mTime = filemtime(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $path);
        return $mTime;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path
     * @return array
     */
    public function &getNameList(string $path): array {
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
     * @param bool $overwrite écrasement du fichier
     */
    private function writeFile(string $pathFile, string $content, bool $overwrite = true) {
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
            $this->manageFileOpenError($pathFile, $content, $overwrite);
        }
    }

    /**
     * Faite suite à une erreur d'écriture du fichier cache.
     *
     * @param string $pathFile
     * @param string $content
     * @param bool $overwrite
     */
    private function manageFileOpenError(string $pathFile, string $content, bool $overwrite) {
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

    /**
     * Création récursive des dossiers sur le serveur.
     *
     * @param string $path chemin voulu
     */
    private function writeDirectory(string $path) {
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
                    $this->makeDirectory($currentPath);

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
     * Création d'un dossier sur le serveur.
     *
     * @param string $path
     */
    private function makeDirectory(string $path) {
        // Création du dossier
        mkdir($path, $this->chmod);
        chmod($path, $this->chmod);

        // Vérification de l'existence du fichier
        if (!is_dir($path)) {
            CoreLogger::addException("Bad response for mkdir|chmod command. Path : " . $path);
        }
    }

    /**
     * Supprime le fichier cache.
     *
     * @param string $path
     * @param int $timeLimit
     */
    private function removeFile(string $path, int $timeLimit) {
        if ($this->canRemoveFile($path, $timeLimit)) {
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
     * Détermine si il est possible de supprimer le fichier.
     *
     * @param string $path
     * @param int $timeLimit
     * @return boolean
     */
    private function &canRemoveFile(string $path, int $timeLimit): bool {
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
        return $deleteFile;
    }

    /**
     * Suppression récurive des dossiers sur le serveur.
     *
     * @param string $dirPath
     * @param int $timeLimit
     */
    private function removeDirectory(string $dirPath, int $timeLimit) {
        // Ouverture du dossier
        $handle = opendir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $dirPath);

        // Boucle sur les fichiers
        do {
            $file = readdir($handle);

            if (!empty($file)) {
                // Si c'est un fichier valide
                if ($file !== ".." && $file !== "." && $file !== ".svn") {
                    // Vérification avant suppression
                    if (!$this->canRemove($dirPath, $file, $timeLimit)) {
                        continue;
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

    /**
     * Détermine si le fichier peut être supprimé.
     *
     * @param string $dirPath
     * @param string $file
     * @param int $timeLimit
     * @return bool
     */
    private function &canRemove(string $dirPath, string $file, int $timeLimit): bool {
        $rslt = true;

        if ($timeLimit > 0) {
            if (is_file($dirPath . DIRECTORY_SEPARATOR . $file)) {
                // Si le fichier n'est pas périmé, on passe au suivant
                if ($timeLimit < $this->getCacheMTime($dirPath . DIRECTORY_SEPARATOR . $file)) {
                    $rslt = false;
                }
            } else {
                // C'est un dossier, on ne souhaite pas le supprimer dans ce mode de fonctionnement
                $rslt = false;
            }
        }
        return $rslt;
    }

}
