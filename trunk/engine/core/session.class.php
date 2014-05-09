<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire des sessions.
 *
 * @author Sébastien Villemain
 */
class Core_Session {

    /**
     * Instance de la session.
     *
     * @var Core_Session
     */
    private static $coreSession = null;

    /**
     * Message d'erreur de connexion.
     *
     * @var array
     */
    private static $errorMessage = array();

    /**
     * Timer générale.
     *
     * @var int
     */
    private $timer = 0;

    /**
     * Limite de temps pour le cache.
     *
     * @var int
     */
    private $cacheTimeLimit = 0;

    /**
     * Id du client.
     *
     * @var string
     */
    public $userId = "";

    /**
     * Nom du client.
     *
     * @var string
     */
    public $userName = "";

    /**
     * Type de compte lié au client.
     *
     * @var int
     */
    public $userRank = 0;

    /**
     * Id de la session courante du client.
     *
     * @var string
     */
    private $sessionId = "";

    /**
     * Langue du client.
     *
     * @var string
     */
    public $userLanguage = "";

    /**
     * Template du client.
     *
     * @var string
     */
    public $userTemplate = "";

    /**
     * Adresse Ip du client bannis.
     *
     * @var string
     */
    public $userIpBan = "";

    /**
     * URL de l'avatar de l'utilisateur.
     *
     * @var string
     */
    public $userAvatar = "includes/avatars/nopic.png";

    /**
     * Adresse email du client.
     *
     * @var string
     */
    public $userMail = "";

    /**
     * Date d'inscription du client.
     *
     * @var string
     */
    private $userInscriptionDate = "";

    /**
     * Signature du client.
     *
     * @var string
     */
    public $userSignature = "";

    /**
     * Site Internet du client.
     *
     * @var string
     */
    private $userWebSite = "";

    /**
     * Nom des cookies.
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
     * Démarrage du système de session.
     *
     * @param boolean $setupCache Configuration du cache
     */
    private function __construct($setupCache) {
        // Marque le timer
        $this->timer = time();

        $coreMain = Core_Main::getInstance();

        // Durée de validité du cache en jours
        $this->cacheTimeLimit = $coreMain->getCacheTimeLimit() * 86400;

        // Complète le nom des cookies
        foreach ($this->cookieName as $key => $name) {
            $this->cookieName[$key] = $coreMain->getCookiePrefix() . $name;
        }

        if ($setupCache) {
            // Nettoyage du cache
            Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS)->cleanCache($this->timer - $this->cacheTimeLimit);
        }
    }

    /**
     * Instance du gestionnaire des sessions.
     *
     * @return Core_Session
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$coreSession;
    }

    /**
     * Vérification de l'instance du gestionnaire des sessions.
     */
    public static function checkInstance() {
        if (self::$coreSession === null) {
            // Création d'un instance autonome
            self::$coreSession = new self(true);

            // Lanceur de session
            if (!self::$coreSession->searchSession()) {
                // La session est potentiellement corrompue
                self::stopConnection();

                // Nouvelle instance vierge
                self::$coreSession = new self(false);
            }
        }
    }

    /**
     * Tentative de création d'un nouvelle session.
     *
     * @param string $userName Nom du compte (identifiant)
     * @param string $userPass Mot de passe du compte
     * @return boolean true succès
     */
    public static function &startConnection($userName, $userPass) {
        $rslt = false;

        // Arrête de la session courante si besoin
        self::stopConnection();

        if (self::validLogin($userName) && self::validPassword($userPass)) {
            $userPass = self::cryptPass($userPass);
            $userInfos = self::getUserInfo(array(
                "name = '" . $userName . "'",
                "&& pass = '" . $userPass . "'"));

            if (count($userInfos) > 1) {
                $newSession = self::getInstance(false);

                // Injection des informations du client
                $newSession->setUser($userInfos);

                // Tentative d'ouverture de session
                $rslt = $newSession->openSession();

                if (!$rslt) {
                    self::stopConnection();
                }
            } else {
                self::$errorMessage['login'] = ERROR_LOGIN_OR_PASSWORD_INVALID;
            }
        }
        return $rslt;
    }

    /**
     * Détermine si une session existe.
     *
     * @return boolean
     */
    public static function hasConnection() {
        return (self::$coreSession !== null && self::$coreSession->userLogged());
    }

    /**
     * Coupe proprement une session ouverte.
     */
    public static function stopConnection() {
        if (self::hasConnection()) {
            self::$coreSession->closeSession();
        }

        if (self::$coreSession !== null) {
            self::$coreSession = null;
        }
    }

    /**
     * Vérification du nom du compte.
     *
     * @param string $login
     * @return boolean true login valide
     */
    public static function &validLogin($login) {
        $rslt = false;

        if (!empty($login)) {
            $len = strlen($login);

            if ($len >= 3 && $len <= 16) {
                if (preg_match("/^[A-Za-z0-9_-]{3,16}$/ie", $login)) {
                    $rslt = true;
                } else {
                    self::$errorMessage['login'] = ERROR_LOGIN_CARACTERE;
                }
            } else {
                self::$errorMessage['login'] = ERROR_LOGIN_NUMBER_CARACTERE;
            }
        } else {
            self::$errorMessage['login'] = ERROR_LOGIN_EMPTY;
        }
        return $rslt;
    }

    /**
     * Vérification du mot de passe.
     *
     * @param string $password
     * @return boolean true password valide
     */
    public static function &validPassword($password) {
        $rslt = false;

        if (!empty($password)) {
            if (strlen($password) >= 5) {
                $rslt = true;
            } else {
                self::$errorMessage['password'] = ERROR_PASSWORD_NUMBER_CARACTERE;
            }
        } else {
            self::$errorMessage['password'] = ERROR_PASSWORD_EMPTY;
        }
        return $rslt;
    }

    /**
     * Crypte un mot de passe pour un compte client.
     *
     * @param string $pass
     * @return string
     */
    public static function &cryptPass($pass) {
        return Exec_Crypt::cryptData($pass, $pass, "md5+");
    }

    /**
     * Retourne les messages d'erreurs en attentes.
     *
     * @param string $key
     * @return array
     */
    public static function &getErrorMessage($key = "") {
        $rslt = array();

        if (!empty($key)) {
            $rslt = array(
                $self::$errorMessage[$key]
            );
        } else {
            $rslt = self::$errorMessage;
        }
        return $rslt;
    }

    /**
     * Suppression de l'Ip bannie.
     */
    public function cancelIpBan() {
        $this->userIpBan = "";
        Exec_Cookie::destroyCookie(self::getCookieName($this->cookieName['BLACKBAN']));
    }

    /**
     * Actualise la session courante.
     */
    public function refreshSession() {
        if ($this->userLogged()) {
            // Rafraichir le cache de session
            $user = self::getUserInfo(array(
                "user_id = '" . $this->userId . "'"));

            if (count($user) > 1) {
                $this->setUser($user, true);
                Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS);
                Core_Cache::getInstance()->writingCache($this->sessionId . ".php", $this->getUserInfosSerialized(), true);
            }
        }
    }

    /**
     * Récupèration d'une session ouverte.
     *
     * @return boolean false session corrompue
     */
    private function searchSession() {
        // Par défaut, la session actuel est valide
        $isValidSession = true;

        if (!$this->userLogged()) {
            // Cookie de l'id du client
            $userId = self::getCookie($this->cookieName['USER']);

            // Cookie de session
            $sessionId = self::getCookie($this->cookieName['SESSION']);

            // Vérifie si une session est ouverte
            if ((!empty($userId) && !empty($sessionId))) {
                // La session doit être entièrement re-validée
                $isValidSession = false;

                // Cookie de langue
                $this->userLanguage = self::getCookie($this->cookieName['LANGUE']);

                // Cookie de template
                $this->userTemplate = self::getCookie($this->cookieName['TEMPLATE']);

                // Cookie de l'IP BAN voir Core_BlackBan
                $this->userIpBan = self::getCookie($this->cookieName['BLACKBAN']);

                if (Core_Cache::getInstance()->cached($sessionId . ".php")) {
                    // Si fichier cache trouvé, on l'utilise
                    $sessions = Core_Cache::getInstance()->getCache($sessionId . ".php");

                    if ($sessions['userId'] === $userId && $sessions['sessionId'] === $sessionId) {
                        // Mise a jour du dernier accès toute les 5 min
                        if ((Core_Cache::getInstance()->getCacheMTime($sessionId . ".php") + 5 * 60) < $this->timer) {
                            // En base
                            $isValidSession = $this->updateLastConnect($userId);

                            // En cache
                            Core_Cache::getInstance()->touchCache($sessionId . ".php");
                        } else {
                            $isValidSession = true;
                        }
                    }

                    if ($isValidSession) {
                        // Injection des informations du client
                        $this->setUser($sessions);
                    }
                }
            }
        }
        return $isValidSession;
    }

    /**
     * Ferme une session ouverte.
     */
    private function closeSession() {
        // Destruction du fichier de session
        Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS);

        if (Core_Cache::getInstance()->cached($this->sessionId . ".php")) {
            Core_Cache::getInstance()->removeCache($this->sessionId . ".php");
        }

        // Destruction des éventuelles cookies
        foreach ($this->cookieName as $key => $value) {
            // On évite de supprimer le cookie de bannissement
            if ($key === "BLACKBAN") {
                continue;
            }

            Exec_Cookie::destroyCookie(self::getCookieName($value));
        }
    }

    /**
     * Ouvre une nouvelle session.
     *
     * @return boolean ture succès
     */
    private function &openSession() {
        $rslt = false;
        $this->sessionId = Exec_Crypt::createId(32);

        // Durée de connexion automatique via cookie
        $cookieTimeLimit = $this->timer + $this->cacheTimeLimit;

        // Creation des cookies
        $cookieUser = Exec_Cookie::createCookie(
        self::getCookieName($this->cookieName['USER']), Exec_Crypt::md5Encrypt(
        $this->userId, self::getSalt()
        ), $cookieTimeLimit
        );

        $cookieSession = Exec_Cookie::createCookie(
        self::getCookieName($this->cookieName['SESSION']), Exec_Crypt::md5Encrypt(
        $this->sessionId, self::getSalt()
        ), $cookieTimeLimit
        );

        if ($cookieUser && $cookieSession) {
            // Ecriture du cache
            Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS);
            Core_Cache::getInstance()->writingCache($this->sessionId . ".php", $this->getUserInfosSerialized());
            $rslt = true;
        } else {
            Core_Logger::addWarningMessage(ERROR_SESSION_COOKIE);
        }
        return $rslt;
    }

    /**
     * Mise en chaine de caractères des informations du client.
     *
     * @return string
     */
    private function &getUserInfosSerialized() {
        $data = array(
            "userId" => $this->userId,
            "name" => $this->userName,
            "mail" => $this->userMail,
            "rank" => $this->userRank,
            "date" => $this->userInscriptionDate,
            "avatar" => $this->userAvatar,
            "signature" => $this->userSignature,
            "website" => $this->userWebSite,
            "langue" => $this->userLanguage,
            "template" => $this->userTemplate,
            "userIpBan" => $this->userIpBan,
            "sessionId" => $this->sessionId
        );
        return Core_Cache::getInstance()->serializeData($data);
    }

    /**
     * Détermine si l'utilisateur est identifié.
     *
     * @return boolean true c'est un client valide
     */
    private function userLogged() {
        return (!empty($this->userId) && !empty($this->userName) && !empty($this->sessionId) && $this->userRank > 0);
    }

    /**
     * Injection des informations du client.
     *
     * @param array $info
     * @param boolean $refreshAll
     */
    private function setUser($info, $refreshAll = false) {
        $this->userId = isset($info['userId']) ? $info['userId'] : $info['user_id'];
        $this->userName = Exec_Entities::stripSlashes($info['name']);
        $this->userMail = $info['mail'];
        $this->userRank = (int) $info['rank'];
        $this->userInscriptionDate = $info['date'];
        $this->userAvatar = $info['avatar'];
        $this->userSignature = Exec_Entities::stripSlashes($info['signature']);
        $this->userWebSite = Exec_Entities::stripSlashes($info['website']);

        if (!empty($info['sessionId'])) {
            $this->sessionId = $info['sessionId'];
        }

        if (!empty($info['userIpBan'])) {
            $this->userIpBan = $info['userIpBan'];
        }

        if (empty($this->userLanguage) || $refreshAll) {
            $this->userLanguage = $info['langue'];
        }

        if (empty($this->userTemplate) || $refreshAll) {
            $this->userTemplate = $info['template'];
        }
    }

    /**
     * Mise à jour de la dernière connexion.
     *
     * @param string $userId
     * @return boolean true succès de la mise à jour
     */
    private function updateLastConnect($userId = "") {
        // Récupere l'id du client
        if (empty($userId)) {
            $userId = $this->userId;
        }

        $coreSql = Core_Sql::getInstance();
        $coreSql->addQuoted("", "NOW()");

        // Envoi la requête Sql de mise à jour
        $coreSql->update(Core_Table::USERS_TABLE, array(
            "last_connect" => "NOW()"), array(
            "user_id = '" . $userId . "'")
        );
        return ($coreSql->affectedRows() === 1) ? true : false;
    }

    /**
     * Retourne le contenu décrypté du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    private static function &getCookie($cookieName) {
        $cookieName = self::getCookieName($cookieName);
        $cookieContent = Exec_Cookie::getCookie($cookieName);
        $cookieContent = Exec_Crypt::md5Decrypt($cookieContent, self::getSalt());
        return $cookieContent;
    }

    /**
     * Retourne le nom crypté du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    private static function &getCookieName($cookieName) {
        return Exec_Crypt::cryptData($cookieName, self::getSalt(), "md5+");
    }

    /**
     * Retourne la combinaison de clés pour le salt.
     *
     * @return string
     */
    private static function getSalt() {
        return Core_Main::getInstance()->getCryptKey() . Exec_Agent::$userBrowserName;
    }

    /**
     * Retourne les informations utilisateur via la base de données.
     *
     * @return array
     */
    private static function &getUserInfo(array $where) {
        $info = array();

        $coreSql = Core_Sql::getInstance();
        $coreSql->select(
        Core_Table::USERS_TABLE, array(
            "user_id",
            "name",
            "mail",
            "rank",
            "date",
            "avatar",
            "website",
            "signature",
            "template",
            "langue"), $where
        );

        if ($coreSql->affectedRows() === 1) {
            $info = $coreSql->fetchArray();
        }
        return $info;
    }

}
