<?php
if (preg_match("/session.class.php/ie", $_SERVER['PHP_SELF'])) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire des sessions
 * 
 * @author Sébastien Villemain
 *
 */
class Core_Session {
	
	/**
	 * Timer générale
	 * 
	 * @var int
	 */ 
	private $timer;
	
	/**
	 * Limite de temps pour le cache
	 * 
	 * @var int
	 */ 
	private $cacheTimeLimit;
	
	/**
	 * Limite de temps pour les cookies
	 * 
	 * @var int
	 */ 
	private $cookieTimeLimit;
	
	/**
	 * Informations du client
	 */
	private $user = array();
	
	/**
	 * Nom des cookies
	 * 
	 * @var array
	 */ 
	private $cookieName = array(
		"USER" => "_user_id",
		"SESSION" => "_sess_id",
		"LANGUE" => "_user_langue"
	);
	
	/**
	 * Démarrage du système de session
	 */
	public function __construct() {
		// Reset du client 
		$this->resetUser();
		
		// Marque le timer
		$this->timer = time();
		
		// Durée de validité du cache en jours
		$this->cacheTimeLimit = Core_Main::$coreConfig['cacheTimeLimit'] * 86400;
		// Durée de validité des cookies
		$this->cookieTimeLimit = $this->timer + (Core_Main::$coreConfig['cookieTimeLimit'] * 86400);
		
		// Complète le nom des cookies
		foreach ($this->cookieName as $key => $name) {
			$this->cookieName[$key] = Core_Main::$coreConfig['cookiePrefix'] . $name;
		}
		
		// Recherche de cookie de langue
		$this->user['userLanguage'] = Exec_Cookie::getCookie($this->cookieName['LANGUE']);
		
		// Configuration du gestionnaire de cache
		Core_CacheBuffer::setSectionName("sessions");
		
		// Nettoyage du cache
		$this->checkSessionCache();
		
		// Lanceur de session
		$this->sessionSelect();
	}
	
	/**
	 * Parcours récursivement le dossier cache des sessions afin de supprimer les fichiers trop vieux
	 */
	private function checkSessionCache() {
		// Vérification de la validité du checker
		if (!Core_CacheBuffer::checked($this->timer - $this->cacheTimeLimit)) {
			// Mise à jour ou creation du fichier checker
			Core_CacheBuffer::touchChecker();
			// Suppression du cache périmé
			Core_CacheBuffer::cleanCache($this->timer - $this->cacheTimeLimit);
		} 
	}
	
	/**
	 * Vérifie si une session est ouverte
	 * 
	 * @return boolean true une session peut être recupere
	 */
	private function sessionFound() {
		if (Exec_Cookie::getCookie($this->cookieName['SESSION']) != ""
				&& Exec_Cookie::getCookie($this->cookieName['USER']) != "") {
			return true;	
		}
		return false;
	}
	
	private function sessionSelect() {
		if ($this->sessionFound()) {
			// Chargement du crypteur
			Core_Loader::classLoader("Exec_Crypt");
			
			$userId = Exec_Crypt::md5Decrypt(Exec_Cookie::getCookie($this->cookieName['USER']), $this->getSalt());
			$sessionId = Exec_Crypt::md5Decrypt(Exec_Cookie::getCookie($this->cookieName['SESSION']), $this->getSalt());
			
		    if ($userId != "" && $sessionId != "" && Core_CacheBuffer::cached($sessionId . ".php")) {
				// Si fichier cache trouvé, on l'utilise
				$sessions = array();
				$sessions = Core_CacheBuffer::getCache($sessionId . ".php");
				
				// Verification + mise à jour toute les 5 minutes
				if ($sessions['userId'] == $userId) {
					// Mise à jour toute les 5 min
					if ((Core_CacheBuffer::cacheMTime($sessions['sessionId'] . ".php") + (5*60)) < $this->timer) {
						$updVerif = $this->updateLastConnect($sessions['userId']);
						// Mise a jour du dernier accès
						Core_CacheBuffer::touchCache($sessions['sessionId'] . ".php");
					} else {
						$updVerif = 1;
					}
				} else {
					$updVerif = false;
				}
				
				if ($updVerif == false) {
					// La mise à jour a échoué, on détruit la session
					$this->sessionClose();
				} else {
					// Injection des informations du client
					$this->setUser($sessions);
				}
		    } else if ($userId != "") {
				// Si plus de fichier cache, on tente de retrouver le client
				$sql = Core_Sql::getInstance();
				$sql->select(
					Core_Table::$USERS_TABLE,
					array("user_name", "user_rang"),
					array("user_id = '" . $userId . "'")
				);
				
				if ($sql->affectedRows() > 0) {
					// Si le client a été trouvé, on recupere les informations
					list($userName, $userRang) = $sql->fetchArray();
					
					// Injection des informations du client
					$this->setUser(array(
						"userId" => $userId,
						"userName" => $userName,
						"userRang" => $userRang,
					));
					// Mise à jour de derniere connexion
					$this->updateLastConnect();
										
					// Creation d'une nouvelle session
					$this->sessionOpen(1);
				} else {
					// userId invalide, on détruit
					$this->sessionClose();
				}
			}
		}
		return $user;
	}
	
	/**
	 * Mise à jour de la dernière connexion
	 * 
	 * @return ressource ID ou false 
	 */
	private function updateLastConnect($userId = "") {
		// Récupere l'id du client
		if (!$userId) $userId = $this->user['userId'];
		
		// Envoie la requête Sql
		return Core_Sql::getInstance()->update(Core_Table::$USERS_TABLE, 
			array("last_connect" => "NOW()"), 
			array("user_id" => $userId));
	}
	
	/**
	 * Mutateur des informations sur le client
	 * 
	 * @param $userInfo array
	 */
	private function setUser($userInfo = array()) {
		if (is_array($userInfo)) {
			foreach($userInfo as $key => $value) {
				$this->user[$key] = $value;
			}
		}
	}
	
	/**
	 * Vérifie si c'est un client connu donc logé
	 * 
	 * @return boolean true c'est un client
	 */
	private function isUser() {
		if ($this->user['userId'] != ""
			&& $this->user['userName'] != ""
			&& $this->user['sessionId'] != "") {
				return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Remise à zéro des informations sur le client
	 */
	private function resetUser() {
		$this->user = array(
			"userId" => "", // Id de l'utilisateur
			"userName" => "", // Nom de client
			"userRang" => 0, // Type de compte
			"sessionId" => "", // Id de la session
			"userLanguage" => "" // Langue de l'utilisateur
		);
	}
	
	/**
	 * Retourne les informations de l'utilisateur courant
	 * 
	 * @return array
	 */
	public function getUser() {
		return $this->user;
	}
	
	/**
	 * Ouvre une nouvelle session
	 * 
	 * @param $auto int connexion automatique
	 * @return unknown_type
	 */
	private function sessionOpen($auto = 1) {		
		// Destruction d'une éventuelle session
		$this->sessionClose();
		
		// Chargement de l'outil de cryptage
		Core_Loader::classLoader("Exec_Crypt");
		$this->user['sessionId'] = Exec_Crypt::creatId(32);
		
		// Connexion automatique via cookie
		if ($auto == 1) $cookieTimeLimit = $this->cookieTimeLimit;
		else $cookieTimeLimit = "";
		
		if (Exec_Cookie::createCookie($this->cookieName['USER'], Exec_Crypt::md5Encrypt($this->user['userId'], $this->getSalt()), $cookieTimeLimit)) {
			if (Exec_Cookie::createCookie($this->cookieName['SESSION'], Exec_Crypt::md5Encrypt($this->user['sessionId'], $this->getSalt()), $cookieTimeLimit)) {
				// Préparation des informations pour le cache
				$content = "";
				foreach ($this->user as $key => $value) {
					$content .= "$" . Core_CacheBuffer::getSectionName() . "[" . $key . "] = \"" . Core_CacheBuffer::preparingCaching($value) . "\"; ";
				}
				// Ecriture du cache
				Core_CacheBuffer::writingCache($this->user['sessionId'] . ".php",	$content);
			}
		}
	}
	
	/**
	 * Ferme une session ouverte
	 */
	private function sessionClose() {
		// Destruction du fichier de session
		if (Core_CacheBuffer::cached($this->user['sessionId'] . ".php")) {
			Core_CacheBuffer::removeCache($this->user['sessionId'] . ".php");
		}
		
		// Remise à zéro des infos client
		$this->resetUser();
		
		// Destruction des éventuelles cookies
		foreach ($this->cookieName as $key => $value) {
			Exec_Cookie::destroyCookie($this->cookieName[$key]);
		}
	}
	
	/**
	 * Tentative de creation d'un nouvelle session
	 * 
	 * @param $name Nom du compte (identifiant)
	 * @param $pass Mot de passe du compte
	 * @param $auto Connexion automatique
	 * @return boolean ture succès
	 */
	public function startConnection($name, $pass, $auto) {
		// Arrête de la session courante si il y en a une
		$this->stopConnection();
		$sql = Core_Sql::getInstance();
		
		$sql->select(
			Core_Table::$USERS_TABLE,
			array("user_id", "user_rang"),
			array("user_name = '" . $name . "'", "&& user_pass = '" . md5($pass) . "'")
		);
		
		if ($sql->affectedRows() > 0) {
			// Si le client a été trouvé, on recupere les informations
			list($userId, $rang) = $sql->fetchArray();
			// Injection des informations
			$this->setUser(array(
				"userId" => $userId,
				"userName" => $name,
				"userRang" => $rang
			));
			// Tentative d'ouverture de session
			return $this->sessionOpen($auto);
		} else {
			return false;
		}
	}
	
	/**
	 * Coupe proprement une session ouverte
	 */
	public function stopConnection() {
		if ($this->isUser()) {
			$this->sessionClose();
		}
	}
	
	/**
	 * Retourne la combinaise de cles pour le salt
	 * 
	 * @return String
	 */
	private function getSalt() {
		return Core_Main::$coreConfig['cryptKey'] . Exec_Agent::$userBrowser;
	}
}
?>