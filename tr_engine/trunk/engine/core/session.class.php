<?php
if (!defined("TR_ENGINE_INDEX")) {
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
	 * Instance de la session
	 * 
	 * @var Core_Session
	 */
	private static $session = false;
	
	/**
	 * Timer g�n�rale
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
	 * Id du client
	 * 
	 * @var String
	 */
	public static $userId = "";
	
	/**
	 * Nom du client
	 * 
	 * @var String
	 */
	public static $userName = "";
	
	/**
	 * Type de compte li� au client
	 * 
	 * @var int
	 */
	public static $userRang = 0;
	
	/**
	 * Id de la session courante du client
	 * 
	 * @var String
	 */
	public static $sessionId = "";
	
	/**
	 * Langue du client
	 * 
	 * @var String
	 */
	public static $userLanguage = "";
	
	/**
	 * Template du client
	 * 
	 * @var String
	 */
	public static $userTemplate = "";
	
	/**
	 * Adresse Ip du client bannis
	 * 
	 * @var String
	 */
	public static $userIpBan = "";

	/**
	 * Nom des cookies
	 * 
	 * @var array
	 */ 
	private $cookieName = array(
		"USER" => "_user_id",
		"SESSION" => "_sess_id",
		"LANGUE" => "_user_langue",
		"TEMPLATE" => "_user_template",
		"BLACKBAN" => "_user_ip_ban"
	);
	
	/**
	 * D�marrage du syst�me de session
	 */
	public function __construct() {
		// Reset du client 
		$this->resetUser();
		
		// Marque le timer
		$this->timer = time();
		
		// Dur�e de validit� du cache en jours
		$this->cacheTimeLimit = Core_Main::$coreConfig['cacheTimeLimit'] * 86400;
		// Dur�e de validit� des cookies
		$this->cookieTimeLimit = $this->timer + (Core_Main::$coreConfig['cookieTimeLimit'] * 86400);
		
		// Compl�te le nom des cookies
		foreach ($this->cookieName as $key => $name) {
			$this->cookieName[$key] = Core_Main::$coreConfig['cookiePrefix'] . $name;
		}
		
		// Configuration du gestionnaire de cache
		Core_CacheBuffer::setSectionName("sessions");
		
		// Nettoyage du cache
		$this->checkSessionCache();
		
		// Lanceur de session
		$this->sessionSelect();
	}
	
	/**
	 * Creation et r�cup�ration de l'instance de session
	 * 
	 * @return Core_Session
	 */
	public static function &getInstance() {
		if (!self::$session) {
			self::$session = new self();
		}
		return self::$session;
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
	
	/**
	 * Recuperation d'une session ouverte
	 */
	private function sessionSelect() {
		if ($this->sessionFound()) {			
			// Cookie de l'id du client
			$userId = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['USER'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			// Cookie de session
			$sessionId = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['SESSION'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			// Cookie de langue
			$userLanguage = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['LANGUE'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			// Cookie de langue
			$userTemplate = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['TEMPLATE'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			// Cookie de l'IP BAN voir Core_BlackBan
			$userIpBan = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['BLACKBAN'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			
		    if ($userId != "" && $sessionId != "" && Core_CacheBuffer::cached($sessionId . ".php")) {
				// Si fichier cache trouv�, on l'utilise
				$sessions = Core_CacheBuffer::getCache($sessionId . ".php");
				
				// Verification + mise � jour toute les 5 minutes
				if ($sessions['userId'] == $userId) {
					// Mise � jour toute les 5 min
					if ((Core_CacheBuffer::cacheMTime($sessions['sessionId'] . ".php") + (5*60)) < $this->timer) {
						$updVerif = $this->updateLastConnect($sessions['userId']);
						// Mise a jour du dernier acc�s
						Core_CacheBuffer::touchCache($sessions['sessionId'] . ".php");
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
					self::$userId = $sessions['userId'];
					self::$userName = Exec_Entities::stripSlashes($sessions['userName']);
					self::$userRang = $sessions['userRang'];
					self::$sessionId = $sessions['sessionId'];
					self::$userLanguage = ($userLanguage != "") ? $userLanguage : $sessions['userLanguage'];
					self::$userTemplate = ($userTemplate != "") ? $userTemplate : $sessions['userTemplate'];
					self::$userIpBan = $userIpBan;
				}
		    } else if ($userId != "") {
				// Si plus de fichier cache, on tente de retrouver le client
				Core_Sql::select(
					Core_Table::$USERS_TABLE,
					array("user_name", "user_rang", "user_language", "user_template"),
					array("user_id = '" . $userId . "'")
				);
				
				if (Core_Sql::affectedRows() > 0) {
					// Si le client a �t� trouv�, on recupere les informations
					list($userName, $userRang, $userLanguage, $userTemplate) = Core_Sql::fetchArray();
					
					// Injection des informations du client
					self::$userId = $userId;
					self::$userName = $userName;
					self::$userRang = $userRang;
					self::$userLanguage = $userLanguage;
					self::$userTemplate = $userTemplate;
					self::$userIpBan = $userIpBan;
					
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
	}
	
	/**
	 * Suppression de l'Ip bannie
	 */
	public function deleteUserIpBan() {
		self::$userIpBan = "";
		
		Exec_Cookie::destroyCookie(
			Exec_Crypt::md5Encrypt(
				$this->cookieName['BLACKBAN'],
				$this->getSalt()
			)
		);
	}
	
	/**
	 * Mise � jour de la derni�re connexion
	 * 
	 * @return ressource ID ou false 
	 */
	private function updateLastConnect($userId = "") {
		// R�cupere l'id du client
		if (!$userId) $userId = self::$userId;
		
		// Envoie la requ�te Sql
		return Core_Sql::update(Core_Table::$USERS_TABLE, 
			array("last_connect" => "NOW()"), 
			array("user_id" => $userId));
	}
	
	/**
	 * V�rifie si c'est un client connu donc log�
	 * 
	 * @return boolean true c'est un client
	 */
	private function isUser() {
		if (self::$userId != ""
			&& self::$userName != ""
			&& self::$sessionId != "") {
				return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Remise � z�ro des informations sur le client
	 */
	private function resetUser() {
		self::$userId = "";
		self::$userName = "";
		self::$userRang = 0;
		self::$sessionId = "";
		self::$userLanguage = "";
		self::$userTemplate = "";
		self::$userIpBan = "";
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
		self::$sessionId = Exec_Crypt::creatId(32);
		
		// Connexion automatique via cookie
		if ($auto == 1) $cookieTimeLimit = $this->cookieTimeLimit;
		else $cookieTimeLimit = "";
		
		if (Exec_Cookie::createCookie(
				Exec_Crypt::md5Encrypt(
					$this->cookieName['USER'], 
					$this->getSalt()
				), Exec_Crypt::md5Encrypt(
					$this->user['userId'], 
					$this->getSalt()
				), $cookieTimeLimit)) {
			if (Exec_Cookie::createCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['SESSION'],
						$this->getSalt()
					), Exec_Crypt::md5Encrypt(
						$this->user['sessionId'],
						$this->getSalt()
					), $cookieTimeLimit)) {
				// Pr�paration des informations pour le cache
				$content = "";
				foreach ($this->user as $key => $value) {
					$content .= "$" . Core_CacheBuffer::getSectionName() . "[" . $key . "] = \"" . Exec_Entities::addSlashes($value) . "\"; ";
				}
				// Ecriture du cache
				Core_CacheBuffer::writingCache(self::$sessionId . ".php",	$content);
			}
		}
	}
	
	/**
	 * Ferme une session ouverte
	 */
	private function sessionClose() {
		// Destruction du fichier de session
		if (Core_CacheBuffer::cached(self::$sessionId . ".php")) {
			Core_CacheBuffer::removeCache(self::$sessionId . ".php");
		}
		
		// Remise � z�ro des infos client
		$this->resetUser();
		
		// Destruction des �ventuelles cookies
		foreach ($this->cookieName as $key => $value) {
			// On �vite de supprimer le cookie de bannissement
			if ($key == "BLACKBAN") continue;
			
			Exec_Cookie::destroyCookie(
				Exec_Crypt::md5Encrypt(
					$this->cookieName[$key],
					$this->getSalt()
				)
			);
		}
	}
	
	/**
	 * Tentative de creation d'un nouvelle session
	 * 
	 * @param $name Nom du compte (identifiant) crypt� !
	 * @param $pass Mot de passe du compte crypt� !
	 * @param $auto Connexion automatique
	 * @return boolean ture succ�s
	 */
	public function startConnection($userName, $userPass, $auto) {
		// Arr�te de la session courante si il y en a une
		$this->stopConnection();
		
		$userName = Exec_Crypt::md5Decrypt($userName, $this->getSalt());
		
		Core_Sql::select(
			Core_Table::$USERS_TABLE,
			array("user_id", "user_rang", "user_language", "user_template"),
			array("user_name = '" . $userName . "'", "&& user_pass = '" . $userPass . "'")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			// Si le client a �t� trouv�, on recupere les informations
			list($userId, $userRang, $userLanguage, $userTemplate) = Core_Sql::fetchArray();
			
			// Injection des informations du client
			self::$userId = $userId;
			self::$userName = $userName;
			self::$userRang = $userRang;
			self::$userLanguage = $userLanguage;
			self::$userTemplate = $userTemplate;
			
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
	 * Retourne la combinaison de cles pour le salt
	 * 
	 * @return String
	 */
	private function getSalt() {
		return Core_Main::$coreConfig['cryptKey'] . Exec_Agent::$userBrowserName;
	}
	
	public function getAdminRight($userIdAdmin = "") {
		// Id du client courant sinon utilisation de l'id indiqu�
		if (!$userIdAdmin) $userIdAdmin = self::$userId;
		else $userIdAdmin = Exec_Entities::secureText($userIdAdmin);
		
		Core_Sql::select(
			Core_Table::$ADMIN_USERS_TABLE,
			array("rights"),
			array("user_id = '" . $userIdAdmin . "'")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			list($rights) = Core_Sql::fetchArray();
			return explode("|", $rights);
		}
		return false;
	}
}
?>