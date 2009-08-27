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
	private static $session = false;
	
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
	 * Limite de temps pour les cookies
	 * 
	 * @var int
	 */ 
	private $cookieTimeLimit = 0;
	
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
		
		// Configuration du gestionnaire de cache
		Core_CacheBuffer::setSectionName("sessions");
		
		// Nettoyage du cache
		$this->checkSessionCache();
		
		// Lanceur de session
		$this->sessionSelect();
	}
	
	/**
	 * Creation et récupèration de l'instance de session
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
		$cookieSession = Exec_Cookie::getCookie($this->cookieName['SESSION']);
		$cookieUser = Exec_Cookie::getCookie($this->cookieName['USER']);
		if (!empty($cookieSession) && !empty($cookieUser)) {
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
			self::$userLanguage = $userLanguage;
			// Cookie de template
			$userTemplate = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['TEMPLATE'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			self::$userTemplate = $userTemplate;
			// Cookie de l'IP BAN voir Core_BlackBan
			$userIpBan = Exec_Crypt::md5Decrypt(
				Exec_Cookie::getCookie(
					Exec_Crypt::md5Encrypt(
						$this->cookieName['BLACKBAN'],
						$this->getSalt()
					), $this->getSalt()
				)
			);
			self::$userIpBan = $userIpBan;
			
		    if (!empty($userId) && !empty($sessionId) && Core_CacheBuffer::cached($sessionId . ".php")) {
				// Si fichier cache trouvé, on l'utilise
				$sessions = Core_CacheBuffer::getCache($sessionId . ".php");
				
				// Verification + mise à jour toute les 5 minutes
				if ($sessions['user_id'] == $userId) {
					// Mise à jour toute les 5 min
					if ((Core_CacheBuffer::cacheMTime($sessions['sessionId'] . ".php") + (5*60)) < $this->timer) {
						$updVerif = $this->updateLastConnect($sessions['user_id']);
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
		    } else if (!empty($userId)) {
				// Si plus de fichier cache, on tente de retrouver le client
				$user = $this->getUserInfo(array("user_id = '" . $userId . "'"));
				if (count($user) > 1) {					
					// Injection des informations du client
					$this->setUser($user);
					
					// Mise à jour de derniere connexion
					$this->updateLastConnect();
										
					// Creation d'une nouvelle session
					$this->sessionOpen();
				} else {
					// userId invalide, on détruit
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
		self::$sessionId = $info['sessionId'];
		if (empty(self::$userLanguage)) self::$userLanguage = $info['langue'];
		if (empty(self::$userTemplate)) self::$userTemplate = $info['template'];
		if (empty(self::$userIpBan)) self::$userIpBan = $info['userIpBan'];
	}
	
	/**
	 * Mise en chaine de caractères des infos du client
	 * Préparation des informations pour le cache
	 * 
	 * @return String
	 */
	private function getUser() {
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
	 * Retourne les infos utilisateur via la base de donnée
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
		Exec_Cookie::destroyCookie(
			Exec_Crypt::md5Encrypt(
				$this->cookieName['BLACKBAN'],
				$this->getSalt()
			)
		);
	}
	
	/**
	 * Mise à jour de la dernière connexion
	 * 
	 * @return ressource ID ou false 
	 */
	private function updateLastConnect($userId = "") {
		// Récupere l'id du client
		if (empty($userId)) $userId = self::$userId;
		
		// Envoie la requête Sql
		return Core_Sql::update(Core_Table::$USERS_TABLE, 
			array("last_connect" => "NOW()"), 
			array("user_id" => $userId)
		);
	}
	
	/**
	 * Vérifie si c'est un client connu donc logé
	 * 
	 * @return boolean true c'est un client
	 */
	public function isUser() {
		if (!empty(self::$userId)
			&& !empty(self::$userName)
			&& !empty(self::$sessionId)) {
				return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Remise à zéro des informations sur le client
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
	 * @return boolean ture succès
	 */
	private function sessionOpen($auto = true) {
		self::$sessionId = Exec_Crypt::creatId(32);
		
		// Connexion automatique via cookie
		$cookieTimeLimit = ($auto) ? $this->cookieTimeLimit : "";
		
		$cookieUser = Exec_Cookie::createCookie(
			Exec_Crypt::md5Encrypt(
				$this->cookieName['USER'], 
				$this->getSalt()
			), Exec_Crypt::md5Encrypt(
				$this->user['userId'], 
				$this->getSalt()
		), $cookieTimeLimit);
		$cookieSession = Exec_Cookie::createCookie(
			Exec_Crypt::md5Encrypt(
				$this->cookieName['SESSION'],
				$this->getSalt()
			), Exec_Crypt::md5Encrypt(
				$this->user['sessionId'],
				$this->getSalt()
		), $cookieTimeLimit);
		
		if ($cookieUser && $cookieSession) {
			// Ecriture du cache
			Core_CacheBuffer::writingCache(self::$sessionId . ".php", $this->getUser());
			return true;
		} else {
			Core_Exception::addNoteError(ERROR_SESSION_COOKIE);
			return false;
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
		
		// Remise à zéro des infos client
		$this->resetUser();
		
		// Destruction des éventuelles cookies
		foreach ($this->cookieName as $key => $value) {
			// On évite de supprimer le cookie de bannissement
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
	 * @param $name String Nom du compte (identifiant)
	 * @param $pass String Mot de passe du compte
	 * @param $auto boolean Connexion automatique
	 * @return boolean ture succès
	 */
	public function startConnection($userName, $userPass, $auto) {
		// Arrête de la session courante si il y en a une
		$this->stopConnection();
		
		if ($this->validLogin($userName) && $this->validPassword($userPass)) {
			$userPass = Exec_Crypt::cryptData($userPass, "", "my411");
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
	 * Retourne la combinaison de cles pour le salt
	 * 
	 * @return String
	 */
	private function getSalt() {
		return Core_Main::$coreConfig['cryptKey'] . Exec_Agent::$userBrowserName;
	}
	
	/**
	 * Vérification du login
	 * 
	 * @param $login
	 * @return boolean true login valide
	 */
	public function validLogin($login) {
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
	public function validPassword($password) {
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
	 * @return String
	 */
	public function getErrorMessage($key = "") {
		if (!empty($key)) return $this->errorMessage[$key];
		return $this->errorMessage;
	}
	
	/**
	 * Retourne le code javascript vérifiant le login et le mot de passe
	 * 
	 * @param $loginId String id du champs login
	 * @param $passwordId String id du champs password
	 * @return String
	 */
	public static function getJavascriptLogon($formId, $loginId, $passwordId) {
		$formId = "#" . $formId;
		$loginId = "#" . $loginId;
		$passwordId = "#" . $passwordId;
		return "validLogin('" . $formId . "', '" . $loginId . "', '" . $passwordId . "');";
	}
}
?>