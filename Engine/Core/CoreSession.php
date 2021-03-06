<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Exec\ExecCookie;
use PassionEngine\Engine\Exec\ExecCrypt;
use PassionEngine\Engine\Exec\ExecEmail;
use PassionEngine\Engine\Exec\ExecUtils;
use PassionEngine\Engine\Exec\ExecString;
use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Fail\FailBase;
use PassionEngine\Engine\Fail\FailEngine;
use PassionEngine\Engine\Core\CoreRequestType;

/**
 * Gestionnaire de sessions.
 *
 * @author Sébastien Villemain
 */
class CoreSession
{

    /**
     * Nom du fichier cache de bannissement.
     *
     * @var string
     */
    private const BANISHMENT_FILENAME = 'banishment.txt';

    /**
     * Durée (en jour) d'un bannissement.
     *
     * @var int
     */
    private const BANISHMENT_DURATION = 2;

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
     * Limite de temps pour le cache.
     *
     * @var int
     */
    private $sessionTimeLimit = 0;

    /**
     * Adresse IP du client banni.
     *
     * @var string
     */
    private $userIpBanned = '';

    /**
     * Identifiant de la session courante du client.
     *
     * @var string
     */
    private $sessionId = '';

    /**
     * Informations sur le client.
     *
     * @var CoreSessionData
     */
    private $sessionData = null;

    /**
     * Détermine l'état de la session PHP.
     *
     * <ul>
     * <li><code>NULL</code>: inconnu</li>
     * <li><code>false</code>: désactivé</li>
     * <li><code>true></code>: activé</li>
     * </ul>
     *
     * @var bool
     */
    private $nativeSessionEnabled = null;

    /**
     * Nom des cookies.
     *
     * @var array
     */
    private $cookieName = array(
        'USER' => '_user_id',
        'SESSION' => '_sess_id',
        'LANGUE' => '_user_langue',
        'TEMPLATE' => '_user_template',
        'BLACKBAN' => '_user_ip_ban'
    );

    /**
     * Démarrage du système de session.
     */
    private function __construct()
    {
        $coreMain = CoreMain::getInstance();

        // Durée de validité du cache en jours
        $this->sessionTimeLimit = $coreMain->getConfigs()->getSessionTimeLimit() * 86400;

        // Complète le nom des cookies
        foreach ($this->cookieName as $key => $name) {
            $this->cookieName[$key] = $coreMain->getConfigs()->getCookiePrefix() . $name;
        }

        $this->createNativeSession();
    }

    public function __destruct()
    {
        $this->saveNativeSession();
    }

    /**
     * Instance du gestionnaire des sessions.
     *
     * @return CoreSession
     */
    public static function &getInstance(): CoreSession
    {
        self::checkInstance();
        return self::$coreSession;
    }

    /**
     * Vérification de l'instance du gestionnaire des sessions.
     */
    public static function checkInstance(): void
    {
        if (self::$coreSession === null) {
            // Création d'un instance autonome
            self::$coreSession = new CoreSession();
            self::$coreSession->cleanCache();

            // Lanceur de session
            if (!self::$coreSession->searchSession()) {
                // La session est potentiellement corrompue
                self::closeSession();

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
     * @return bool succès
     */
    public static function &openSession(string $userName,
                                        string $userPass): bool
    {
        $rslt = false;

        // Arrête de la session courante si besoin
        self::closeSession();

        if (self::validLogin($userName) && self::validPassword($userPass)) {
            $userPass = self::cryptPass($userPass);
            $userArrayDatas = self::loadUserData(array(
                    'name = \'' . $userName . '\'',
                    'AND pass = \'' . $userPass . '\''
            ));

            if (count($userArrayDatas) > 1) {
                $newSession = self::getInstance();

                // Injection des informations du client
                $newSession->makeSessionData($userArrayDatas,
                                             true);

                // Tentative d'ouverture de session
                $rslt = $newSession->createSession();

                if (!$rslt) {
                    self::closeSession();
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
    public static function connected(): bool
    {
        return (self::$coreSession !== null && self::$coreSession->hasValidSessionData());
    }

    /**
     * Coupe proprement une session ouverte.
     */
    public static function closeSession(): void
    {
        if (self::connected()) {
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
     * @return bool Login valide.
     */
    public static function &validLogin(string $login): bool
    {
        $rslt = false;

        if (!empty($login)) {
            $len = strlen($login);

            if ($len >= 3 && $len <= 16) {
                if (preg_match('/^[A-Za-z0-9_-]{3,16}$/ie',
                               $login)) {
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
     * @return bool Valide.
     */
    public static function &validPassword(string $password): bool
    {
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
    public static function &cryptPass(string $pass): string
    {
        return ExecCrypt::cryptByStandard($pass,
                                          $pass);
    }

    /**
     * Retourne les messages d'erreurs en attentes.
     *
     * @param string $key
     * @return array
     */
    public static function &getErrorMessage(string $key = ''): array
    {
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
    public function getSessionData(): CoreSessionData
    {
        if ($this->sessionData === null) {
            $this->makeAnonymousSessionData();
        }
        return $this->sessionData;
    }

    /**
     * Actualise la session courante.
     */
    public function refreshSessionData(): void
    {
        if ($this->hasValidSessionData()) {
            // Rafraichir le cache de session
            $userArrayDatas = self::loadUserData(array(
                    'user_id = \'' . $this->sessionData->getId() . '\''
            ));

            if (count($userArrayDatas) > 1) {
                $this->makeSessionData($userArrayDatas,
                                       true);
                CoreCache::getInstance(CoreCacheSection::SESSIONS)->writeCacheAsString($this->sessionId . '.php',
                                                                                       $this->getSerializedSession());
            }
        }
    }

    /**
     * Détermine si l'utilisateur a été banni.
     *
     * @return bool Le client est banni.
     */
    public function bannedSession(): bool
    {
        return empty($this->userIpBanned) ? false : true;
    }

    /**
     * Routine de vérification des bannissements.
     */
    public function checkBanishment(): void
    {
        $this->cleanOldBanishment();

        // Bannissement par cookie
        $this->userIpBanned = self::getCookie($this->getBanishmentCookieName());

        if ($this->bannedSession()) {
            $this->updateBanishment();
        } else {
            $this->searchBanishment();
        }
    }

    /**
     * Affichage de l'isoloir pour le bannissement.
     */
    public function displayBanishment(): void
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::BANNED,
                         array('reason'),
                         array('ip = \'' . $this->userIpBanned . '\''))->query();

        if ($coreSql->affectedRows() > 0) {
            $coreMain = CoreMain::getInstance();
            $email = $coreMain->getConfigs()->getDefaultAdministratorEmail();
            $email = ExecEmail::displayEmail($email,
                                             $coreMain->getConfigs()->getDefaultSiteName());
            $reason = $coreSql->fetchArray()['reason'];

            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assignString('email',
                                        $email);
            $libMakeStyle->assignString('reason',
                                        ExecString::textDisplay($reason));
            $libMakeStyle->assignString('ip',
                                        $this->userIpBanned);
            $libMakeStyle->display('banishment');
        }
    }

    /**
     * Retourne l'identifiant de la session PHP.
     *
     * @return string
     * @throws FailEngine
     */
    public function getNativeSessionId(): string
    {
        if (!$this->nativeSessionEnabled) {
            throw new FailEngine('session is inactive',
                                 FailBase::getErrorCodeName(14));
        }
        return session_id();
    }

    /**
     * Retourne l'identifiant de la session PHP.
     *
     * @return string
     * @throws FailEngine
     */
    public function getNativeSessionName(): string
    {
        return session_name();
    }

    /**
     * Création de la session PHP (si nécessaire).
     *
     * @throws FailEngine
     */
    private function createNativeSession(): void
    {
        if ($this->nativeSessionEnabled === null) {
            if ((ini_get('session.auto_start') === '1')) {
                $this->destroyNativeSession();
            }
            $this->nativeSessionEnabled = false;
        }

        if (!$this->nativeSessionEnabled) {
            $nativeSessionId = CoreRequest::getString($this->getNativeSessionName(),
                                                      '',
                                                      CoreRequestType::COOKIE);
            session_id($nativeSessionId);

            if (!session_start()) {
                throw new FailEngine('fail to start session',
                                     FailBase::getErrorCodeName(14));
            }

            $this->nativeSessionEnabled = true;
            $this->collectNativeSession();
        }
    }

    /**
     * Régénération de la session PHP (si nécessaire).
     *
     * @throws FailEngine
     */
    private function regenerateNativeSession(): void
    {
        $this->destroyNativeSession();
        $this->createNativeSession();
    }

    /**
     * Récupère les informations de la session PHP.
     *
     * @link http://php.net/manual/function.session-unset.php#refsect1-function.session-unset-notes
     * @see CoreSession::saveNativeSession()
     */
    private function collectNativeSession(): void
    {
        if (isset($_SESSION)) {
            $name = CoreRequestType::SESSION;
            CoreInfo::addGlobalVars($name,
                                    $_SESSION);
            // Attention, il faudra enregistrer de nouveau les informations
            unset($_SESSION);
        }
    }

    /**
     * Enregistre les informations de session vers la session PHP.
     */
    private function saveNativeSession(): void
    {
        if ($this->nativeSessionEnabled) {
            $nativeSessionName = $this->getNativeSessionName();
            $_COOKIE[$nativeSessionName] = CoreRequest::getString($nativeSessionName,
                                                                  $this->getNativeSessionId(),
                                                                  CoreRequestType::COOKIE);
            $_SESSION = CoreInfo::getGlobalVars(CoreRequestType::SESSION);

            if (!session_write_close()) {
                CoreLogger::addDebug('Unable to close session.');
            }

            $this->nativeSessionEnabled = false;
        }
    }

    /**
     * Efface toutes les données de la session PHP en mémoire.
     *
     * @return void
     */
    private function destroyNativeSession(): void
    {
        if ($this->nativeSessionEnabled) {
            if (!session_unset()) {
                CoreLogger::addDebug('Unable to unset session variables.');
            }

            if (!session_destroy()) {
                CoreLogger::addDebug('Unable to destroy session.');
            }

            $this->nativeSessionEnabled = false;
        }
    }

    /**
     * Nettoyage des bannissements périmés.
     */
    private function cleanOldBanishment(): void
    {
        $coreCache = CoreCache::getInstance(CoreCacheSection::TMP);
        $cleanBanishment = false;

        // Vérification du fichier cache
        if (!$coreCache->cached(self::BANISHMENT_FILENAME)) {
            $cleanBanishment = true;
            $coreCache->writeCacheAsString(self::BANISHMENT_FILENAME,
                                           '1');
        } else if ((ExecUtils::getMemorizedTimestamp() - (self::BANISHMENT_DURATION * 24 * 60 * 60)) > $coreCache->getCacheMTime(self::BANISHMENT_FILENAME)) {
            $cleanBanishment = true;
            $coreCache->touchCache(self::BANISHMENT_FILENAME);
        }

        // Nettoyage des adresses IP périmées de la base de données.
        if ($cleanBanishment) {
            CoreSql::getInstance()->getSelectedBase()->delete(CoreTable::BANNED,
                                                              array('ip != \'\'',
                    '&& (name = \'Hacker\' || name = \'\')',
                    '&& type = \'0\'',
                    '&& DATE_ADD(banishment_date, INTERVAL ' . self::BANISHMENT_DURATION . ' DAY) > CURDATE()'
            ))->query();
        }
    }

    /**
     * Mise à jour automatique du bannissement.
     */
    private function updateBanishment(): void
    {
        if (!empty($this->userIpBanned)) {
            $userIp = CoreMain::getInstance()->getAgentInfos()->getAddressIp();

            if (!empty($userIp) && $this->userIpBanned != $userIp) {
                $coreSql = CoreSql::getInstance()->getSelectedBase();

                // Mise à jour de l'ancienne IP
                $coreSql->update(CoreTable::BANNED,
                                 array('ip' => $userIp),
                                 array('ip = \'' . $this->userIpBanned . '\''))->query();

                if ($coreSql->affectedRows() >= 1) {
                    $this->updateUserIpBanned($userIp);
                } else {
                    $this->deleteUserIpBanned();
                }
            }
        }
    }

    private function updateUserIpBanned(string $userIp): void
    {
        // Durée de connexion automatique via cookie
        $cookieTimeLimit = ExecUtils::getMemorizedTimestamp() + $this->sessionTimeLimit;

        $this->userIpBanned = $userIp;
        ExecCookie::createCookie($this->getBanishmentCookieName(),
                                 ExecCrypt::md5Encrypt($userIp,
                                                       self::getSalt()),
                                                       $cookieTimeLimit);
    }

    /**
     * Suppression du bannissement.
     */
    private function deleteUserIpBanned(): void
    {
        $this->userIpBanned = '';
        ExecCookie::destroyCookie($this->getBanishmentCookieName());
    }

    /**
     * Recherche avancée d'un bannissement de session.
     */
    private function searchBanishment(): void
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $userIp = CoreMain::getInstance()->getAgentInfos()->getAddressIp();

        // TODO ajouter dans le cache
        // Sinon on recherche dans la base les bannis; leurs ip et leurs pseudo
        $coreSql->select(CoreTable::BANNED,
                         array('ip', 'name', 'email'),
                         array(),
                         array('banned_id'))->query();

        foreach ($coreSql->fetchArray() as $value) {
            $this->checkUserBanned($userIp,
                                   $value);

            // La vérification a déjà aboutie, on arrête
            if ($this->bannedSession()) {
                $this->updateBanishment();
                break;
            }
        }
    }

    /**
     * Vérification du bannissement de l'utilisateur.
     *
     * @param string $userIp
     * @param array $value
     */
    private function checkUserBanned(string $userIp,
                                     array $value): void
    {
        $banned = false;

        if (!empty($userIp) && $userIp == $value['ip']) {
            // Bannissement par IP
            $banned = true;
        } else if ($this->sessionData !== null && ($this->sessionData->getName() === $value['name'] || $this->sessionData->getEmail() === $value['email'])) {
            // Bannissement par pseudo ou email
            $banned = true;
        }

        $this->userIpBanned = $banned ? $userIp : '';
    }

    /**
     * Nettoyage du cache de session utilisateur.
     */
    private function cleanCache(): void
    {
        CoreCache::getInstance(CoreCacheSection::SESSIONS)->cleanCache(ExecUtils::getMemorizedTimestamp() - $this->sessionTimeLimit);
    }

    /**
     * Récupération d'une session ouverte.
     *
     * @return bool Session valide.
     */
    private function searchSession(): bool
    {
        // Par défaut, la session actuel est valide
        $isValidSession = true;

        if (!$this->hasValidSessionData()) {
            $userId = self::getCookie($this->getUserCookieName());
            $sessionId = self::getCookie($this->getSessionCookieName());

            // Vérifie si une session est ouverte
            if ((!empty($userId) && !empty($sessionId))) {
                // La session doit être entièrement re-validée
                $isValidSession = $this->tryOpenSession($userId,
                                                        $sessionId);
            } else {
                $userLanguage = self::getCookie($this->getLangueCookieName());
                $userTemplate = self::getCookie($this->getTemplateCookieName());

                $this->makeAnonymousSessionData();
                $this->sessionData->setLangue($userLanguage);
                $this->sessionData->setTemplate($userTemplate);
            }
        }
        return $isValidSession;
    }

    /**
     * Tentative d'ouverture de la session.
     *
     * @param int $userId
     * @param string $sessionId
     * @return bool
     */
    private function tryOpenSession(int $userId,
                                    string $sessionId): bool
    {
        // La session doit être entièrement re-validée
        $isValidSession = false;
        $coreCache = CoreCache::getInstance(CoreCacheSection::SESSIONS);

        if ($coreCache->cached($sessionId . '.php')) {
            // Si fichier cache trouvé, on l'utilise
            $sessionArrayDatas = $coreCache->readCacheAsArray($sessionId . '.php');

            if ($sessionArrayDatas['user_id'] === $userId && $sessionArrayDatas['sessionId'] === $sessionId) {
                // Mise a jour du dernier accès toute les 5 min
                if (($coreCache->getCacheMTime($sessionId . '.php') + 5 * 60) < ExecUtils::getMemorizedTimestamp()) {
                    // En base
                    $isValidSession = $this->updateLastConnect($userId);

                    if ($isValidSession) {
                        // En cache
                        $coreCache->touchCache($sessionId . '.php');
                    }
                } else {
                    $isValidSession = true;
                }
            }

            if ($isValidSession) {
                $userLanguage = self::getCookie($this->getLangueCookieName());
                $userTemplate = self::getCookie($this->getTemplateCookieName());

                // Injection des informations du client
                $this->makeSessionData($sessionArrayDatas);
                $this->sessionData->setLangue($userLanguage);
                $this->sessionData->setTemplate($userTemplate);
            }
        }
        return $isValidSession;
    }

    /**
     * Retourne le nom du cookie stockant l'identifiant du client.
     *
     * @return string
     */
    private function &getUserCookieName(): string
    {
        return self::getCryptCookieName($this->cookieName['USER']);
    }

    /**
     * Retourne le nom du cookie stockant l'identifiant de session.
     *
     * @return string
     */
    private function &getSessionCookieName(): string
    {
        return self::getCryptCookieName($this->cookieName['SESSION']);
    }

    /**
     * Retourne le nom du cookie stockant la langue du client.
     *
     * @return string
     */
    private function &getLangueCookieName(): string
    {
        return self::getCryptCookieName($this->cookieName['LANGUE']);
    }

    /**
     * Retourne le nom du cookie stockant le thème du client.
     *
     * @return string
     */
    private function &getTemplateCookieName(): string
    {
        return self::getCryptCookieName($this->cookieName['TEMPLATE']);
    }

    /**
     * Retourne le nom du cookie stockant le bannissement du client.
     *
     * @return string
     */
    private function &getBanishmentCookieName(): string
    {
        return self::getCryptCookieName($this->cookieName['BLACKBAN']);
    }

    /**
     * Ferme une session ouverte.
     */
    private function destroySession(): void
    {
        // Destruction du fichier de session
        $coreCache = CoreCache::getInstance(CoreCacheSection::SESSIONS);

        if ($coreCache->cached($this->sessionId . '.php')) {
            $coreCache->removeCache($this->sessionId . '.php');
        }

        // Destruction des éventuelles cookies
        foreach ($this->cookieName as $key => $value) {
            // On évite de supprimer le cookie de bannissement
            if ($key === 'BLACKBAN') {
                continue;
            }

            ExecCookie::destroyCookie(self::getCryptCookieName($value));
        }

        $this->regenerateNativeSession();
    }

    /**
     * Ouvre une nouvelle session.
     *
     * @return bool Succès
     */
    private function &createSession(): bool
    {
        $rslt = false;
        $this->sessionId = ExecCrypt::makeIdentifier(32);

        // Durée de connexion automatique via cookie
        $cookieTimeLimit = ExecUtils::getMemorizedTimestamp() + $this->sessionTimeLimit;

        // Creation des cookies
        $cookieUser = ExecCookie::createCookie($this->getUserCookieName(),
                                               ExecCrypt::md5Encrypt($this->sessionData->getId(),
                                                                     self::getSalt()),
                                                                     $cookieTimeLimit);
        $cookieSession = ExecCookie::createCookie($this->getSessionCookieName(),
                                                  ExecCrypt::md5Encrypt($this->sessionId,
                                                                        self::getSalt()),
                                                                        $cookieTimeLimit);

        if ($cookieUser && $cookieSession) {
            // Ecriture du cache
            CoreCache::getInstance(CoreCacheSection::SESSIONS)->writeCacheAsString($this->sessionId . '.php',
                                                                                   $this->getSerializedSession());
            $rslt = true;
        } else {
            CoreLogger::addUserWarning(ERROR_SESSION_COOKIE);
        }
        return $rslt;
    }

    /**
     * Retourne les informations de session sérialisées.
     *
     * @return string
     */
    private function &getSerializedSession(): string
    {
        $sessionArrayDatas = $this->sessionData->getData();
        $sessionArrayDatas['userIpBan'] = $this->userIpBanned;
        $sessionArrayDatas['sessionId'] = $this->sessionId;
        return CoreCache::getInstance(CoreCacheSection::SESSIONS)->serializeData($sessionArrayDatas);
    }

    /**
     * Détermine si l'utilisateur est identifié.
     *
     * @return bool Client valide
     */
    private function hasValidSessionData(): bool
    {
        return (!empty($this->sessionId) && $this->sessionData !== null);
    }

    /**
     * Injection d'un client anonyme.
     */
    private function makeAnonymousSessionData(): void
    {
        $empty = array();
        $this->makeSessionData($empty);
    }

    /**
     * Injection des informations du client.
     *
     * @param array $sessionArrayDatas
     * @param bool $refreshAll
     */
    private function makeSessionData(array $sessionArrayDatas,
                                     bool $refreshAll = false): void
    {
        if ($this->sessionData === null || $refreshAll) {
            $this->sessionData = new CoreSessionData($sessionArrayDatas);
        }

        if (!empty($sessionArrayDatas['sessionId'])) {
            $this->sessionId = $sessionArrayDatas['sessionId'];
        }

        if (!empty($sessionArrayDatas['userIpBan'])) {
            $this->userIpBanned = $sessionArrayDatas['userIpBan'];
        }
    }

    /**
     * Mise à jour de la dernière connexion.
     *
     * @param int $userId
     * @return bool true succès de la mise à jour
     */
    private function updateLastConnect(int $userId): bool
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->addQuotedValue('NOW()');

        // Envoi la requête Sql de mise à jour
        $coreSql->update(CoreTable::USERS,
                         array('last_connect' => 'NOW()'),
                         array('user_id = \'' . $userId . '\''))->query();
        return ($coreSql->affectedRows() === 1) ? true : false;
    }

    /**
     * Retourne le contenu décrypté du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    private static function &getCookie(string $cookieName): string
    {
        $cookieContent = ExecCookie::getCookie($cookieName);
        $cookieContent = ExecCrypt::md5Decrypt($cookieContent,
                                               self::getSalt());
        return $cookieContent;
    }

    /**
     * Retourne le nom crypté du cookie.
     *
     * @param string $cookieName
     * @return string
     */
    private static function &getCryptCookieName(string $cookieName): string
    {
        return ExecCrypt::cryptByStandard($cookieName,
                                          self::getSalt());
    }

    /**
     * Retourne la combinaison de clés pour le salt.
     *
     * @return string
     */
    private static function getSalt(): string
    {
        $coreMain = CoreMain::getInstance();
        return $coreMain->getConfigs()->getCryptKey() . $coreMain->getAgentInfos()->getBrowserName();
    }

    /**
     * Chargement les informations utilisateur via la base de données.
     *
     * @return array
     */
    private static function &loadUserData(array $where): array
    {
        $userArrayDatas = array();
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::USERS,
                         array('user_id',
                'name',
                'email',
                'rank',
                'registration_date',
                'avatar',
                'website',
                'signature',
                'template',
                'langue'),
                         $where)->query();
        // TODO vérifier si l'utilisateur n'est pas banni
        if ($coreSql->affectedRows() === 1) {
            $userArrayDatas = $coreSql->fetchArray()[0];
        }
        return $userArrayDatas;
    }
}