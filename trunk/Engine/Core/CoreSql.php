<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Base\BaseModel;
use Exception;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de la communication SQL.
 *
 * @author Sébastien Villemain
 */
class CoreSql extends BaseModel {

    /**
     * Gestionnnaire SQL.
     *
     * @var CoreSql
     */
    private static $coreSql = null;

    /**
     * Modèle de base sélectionné.
     *
     * @var BaseModel
     */
    private $selectedBase = null;

    /**
     * Nouveau gestionnaire.
     */
    protected function __construct() {
        parent::__construct();

        $baseClassName = "";
        $loaded = false;
        $databaseConfig = CoreMain::getInstance()->getConfigDatabase();

        if (!empty($databaseConfig) && isset($databaseConfig['type'])) {
            // Chargement des drivers pour la base
            $baseClassName = CoreLoader::getFullQualifiedClassName("Base" . ucfirst($databaseConfig['type']));
            $loaded = CoreLoader::classLoader($baseClassName);
        }

        if (!$loaded) {
            CoreSecure::getInstance()->throwException("sqlType", null, array(
                $databaseConfig['type']));
        }

        if (!CoreLoader::isCallable($baseClassName, "initialize")) {
            CoreSecure::getInstance()->throwException("sqlCode", null, array(
                $baseClassName));
        }

        try {
            $this->selectedBase = new $baseClassName();
            $this->selectedBase->initialize($databaseConfig);
        } catch (Exception $ex) {
            $this->selectedBase = null;
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    public function initialize(array &$database) {
        // NE RIEN FAIRE
        unset($database);
    }

    public function __destruct() {
        $this->selectedBase = null;
    }

    /**
     * Retourne l'instance du gestionnaire SQL.
     *
     * @return CoreSql
     */
    public static function &getInstance(): CoreSql {
        self::checkInstance();
        return self::$coreSql;
    }

    /**
     * Vérification de l'instance du gestionnaire SQL.
     */
    public static function checkInstance() {
        if (self::$coreSql === null) {
            self::$coreSql = new CoreSql();
        }
    }

    /**
     * Détermine si une connexion est disponible.
     *
     * @return boolean
     */
    public static function hasConnection(): bool {
        return self::$coreSql !== null && self::$coreSql->selectedBase !== null;
    }

    /**
     * Retourne la liste des types de base supporté
     *
     * @return array
     */
    public static function &getBaseList(): array {
        return CoreCache::getInstance()->getFileList("Engine/Base", "Base");
    }

    /**
     * Etablie une connexion à la base de données.
     */
    public function netConnect(): bool {
        $this->selectedBase->netConnect();
    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function netConnected(): bool {
        return $this->selectedBase !== null && $this->selectedBase->netConnected();
    }

    /**
     * Déconnexion de la base de données.
     */
    public function netDeconnect() {
        if (self::hasConnection()) {
            $this->selectedBase->netDeconnect();
        }
    }

    /**
     * Sélectionne la base de données.
     *
     * @return boolean
     */
    public function &netSelect(): bool {
        return $this->selectedBase->netSelect();
    }

    /**
     * Retourne le nombre de ligne affectées par la dernière commande.
     * (SELECT, SHOW, DELETE, INSERT, REPLACE and UPDATE).
     *
     * @return int
     */
    public function &affectedRows(): int {
        return $this->selectedBase->affectedRows();
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost(): string {
        return $this->selectedBase->getTransactionHost();
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser(): string {
        return $this->selectedBase->getTransactionUser();
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass(): string {
        return $this->selectedBase->getTransactionPass();
    }

    /**
     * Retourne le nom de la base de données.
     *
     * @return string
     */
    public function &getDatabaseName(): string {
        return $this->selectedBase->getDatabaseName();
    }

    /**
     * Retourne le type de base (exemple mysqli).
     *
     * @return string
     */
    public function &getTransactionType(): string {
        return $this->selectedBase->getTransactionType();
    }

    /**
     * Retourne le préfixe à utiliser sur les tables.
     *
     * @return string
     */
    public function &getDatabasePrefix(): string {
        return $this->selectedBase->getDatabasePrefix();
    }

    /**
     * Supprime des informations.
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete($table, array $where = array(), array $like = array(), $limit = "") {
        $this->selectedBase->delete($table, $where, $like, $limit);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Retourne un tableau contenant toutes les lignes demandées.
     *
     * @return array
     */
    public function &fetchArray(): array {
        return $this->selectedBase->fetchArray();
    }

    /**
     * Retourne un tableau contenant tous les objets demandés.
     *
     * @return object[]
     */
    public function &fetchObject($className = null): array {
        return $this->selectedBase->fetchObject($className);
    }

    /**
     * Insère une ou des valeurs dans une table.
     *
     * @param string $table Nom de la table
     * @param array $keys
     * @param array $values
     */
    public function insert($table, array $keys, array $values) {
        $this->selectedBase->insert($table, $keys, $values);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Retourne l'identifiant de la dernière ligne inserée.
     *
     * @return string
     */
    public function &insertId(): string {
        return $this->selectedBase->insertId();
    }

    /**
     * Envoi une requête Sql.
     *
     * @param string $sql
     * @throws Exception
     */
    public function query($sql = "") {
        $sql = (!empty($sql)) ? $sql : $this->getSql();

        $this->selectedBase->query($sql);
        $this->selectedBase->resetQuoted();

        // Ajout la requête au log
        if (CoreSecure::debuggingMode()) {
            CoreLogger::addSqlRequest($sql);
        }

        // Création d'une exception si une réponse est négative (false)
        if ($this->getQueries() === false) {
            throw new FailSql("sqlReq");
        }
    }

    /**
     * Sélection des informations d'une table.
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select($table, array $values, array $where = array(), array $orderby = array(), $limit = "") {
        $this->selectedBase->select($table, $values, $where, $orderby, $limit);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Mise à jour des données d'une table.
     *
     * @param string $table Nom de la table
     * @param array $values Sous la forme array("keyName" => "newValue")
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update($table, array $values, array $where, array $orderby = array(), $limit = "") {
        $this->selectedBase->update($table, $values, $where, $orderby, $limit);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Retourne le dernier résultat de la dernière requête executée.
     *
     * @return resource
     */
    public function &getQueries() {
        return $this->selectedBase->getQueries();
    }

    /**
     * Retourne la dernière requête sql.
     *
     * @return string
     */
    public function &getSql(): string {
        return $this->selectedBase->getSql();
    }

    /**
     * Libère la mémoire du résultat.
     *
     * @param resource $query
     * @return boolean
     */
    public function &freeResult($query = null): bool {
        $query = (!empty($query)) ? $query : $this->getQueries();
        return $this->selectedBase->freeResult($query);
    }

    /**
     * Ajoute le dernier résultat dans le buffer typé en tableau.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addArrayBuffer($name, $key = "") {
        $this->selectedBase->addArrayBuffer($name, $key);
        $this->freeResult();
    }

    /**
     * Ajoute le dernier résultat dans le buffer typé en object.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addObjectBuffer($name, $key = "") {
        $this->selectedBase->addObjectBuffer($name, $key);
        $this->freeResult();
    }

    /**
     * Retourne le buffer courant puis l'incremente.
     *
     * @param string $name
     * @return array or object
     */
    public function &fetchBuffer($name) {
        return $this->selectedBase->fetchBuffer($name);
    }

    /**
     * Retourne le buffer complet choisi.
     *
     * @param string $name
     * @return array or object
     */
    public function &getBuffer($name) {
        return $this->selectedBase->getBuffer($name);
    }

    /**
     * Retourne les dernières erreurs.
     *
     * @return array
     */
    public function &getLastError(): array {
        return $this->selectedBase->getLastError();
    }

    /**
     * Marquer une clé comme déjà protégée.
     *
     * @param string $key
     */
    public function addQuotedKey($key) {
        $this->selectedBase->addQuotedKey($key);
    }

    /**
     * Marquer une valeur comme déjà protégée.
     *
     * @param string $value
     */
    public function addQuotedValue($value) {
        $this->selectedBase->addQuotedValue($value);
    }

    public function resetQuoted() {
        // NE RIEN FAIRE
    }

    /**
     * Retourne la version de la base de données.
     *
     * @return string
     */
    public function &getVersion(): string {
        return $this->selectedBase->getVersion();
    }

    /**
     * Retourne le type d'encodage de la base de données.
     *
     * @return string
     */
    public function &getCollation(): string {
        return $this->selectedBase->getCollation();
    }

}
