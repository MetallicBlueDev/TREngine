<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

class Core_CacheBuffer {
	
	/**
	 * Réécriture du cache
	 * 
	 * @var array
	 */
	protected static $writingCache = array();
	
	/**
	 * Ecriture du cache a la suite
	 * 
	 * @var array
	 */
	protected static $addCache = array();
	
	/**
	 * Suppression du cache
	 * 
	 * @var array
	 */
	protected static $removeCache = array();
	
	/**
	 * Mise à jour de derniere modification du cache
	 * 
	 * @var array
	 */
	protected static $updateCache = array();
	
	/**
	 * Tableau avec les chemins des differentes rubriques
	 * 
	 * @var array
	 */
	protected static $sectionDir = array(
		"tmp" => "tmp",
		"log" => "tmp/log",
		"sessions" => "tmp/sessions",
		"lang" => "tmp/lang",
		"menus" => "tmp/menus",
		"modules" => "tmp/modules"
	);
	
	/**
	 * Nom de la section courante
	 * 
	 * @var String
	 */
	protected static $sectionName = "";
	
	/**
	 * Etat des modes de gestion des fichiers
	 * 
	 * @var arry
	 */
	protected static $modeActived = array (
		"php" => false,
		"ftp" => false,
		"sftp" => false
	);
	
	/**
	 * Donnée du ftp
	 * 
	 * @var array
	 */
	protected static $ftp = array();
	
	/**
	 * Modifier le nom de la section courante
	 * 
	 * @param $sectionName
	 */
	public static function setSectionName($sectionName = "") {
		if (!empty($sectionName) 
				&& isset(self::$sectionDir[$sectionName])) {
			self::$sectionName = $sectionName;
		} else {
			self::$sectionName = "tmp";
		}
	}
	
	/**
	 * Retourne le chemin de la section courante
	 * 
	 * @return String
	 */
	private static function &getSectionPath() {
		// Si pas de section, on met par défaut
		if (empty(self::$sectionName)) self::setSectionName();
		// Chemin de la section courante
		return self::$sectionDir[self::$sectionName];
	}
	
	/**
	 * Nom de la section courante
	 * 
	 * @return String Section courante
	 */
	public static function &getSectionName() {
		return self::$sectionName;
	}
	
	/**
	 * Ecriture du fichier cache
	 * 
	 * @param $path chemin complet
	 * @param $content donnée a écrire
	 * @param $overWrite boolean true réécriture complete, false écriture a la suite
	 */
	public static function writingCache($path, $content, $overWrite = true) {
		// Mise en forme de la cles
		$key = self::encodePath(self::getSectionPath(). "/" . $path);
		// Ajout dans le cache
		if ($overWrite) self::$writingCache[$key] = $content;
		else self::$addCache[$key] = $content;
	}
	
	/**
	 * Supprime un fichier ou supprime tout fichier trop vieux
	 * 
	 * @param $dir chemin vers le fichier ou le dossier
	 * @param $timeLimit limite de temps
	 */
	public static function removeCache($dir, $timeLimit = 0) {
		// Configuration du path
		if (!empty($dir)) $dir = "/" . $dir;
		$dir = self::getSectionPath() . $dir;
		self::$removeCache[self::encodePath(self::getSectionPath() . $dir)] = $timeLimit;
	}
	
	/**
	 * Nettoie le dossier courant du cache
	 * 
	 * @param $timeLimit La limite de temps
	 */
	public static function cleanCache($timeLimit) {
		self::removeCache("", $timeLimit);
	}
	
	/**
	 * Mise à jour de la date de dernière modification
	 * 
	 * @param $path chemin vers le fichier cache
	 */
	public static function touchCache($path) {
		self::$updateCache[self::encodePath(self::getSectionPath() . "/" . $path)] = time();
	}
	
	/**
	 * Vérifie si le fichier est en cache
	 * 
	 * @param $path chemin vers le fichier cache
	 * @return boolean true le fichier est en cache
	 */
	public static function cached($path) {
		return is_file(self::getPath($path));
	}
	
	/**
	 * Date de dernière modification
	 * 
	 * @param $path chemin vers le fichier cache
	 * @return int Unix timestamp ou false
	 */
	public static function cacheMTime($path) {
		return filemtime(self::getPath($path));
	}
	
	/**
	 * Vérifie la présence du checker et sa validité
	 * 
	 * @return boolean true le checker est valide
	 */
	public static function checked($timeLimit = 0) {
		if (self::cached("checker.txt")) {
			// On a demandé un comparaison de temps
			if ($timeLimit > 0) {
				if ($timeLimit < self::checkerMTime()) return true;
			} else {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Ecriture du checker
	 */
	private static function writingChecker() {
		self::writingCache("checker.txt", "ok");
	}
	
	/**
	 * Mise à jour du checker
	 */
	public static function touchChecker() {
		if (!self::cached("checker.txt")) self::writingChecker();
		else self::touchCache("checker.txt");
	}
	
	/**
	 * Date de dernière modification du checker
	 * 
	 * @return int Unix timestamp ou false
	 */
	public static function checkerMTime() {
		return self::cacheMTime("checker.txt");
	}
	
	/**
	 * Encode un chemin
	 * 
	 * @param String $path
	 * @return String
	 */
	protected static function &encodePath($path) {
		return $path;
	}
	
	/**
	 * Décode un chemin
	 * 
	 * @param String $encodePath
	 * @return String
	 */
	protected static function &decodePath($encodePath) {
		return $encodePath;
	}
	
	/**
	 * Retourne le chemin complet vers le fichier cache
	 * 
	 * @param $path chemin du fichier
	 * @return String chemin complet
	 */
	public static function getPath($path) {
		return TR_ENGINE_DIR . "/" . self::getSectionPath() . "/" . $path;
	}
	
	/**
	 * Capture le cache ciblé dans un tableau
	 * 
	 * @param $path Chemin du cache
	 * @return mixed
	 */
	public static function getCache($path) {
		// Réglage avant capture
		$variableName = self::$sectionName;
		// Rend la variable global a la fonction
		${$variableName} = "";
		
		// Capture du fichier
		if (self::cached($path)) {
			require(self::getPath($path));
		}
		return ${$variableName};
	}
	
	/**
	 * Recherche si le cache a besoin de génére une action
	 * 
	 * @param array $required
	 * @return boolean true action demandée
	 */
	private static function cacheRequired($required) {
		if (is_array($required) && count($required) > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Test si le chemin est celui d'un dossier
	 * 
	 * @param $path
	 * @return boolean true c'est un dossier
	 */
	public static function isDir($path) {
		$pathIsDir = false;
		
		if (substr($path, -1) == "/") {
			// Nettoyage du path qui est enfaite un dir
			$path = substr($path, 0, -1);
			$pathIsDir = true;
		} else {
			// Recherche du bout du path
			$pos = strrpos("/", $path);
			if (!is_bool($pos)) {
				$last = substr($path, $pos);
			} else {
				$last = $path;
			}
			
			// Si ce n'est pas un fichier (avec ext.)
			if (strpos($last, ".") === false) {
				$pathIsDir = true;
			}
		}
		return $pathIsDir;
	}
	
	/**
	 * Ecriture des entêtes de fichier
	 * 
	 * @param $pathFile
	 * @param $content
	 * @return String $content
	 */
	public static function &getHeader($pathFile, $content) {
		$ext = substr($pathFile, -3);
		// Entête des fichier PHP
		if ($ext == "php") {
			// Recherche du dossier parent
			$dirBase = "";
			$nbDir = count(explode("/", $pathFile));
			for($i = 1; $i < $nbDir; $i++) { $dirBase .= "../"; }
			
			// Ecriture de l'entête
			$content = "<?php\n"
			. "if (!defined(\"TR_ENGINE_INDEX\")){"
			. "if(!class_exists(\"Core_Secure\")){"
			. "include(\"" . $dirBase . "engine/core/secure.class.php\");"
			. "}new Core_Secure();}"
			. "// Generated on " . date('Y-m-d H:i:s') . "\n"
			. $content
			. "\n?>";
		}
		return $content;
	}
	
	/**
	 * Retourne une chaine de caratère l'integalité d'un tableau
	 * 
	 * @param $array array
	 * @param $lastKey String
	 * @return String
	 */
	public static function &linearizeCache($array, $lastKey = "") {
		$content = "";
		foreach($array as $key => $value) {
			if (is_array($value)) {
				$lastKey .= "['" . $key . "']";
				$content .= self::linearizeCache($value, $lastKey);
			} else {
				$content .= "$" . Core_CacheBuffer::getSectionName() . $lastKey . "['" . $key . "'] = \"" . $value . "\"; ";
			}
		}
		return $content;
	}
	
	/**
	 * Active les modes de cache disponible
	 * 
	 * @param $modes array
	 */
	public static function setModeActived($modes = array()) {
		if (!is_array($modes)) $modes = array($modes);

		foreach($modes as $mode) {
			if (isset(self::$modeActived[$mode])) {
				self::$modeActived[$mode] = true;
			}
		}
	}
	
	/**
	 * Injecter les données du FTP
	 * 
	 * @param array
	 */
	public static function setFtp($ftp = array()) {
		self::$ftp = $ftp;
	}
	
	/**
	 * Execute la routine du cache
	 */
	public static function valideCacheBuffer() {
		// Si le cache a besoin de générer une action
		if (self::cacheRequired(self::$removeCache)
			|| self::cacheRequired(self::$writingCache)
			|| self::cacheRequired(self::$addCache)
			|| self::cacheRequired(self::$updateCache)) {
				
				
			/**
			 * TODO code sur la détection du mode a utiliser a faire
			 * Parametrage du gestionnaire
			 * Détection du mode a utiliser
			 * Mode PHP, utilisation des fonctions classique PHP
			 * Mode FTP, utilisation de la classe FTP dédié
			 */
			
			if (self::$modeActived['php']) {
				// Démarrage du gestionnaire de fichier
				Core_Loader::classLoader("Libs_FileManager");
				$execProtocol = new Exec_FileManager();
			} else if (self::$modeActived['ftp']) {
				// Démarrage du gestionnaire FTP
				Core_Loader::classLoader("Libs_FtpManager");
				$execProtocol = new Exec_FtpManager();
				$execProtocol->setFtp(self::$ftp);
			} else if (self::$modeActived['sftp']) {
				// Démarrage du gestionnaire SFTP
				Core_Loader::classLoader("Libs_SftpManager");
				$execProtocol = new Exec_SftpManager();
				$execProtocol->setFtp(self::$ftp);
			} else {
				Core_Exception::setException("no protocol actived for cache.");
				return null;
			}
			
			// Suppression de cache demandée
			if (self::cacheRequired(self::$removeCache)) {
				foreach(self::$removeCache as $dir => $timeLimit) {
					$execProtocol->removeCache($dir, $dir);
				}
			}
			
			// Ecriture de cache demandée
			if (self::cacheRequired(self::$writingCache)) {
				foreach(self::$writingCache as $path => $content) {
					$execProtocol->writingCache($path, $content, true);
				}
			}
			
			// Ecriture à la suite de cache demandée
			if (self::cacheRequired(self::$addCache)) {
				foreach(self::$addCache as $path => $content) {
					$execProtocol->writingCache($path, $content, false);
				}
			}
			
			// Mise à jour de cache demandée
			if (self::cacheRequired(self::$updateCache)) {
				foreach(self::$updateCache as $path => $updateTime) {
					$execProtocol->touchCache($path, $updateTime);
				}
			}
			
			// Destruction du gestionnaire
			unset($execProtocol);
		}
	}
}
?>