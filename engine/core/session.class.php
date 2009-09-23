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
	 * D�marrage du syst�me de session
	 */
	public function __construct() {
		// Reset du client 
		$this->resetUser();
		
		// Marque le timer
		$this->timer = time();
		
		// Dur�e de validit� du cache en jours
		$this->cacheTimeLimit = Core_Main::$coreConfig['cacheTimeLimit'] * 86400;
		
		// Compl�te le nom des cookies
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
	 * Creation et r�cup�ration de l'instance de session
	 * 
	 * @return Core_Session
	 */
	public static function &getInstance() {
		if (self::$session === false) {
			self::$session = new self();
		}
		return self::$session;
	}
	
	/**
	 * V�rifie si une session est ouverte
	 * 
	 * @return boolean true une session peut �tre recupere
	 */
	private function &sessionFound() {
		$cookieUser = $this->getCookie($this->cookieName['USER']);
		$cookieSession = $this->getCookie($this->cookieName['SESSION']);
		if (!empty($cookieUser) && !empty($cookieSession)) {
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
					// Si fichier cache trouv�, on l'utilise
					$sessions = Core_CacheBuffer::getCache($sessionId . ".php");
					
					// Verification + mise � jour toute les 5 minutes
					$updVerif = false;
					if ($sessions['user_id'] == $userId && $sessions['sessionId'] == $sessionId) {
						// Mise � jour toute les 5 min
						if ((Core_CacheBuffer::cacheMTime($sessions['sessionId'] . ".php") + 5*60) < $this->timer) {
							// Mise a jour du dernier acc�s
							$updVerif = $this->updateLastConnect($sessions['user_id']);
							Core_CacheBuffer::touchCache($sessions['sessionId'] . ".php");
						} else {
							$updVerif = true;
						}
					}
					if ($updVerif === true) {
						// Injection des informations du client					
						$this->setUser($sessions);
					} else {
						// La mise � jour a �chou�, on d�truit la session
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
	 */
	private function setUser($info) {	
		self::$userId = $info['user_id'];
		self::$userName = Exec_Entities::stripSlashes($info['name']);
		self::$userMail = $info['mail'];
		self::$userRang = (int) $info['rang'];
		self::$userInscriptionDate = $info['date'];
		self::$userAvatar = $info['avatar'];
		self::$userSignature = Exec_Entities::stripSlashes($info['signature']);
		self::$sessionId = (!empty($info['sessionId'])) ? $info['sessionId'] : self::$sessionId;
		if (empty(self::$userLanguage)) self::$userLanguage = $info['langue'];
		if (empty(self::$userTemplate)) self::$userTemplate = $info['template'];
		if (empty(self::$userIpBan)) self::$userIpBan = (!empty($info['userIpBan'])) ? $info['userIpBan'] : self::$userIpBan;
	}
	
	/**
	 * Mise en chaine de caract�res des infos du client
	 * Pr�paration des informations pour le cache
	 * 
	 * @return String
	 */
	private function &getUser() {
		$rslt = "$" . Core_CacheBuffer::getSectionName() . "['user_id'] = \"" . self::$userId . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['name'] = \"" . Exec_Entities::addSlashes(self::$userName) . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['mail'] = \"" . self::$userMail . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['rang'] = \"" . self::$userRang . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['date'] = \"" . self::$userInscriptionDate . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['avatar'] = \"" . self::$userAvatar . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['signature'] = \"" . Exec_Entities::addSlashes(self::$userSignature) . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['langue'] = \"" . self::$userLanguage . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['template'] = \"" . self::$userTemplate . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['userIpBan'] = \"" . self::$userIpBan . "\"; ";
		$rslt .= "$" . Core_CacheBuffer::getSectionName() . "['sessionId'] = \"" . self::$sessionId . "\"; ";
		return $rslt;
	}
	
	/**
	 * Retourne les infos utilisateur via la base de donn�e
	 * 
	 * @return array
	 */
	private function getUserInfo($where) {
		Core_Sql::select(
			Core_Table::$USERS_TABLE,
			array("user_id", "name", "mail", "rang", "date", "avatar", "signature", "template", "langue"),
			$where
		);
		if (Core_Sql::affectedRows() == 1) {
			return Core_Sql::fetchArray();
		}
		return array();
	}
	
	/**
	 * Suppression de l'Ip bannie
	 */
	public function deleteUserIpBan() {
		self::$userIpBan = "";
		Exec_Cookie::destroyCookie($this->getCookieName($this->cookieName['BLACKBAN']));
	}
	
	/**
	 * Mise � jour de la derni�re connexion
	 * 
	 * @param $userId String
	 * @return boolean true succes de la mise � jour
	 */
	private function &updateLastConnect($userId = "") {
		// R�cupere l'id du client
		if (empty($userId)) $userId = self::$userId;
		Core_Sql::addQuoted("", "NOW()");
		// Envoie la requ�te Sql de mise � jour
		Core_Sql::update(Core_Table::$USERS_TABLE, 
			array("last_connect" => "NOW()"), 
			array("user_id = '" . $userId . "'")
		);
		return ((Core_Sql::affectedRows() == 1) ? true : false);
	}
	
	/**
	 * V�rifie si c'est un client connu donc log�
	 * 
	 * @return boolean true c'est un client
	 */
	public function &isUser() {
		if (!empty(self::$userId)
			&& !empty(self::$userName)
			&& !empty(self::$sessionId)
			&& self::$userRang > 0) {
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
	 * @param $auto boolean connexion automatique
	 * @return boolean ture succ�s
	 */
	private function &sessionOpen($auto = true) {
		self::$sessionId = Exec_Crypt::createId(32);
		
		// Connexion automatique via cookie
		$cookieTimeLimit = ($auto) ? $this->timer + $this->cacheTimeLimit : "";
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
			$this->setUser($user);
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
		
		// Remise � z�ro des infos client
		$this->resetUser();
		
		// Destruction des �ventuelles cookies
		foreach ($this->cookieName as $key => $value) {
			// On �vite de supprimer le cookie de bannissement
			if ($key == "BLACKBAN") continue;
			Exec_Cookie::destroyCookie($this->getCookieName($this->cookieName[$key]));
		}
	}
	
	/**
	 * Tentative de creation d'un nouvelle session
	 * 
	 * @param $name String Nom du compte (identifiant)
	 * @param $pass String Mot de passe du compte
	 * @param $auto boolean Connexion automatique
	 * @return boolean ture succ�s
	 */
	public function &startConnection($userName, $userPass, $auto) {
		// Arr�te de la session courante si il y en a une
		$this->stopConnection();
		
		if ($this->validLogin($userName) && $this->validPassword($userPass)) {
			$userPass = $this->cryptPass($userPass);
			$user = $this->getUserInfo(array("name = '" . $userName . "'", "&& pass = '" . $userPass . "'"));
			
			if (count($user) > 1) {
				// Injection des informations du client
				$this->setUser($user);
				
				// Tentative d'ouverture de session
				return $this->sessionOpen($auto);
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
		return Core_Main::$coreConfig['cryptKey'] . Exec_Agent::$userBrowserName;
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
	 * Retourne le contenu d�crypt� du cookie
	 * 
	 * @param $cookieName String
	 * @return String
	 */
	private function &getCookie($cookieName) {
		$cookieName = $this->getCookieName($cookieName);
		$cookieContent = Exec_Cookie::getCookie($cookieName);
		return Exec_Crypt::md5Decrypt($cookieContent, $this->getSalt());
	}
	
	/**
	 * Retourne le nom crypt� du cookie
	 * 
	 * @param $cookieName String
	 * @return String
	 */
	private function &getCookieName($cookieName) {
		return Exec_Crypt::cryptData($cookieName, $this->getSalt(), "md5+");
	}
	
	/**
	 * V�rification du login
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
	 * V�rification du password
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