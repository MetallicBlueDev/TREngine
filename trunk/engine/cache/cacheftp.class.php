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
class Libs_CacheFtp extends Libs_CacheModel {

    /**
     * Utilisation du mode natif avec extension.
     *
     * @var boolean
     */
    private $nativeMode = false;

    /**
     * Identification de la connexion.
     *
     * @var int
     */
    private $connId = 0;

    /**
     * Le retour chariot de chaque OS.
     *
     * @var string
     */
    private $CRLF = "";

    /**
     * Le timeout de la connexion.
     *
     * @var int
     */
    private $timeOut = 10;

    /**
     * dernière réponse du serveur.
     *
     * @var string
     */
    private $response = "";

    /**
     * Code de réponse du serveur.
     *
     * @var int
     */
    private $responseCode = "";

    /**
     * Message de réponse du serveur.
     *
     * @var string
     */
    private $responseMsg = "";

    /**
     * Adresse IP recu en mode passif.
     *
     * @var string
     */
    private $passiveIp = "";

    /**
     * Port recu en mode passif.
     *
     * @var string
     */
    private $passivePort = "";

    /**
     * Donnée recu en mode passif.
     *
     * @var string
     */
    private $passiveData = "";

    public function initializeCache(array &$ftp) {
        $ftp = Core_Main::getInstance()->getConfigFtp();
        parent::initializeCache($ftp);

        // Recherche de l'extension FTP
        if (extension_loaded("ftp") && function_exists('ftp_connect')) {
            $this->nativeMode = true;
        }

        // Le retour chariot de chaque OS
        if (TR_ENGINE_PHP_OS === "WIN") {
            $this->CRLF = "\r\n";
        } else if (TR_ENGINE_PHP_OS === "MAC") {
            $this->CRLF = "\r";
        } else {
            $this->CRLF = "\n";
        }

        // Engagement de la connexion
        if ($this->connect()) {
            // Identification
            if ($this->logon()) {
                // Configuration du chemin FTP
                $this->rootConfig();
            }
        }
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function canUse() {
        return $this->connected();
    }

    public function writingCache($path, $content, $overWrite = true) {
        if (!is_file($this->getRootPath($path))) {
            // Soit le fichier n'exite pas, soit tout le dossier n'existe pas
            // On commence par vérifier et si besoin écrire le dossier
            $this->writingDirectory($path);
        }

        // Réécriture rapide sur un fichier
        $this->writingFile($path, $content, $overWrite);
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

    public function &getFileNames($path = "") {
        $dirList = array();

        if ($this->connected()) {
            // Demarrage du mode passif
            if ($this->setPassiveMode()) {
                if ($this->nativeMode) {
                    // Recherche la liste
                    $dirList = ftp_nlist($this->connId, $this->getRootPath($path));

                    // Si aucune erreur, on nettoie
                    if (!is_bool($dirList)) {
                        $dirList = preg_replace('#^' . preg_quote($this->getRootPath($path), '#') . '[/\\\\]?#', '', $dirList);
                    }
                } else {
                    // Si un chemin est précisé, on ajoute un espace pour la commande
                    $path = (!empty($path)) ? " " . $this->getRootPath($path) : "";

                    // Chaine contenant tous les dossiers
                    $dirListString = "";

                    // Envoie de la requete
                    if ($this->setCommand("NLST" . $path, array(
                        150,
                        125))) {
                        // On évite la boucle infinie
                        if ($this->passiveData !== false) {
                            while (!feof($this->passiveData)) {
                                $dirListString .= fread($this->passiveData, 4096);
                            }
                        }
                    }

                    fclose($this->passiveData);

                    // Verification
                    if ($this->responseCode(array(
                        226))) {
                        $dirList = preg_split("/[" . $this->CRLF . "]+/", $dirListString, -1, PREG_SPLIT_NO_EMPTY);
                        $dirList = preg_replace('#^' . preg_quote(substr($path, 1), '#') . '[/\\\\]?#', '', $dirList);
                    }
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

        if ($this->connected()) {
            if ($this->nativeMode) {
                $rslt = ftp_mdtm($this->connId, $this->getRootPath($path));

                if ($rslt === -1) { // Une erreur est survenue
                    $this->nativeMode = false;

                    Core_Logger::addException("Bad response for ftp_mdtm command. Path : " . $path
                    . " Turn off the native command.");

                    $mTime = $this->getMTime($path);
                } else {
                    $mTime = $rslt;
                }
            } else {
                if (!$this->setCommand("MDTM " . $this->getRootPath($path), array(
                    250))) {
                    Core_Logger::addException("Bad response for MDTM command. Path : " . $path);
                }

                $mTime = $this->responseMsg;
            }
        }
        return $mTime;
    }

    /**
     * Vérifie si la connexion est établie.
     *
     * @return boolean true succès
     */
    public function connected() {
        // Stockage pour passage par référence
        return is_resource($this->connId);
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
     * Identification sur le FTP.
     *
     * @return boolean : true si logé
     */
    private function &logon() {
        $rslt = false;

        if ($this->connected()) {
            if ($this->nativeMode) {
                $rslt = ftp_login($this->connId, $this->getFtpUser(), $this->getFtpPass());
            } else {
                // Envoi user
                if ($this->setCommand("USER " . $this->getFtpUser(), array(
                    331,
                    503))) {
                    if ($this->responseCode === 503) {
                        // Désolé, déjà identifié
                        $rslt = true;
                    } else {
                        // Envoi du mot de passe
                        $rslt = $this->setCommand("PASS " . $this->getFtpPass(), array(
                            230));
                    }
                }
            }
        }
        return $rslt;
    }

    /**
     * Configuration du dossier root du FTP.
     */
    private function rootConfig() {
        // Si aucun root n'est précisé
        if ($this->getFtpRoot() === DIRECTORY_SEPARATOR) {
            // On commence la recherche
            $pathFound = "";
            $listNames = $this->getFileNames();

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
            $this->setFtpRoot($pathFound);
        } else if (empty($this->getFtpRoot())) {
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
        if ($this->connected()) {
            if ($this->nativeMode) {
                if (ftp_site($this->connId, "CHMOD " . $mode . " " . $this->getRootPath($path))) {
                    Core_Logger::addException("Bad response for ftp_site CHMOD command. Path : " . $path);
                }
            } else {
                // Envoie de la commande CHMOD
                if ($this->setCommand("SITE CHMOD " . $mode . " " . $path, array(
                    200,
                    250))) {
                    Core_Logger::addException("Bad response for SITE CHMOD command. Path : " . $path);
                }
            }
        }
    }

    /**
     * Création d'un fichier sur le serveur FTP.
     *
     * @param string $path
     * @param string $content
     * @param boolean $overWrite
     */
    private function writingFile($path, $content, $overWrite = true) {
        $content = ($overWrite) ? Core_CacheBuffer::getHeader($path, $content) : $content;
//$path : local => chemin valide local jusqu'au fichier, remote => chemin valide FTP (avec le root donc) jusqu'au fichier
        if ($this->connected()) {
            // Demarrage du mode passif
            if ($this->setPassiveMode()) {
                if ($this->nativeMode) {
                    // Tentative de création du fichier
                    $buffer = @fopen($this->getRootPath($path), "a"); // TODO il faut mettre un path local ici !
                    fwrite($buffer, $content);
                    rewind($buffer);

                    // Ecriture du fichier
                    if (!ftp_fget($this->connId, $buffer, $this->getRootPath($path), FTP_ASCII)) {// TODO il faut mettre une path remote ici !
                        Core_Logger::addException("Bad response for ftp_fget command. Path : " . $path);
                    }

                    fclose($buffer);
                } else {
                    // Envoi de la commande
                    if ($overWrite) {
                        $this->setCommand("STOR " . $this->getRootPath($path), array(
                            150,
                            125));
                    } else {// TODO verifier le code réponse du serveur (150 et 125)
                        $this->setCommand("APPE " . $this->getRootPath($path), array(
                            150,
                            125));
                    }

                    // Ecriture du contenu
                    do {
                        // Ecriture ligne a ligne
                        $responseSource = fwrite($this->passiveData, $content);

                        // Il n'y a plus rien a écrire
                        if ($responseSource === false) {
                            break;
                        }

                        // Ligne suivante
                        $content = substr($content, $responseSource);
                    } while (!empty($content));

                    fclose($this->passiveData);

                    // Verification
                    if (!$this->responseCode(array(
                        226))) {
                        Core_Logger::addException("Bad response for STOR|APPE|fwrite command. Path : " . $path);
                    }
                }
            }
        }
    }

    /**
     * Création d'un dossier sur le serveur FTP.
     *
     * @param string $path : chemin valide à créer
     */
    private function writingDirectory($path) {
        // Savoir si le path est un dossier ou un fichier
        $pathIsDir = Core_CacheBuffer::isDir($path);

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
                    if ($this->connected()) {
                        if ($this->nativeMode) {
                            if (!ftp_mkdir($this->connId, $path)) {
                                Core_Logger::addException("Bad response for ftp_mkdir command. Path : " . $path);
                            }
                        } else {
                            if (!$this->setCommand("MKD " . $path, array(
                                257))) {
                                Core_Logger::addException("Bad response for MKD command. Path : " . $path);
                            }
                        }

                        // Ajuste les droits CHMOD
                        $this->chmod($path, $this->chmod);
                    }

                    // Des petites fichiers bonus...
                    if ($dir === "tmp") {
                        $this->writingFile($currentPath . DIRECTORY_SEPARATOR . "index.php", "header(\"Location: .." . DIRECTORY_SEPARATOR . "index.php\");");
                    } else {
                        $this->writingFile($currentPath . DIRECTORY_SEPARATOR . ".htaccess", "deny from all");
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

        if ($deleteFile && $this->connected()) {
            if ($this->nativeMode) {
                // On efface le fichier, si c'est un fichier
                if (!ftp_delete($this->connId, $this->getRootPath($path))) {
                    Core_Logger::addException("Bad response for ftp_delete command. Path : " . $path);
                }
            } else {
                // Envoie de la commande de suppression du fichier
                if (!$this->setCommand("DELE " . $this->getRootPath($path), array(
                    250))) {
                    Core_Logger::addException("Bad response for DELE command. Path : " . $path);
                }
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
        $dirList = $this->getFileNames($path);

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
        if ($timeLimit === 0 && $this->connected()) {
            if ($this->nativeMode) {
                if (!ftp_rmdir($this->connId, $this->getRootPath($path))) {
                    Core_Logger::addException("Bad response for ftp_rmdir command. Path : " . $path);
                }
            } else {
                // Envoi de la commande de suppression du fichier
                if ($this->setCommand("RMD " . $this->getRootPath($path), array(
                    250))) {
                    Core_Logger::addException("Bad response for RMD command. Path : " . $path);
                }
            }
        }
    }

    /**
     * Envoi une commande sur le FTP.
     *
     * @param string $cmd : la commande à executer
     * @param array $expectedResponse : code de réponse attendu
     * @return boolean true si aucune erreur
     */
    private function &setCommand($cmd, array $expectedResponse) {
        $rslt = false;

        if ($this->connected()) {
            // Envoie de la commande au serveur
            if (!fwrite($this->connId, $cmd . $this->CRLF)) {
                Core_Logger::addException("Unable to send command: " . $cmd);
            }

            $rslt = $this->responseCode($expectedResponse);
        }
        return $rslt;
    }

    /**
     * Vérification du code de réponse reçu.
     *
     * @param array $expected : code de réponse attendu
     * @return boolean true si aucune erreur
     */
    private function &responseCode(array $expected) {
        $rslt = false;

        // Si une connexion est engagé
        if ($this->connected()) {
            // Attente du serveur
            $endTime = time() + $this->timeOut;

            // Réponse du serveur
            $this->response = "";

            do {
                $this->response .= fgets($this->connId, 4096);
            } while (!preg_match("/^([0-9]{3})(-(.*" . $this->CRLF . ")+\\1)? [^" . $this->CRLF . "]+" . $this->CRLF . "$/", $this->response, $parts) && time() < $endTime);

            // Vérification du résultat
            if (isset($parts[1])) {
                // On sépare le code du message
                $this->responseCode = $parts[1];
                $this->responseMsg = $parts[0];

                // Verification du code recu
                if (Exec_Utils::inArray($this->responseCode, $expected)) {
                    $rslt = true;
                }
            } else {
                Core_Logger::addException("Timeout or unrecognized response while waiting for a response from the server. Full response : " . $this->response);
            }
        }
        return $rslt;
    }

    /**
     * Démarre le mode passif du serveur FTP.
     *
     * @return boolean true si aucune erreur
     */
    private function &setPassiveMode() {
        $rslt = false;

        if ($this->connected()) {
            if ($this->nativeMode) {
                if (ftp_pasv($this->connId, true)) {
                    $rslt = true;
                }
            } else {
                // Envoi de la requête
                if ($this->setCommand("PASV", array(
                    227))) {
                    // Recherche de l'adresse IP et du port...
                    if (preg_match('~\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))\)~', $this->responseCode, $matches)) {
                        // Fabuleux, c'est trouvé!
                        $this->passiveIp = $matches[1] . "." . $matches[2] . "." . $matches[3] . "." . $matches[4];
                        $this->passivePort = $matches[5] * 256 + $matches[6];

                        // Tentative de connexion
                        $this->passiveData = fsockopen($this->passiveIp, $this->passivePort, $socket_error_number, $socket_error_message, $this->timeOut);

                        if ($this->passiveData !== false) {
                            // On définie le timeout, si possible
                            $this->setTimeOut();
                            $rslt = true;
                        } else {
                            Core_Logger::addException("Could not connect to host " . $this->passiveIp . " on port " . $this->passivePort
                            . ". Socket error number " . $$socket_error_number . " and error message: " . $socket_error_message);
                        }
                    }
                }
            }
        }
        return $rslt;
    }

    /**
     * Engage le timeout sur le serveur.
     *
     * @return boolean true le timeout a été configuré sur le serveur
     */
    private function &setTimeOut() {
        $rslt = false;

        if ($this->nativeMode) {
            $rslt = ftp_set_option($this->connId, FTP_TIMEOUT_SEC, $this->timeout);
        } else {
            $rslt = stream_set_timeout($this->connId, $this->timeout);
        }
        return $rslt;
    }

    /**
     * Connexion au serveur FTP.
     *
     * @return boolean true si aucune erreur
     */
    private function &connect() {
        $rslt = false;

        // Si aucune connexion engagé
        if (!$this->connected()) {
            if ($this->nativeMode) {
                $this->connId = ftp_connect($this->getFtpHost(), $this->getFtpPort(), $this->timeOut);
            } else {
                $this->connId = fsockopen($this->getFtpHost(), $this->getFtpPort(), $socket_error_number, $socket_error_message, $this->timeOut);
            }

            // On définie le timeout, si possible
            if ($this->connected()) {
                $this->setTimeOut();
            }
        }

        // Vérification de la connexion
        if (($this->nativeMode && $this->responseCode(array(
            220))) || $this->connected()) {
            $rslt = true;
        } else {
            Core_Logger::addException("Could not connect to host " . $this->getFtpHost() . " on port " . $this->getFtpPort());
        }
        return $rslt;
    }

    /**
     * Fermeture de la connexion du serveur FTP.
     *
     * @return boolean true si aucune erreur
     */
    private function &disconnect() {
        $rslt = true;

        if ($this->connected()) {
            if ($this->nativeMode) {
                $rslt = ftp_close($this->connId);
            } else {
                fwrite($this->connId, "QUIT" . $this->CRLF);
                $rslt = fclose($this->connId);
            }
        }
        return $rslt;
    }

}
