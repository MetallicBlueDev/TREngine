<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}
// TODO il faut verifier entierement les paths de la classe

/**
 * Gestionnaire de fichier via une interface de connexion (socket).
 *
 * @author Sébastien Villemain
 */
class Cache_Socket extends Cache_Model {

    /**
     * Limite de temps de la connexion.
     *
     * @var int
     */
    private $timeOut = 10;

    /**
     * Le dernier code de réponse du serveur.
     *
     * @var int
     */
    private $lastResponseCode = "";

    /**
     * Le dernier message de réponse du serveur.
     *
     * @var string
     */
    private $lastResponseMessage = "";

    /**
     * Adresse IP reçu en mode passif.
     *
     * @var string
     */
    private $passiveIp = "";

    /**
     * Port reçu en mode passif.
     *
     * @var string
     */
    private $passivePort = "";

    /**
     * Donnée reçu en mode passif.
     *
     * @var string
     */
    private $passiveData = "";

    public function canUse() {
        $rslt = function_exists("fsockopen");

        if (!$rslt) {
            Core_Logger::addException("Socket function not found");
        }
        return false;
    }

    public function netConnect() {
        // Si aucune connexion engagé
        if ($this->connId === null) {
            $socketErrorNumber = -1;
            $socketErrorMessage = "";

            // Connexion au serveur
            $this->connId = fsockopen($this->getTransactionHost(), $this->getServerPort(), $socketErrorNumber, $socketErrorMessage, $this->timeOut);

            if ($this->connId === false) {
                Core_Logger::addException("Could not connect to host " . $this->getTransactionHost() . " on port " . $this->getServerPort() . ". ErrorCode = " . $socketErrorNumber . " ErrorMessage = " . $socketErrorMessage);
                $this->connId = null;
            } else {
                // Force le timeout, si possible
                $this->setTimeOut();
            }
        }
    }

    public function netDeconnect() {
        if ($this->netConnected()) {
            $this->sendCommand("QUIT");

            if (!fclose($this->connId)) {
                Core_Logger::addException("Unable to close connection");
            }
        }
    }

    public function &netSelect() {
        $rslt = false;

        // Envoi de l'identifiant
        if ($this->sendCommandAndCheckResponse("USER " . $this->getTransactionUser(), array(
            331,
            503))) {
            if ($this->lastResponseCode === 503) {
                // Oops, déjà identifié
                $rslt = true;
            } else {
                // Envoi du mot de passe
                $rslt = $this->sendCommandAndCheckResponse("PASS " . $this->getTransactionPass(), array(
                    230));

                if ($rslt) {
                    // Configuration du chemin FTP
                    $this->rootConfig();
                } else {
                    Core_Logger::addException("Unable to login: bad password?");
                }
            }
        } else {
            Core_Logger::addException("Unable to login: bad user?");
        }
        return $rslt;
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

        if ($this->netConnected()) {
            // Demarrage du mode passif
            if ($this->setPassiveMode()) {
                // Si un chemin est précisé, on ajoute un espace pour la commande
                $path = (!empty($path)) ? " " . $this->getRootPath($path) : "";

                // Chaine contenant tous les dossiers
                $dirListString = "";

                // Envoi de la requete
                if ($this->sendCommandAndCheckResponse("NLST" . $path, array(
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

                // Verification du résultat
                if ($this->receiveResponseCode(array(
                    226))) {
                    $dirList = preg_split("/[" . TR_ENGINE_CRLF . "]+/", $dirListString, -1, PREG_SPLIT_NO_EMPTY);
                    $dirList = preg_replace('#^' . preg_quote(substr($path, 1), '#') . '[/\\\\]?#', '', $dirList);
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
            if (!$this->sendCommandAndCheckResponse("MDTM " . $this->getRootPath($path), array(
                250))) {
                Core_Logger::addException("Bad response for MDTM command. Path : " . $path);
            }

            $mTime = $this->lastResponseMessage;
        }
        return $mTime;
    }

    /**
     * Configure le timeout sur le serveur.
     *
     * @return boolean true le timeout a été configuré sur le serveur
     */
    private function &setTimeOut() {
        $rslt = stream_set_timeout($this->connId, $this->timeout);
        return $rslt;
    }

    /**
     * Envoi une commande sur le serveur et vérifie la réponse.
     *
     * @param string $cmd : la commande à executer
     * @param array $expectedResponse : code de réponse attendu
     * @return boolean true si aucune erreur
     */
    private function &sendCommandAndCheckResponse($cmd, array $expectedResponse) {
        $rslt = false;

        if ($this->netConnected()) {
            $this->sendCommand($cmd);
            $rslt = $this->receiveResponseCode($expectedResponse);
        }
        return $rslt;
    }

    /**
     * Envoi une commande sur le serveur.
     *
     * @param string $cmd : la commande à executer
     * @return boolean true si aucune erreur
     */
    private function sendCommand($cmd) {
        if (!fwrite($this->connId, $cmd . TR_ENGINE_CRLF)) {
            Core_Logger::addException("Unable to send command: " . $cmd);
        }
    }

    /**
     * Vérification du code de réponse reçu.
     *
     * @param array $expected code de réponse attendu
     * @return boolean true si aucune erreur
     */
    private function &receiveResponseCode(array $expected) {
        $rslt = false;

        // Attente du serveur
        $endTime = time() + $this->timeOut;

        // Réponse du serveur
        $response = "";

        do {
            $response .= fgets($this->connId, 4096);
        } while (!preg_match("/^([0-9]{3})(-(.*" . TR_ENGINE_CRLF . ")+\\1)? [^" . TR_ENGINE_CRLF . "]+" . TR_ENGINE_CRLF . "$/", $response, $parts) && time() < $endTime);

        // Vérification du résultat
        if (isset($parts[1])) {
            // On sépare le code du message
            $this->lastResponseCode = $parts[1];
            $this->lastResponseMessage = $parts[0];

            // Verification du code recu
            if (Exec_Utils::inArray($this->lastResponseCode, $expected)) {
                $rslt = true;
            }
        } else {
            Core_Logger::addException("Timeout or unrecognized response while waiting for a response from the server. Full response : " . $response);
        }
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
        if ($this->sendCommandAndCheckResponse("SITE CHMOD " . $mode . " " . $path, array(
            200,
            250))) {
            Core_Logger::addException("Bad response for SITE CHMOD command. Path : " . $path);
        }
    }

    /**
     * Création d'un fichier sur le serveur.
     *
     * @param string $path
     * @param string $content
     * @param boolean $overWrite
     */
    private function writingFile($path, $content, $overWrite = true) {
        $content = ($overWrite) ? Core_Cache::getHeader($path, $content) : $content;
//$path : local => chemin valide local jusqu'au fichier, remote => chemin valide FTP (avec le root donc) jusqu'au fichier
        if ($this->netConnected()) {
            // Demarrage du mode passif
            if ($this->setPassiveMode()) {
                // Envoi de la commande
                if ($overWrite) {
                    $this->sendCommandAndCheckResponse("STOR " . $this->getRootPath($path), array(
                        150,
                        125));
                } else {// TODO verifier le code réponse du serveur (150 et 125)
                    $this->sendCommandAndCheckResponse("APPE " . $this->getRootPath($path), array(
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
                if (!$this->receiveResponseCode(array(
                    226))) {
                    Core_Logger::addException("Bad response for STOR|APPE|fwrite command. Path : " . $path);
                }
            }
        }
    }

    /**
     * Création d'un dossier sur le serveur .
     *
     * @param string $path : chemin valide à créer
     */
    private function writingDirectory($path) {
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
                        if (!$this->sendCommandAndCheckResponse("MKD " . $path, array(
                            257))) {
                            Core_Logger::addException("Bad response for MKD command. Path : " . $path);
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
     * Suppression d'un fichier sur le serveur.
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
            // Envoie de la commande de suppression du fichier
            if (!$this->sendCommandAndCheckResponse("DELE " . $this->getRootPath($path), array(
                250))) {
                Core_Logger::addException("Bad response for DELE command. Path : " . $path);
            }
        }
    }

    /**
     * Suppression d'un dossier sur le serveur.
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
        if ($timeLimit === 0 && $this->netConnected()) {
            // Envoi de la commande de suppression du fichier
            if ($this->sendCommandAndCheckResponse("RMD " . $this->getRootPath($path), array(
                250))) {
                Core_Logger::addException("Bad response for RMD command. Path : " . $path);
            }
        }
    }

    /**
     * Démarre le mode passif du serveur.
     *
     * @return boolean true si aucune erreur
     */
    private function &setPassiveMode() {
        $rslt = false;

        // Envoi de la requête
        if ($this->sendCommandAndCheckResponse("PASV", array(
            227))) {
            // Recherche de l'adresse IP et du port...
            if (preg_match('~\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))\)~', $this->lastResponseCode, $matches)) {
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
        return $rslt;
    }

}
