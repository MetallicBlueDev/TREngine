<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire des connexions vers le FTP
 * 
 * @author Sébastien Villemain
 *
 */
class Libs_FtpManager extends Cache_Model {
	
	/**
	 * Utilisation du mode natif avec extension
	 * 
	 * @var boolean
	 */
	private $nativeMode = false;
	
	/**
	 * Identification de la connexion
	 * 
	 * @var int
	 */
	private $connId = 0;
	
	/**
	 * Le retour chariot de chaque OS
	 * 
	 * @var String
	 */
	private $CRLF = "";
	
	/**
	 * Configuration du FTP
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
	 * Le timeout de la connexion
	 * 
	 * @var int
	 */
	private $timeOut = 15;
	
	/**
	 * Derniere réponse du serveur
	 * 
	 * @var String
	 */
	private $response = "";
	
	/**
	 * Code de réponse du serveur
	 * 
	 * @var int
	 */
	private $responseCode = "";
	
	/**
	 * Message de réponse du serveur
	 * 
	 * @var String
	 */
	private $responseMsg = "";
	
	/**
	 * Adresse IP recu en mode passif
	 * 
	 * @var String
	 */
	private $passiveIp = "";
	
	/**
	 * Port recu en mode passif
	 * 
	 * @var String
	 */
	private $passivePort = "";
	
	/**
	 * Donnée recu en mode passif
	 * 
	 * @var String
	 */
	private $passiveData = "";
	
	/**
	 * Droit d'écriture CHMOD
	 * Sous forme de 4 octets
	 * Exemple : 0777
	 * 
	 * @var int
	 */
	private $chmod = 0777;
	
	// TODO il faut verifier entierement les paths de la classe
	
	/**
	 * Creation de la connexion
	 * Paramatrage de la connexion
	 */
	public function __construct() {
		// Recherche de l'extension FTP
		if (extension_loaded("ftp") && function_exists('ftp_connect')) {
			$this->nativeMode = true;
		}
		
		// Le retour chariot de chaque OS
		if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") $this->CRLF = "\r\n";
		else if (strtoupper(substr(PHP_OS, 0, 3)) === "MAC") $this->CRLF = "\r";
		else $this->CRLF = "\n";
		
		// Engagement de la connexion
		if ($this->connect()) {
			// Identification
			if ($this->logon()) {
				// Configuration du chemin FTP
				$this->rootConfig();
			}
		}
	}
	
	/**
	 * Ajout des informations du FTP
	 * 
	 * @param $ftp array
	 */
	public function setFtp($ftp = array()) {
		// Pré-configuration
		if (preg_match("/(ftp:\/\/)(.+)/", $ftp['host'], $matches)) $ftp['host'] = $matches[2];
		if (preg_match("/(.+)(\/)/", $ftp['host'], $matches)) $ftp['host'] = $matches[1];
		
		// Réglage de configuration
		$ftp['host'] = (empty($ftp['host'])) ? "127.0.0.1" : $ftp['host'];
		$ftp['port'] = (is_numeric($ftp_port)) ? $ftp['port'] : 21;
		$ftp['user'] = (empty($ftp['user'])) ? "root" : $ftp['user'];
		$ftp['pass'] = (empty($ftp['pass'])) ? "" : $ftp['pass'];
		// Le dossier root sera redéfinie après être logué
		$ftp['root'] = (empty($ftp['root'])) ? "/" : $ftp['root'];
		
		$this->ftp = $ftp;
	}
	
	/**
	 * Connexion au serveur FTP
	 *
	 * @return boolean true si aucune erreur
	 */
	private function connect() {
		// Si aucune connexion engagé
		if (!$this->isConnected()) {
			if ($this->nativeMode) {
				$this->connId = @ftp_connect($this->$ftp['host'], $this->$ftp['port'], $this->timeOut);
				// On définie le timeout, si possible
				$this->setTimeOut();
			} else {
				$this->connId = @fsockopen($this->$ftp['host'], $this->$ftp['port'], $socket_error_number, $socket_error_message, $this->timeOut);
				// On définie le timeout, si possible
				$this->setTimeOut();
			}
		}
		
		// Vérification de la connexion
		if (($this->nativeMode && $this->responseCode(220))
			|| $this->isConnected()) {
			return true;
		} else {
			Core_Exception::setException("could not connect to host " . $this->$ftp['host'] . " on port " . $this->$ftp['port']);
			return false;
		}
	}
	
	/**
	 * Vérifie si la connexion est établie
	 * 
	 * @return boolean true succès
	 */
	public function isConnected() {
		return is_resource($this->connId);
	}
	
	/**
	 * Fermeture de la connexion du serveur FTP
	 *
	 * @return boolean : true si aucune erreur
	 */
	public function deconnect() {
		if ($this->isConnected()) {
			if ($this->nativeMode) {
				return ftp_close($this->connId);
			} else {
				fwrite($this->connId, "QUIT" . $this->CRLF);
				return fclose($this->connId);
			}
		}
		// C'est déjà déconnecté
		return true;
	}
	
	/**
	 * Envoie une commande sur le FTP
	 * 
	 * @param string $cmd : la commande à executer
	 * @param string $expectedResponse : code de réponse attendu
	 * @return boolean true si aucune erreur
	 */
	private function setCommand($cmd, $expectedResponse) {
		if ($this->isConnected()) {
			// Envoie de la commande au serveur
			if (!@fwrite($this->connId, $cmd . $this->CRLF)) {
				Core_Exception::setException("unable to send command: " . $cmd);
			}
			return $this->responseCode($expectedResponse);
		}
		return false;
	}
	
	/**
	 * Vérification du code de réponse reçu
	 * 
	 * @param string $expected : code de réponse attendu
	 * @return boolean true si aucune erreur
	 */
	private function responseCode($expected) {
		// Si une connexion est engagé
		if ($this->isConnected()) {
			// Attente du serveur
			$endTime = time() + $this->timeOut;
			
			// Réponse du serveur
			$this->response = "";
			do {
				$this->response .= @fgets($this->connId, 4096);
			} while (!preg_match("/^([0-9]{3})(-(.*" . $this->CRLF . ")+\\1)? [^" . $this->CRLF . "]+" . $this->CRLF . "$/", $this->response, $parts) && time() < $endTime);
			
			// Vérification du résultat
			if (isset($parts[1])) {
				// On sépare le code du message
				$this->responseCode = $parts[1];
				$this->responseMsg = $parts[0];
				
				// Verification du code recu
				if (is_array($expected)) {
					if (Core_Utils::inArray($this->responseCode, $expected)) return true;
				} else {
					if ($this->responseCode == $expected) return true;
				}
			} else {
				Core_Exception::setException("timeout or unrecognized response while waiting for a response from the server. Full response : " . $this->response);
			}
		}
		return false;
	}
	
	/**
	 * Démarre le mode passif du serveur FTP
	 * 
	 * @return boolean true si aucune erreur
	 */
	private function setPassiveMode() {
		if ($this->isConnected()) {
			if ($this->nativeMode) {
				if (ftp_pasv($this->connId, true)) return true;
			} else {
				// Envoie de la requete
				if ($this->setCommand("PASV", 227)) {
					// Recherche de l'adresse IP et du port...
					if (preg_match('~\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))\)~', $this->responseCode, $matches)) {
						// Fabuleux, c'est trouvé!
						$this->passiveIp = $matches[1] . "." . $matches[2] . "." . $matches[3] . "." . $matches[4];
						$this->passivePort = $matches[5] * 256 + $matches[6];
						// Tentative de connexion
						$this->passiveData = @fsockopen($this->passiveIp, $this->passivePort, $socket_error_number, $socket_error_message, $this->timeOut);
						
						if ($this->passiveData != false) {
							// On définie le timeout, si possible
							$this->setTimeOut();
							return true;
						} else {
							Core_Exception::setException("could not connect to host " . $this->passiveIp . " on port " . $this->passivePort
							. ". Socket error number " . $$socket_error_number . " and error message: " . $socket_error_message);
						}
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * Engage le timeout sur le serveur
	 * 
	 * @return boolean true le timeout a été configuré sur le serveur
	 */
	private function &setTimeOut() {
		if ($this->nativeMode) {
			return ftp_set_option($this->connId, FTP_TIMEOUT_SEC, $this->timeout);
		}
		return stream_set_timeout($this->connId, $this->timeout);
	}
	
	/**
	 * Supprime un fichier ou supprime tout fichier trop vieux
	 * 
	 * @param $dir chemin vers le fichier ou le dossier
	 * @param $timeLimit limite de temps
	 */
	public function removeCache($dir = "", $timeLimit = 0) {
		if (!empty($dir) && is_file($this->getRootPath($dir))) {
			// C'est un fichier a supprimer
			$this->removeFile($dir, $timeLimit);
		} else if (is_dir($this->getRootPath($dir))) {
			// C'est un dossier a nettoyer
			$this->removeDirectory($dir, $timeLimit);
		}
	}
	
	/**
	 * Suppression d'un fichier sur le serveur FTP
	 * 
	 * @param string $path : chemin valide à supprimer
	 * @param $timeLimit int
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
		
		
		if ($deleteFile && $this->isConnected()) {
			if ($this->nativeMode) {
				// On efface le fichier, si c'est un fichier
				if (!@ftp_delete($this->connId, $this->getRootPath($path))) {
					Core_Exception::setException("bad response for ftp_delete command. Path : " . $path);
				}
			} else {				
				// Envoie de la commande de suppression du fichier
				if (!$this->setCommand("DELE " . $this->getRootPath($path), 250)) {
					Core_Exception::setException("bad response for DELE command. Path : " . $path);
				}
			}
		}
	}
	
	/**
	 * Suppression d'un dossier sur le serveur FTP
	 * 
	 * @param string $path : chemin valide à supprimer
	 * @param $timeLimit int
	 */
	private function removeDirectory($path, $timeLimit) {
		// Récuperation des elements présents
		$dirList = $this->listNames($path);
		
		if (is_array($dirList) && count($dirList) > 0) {
			foreach($dirList as $dirPath) {
				// Vérification avant suppression
				if ($timeLimit > 0) {
					if (is_file($this->getRootPath($path . "/" . $dirPath))) {
						// Si le fichier n'est pas périmé, on passe au suivant
						if ($timeLimit < $this->getMTime($path . "/" . $dirPath)) continue;
					} else {
						// C'est un dossier, 
						// on ne souhaite pas le supprimer dans ce mode de fonctionnement
						continue;
					}
				}
				
				if (is_file($this->getRootPath($path . "/" . $dirPath))) {
					// Suppression du fichier
					$this->removeFile($path . "/" . $dirPath, $timeLimit);
				} else {
					// Suppression du dossier
					$this->removeDirectory($path . "/" . $dirPath, $timeLimit);
				}
			}
		}
		
		// Suppression du derniere dossier
		if ($timeLimit == 0 && $this->isConnected()) {
			if ($this->nativeMode) {
				if (!@ftp_rmdir($this->connId, $this->getRootPath($path))) {
					Core_Exception::setException("bad response for ftp_rmdir command. Path : " . $path);
				}
			} else {
				// Envoie de la commande de suppression du fichier
				if ($this->setCommand("RMD " . $this->getRootPath($path), 250)) {
					Core_Exception::setException("bad response for RMD command. Path : " . $path);
				}
			}
		}
	}
	
	/**
	 * Récupération de la liste des dossiers et fichiers au chemin path 
	 * 
	 * @param string $path : chemin où doit être listé les dossiers
	 * @return array string liste des dossiers trouvés
	 */
	public function &listNames($path = "") {
		$dirList = array();
		if ($this->isConnected()) {
			// Demarrage du mode passif
			if ($this->setPassiveMode()) {
				if ($this->nativeMode) {
					// Recherche la liste
					$dirList = @ftp_nlist($this->connId, $this->getRootPath($path));
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
					if ($this->setCommand("NLST" . $path, array (150, 125))) {
						// On évite la boucle infinie
						if ($this->passiveData !== false) {
							while (!feof($this->passiveData)) {
								$dirListString .= fread($this->passiveData, 4096);
							}
						}
					}
					fclose($this->passiveData);
					
					// Verification
					if ($this->responseCode(226)) {
						$dirList = preg_split("/[" . $this->CRLF . "]+/", $dirListString, -1, PREG_SPLIT_NO_EMPTY);
						$dirList = preg_replace('#^'.preg_quote(substr($path, 1), '#').'[/\\\\]?#', '', $dirList);
					}
				}
			}
		}
		
		// On supprime les mauvaises cles
		$dirListKeys = array_merge(
			array_keys($dirList, ".."), 
			array_keys($dirList, "."),
			array_keys($dirList, "index.html"), 
			array_keys($dirList, "index.htm"),
			array_keys($dirList, "index.php"),
			array_keys($dirList, ".htaccess"),
			array_keys($dirList, ".svn"),
			array_keys($dirList, "checker.txt")
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
	
	/**
	 * Recherche la date de derniere modification du fichier
	 * 
	 * @param $path
	 * @return int date de derniere modification
	 */
	private function &getMTime($path) {
		$mTime = 0;
		if ($this->isConnected()) {
			if ($this->nativeMode) {
				$rslt = ftp_mdtm($this->connId, $this->getRootPath($path));
				
				if ($rslt == -1) { // Une erreur est survenue
					$this->nativeMode = false;
					Core_Exception::setException("bad response for ftp_mdtm command. Path : " . $path
					. " Turn off the native command.");
					$mTime = $this->getMTime($path);
				} else {
					$mTime = $rslt;
				}
			} else {
				if (!$this->setCommand("MDTM " . $this->getRootPath($path), 250)) {
					Core_Exception::setException("bad response for MDTM command. Path : " . $path);
				}
				$mTime = $this->responseMsg;
			}
		}
		return $mTime;
	}
	
	/**
	 * Retourne le path complet FTP
	 * 
	 * @param $path chemin local
	 * @return String
	 */
	private function getRootPath($path) {
		// TODO mettre en place le root path !!
		return $path;
	}
	
	/**
	 * Identification sur le FTP
	 *
	 * @return boolean : true si logé
	 */
	private function logon() {
		if ($this->isConnected()) {
			if ($this->nativeMode) {
				return @ftp_login($this->connId, $this->$ftp['user'], $this->$ftp['pass']);
			} else {
				// Envoie de l'user
				if ($this->setCommand("USER " . $this->$ftp['user'], array(331, 503))) {
					if ($responseCode[0] == 503) {
						// Désolé, déjà identifié 
						return true;
					} else {
						// Envoie du mot de passe
						return $this->setCommand("PASS " . $this->$ftp['pass'], 230);
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * Configuration du dossier root du FTP
	 */
	private function rootConfig() {
		// Si aucun root n'est précisé
		if ($this->$ftp['root'] == "/") {
			// On commence la recherche
			$pathFound = "";
			$listNames = $this->listNames();
			
			if (is_array($listNames)) {
				// On décompose les dossiers
				$listNamesSearch = explode("/", TR_ENGINE_DIR);
				
				// On recherche des correspondances
				foreach($listNames as $dirName) {
					foreach($listNamesSearch as $dirNameSearch) {
						// On construit le lien
						if (($dirNameSearch == $dirName) || !empty($pathFound)) {
							$pathFound = (!empty($pathFound)) ? $pathFound . "/" . $dirNameSearch : $dirNameSearch;
						} else {
							$pathRebuild = (!empty($pathRebuild)) ? $pathRebuild . "/" . $dirNameSearch : $dirNameSearch;
						}
					}
		
					// On verifie si c'est bon et on arrete si c'est trouvé 
					if (!empty($pathFound) 
						&& is_file("/" . $pathRebuild . "/" . $pathFound . "/engine/core/secure.class.php")) {
						break;
					} else {
						// Resets
						$pathFound = $pathRebuild = "";
					}
				}
			}
		}
		
		// Vérification du root path
		if (@is_file("/" . $pathRebuild . "/" . $pathFound . "/engine/core/secure.class.php")) {
			$this->$ftp['root'] = $pathFound;
		} else if (empty($this->$ftp['root'])) {
			Core_Exception::setException("Unable to configure root path.");
		}
	}
	
	/**
	 * Modification des droits CHMOD
	 * 
	 * @param string $path : chemin du dossier
	 * @param octal $mode : droit à attribuer en OCTAL (en octal: 0777 -> 777)
	 */
	private function chmod($path, $mode) {
		if ($this->isConnected()) {
			if ($this->nativeMode) {
				if (ftp_site($this->connId, "CHMOD " . $mode . " " . $this->getRootPath($path))) {
					Core_Exception::setException("bad response for ftp_site CHMOD command. Path : " . $path);
				}
			} else {			
				// Envoie de la commande CHMOD
				if ($this->setCommand("SITE CHMOD " . $mode . " " . $path, array(200, 250))) {
					Core_Exception::setException("bad response for SITE CHMOD command. Path : " . $path);
				}
			}
		}
	}
	
	/**
	 * Ecriture du ficher cache
	 * 
	 * @param $pathFile String chemin vers le fichier cache
	 * @param $content String contenu du fichier cache
	 * @param $overWrite boolean écrasement du fichier
	 */
	public function writingCache($path, $content, $overWrite = true) {
		if (is_file($this->getRootPath($path))) {
			// Réécriture rapide sur un fichier
			$this->writingFile($path, $content, $overWrite);
		} else {
			// Soit le fichier n'exite pas soit tout le dossier n'existe pas
			// On commence par vérifier et si besoin écrire le dossier
			$this->writingDirectory($path);
			// Puis nous écrivons notre fichier
			$this->writingFile($path, $content, $overWrite);
		}
	}
	
	/**
	 * Création d'un fichier sur le serveur FTP
	 * 
	 * @param array string $path : local => chemin valide local jusqu'au fichier, remote => chemin valide FTP (avec le root donc) jusqu'au fichier
	 */
	private function writingFile($path, $content, $overWrite = true) {
		$content = ($overWrite) ? Core_CacheBuffer::getHeader($pathFile, $content) : $content;
		
		if ($this->isConnected()) {
			// Demarrage du mode passif
			if ($this->setPassiveMode()) {
				if ($this->nativeMode) {
					// Tentative de création du fichier
					$buffer = @fopen($this->getRootPath($path), "a");// TODO il faut mettre un path local ici !
					@fwrite($buffer, $content);
					@rewind($buffer);
					
					// Ecriture du fichier
					if (!@ftp_fget($this->connId, $buffer, $this->getRootPath($path), FTP_ASCII)) {// TODO il faut mettre une path remote ici !
						Core_Exception::setException("bad response for ftp_fget command. Path : " .$path);
					}
					@fclose($buffer);
				} else {
					// Envoie de la commande
					if ($overWrite) {
						$this->setCommand("STOR " . $this->getRootPath($path), array (150, 125));
					} else {// TODO verifier le code réponse du serveur (150 et 125)
						$this->setCommand("APPE " . $this->getRootPath($path), array (150, 125));
					}
					
					// Ecriture du contenu
					do {
						// Ecriture ligne a ligne
						$responseSource = @fwrite($this->passiveData, $content);
						
						// Il n'y a plus rien a écrire
						if ($responseSource === false) break;
						
						// Ligne suivante
						$content = substr($content, $responseSource);
					} while (!empty($content));
					@fclose($this->passiveData);
					
					// Verification
					if (!$this->responseCode(226)) {
						Core_Exception::setException("bad response for STOR|APPE|fwrite command. Path : " . $path);
					}
				}
			}
		}
	}

	/**
	 * Création d'un dossier sur le serveur FTP
	 * 
	 * @param string $path : chemin valide à créer
	 */
	private function writingDirectory($path) {
		// Savoir si le path est un dossier ou un fichier
		$pathIsDir = Core_CacheBuffer::isDir($path);
		
		// Information sur les dossiers
		$dirs = explode("/", $this->getPath($path));
		$nbDir = count($dirs);
		$currentPath = "";
		$count = 0;
		
		if ($nbDir > 0) {
			foreach($dirs as $dir) {
				$count++;
				// Si le dernier élèment est un fichier ou simplement vide
				if (($count == $nbDir && !$pathIsDir) || empty($dir)) {
					break; // on passe a la suite...
				}
				
				// Mise à jour du dossier courant
				$currentPath = ($count == 1) ? $currentPath = $dir : $currentPath . "/" . $dir;
				
				if (!is_dir($currentPath)) {
					// Création du dossier
					if ($this->isConnected()) {	
						if ($this->nativeMode) {
							if (!@ftp_mkdir($this->connId, $path)) {
								Core_Exception::setException("bad response for ftp_mkdir command. Path : " . $path);
							}
						} else {
							if (!$this->setCommand("MKD " . $path, 257)) {
								Core_Exception::setException("bad response for MKD command. Path : " . $path);
							}
						}
						// Ajuste les droits CHMOD
						$this->chmod($path, $this->chmod);
					}
					
					// Des petites fichiers bonus...
					if ($dir == "tmp") {
						$this->writingFile($currentPath . "/index.php", "header(\"Location: ../index.php\");");
					} else {
						$this->writingFile($currentPath . "/.htaccess", "deny from all");
					}
				}
			}
		}
	}
	
	public function touchCache($path, $updateTime = 0) {
		// TODO mise a jour de la date de modif a coder
	}
	
	public function __destruct() {
		$this->deconnect();
	}
}
?>