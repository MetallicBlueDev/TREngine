<?php
if (preg_match("/session.class.php/ie", $_SERVER['PHP_SELF'])) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire des sessions
 * 
 * @author S�bastien Villemain
 *
 */
class Core_Session {
	
	/**
	 * Timer g�n�rale
	 */ 
	private $timer;
	
	/**
	 * Limite de temps pour le cache
	 */ 
	private $cacheTimeLimit;
	
	/**
	 * Limite de temps pour les cookies
	 */ 
	private $cookieTimeLimit;
	
	/**
	 * Informations du client
	 */
	private $user = array();
	
	/**
	 * Nom des cookies
	 */ 
	private $cookieName = array(
		"USER" => "_user_id",
		"SESSION" => "_sess_id",
		"LANGUE" => "_user_langue"
	);
	
	/**
	 * D�marrage du syst�me de session
	 */
	public function __construct() {
		// Reset du client 
		$this->resetUser();
		
		// Marque le timer
		$this->timer = time();
		
		// Gestionnaire des cookie
		Core_Loader::classLoader("Exec_Cookie");
		// Dur�e de validit� du cache en jours
		$this->cacheTimeLimit = Core_Main::$coreConfig['cacheTimeLimit'] * 86400;
		// Dur�e de validit� des cookies
		$this->cookieTimeLimit = $this->timer + (Core_Main::$coreConfig['cookieTimeLimit'] * 86400);
		
		// Compl�te le nom des cookies
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
		$this->selectSession();
	}
	
	/**
	 * Parcours r�cursivement le dossier cache des sessions afin de supprimer les fichiers trop vieux
	 */
	private function checkSessionCache() {
		// V�rification de la validit� du checker
		if (!Core_CacheBuffer::checked($this->timer - $this->cacheTimeLimit)) {
			// Mise � jour ou creation du fichier checker
			Core_CacheBuffer::touchChecker();
			// Suppression du cache p�rim�
			Core_CacheBuffer::cleanCache($this->timer - $this->cacheTimeLimit);
		} 
	}
	
	/**
	 * V�rifie si une session est ouverte
	 * 
	 * @return boolean true une session peut �tre recupere
	 */
	private function sessionFound() {
		if (Exec_Cookie::getCookie($this->cookieName['SESSION']) != ""
				&& Exec_Cookie::getCookie($this->cookieName['USER']) != "") {
			return true;	
		}
		return false;
	}
	
	private function selectSession() {
		if ($this->sessionFound()) {
			$userId = Exec_Cookie::getCookie($this->cookieName['USER']);
			$sessionId = Exec_Cookie::getCookie($this->cookieName['SESSION']);
			
		    if ($userId != "" && $sessionId != "" && Core_CacheBuffer::cached($sessionId . ".php")) {
				// Si fichier cache trouv�, on l'utilise
				$sessions = array();
				$sessions = Core_CacheBuffer::getCache($sessionId . ".php");
				
				// Verification + mise � jour toute les 5 minutes
				if ($sessions['userId'] === $userId) {
					// Mise � jour toute les 5 min
					if ((Core_CacheBuffer::cacheMTime($sessionId . ".php") + (5*60)) < $this->timer) {
						$updVerif = $this->updateLastConnect($userId);
						// Mise a jour du dernier acc�s
						Core_CacheBuffer::touchCache($sessionId . ".php");
					} else {
						$updVerif = 1;
					}
				} else {
					$updVerif = false;
				}
				
				if ($updVerif == false) {
					// La mise � jour a �chou�, on d�truit la session
					$this->sessionClose();
				} else {
					// Injection des informations du client
					$this->setUser($sessions);
				}
		    } else if ($userId != "") {
				// Si plus de fichier cache, on tente de retrouver le client
				Core_Main::$coreSql->select(
					Core_Table::$USERS_TABLE,
					array("user_name", "user_rang"),
					array("user_id = '" . $userID . "'")
				);
				
				if (Core_Main::$coreSql->getQueries() != false) {
					// Si le client a �t� trouv�, on recupere les informations
					list($userName, $userRang) = Core_Main::$coreSql->fetchArray();
					
					// Injection des informations du client
					$this->setUser(array(
						"userId" => $userId,
						"userName" => $userName,
						"userRang" => $userRang,
					));
					// Mise � jour de derniere connexion
					$this->updateLastConnect();
										
					// Creation d'une nouvelle session
					$this->sessionOpen(1);
				} else {
					// userId invalide, on d�truit
					$this->sessionClose();
				}
			}
		}
		return $user;
	}
	
	/**
	 * Mise � jour de la derni�re connexion
	 * 
	 * @return ressource ID ou false 
	 */
	private function updateLastConnect($userId = "") {
		// R�cupere l'id du client
		if (!$userId) $userId = $this->user['userId'];
		
		// Envoie la requ�te Sql
		return Core_Main::$coreSql->update(Core_Table::$USERS_TABLE, 
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
	 * V�rifie si c'est un client connu donc log�
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
	 * Remise � z�ro des informations sur le client
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
		// Destruction d'une �ventuelle session
		$this->sessionClose();
		
		// Chargement de l'outil de cryptage
		Core_Loader::classLoader("Exec_Crypt");
		$this->user['sessionId'] = Exec_Crypt::creatId(34);
		
		// Connexion automatique via cookie
		if ($auto == 1) $cookieTimeLimit = $this->cookieTimeLimit;
		else $cookieTimeLimit = "";
		
		if (Exec_Cookie::createCookie($this->cookieName['USER'], $this->user['userId'], $cookieTimeLimit)) {
			if (Exec_Cookie::createCookie($this->cookieName['SESSION'], $this->user['sessionId'], $cookieTimeLimit)) {
				// Pr�paration des informations pour le cache
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
		
		// Remise � z�ro des infos client
		$this->resetUser();
		
		// Destruction des �ventuelles cookies
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
	 * @return boolean ture succ�s
	 */
	public function startConnection($name, $pass, $auto) {
		// Arr�te de la session courante si il y en a une
		$this->stopConnection();
		
		Core_Main::$coreSql->select(
			Core_Table::$USERS_TABLE,
			array("user_id", "user_rang"),
			array("user_name = '" . $name . "'", "&& user_pass = '" . md5($pass) . "'")
		);
		
		if (Core_Main::$coreSql->getQueries() != false) {
			// Si le client a �t� trouv�, on recupere les informations
			list($id_utilisateur, $rang) = Core_Main::$coreSql->fetchArray();
			// Injection des informations
			$this->setUser(array(
				"userId" => $id_utilisateur,
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
}
?>