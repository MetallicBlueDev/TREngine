<?php
if (!defined("TR_ENGINE_INDEX")) {
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
	 * Instance de la session
	 * 
	 * @var Core_Session
	 */
	private static $session = null;
	
	/**
	 * Timer générale
	 * 
	 * @var int
	 */ 
	private $timer = 0;
	
	/**
	 * Limite de temps pour le cache
	 * 
	 * @var int
	 */ 
	private $cacheTimeLimit = 0;
	
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
	 * Type de compte lié au client
	 * 
	 * @var int
	 */
	public static $userRank = 0;
	
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
	 * URL de l'avatar de l'utilisateur
	 * 
	 * @var String
	 */
	public static $userAvatar = "includes/avatars/nopic.png";
	
	/**
	 * Adresse email du client
	 * 
	 * @var String
	 */
	public static $userMail = "";
	
	/**
	 * Date d'inscription du client
	 * 
	 * @var String
	 */
	public static $userInscriptionDate = "";
	
	/**
	 * Signature du client
	 * 
	 * @var String
	 */
	public static $userSignature = "";
	
	/**
	 * Site Internet du client
	 * 
	 * @var String
	 */
	public static $userWebSite = "";

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
	 * Message d'erreur de connexion
	 * 
	 * @var array
	 */
	private $errorMessage = array();
	
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
		
		// Complète le nom des cookies
		foreach ($this->cookieName as $key => $name) {
			$this->cookieName[$key] = Core_Main::$coreConfig['cookiePrefix'] . $name;
		}
		
		// Configuration du gestionnaire de cache
		Core_CacheBuffer::setSectionName("sessions");
		// Nettoyage du cache
		Core_CacheBuffer::cleanCache($this->timer - $this->cacheTimeLimit);
		
		// Lanceur de session
		$this->sessionSelect();
	}
	
	/**
	 * Creation et récupèration de l'instance de session
	 * 
	 * @return Core_Session
	 */
	public static function &getInstance() {
		if (self::$session == null) {
			self::$session = new self();
		}
		return self::$session;
	}
	
	/**
	 * Vérifie si une session est ouverte
	 * 
	 * @return boolean true une session peut être recupere
	 */
	private function sessionFound() {
		$cookieUser = $this->getCookie($this->cookieName['USER']);
		$cookieSession = $this->getCookie($this->cookieName['SESSION']);
		return (!empty($cookieUser) && !empty($cookieSession));
	}
	
	/**
	 * Recuperation d'une session ouverte
	 */
	private function sessionSelect() {
		if ($this->sessionFound()) {
			// Cookie de l'id du client
			$userId = $this->getCookie($this->cookieName['USER']);
			// Cookie de session
			$sessionId = $this->getCookie($this->cookieName['SESSION']);
			// Cookie de langue
			$userLanguage = $this->getCookie($this->cookieName['LANGUE']);
			self::$userLanguage = $userLanguage;
			// Cookie de template
			$userTemplate = $this->getCookie($this->cookieName['TEMPLATE']);
			self::$userTemplate = $userTemplate;
			// Cookie de l'IP BAN voir Core_BlackBan
			$userIpBan = $this->getCookie($this->cookieName['BLACKBAN']);
			self::$userIpBan = $userIpBan;
			
		    if (!empty($userId) && !empty($sessionId)) {
		    	if (Core_CacheBuffer::cached($sessionId . ".php")) {
					// Si fichier cache trouvé, on l'utilise
					$sessions = Core_CacheBuffer::getCache($sessionId . ".php");
					
					// Verification + mise à jour toute les 5 minutes
					$updVerif = false;
					if ($sessions['userId'] == $userId && $sessions['sessionId'] == $sessionId) {
						// Mise à jour toute les 5 min
						if ((Core_CacheBuffer::cacheMTime($sessions['sessionId'] . ".php") + 5*60) < $this->timer) {
							// Mise a jour du dernier accès
							$updVerif = $this->updateLastConnect($sessions['userId']);
							Core_CacheBuffer::touchCache($sessions['sessionId'] . ".php");
						} else {
							$updVerif = true;
						}
					}
					if ($updVerif === true) { // Injection des informations du client					
						$this->setUser($sessions);
					} else { // La mise à jour a échoué, on détruit la session
						$this->sessionClose();
					}
		    	} else {
		    		$this->sessionClose();
		    	}
		    }
		}
	}
	
	/**
	 * Injection des informations du client
	 * 
	 * @param $info array
	 * @param $refreshAll boolean
	 */
	private function setUser($info, $refreshAll = false) {	
		self::$userId = isset($info['userId']) ? $info['userId'] : $info['user_id'];
		self::$userName = Exec_Entities::stripSlashes($info['name']);
		self::$userMail = $info['mail'];
		self::$userRank = (int) $info['rank'];
		self::$userInscriptionDate = $info['date'];
		self::$userAvatar = $info['avatar'];
		self::$userSignature = Exec_Entities::stripSlashes($info['signature']);
		self::$userWebSite = Exec_Entities::stripSlashes($info['website']);
		self::$sessionId = (!empty($info['sessionId'])) ? $info['sessionId'] : self::$sessionId;
		self::$userIpBan = (!empty($info['userIpBan'])) ? $info['userIpBan'] : self::$userIpBan;
		
		// Force une mise à jour complète
		if ($refreshAll) {
			self::$userLanguage = $info['langue'];
			self::$userTemplate = $info['template'];
		} else {
			// Mise à jour uniquement si aucune donnée
			if (empty(self::$userLanguage)) self::$userLanguage = $info['langue'];
			if (empty(self::$userTemplate)) self::$userTemplate = $info['template'];
		}
	}
	
	/**
	 * Mise en chaine de caractères des informations du client
	 * 
	 * @return String
	 */
	private function &getUser() {
		$rslt = "";
		$data = array(
			"userId" => self::$userId,
			"name" => self::$userName,
			"mail" => self::$userMail,
			"rank" => self::$userRank,
			"date" => self::$userInscriptionDate,
			"avatar" => self::$userAvatar,
			"signature" => self::$userSignature,
			"website" => self::$userWebSite,
			"langue" => self::$userLanguage,
			"template" => self::$userTemplate,
			"userIpBan" => self::$userIpBan,
			"sessionId" => self::$sessionId
		);
		$rslt = Core_CacheBuffer::serializeData($data);
		return $rslt;
	}
	
	/**
	 * Retourne les infos utilisateur via la base de donnée
	 * 
	 * @return array
	 */
	private function getUserInfo($where) {
		Core_Sql::select(
			Core_Table::$USERS_TABLE,
			array("user_id", "name", "mail", "rank", "date", "avatar", "website", "signature", "template", "langue"),
			$where
		);
		return (Core_Sql::affectedRows() == 1) ? Core_Sql::fetchArray() : array();
	}
	
	/**
	 * Suppression de l'Ip bannie
	 */
	public function deleteUserIpBan() {
		self::$userIpBan = "";
		Exec_Cookie::destroyCookie($this->getCookieName($this->cookieName['BLACKBAN']));
	}
	
	/**
	 * Mise à jour de la dernière connexion
	 * 
	 * @param $userId String
	 * @return boolean true succes de la mise à jour
	 */
	private function &updateLastConnect($userId = "") {
		// Récupere l'id du client
		if (empty($userId)) $userId = self::$userId;
		Core_Sql::addQuoted("", "NOW()");
		// Envoie la requête Sql de mise à jour
		Core_Sql::update(Core_Table::$USERS_TABLE, 
			array("last_connect" => "NOW()"), 
			array("user_id = '" . $userId . "'")
		);
		return (Core_Sql::affectedRows() == 1) ? true : false;
	}
	
	/**
	 * Vérifie si c'est un client connu donc logé
	 * 
	 * @return boolean true c'est un client
	 */
	public function &isUser() {
		if (!empty(self::$userId)
			&& !empty(self::$userName)
			&& !empty(self::$sessionId)
			&& self::$userRank > 0) {
				return true;
		}
		return false;
	}
	
	/**
	 * Remise à zéro des informations sur le client
	 */
	private function resetUser() {
		self::$userId = "";
		self::$userName = "";
		self::$userRank = 0;
		self::$sessionId = "";
		self::$userLanguage = "";
		self::$userTemplate = "";
		self::$userIpBan = "";
	}
	
	/**
	 * Ouvre une nouvelle session
	 * 
	 * @return boolean ture succès
	 */
	private function &sessionOpen() {
		self::$sessionId = Exec_Crypt::createId(32);
		
		// Durée de connexion automatique via cookie
		$cookieTimeLimit = $this->timer + $this->cacheTimeLimit;
		// Creation des cookies
		$cookieUser = Exec_Cookie::createCookie(
			$this->getCookieName($this->cookieName['USER']), 
			Exec_Crypt::md5Encrypt(
				self::$userId, 
				$this->getSalt()
			), 
			$cookieTimeLimit
		);
		$cookieSession = Exec_Cookie::createCookie(
			$this->getCookieName($this->cookieName['SESSION']), 
			Exec_Crypt::md5Encrypt(
				self::$sessionId, 
				$this->getSalt()
			), 
			$cookieTimeLimit
		);
		
		if ($cookieUser && $cookieSession) {
			// Ecriture du cache
			Core_CacheBuffer::setSectionName("sessions");
			Core_CacheBuffer::writingCache(self::$sessionId . ".php", $this->getUser());
			return true;
		} else {
			Core_Exception::addNoteError(ERROR_SESSION_COOKIE);
			return false;
		}
	}
	
	/**
	 * Rafraichir le cache de session
	 */
	private function sessionRefresh() {
		$user = $this->getUserInfo(array("user_id = '" . self::$userId . "'"));

		if (count($user) > 1) {
			$this->setUser($user, true);
			Core_CacheBuffer::setSectionName("sessions");
			Core_CacheBuffer::writingCache(self::$sessionId . ".php", $this->getUser(), true);
		}
	}
	
	/**
	 * Ferme une session ouverte
	 */
	private function sessionClose() {
		// Destruction du fichier de session
		Core_CacheBuffer::setSectionName("sessions");
		if (Core_CacheBuffer::cached(self::$sessionId . ".php")) {
			Core_CacheBuffer::removeCache(self::$sessionId . ".php");
		}
		
		// Remise à zéro des infos client
		$this->resetUser();
		
		// Destruction des éventuelles cookies
		foreach ($this->cookieName as $key => $value) {
			// On évite de supprimer le cookie de bannissement
			if ($key == "BLACKBAN") continue;
			Exec_Cookie::destroyCookie($this->getCookieName($this->cookieName[$key]));
		}
	}
	
	/**
	 * Tentative de creation d'un nouvelle session
	 * 
	 * @param $name String Nom du compte (identifiant)
	 * @param $pass String Mot de passe du compte
	 * @return boolean ture succès
	 */
	public function &startConnection($userName, $userPass) {
		// Arrête de la session courante si il y en a une
		$this->stopConnection();
		
		if ($this->validLogin($userName) && $this->validPassword($userPass)) {
			$userPass = $this->cryptPass($userPass);
			$user = $this->getUserInfo(array("name = '" . $userName . "'", "&& pass = '" . $userPass . "'"));
			
			if (count($user) > 1) {
				// Injection des informations du client
				$this->setUser($user);
				
				// Tentative d'ouverture de session
				return $this->sessionOpen();
			} else {
				$this->errorMessage['login'] = ERROR_LOGIN_OR_PASSWORD_INVALID;
			}
		}
		return false;
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
	 * Rafraichis la connexion courante avec le client
	 */
	public function refreshConnection() {
		if ($this->isUser()) {
			$this->sessionRefresh();
		}
	}
	
	/**
	 * Retourne la combinaison de cles pour le salt
	 * 
	 * @return String
	 */
	private function &getSalt() {
		$salt = Core_Main::$coreConfig['cryptKey'] . Exec_Agent::$userBrowserName;
		return $salt;
	}
	
	/**
	 * Crypte un mot de passe pour un compte client
	 * 
	 * @param $pass String
	 * @return String
	 */
	public function &cryptPass($pass) {
		return Exec_Crypt::cryptData($pass, $pass, "md5+");
	}
	
	/**
	 * Retourne le contenu décrypté du cookie
	 * 
	 * @param $cookieName String
	 * @return String
	 */
	private function &getCookie($cookieName) {
		$cookieName = $this->getCookieName($cookieName);
		$cookieContent = Exec_Cookie::getCookie($cookieName);
		$cookieContent = Exec_Crypt::md5Decrypt($cookieContent, $this->getSalt());
		return $cookieContent;
	}
	
	/**
	 * Retourne le nom crypté du cookie
	 * 
	 * @param $cookieName String
	 * @return String
	 */
	private function &getCookieName($cookieName) {
		return Exec_Crypt::cryptData($cookieName, $this->getSalt(), "md5+");
	}
	
	/**
	 * Vérification du login
	 * 
	 * @param $login
	 * @return boolean true login valide
	 */
	public function &validLogin($login) {
		if (!empty($login)) {
			$len = strlen($login);
			if ($len >= 3 && $len <= 16) {
				if (preg_match("/^[A-Za-z0-9_-]{3,16}$/ie", $login)) {
					return true;
				} else {
					$this->errorMessage['login'] = ERROR_LOGIN_CARACTERE;
				}
			} else {
				$this->errorMessage['login'] = ERROR_LOGIN_NUMBER_CARACTERE;
			}
		} else {
			$this->errorMessage['login'] = ERROR_LOGIN_EMPTY;
		}
		return false;
	}
	
	/**
	 * Vérification du password
	 * 
	 * @param $password
	 * @return boolean true password valide
	 */
	public function &validPassword($password) {
		if (!empty($password)) {
			if (strlen($password) >= 5) {
				return true;
			} else {
				$this->errorMessage['password'] = ERROR_PASSWORD_NUMBER_CARACTERE;
			}
		} else {
			$this->errorMessage['password'] = ERROR_PASSWORD_EMPTY;
		}
		return false;
	}
	
	/**
	 * Retourne un message d'erreur
	 * 
	 * @param $key
	 * @return String or array
	 */
	public function getErrorMessage($key = "") {
		if (!empty($key)) return $this->errorMessage[$key];
		return $this->errorMessage;
	}
}
?>