<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Exec\ExecMailer;
use TREngine\Engine\Exec\ExecCrypt;
use TREngine\Engine\Exec\ExecCookie;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de sessions.
 *
 * @author Sébastien Villemain
 */
class CoreSession {

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
     * @var CoreSession
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
    private $sessionTimeLimit = 0;

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
     * @var CoreSessionData
     */
    private $userInfos = null;

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

        $coreMain = CoreMain::getInstance();

        // Durée de validité du cache en jours
        $this->sessionTimeLimit = $coreMain->getSessionTimeLimit() * 86400;

        // Complète le nom des cookies
        foreach ($this->cookieName as $key => $name) {
            $this->cookieName[$key] = $coreMain->getCookiePrefix() . $name;
        }
    }

    /**
     * Instance du gestionnaire des sessions.
     *
     * @return CoreSession
     */
    public static function &getInstance(): CoreSession {
        self::checkInstance();
        return self::$coreSession;
    }

    /**
     * Vérification de l'instance du gestionnaire des sessions.
     */
    public static function checkInstance() {
        if (self::$coreSession === null) {
            // Création d'un instance autonome
            self::$coreSession = new CoreSession();
            self::$coreSession->cleanCache();

            // Lanceur de session
            if (!self::$coreSession->searchSession()) {
                // La session est potentiellement corrompue
                self::stopConnection();

                // Nouvelle instance vierge
                self::$coreSession = new CoreSession();
            }
        }
    }

    /**
     * Tentative de création d'un nouvelle session.
     *
     * @param string $userName Nom du compte (identifiant)
     * @param string $userPass Mot de passe du compte
     * @return bool true succès
     */
    public static function &startConnection(string $userName, string $userPass): bool {
        $rslt = false;

        // Arrête de la session courante si besoin
        self::stopConnection();

        if (self::validLogin($userName) && self::validPassword($userPass)) {
            $userPass = self::cryptPass($userPass);
            $userInfos = self::getUserInfo(array(
                "name = '" . $userName . "'",
                "&& pass = '" . $userPass . "'"));

            if (count($userInfos) > 1) {
                $newSession = self::getInstance();

                // Injection des informations du client
                $newSession->setUser($userInfos, true);

                // Tentative d'ouverture de session
                $rslt = $newSession->createSession();

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
     * @return bool
     */
    public static function hasConnection(): bool {
        return (self::$coreSession !== null && self::$coreSession->userLogged());
    }

    /**
     * Coupe proprement une session ouverte.
     */
    public static function stopConnection() {
        if (self::hasConnection()) {
            self::$coreSession->destroySession();
        }

        if (self::$coreSession !== null) {
            self::$coreSession = null;
        }
    }

    /**
     * Vérification du nom du compte.
     *
     * @param string $login
     * @return bool true login valide
     */
    public static function &validLogin(string $login): bool {
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
     * @return bool true password valide
     */
    public static function &validPassword(string $password): bool {
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
    public static function &cryptPass(string $pass): string {
        return ExecCrypt::cryptByStandard($pass, $pass);
    }

    /**
     * Retourne les messages d'erreurs en attentes.
     *
     * @param string $key
     * @return array
     */
    public static function &getErrorMessage(string $key = ""): array {
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
     * Retourne les données de session de l'utilisateur actuel.
     *
     * @return CoreSessionData
     */
    public function getUserInfos(): CoreSessionData {
        if ($this->userInfos === null) {
            $this->setUserAnonymous();
        }
        return $this->userInfos;
    }

    /**
     * Actualise la session courante.
     */
    public function refreshSession() {
        if ($this->userLogged()) {
            // Rafraichir le cache de session
            $userInfos = self::getUserInfo(array(
                "user_id = '" . $this->userInfos->getId() . "'"));

            if (count($userInfos) > 1) {
                $this->setUser($userInfos, true);
                CoreCache::getInstance(CoreCache::SECTION_SESSIONS)->writeCache($this->sessionId . ".php", $this->serializeSession());
            }
        }
    }

    /**
     * Détermine si l'utilisateur a été banni.
     *
     * @return bool true le client est banni
     */
    public function bannedSession(): bool {
        return empty($this->userIpBan) ? false : true;
    }

    /**
     * Routine de vérification des bannissements.
     */
    public function checkBanishment() {
        $this->cleanOldBanishment();

        if ($this->bannedSession()) {
            $this->updateBanishment();
        } else {
            $this->searchBanishment();
        }
    }

    /**
     * Affichage de l'isoloire pour le bannissement.
     */
    public function displayBanishment() {
        $coreSql = CoreSql::getInstance();

        $coreSql->select(
        CoreTable::BANNED_TABLE, array(
            "reason"), array(
            "ip = '" . $this->userIpBan . "'")
        );

        if ($coreSql->affectedRows() > 0) {
            $coreMain = CoreMain::getInstance();
            $mail = $coreMain->getDefaultAdministratorMail();
            $mail = ExecMailer::displayMail($mail, $coreMain->getDefaultSiteName());
            $reason = $coreSql->fetchArray()['reason'];

            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assign("mail", $mail);
            $libMakeStyle->assign("reason", ExecEntities::textDisplay($reason));
            $libMakeStyle->assign("ip", $this->userIpBan);
            $libMakeStyle->display("banishment");
        }
    }

    /**
     * Nettoyage des bannissements périmés.
     */
    private function cleanOldBanishment() {
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_TMP);
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
            CoreSql::getInstance()->delete(
            CoreTable::BANNED_TABLE, array(
                "ip != ''",
                "&& (name = 'Hacker' || name = '')",
                "&& type = '0'",
                "&& DATE_ADD(banishment_date, INTERVAL " . self::BANISHMENT_DURATION . " DAY) > CURDATE()"
            )
            );
        }
    }

    /**
     * Mise à jour automatique du bannissmenent.
     */
    private function updateBanishment() {
        $userIp = CoreMain::getInstance()->getAgentInfos()->getAddressIp();

        if ($this->userIpBan != $userIp && !preg_match("/" . $this->userIpBan . "/", $userIp)) {
            $coreSql = CoreSql::getInstance();

            // Vérification en base (au cas ou il y aurait un débannissement)
            $coreSql->select(
            CoreTable::BANNED_TABLE, array(
                "ban_id"), array(
                "ip = '" . $this->userIpBan . "'")
            );

            if ($coreSql->affectedRows() > 0) {
                // Bannissement toujours en place
                $banId = $coreSql->fetchArray()[0]['ban_id'];

                // Mise à jour de l'ip
                $coreSql->update(
                CoreTable::BANNED_TABLE, array(
                    "ip" => $userIp), array(
                    "ban_id = '" . $banId . "'")
                );

                $this->userIpBan = $userIp;
            } else {
                // Suppression du bannissement
                $this->userIpBan = "";
                ExecCookie::destroyCookie($this->getBanishmentCookieName());
            }
        }
    }

    /**
     * Recherche avancée d'un bannissement de session.
     */
    private function searchBanishment() {
        $coreSql = CoreSql::getInstance();
        $userIp = CoreMain::getInstance()->getAgentInfos()->getAddressIp();

        // Sinon on recherche dans la base les bannis; leurs ip et leurs pseudo
        $coreSql->select(
        CoreTable::BANNED_TABLE, array(
            "ip",
            "name"), array(), array(
            "ban_id")
        );

        foreach ($coreSql->fetchArray() as $value) {
            $this->searchBanishmentIp($userIp, $value);

            // La vérification a déjà aboutie, on arrête
            if ($this->bannedSession()) {
                break;
            }
        }
    }

    /**
     * Vérification du banissement de l'IP.
     *
     * @param string $userIp
     * @param array $value
     */
    private function searchBanishmentIp(string $userIp, array $value) {
        $banIp = !empty($value['ip']) ? explode(".", $value['ip']) : array();
        $banIpCounter = count($banIp);
        $searchIp = "";

        // Filtre pour la vérification
        if ($banIpCounter >= 4) {
            $banList = $value['ip'];
            $searchIp = $userIp;
        } else {
            for ($index = 0; $index < $banIpCounter; $index++) {
                $banList .= $banIp[$index];
            }

            $uIp = explode(".", $userIp);
            $userIpCounter = count($uIp);
            $userIpCounter = $userIpCounter < $banIpCounter ? $userIpCounter : $banIpCounter;

            for ($index = 0; $index < $userIpCounter; $index++) {
                $searchIp .= $uIp[$index];
            }
        }

        // Vérification du client
        if (!empty($searchIp) && $searchIp === $banList) {
            // IP bannis !
            $this->userIpBan = $value['ip'];
        } else if ($this->userInfos !== null && $this->userInfos->getName() === $value['name']) {
            // Pseudo bannis !
            $this->userIpBan = $value['ip'];
        } else {
            $this->userIpBan = "";
        }
    }

    /**
     * Nettoyage du cache de session utilisateur.
     */
    private function cleanCache() {
        CoreCache::getInstance(CoreCache::SECTION_SESSIONS)->cleanCache($this->timer - $this->sessionTimeLimit);
    }

    /**
     * Récupèration d'une session ouverte.
     *
     * @return bool false session corrompue
     */
    private function searchSession(): bool {
        // Par défaut, la session actuel est valide
        $isValidSession = true;

        if (!$this->userLogged()) {
            $userId = self::getCookie($this->getUserCookieName());
            $sessionId = self::getCookie($this->getSessionCookieName());
            $this->userIpBan = self::getCookie($this->getBanishmentCookieName());

            // Vérifie si une session est ouverte
            if ((!empty($userId) && !empty($sessionId))) {
                // La session doit être entièrement re-validée
                $isValidSession = $this->tryOpenSession($userId, $sessionId);
            } else {
                $userLanguage = self::getCookie($this->getLangueCookieName());
                $userTemplate = self::getCookie($this->getTemplateCookieName());

                $this->setUserAnonymous();
                $this->userInfos->setLangue($userLanguage);
                $this->userInfos->setTemplate($userTemplate);
            }
        }
        return $isValidSession;
    }

    /**
     * Tentative d'ouverture de la session.
     *
     * @param string $userId
     * @param string $sessionId
     * @return bool
     */
    private function tryOpenSession(string $userId, string $sessionId): bool {
        // La session doit être entièrement re-validée
        $isValidSession = false;
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_SESSIONS);

        if ($coreCache->cached($sessionId . ".php")) {
            // Si fichier cache trouvé, on l'utilise
            $sessions = $coreCache->readCache($sessionId . ".php");

            if ($sessions['user_id'] === $userId && $sessions['sessionId'] === $sessionId) {
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
                $userLanguage = self::getCookie($this->getLangueCookieName());
                $userTemplate = self::getCookie($this->getTemplateCookieName());

                // Injection des informations du client
                $this->setUser($sessions);
                $this->userInfos->setLangue($userLanguage);
                $this->userInfos->setTemplate($userTemplate);
            }
        }
        return $isValidSession;
    }

    /**
     * Retourne le nom du cookie stockant l'identifiant du client.
     *
     * @return string
     */
    private function &getUserCookieName(): string {
        return self::getCryptCookieName($this->cookieName['USER']);
    }

    /**
     * Retourne le nom du cookie stockant l'identifiant de session.
     *
     * @return string
     */
    private function &getSessionCookieName(): string {
        return self::getCryptCookieName($this->cookieName['SESSION']);
    }

    /**
     * Retourne le nom du cookie stockant la langue du client.
     *
     * @return string
     */
    private function &getLangueCookieName(): string {
        return self::getCryptCookieName($this->cookieName['LANGUE']);
    }

    /**
     * Retourne le nom du cookie stockant le thème du client.
     *
     * @return string
     */
    private function &getTemplateCookieName(): string {
        return self::getCryptCookieName($this->cookieName['TEMPLATE']);
    }

    /**
     * Retourne le nom du cookie stockant le bannissement du client.
     *
     * @return string
     */
    private function &getBanishmentCookieName(): string {
        return self::getCryptCookieName($this->cookieName['BLACKBAN']);
    }

    /**
     * Ferme une session ouverte.
     */
    private function destroySession() {
        // Destruction du fichier de session
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_SESSIONS);

        if ($coreCache->cached($this->sessionId . ".php")) {
            $coreCache->removeCache($this->sessionId . ".php");
        }

        // Destruction des éventuelles cookies
        foreach ($this->cookieName as $key => $value) {
            // On évite de supprimer le cookie de bannissement
            if ($key === "BLACKBAN") {
                continue;
            }

            ExecCookie::destroyCookie(self::getCryptCookieName($value));
        }
    }

    /**
     * Ouvre une nouvelle session.
     *
     * @return bool true succès
     */
    private function &createSession(): bool {
        $rslt = false;
        $this->sessionId = ExecCrypt::makeIdentifier(32);

        // Durée de connexion automatique via cookie
        $cookieTimeLimit = $this->timer + $this->sessionTimeLimit;

        // Creation des cookies
        $cookieUser = ExecCookie::createCookie($this->getUserCookieName(), ExecCrypt::md5Encrypt($this->userInfos->getId(), self::getSalt()), $cookieTimeLimit);
        $cookieSession = ExecCookie::createCookie($this->getSessionCookieName(), ExecCrypt::md5Encrypt($this->sessionId, self::getSalt()), $cookieTimeLimit);

        if ($cookieUser && $cookieSession) {
            // Ecriture du cache
            CoreCache::getInstance(CoreCache::SECTION_SESSIONS)->writeCache($this->sessionId . ".php", $this->serializeSession());
            $rslt = true;
        } else {
            CoreLogger::addWarningMessage(ERROR_SESSION_COOKIE);
        }
        return $rslt;
    }

    /**
     * Retourne les informations de session sérialisées.
     *
     * @return string
     */
    private function &serializeSession(): string {
        $data = $this->userInfos->getData();
        $data['userIpBan'] = $this->userIpBan;
        $data['sessionId'] = $this->sessionId;
        return CoreCache::getInstance(CoreCache::SECTION_SESSIONS)->serializeData($data);
    }

    /**
     * Détermine si l'utilisateur est identifié.
     *
     * @return bool true c'est un client valide
     */
    private function userLogged(): bool {
        return (!empty($this->sessionId) && $this->userInfos !== null);
    }

    /**
     * Injection d'un client anonyme.
     */
    private function setUserAnonymous() {
        $empty = array();
        $this->setUser($empty);
    }

    /**
     * Injection des informations du client.
     *
     * @param array $session
     * @param bool $refreshAll
     */
    private function setUser(array $session, bool $refreshAll = false) {
        if ($this->userInfos === null || $refreshAll) {
            $this->userInfos = new CoreSessionData($session);
        }

        if (!empty($session['sessionId'])) {
            $this->sessionId = $session['sessionId'];
        }

        if (!empty($session['userIpBan'])) {
            $this->userIpBan = $session['userIpBan'];
        }
    }

    /**
     * Mise à jour de la dernière connexion.
     *
     * @param string $userId
     * @return bool true succès de la mise à jour
     */
    private function updateLastConnect(string $userId): bool {
        $coreSql = CoreSql::getInstance();
        $coreSql->addQuotedValue("NOW()");

        // Envoi la requête Sql de mise à jour
        $coreSql->update(CoreTable::USERS_TABLE, array(
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
    private static function &getCookie(string $cookieName): string {
        $cookieContent = ExecCookie::getCookie($cookieName);
        $cookieContent = ExecCrypt::md5Decrypt($cookieContent, self::getSalt());
        return $cookieContent;
    }

    /**
     * Retourne le nom crypté du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    private static function &getCryptCookieName(string $cookieName): string {
        return ExecCrypt::cryptByStandard($cookieName, self::getSalt());
    }

    /**
     * Retourne la combinaison de clés pour le salt.
     *
     * @return string
     */
    private static function getSalt(): string {
        $coreMain = CoreMain::getInstance();
        return $coreMain->getCryptKey() . $coreMain->getAgentInfos()->getBrowserName();
    }

    /**
     * Retourne les informations utilisateur via la base de données.
     *
     * @return array
     */
    private static function &getUserInfo(array $where): array {
        $info = array();

        $coreSql = CoreSql::getInstance();
        $coreSql->select(
        CoreTable::USERS_TABLE, array(
            "user_id",
            "name",
            "mail",
            "rank",
            "registration_date",
            "avatar",
            "website",
            "signature",
            "template",
            "langue"), $where
        );

        if ($coreSql->affectedRows() === 1) {
            $info = $coreSql->fetchArray()[0];
        }
        return $info;
    }

}
