<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de fichier
 * Se configure et se lance en mode de gestion des fichiers en PHP ou en FTP
 * 
 * @author S�bastien Villemain
 *
 */
class Exec_FileManager {
	
	/**
	 * CHMOD � utiliser
	 * Sous forme de 4 octets
	 * Exemple : 0777
	 * 
	 * @var int
	 */
	private $chmod = 0777;
	
	/**
	 * Ecriture du ficher cache
	 * 
	 * @param $pathFile String chemin vers le fichier cache
	 * @param $content String contenu du fichier cache
	 * @param $overWrite boolean �crasement du fichier
	 */
	public function writingCache($path, $content, $overWrite = true) {
		if (is_file(TR_ENGINE_DIR . "/" . $path)) {
			// R��criture rapide sur un fichier
			$this->writingFile($path, $content, $overWrite);
		} else {
			// Soit le fichier n'exite pas soit tout le dossier n'existe pas
			// On commence par v�rifier et si besoin �crire le dossier
			$this->writingDirectory($path);
			// Puis nous �crivons notre fichier
			$this->writingFile($path, $content, $overWrite);
		}		
	}
	
	/**
	 * Ecriture du fichier cache
	 * 
	 * @param $pathFile String chemin vers le fichier cache
	 * @param $content String contenu du fichier cache
	 * @param $overWrite boolean �crasement du fichier
	 */
	private function writingFile($pathFile, $content, $overWrite = true) {
		if ($overWrite) {
			$content = Core_CacheBuffer::getHeader($pathFile, $content);
		}
			
		// Tentative d'�criture du fichier
		if ($fp = @fopen(TR_ENGINE_DIR . "/" . $pathFile, 'a')) {
			 // Verrouiller le fichier destination
			@flock($fp, LOCK_EX);
			
			if ($overWrite) {
				// Tronque pour une r��criture complete
				@ftruncate($fp, 0);
			}
			
			// Ecriture du fichier cache
			$nbBytesCmd = @fwrite($fp, $content, $nbBytesFile = strlen($content));
			
			// V�rification des bytes �cris
			if ($nbBytesCmd != $nbBytesFile) {
				@unlink(TR_ENGINE_DIR . "/" . $pathFile);
				Core_Exception::setException("bad response for fwrite command. Path : " . $pathFile . ". "
				. "Server response : " . $nbBytesCmd . " bytes writed, " . $nbBytesFile . " bytes readed.");
			}
			
			// Libere le verrou
			@flock($fp, LOCK_UN);
			@fclose($fp);
		} else {
			Core_Exception::setException("bad response for fopen command. Path : " . $pathFile);
		}
	}
	
	/**
	 * Ecriture des dossiers, reconstitution des dossiers
	 * 
	 * @param $path chemin voulu
	 */
	private function writingDirectory($path) {
		// Savoir si le path est un dossier ou un fichier
		$pathIsDir = Core_CacheBuffer::isDir($path);
		
		// Information sur les dossiers
		$dirs = explode("/", TR_ENGINE_DIR . "/" . $path);
		$nbDir = count($dirs);
		$currentPath = "";
		$count = 0;
		
		if ($nbDir > 0) {
			foreach($dirs as $dir) {
				$count++;
				// Si le dernier �l�ment est un fichier ou simplement vide
				if (($count == $nbDir && !$pathIsDir) || !$dir) {
					break; // on passe a la suite...
				}
				
				// Mise � jour du dossier courant
				$currentPath = ($count == 1) ? $currentPath = $dir : $currentPath . "/" . $dir;
				
				if (!is_dir($currentPath)) {
					// Cr�ation du dossier
					@mkdir($currentPath, $this->chmod);
					@chmod($currentPath, $this->chmod);
					
					// V�rification de l'existence du fichier
					if (!is_dir($currentPath)) {
						Core_Exception::setException("bad response for mkdir|chmod command. Path : " . $currentPath);
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
	
	/**
	 * Supprime un fichier ou supprime tout fichier trop vieux
	 * Suprime aussi un dossier
	 * 
	 * @param $dir chemin vers le fichier ou le dossier
	 * @param $timeLimit limite de temps
	 */
	public function removeCache($dir = "", $timeLimit = 0) {
		if (!empty($dir) && is_file(TR_ENGINE_DIR . "/" . $dir)) {
			// C'est un fichier a supprimer
			$this->removeFile($dir, $timeLimit);
		} else if (is_dir(TR_ENGINE_DIR . "/" . $dir)) {
			// C'est un dossier a nettoyer
			$this->removeDirectory($dir, $timeLimit);
		}
	}
	
	/**
	 * Supprime le fichier cache
	 * 
	 * @param $path
	 * @param $timeLimit
	 */
	private function removeFile($path, $timeLimit) {
		// V�rification de la date d'expiration
		$deleteFile = false;
		
		// V�rification de la date
		if ($timeLimit > 0) {
			// V�rification de la date d'expiration
			if ($timeLimit > filemtime(TR_ENGINE_DIR . "/" . $path)) {
				// Fichier p�rim�, suppression
				$deleteFile = true;
			}
		} else {
			// Suppression du fichier directement
			$deleteFile = true;
		}
		
		if ($deleteFile) {
			if ($fp = @fopen(TR_ENGINE_DIR . "/" . $path, 'a')) {
				// Verrouiller le fichier destination
				@flock($fp, LOCK_EX); 
				// Libere le verrou
				@flock($fp, LOCK_UN);
				@fclose($fp);
				// Suppression
				@unlink(TR_ENGINE_DIR . "/" . $path);
			}
			
			if (is_file(TR_ENGINE_DIR . "/" . $path)) {
				Core_Exception::setException("bad response for fopen|unlink command. Path : " . $path);
			}
		}
	}
	
	/**
	 * Supprime le dossier
	 * 
	 * @param $dirPath
	 * @param $timeLimit
	 */
	private function removeDirectory($dirPath, $timeLimit) {
		// Ouverture du dossier
		$handle = @opendir(TR_ENGINE_DIR . "/" . $dirPath);
		// Boucle sur les fichiers
		while (false !== ($file = @readdir($handle))) {
			// Si c'est un fichier valide
			if ($file != ".." 
					&& $file != "."
					&& $file != "index.html"
					&& $file != "index.htm"
					&& $file != "index.php"
					&& $file != ".htaccess"
					&& $file != "checker.txt") {
				// V�rification avant suppression
				if ($timeLimit > 0) {
					if (is_file($dirPath . "/" . $file)) {
						// Si le fichier n'est pas p�rim�, on passe au suivant
						if ($timeLimit < filemtime(TR_ENGINE_DIR . "/" . $file)) continue;
					} else {
						// C'est un dossier, 
						// on ne souhaite pas le supprimer dans ce mode de fonctionnement
						continue;
					}
				}
				
				// Suppression
				if (is_file(TR_ENGINE_DIR . "/" . $dirPath . "/" . $file)) {
					// Suppression du fichier
					$this->removeFile($dirPath . "/" . $file, $timeLimit);	
				} else {
					// Suppression du dossier
					$this->removeDirectory($dirPath . "/" . $file, 0);
				}
			}
		}
		// Fermeture du dossier
		@closedir($handle);
		
		// Suppression du derniere dossier
		if ($timeLimit == 0) {
			@rmdir(TR_ENGINE_DIR . "/" . $dirPath);
			
			if (is_dir(TR_ENGINE_DIR . "/" . $dirPath)) {
				Core_Exception::setException("bad response for rmdir command. Path : " . $dirPath);
			}
		}
	}
	
	/**
	 * Mise � jour de la date de derni�re modification
	 * 
	 * @param $path chemin vers le fichier cache
	 * @param $updateTime
	 */
	public function touchCache($path, $updateTime = "") {
		if (empty($updateTime)) $updateTime = time();
		if (!@touch(TR_ENGINE_DIR . "/" . $path, $updateTime)) {
			Core_Exception::setException("touch error on " . $path);
		}
	}
	
	/**
	 * Retourne le listing avec uniquement les fichiers pr�sent
	 * 
	 * @param $dirPath
	 * @return array
	 */
	private function &filesList($dirPath) {
		$filesList = array();
		// Ouverture du dossier
		$handle = @opendir(TR_ENGINE_DIR . "/" . $dirPath);
		// Boucle sur les fichiers
		while (false !== ($file = @readdir($handle))) {
			// Si c'est un fichier valide
			if ($file != ".." 
					&& $file != "."
					&& $file != "index.html"
					&& $file != "index.htm"
					&& $file != "index.php"
					&& $file != ".htaccess"
					&& $file != "checker.txt") {
				$filesList[] = $file;
			}
		}
		// Fermeture du dossier
		@closedir($handle);
		// Rangement et mise � z�ro du tableau
		sort($filesList);
		reset($filesList);
		return $filesList;
	}
}

?>