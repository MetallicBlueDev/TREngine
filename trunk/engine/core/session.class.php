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
     * Nom du fichier cache de bannissement.
     */
    const BANISHMENT_FILENAME = "banishment.txt";

    /**
     * Durée (en jour) d'un bannissement.
     */
    const BANISHMENT_DURATION = 2;

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
     * Adresse Ip du client bannis.
     *
     * @var string
     */
    private $userIpBan = "";

    /**
     * Identifiant de la session courante du client.
     *
     * @var string
     */
    private $sessionId = "";

    /**
     * Informations sur le client.
     *
     * @var Core_SessionData
     */
    private $userInfos = null;
    public $userRank = 0;
    public $userLanguage = "";
    public $userTemplate = "";

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
     */
    private function __construct() {
        // Marque le timer
        $this->timer = time();

        $coreMain = Core_Main::getInstance();

        // Durée de validité du cache en jours
        $this->cacheTimeLimit = $coreMain->getCacheTimeLimit() * 86400;

        // Complète le nom des cookies
        foreach ($this->cookieName as $key => $name) {
            $this->cookieName[$key] = $coreMain->getCookiePrefix() . $name;
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
            self::$coreSession = new Core_Session();
            self::$coreSession->cleanCache();

            // Lanceur de session
            if (!self::$coreSession->searchSession()) {
                // La session est potentiellement corrompue
                self::stopConnection();

                // Nouvelle instance vierge
                self::$coreSession = new Core_Session();
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
     *
     * @return Core_SessionData
     */
    public function getUserInfos() {
        if ($this->userInfos === null) {
            $this->userInfos = new Core_SessionData(array());
        }
        return $this->userInfos;
    }

    /**
     * Actualise la session courante.
     */
    public function refreshSession() {
        if ($this->userLogged()) {
            // Rafraichir le cache de session
            $user = self::getUserInfo(array(
                "user_id = '" . $this->userInfos->getId() . "'"));

            if (count($user) > 1) {
                $this->setUser($user, true);
                Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS)->writeCache($this->sessionId . ".php", $this->serializeSession());
            }
        }
    }

    /**
     * Détermine si l'utilisateur a été banni.
     *
     * @return boolean true le client est banni
     */
    public function bannedSession() {
        return empty($this->userIpBan) ? false : true;
    }

    /**
     * Routine de vérification des bannissements.
     */
    public function checkBanishment() {
        $coreCache = Core_Cache::getInstance(Core_Cache::SECTION_TMP);
        $coreSql = Core_Sql::getInstance();

        $cleanBanishment = false;

        // Vérification du fichier cache
        if (!$coreCache->cached(self::BANISHMENT_FILENAME)) {
            $cleanBanishment = true;
            $coreCache->writeCache(self::BANISHMENT_FILENAME, "1");
        } else if ((time() - (self::BANISHMENT_DURATION * 24 * 60 * 60)) > $coreCache->getCacheMTime(self::BANISHMENT_FILENAME)) {
            $cleanBanishment = true;
            $coreCache->touchCache(self::BANISHMENT_FILENAME);
        }

        // Nettoyage des adresses IP périmées de la base de données.
        if ($cleanBanishment) {
            $coreSql->delete(
            Core_Table::BANNED_TABLE, array(
                "ip != ''",
                "&& (name = 'Hacker' || name = '')",
                "&& type = '0'",
                "&& DATE_ADD(date, INTERVAL " . self::BANISHMENT_DURATION . " DAY) > CURDATE()"
            )
            );
        }

        $userIp = Exec_Agent::$userIp;

        // Recherche de bannissement de session
        if ($this->bannedSession()) {
            // Si l'ip n'est plus du tout valide
            if ($this->userIpBan != $userIp && !preg_match("/" . $this->userIpBan . "/", $userIp)) {
                $coreSql = Core_Sql::getInstance();

                // Vérification en base (au cas ou il y aurait un débannissement)
                $coreSql->select(
                Core_Table::BANNED_TABLE, array(
                    "ban_id"), array(
                    "ip = '" . $this->userIpBan . "'")
                );

                if ($coreSql->affectedRows() > 0) {
                    // Bannissement toujours en place
                    list($banId) = $coreSql->fetchArray();

                    // Mise à jour de l'ip
                    $coreSql->update(
                    Core_Table::BANNED_TABLE, array(
                        "ip" => $userIp), array(
                        "ban_id = '" . $banId . "'")
                    );

                    $this->userIpBan = $userIp;
                } else {
                    // Suppression du bannissement
                    $this->userIpBan = "";
                    Exec_Cookie::destroyCookie(self::getCookieName($this->cookieName['BLACKBAN']));
                }
            }
        } else {
            // Sinon on recherche dans la base les bannis; leurs ip et leurs pseudo
            $coreSql->select(
            Core_Table::BANNED_TABLE, array(
                "ip",
                "name"), array(), array(
                "ban_id")
            );

            foreach ($coreSql->fetchArray() as $value) {
                list($banFullIp, $banName) = $value;
                $banIp = explode(".", $banFullIp);

                // Filtre pour la vérification
                if (isset($banIp[3]) && !empty($banIp[3])) {
                    $banList = $banFullIp;
                    $searchIp = $userIp;
                } else {
                    $banList = $banIp[0] . $banIp[1] . $banIp[2];
                    $uIp = explode(".", $userIp);
                    $searchIp = $uIp[0] . $uIp[1] . $uIp[2];
                }

                // Vérification du client
                if ($searchIp === $banList) {
                    // IP bannis !
                    $this->userIpBan = $banFullIp;
                } else if ($this->userInfos !== null && $this->userInfos->getName() === $banName) {
                    // Pseudo bannis !
                    $this->userIpBan = $banFullIp;
                } else {
                    $this->userIpBan = "";
                }

                // La vérification a déjà aboutie, on arrête
                if ($this->bannedSession()) {
                    break;
                }
            }
        }
    }

    /**
     * Affichage de l'isoloire pour le bannissement.
     */
    public function displayBanishment() {
        $coreSql = Core_Sql::getInstance();

        $coreSql->select(
        Core_Table::BANNED_TABLE, array(
            "reason"), array(
            "ip = '" . $this->userIpBan . "'")
        );

        if ($coreSql->affectedRows() > 0) {
            $coreMain = Core_Main::getInstance();
            $mail = $coreMain->getDefaultAdministratorMail();
            $mail = Exec_Mailer::protectedDisplay($mail, $coreMain->getDefaultSiteName());
            $reason = $coreSql->fetchArray()['reason'];

            $libsMakeStyle = new Libs_MakeStyle();
            $libsMakeStyle->assign("mail", $mail);
            $libsMakeStyle->assign("reason", Exec_Entities::textDisplay($reason));
            $libsMakeStyle->assign("ip", $this->userIpBan);
            $libsMakeStyle->display("banishment");
        }
    }

    /**
     * Nettoyage du cache de session utilisateur.
     */
    private function cleanCache() {
        Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS)->cleanCache($this->timer - $this->cacheTimeLimit);
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

                $coreCache = Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS);

                if ($coreCache->cached($sessionId . ".php")) {
                    // Si fichier cache trouvé, on l'utilise
                    $sessions = $coreCache->readCache($sessionId . ".php");

                    if ($sessions['userId'] === $userId && $sessions['sessionId'] === $sessionId) {
                        // Mise a jour du dernier accès toute les 5 min
                        if (($coreCache->getCacheMTime($sessionId . ".php") + 5 * 60) < $this->timer) {
                            // En base
                            $isValidSession = $this->updateLastConnect($userId);

                            // En cache
                            $coreCache->touchCache($sessionId . ".php");
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
        $coreCache = Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS);

        if ($coreCache->cached($this->sessionId . ".php")) {
            $coreCache->removeCache($this->sessionId . ".php");
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
        $this->userInfos->getId(), self::getSalt()
        ), $cookieTimeLimit
        );

        $cookieSession = Exec_Cookie::createCookie(
        self::getCookieName($this->cookieName['SESSION']), Exec_Crypt::md5Encrypt(
        $this->sessionId, self::getSalt()
        ), $cookieTimeLimit
        );

        if ($cookieUser && $cookieSession) {
            // Ecriture du cache
            Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS)->writeCache($this->sessionId . ".php", $this->serializeSession());
            $rslt = true;
        } else {
            Core_Logger::addWarningMessage(ERROR_SESSION_COOKIE);
        }
        return $rslt;
    }

    /**
     * Retourne les informations de session sérialisées.
     *
     * @return string
     */
    private function &serializeSession() {
        $data = $this->userInfos->getData();
        $data['userIpBan'] = $this->userIpBan;
        $data['sessionId'] = $this->sessionId;
        return Core_Cache::getInstance(Core_Cache::SECTION_SESSIONS)->serializeData($data);
    }

    /**
     * Détermine si l'utilisateur est identifié.
     *
     * @return boolean true c'est un client valide
     */
    private function userLogged() {
        return (!empty($this->sessionId) && $this->userInfos !== null);
    }

    /**
     * Injection des informations du client.
     *
     * @param array $info
     * @param boolean $refreshAll
     */
    private function setUser($info, $refreshAll = false) {

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
    private function updateLastConnect($userId) {
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
