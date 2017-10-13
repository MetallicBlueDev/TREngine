<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Base\BaseModel;
use TREngine\Engine\Fail\FailSql;
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
                $databaseConfig['type']
            ));
        }

        if (!CoreLoader::isCallable($baseClassName, "initialize")) {
            CoreSecure::getInstance()->throwException("sqlCode", null, array(
                $baseClassName
            ));
        }

        try {
            $this->selectedBase = new $baseClassName();
            $this->selectedBase->initialize($databaseConfig);
        } catch (Exception $ex) {
            $this->selectedBase = null;
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Aucune action réalisée dans ce contexte.
     *
     * @param array $database
     */
    public function initialize(array &$database) {
        // NE RIEN FAIRE
        unset($database);
    }

    /**
     * Destruction du gestionnaire.
     */
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
     * @return bool
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
     *
     * {@inheritdoc}
     */
    public function netConnect() {
        $this->selectedBase->netConnect();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return bool
     */
    public function netConnected(): bool {
        return $this->selectedBase !== null && $this->selectedBase->netConnected();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function netDeconnect() {
        if (self::hasConnection()) {
            $this->selectedBase->netDeconnect();
        }
    }

    /**
     * Sélectionne la base de données.
     *
     * @return bool
     */
    public function &netSelect(): bool {
        return $this->selectedBase->netSelect();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return int
     */
    public function &affectedRows(): int {
        return $this->selectedBase->affectedRows();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionHost(): string {
        return $this->selectedBase->getTransactionHost();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionUser(): string {
        return $this->selectedBase->getTransactionUser();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionPass(): string {
        return $this->selectedBase->getTransactionPass();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getDatabaseName(): string {
        return $this->selectedBase->getDatabaseName();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionType(): string {
        return $this->selectedBase->getTransactionType();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getDatabasePrefix(): string {
        return $this->selectedBase->getDatabasePrefix();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete(string $table, array $where = array(), array $like = array(), string $limit = "") {
        $this->selectedBase->delete($table, $where, $like, $limit);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function &fetchArray(): array {
        return $this->selectedBase->fetchArray();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $className
     * @return array
     */
    public function &fetchObject(string $className = null): array {
        return $this->selectedBase->fetchObject($className);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $table Nom de la table
     * @param array $keys
     * @param array $values
     */
    public function insert(string $table, array $keys, array $values) {
        $this->selectedBase->insert($table, $keys, $values);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &insertId(): string {
        return $this->selectedBase->insertId();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $sql
     * @throws Exception
     */
    public function query(string $sql = "") {
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
     *
     * {@inheritdoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select(string $table, array $values, array $where = array(), array $orderby = array(), string $limit = "") {
        $this->selectedBase->select($table, $values, $where, $orderby, $limit);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $table Nom de la table
     * @param array $values Sous la forme array("keyName" => "newValue")
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update(string $table, array $values, array $where, array $orderby = array(), string $limit = "") {
        $this->selectedBase->update($table, $values, $where, $orderby, $limit);

        try {
            $this->query();
        } catch (Exception $ex) {
            CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function &getQueries() {
        return $this->selectedBase->getQueries();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getSql(): string {
        return $this->selectedBase->getSql();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param mixed $query
     * @return bool
     */
    public function &freeResult($query = null): bool {
        $query = (!empty($query)) ? $query : $this->getQueries();
        return $this->selectedBase->freeResult($query);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $name
     * @param string $key
     */
    public function addArrayBuffer(string $name, string $key = "") {
        $this->selectedBase->addArrayBuffer($name, $key);
        $this->freeResult();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addObjectBuffer(string $name, string $key = "") {
        $this->selectedBase->addObjectBuffer($name, $key);
        $this->freeResult();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $name
     * @return array
     */
    public function &fetchBuffer(string $name): array {
        return $this->selectedBase->fetchBuffer($name);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $name
     * @return mixed
     */
    public function &getBuffer(string $name) {
        return $this->selectedBase->getBuffer($name);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function &getLastError(): array {
        return $this->selectedBase->getLastError();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $key
     */
    public function addQuotedKey(string $key) {
        $this->selectedBase->addQuotedKey($key);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param string $value
     */
    public function addQuotedValue(string $value) {
        $this->selectedBase->addQuotedValue($value);
    }

    /**
     * Aucune action réalisée dans ce contexte.
     */
    public function resetQuoted() {
        // NE RIEN FAIRE
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getVersion(): string {
        return $this->selectedBase->getVersion();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getCollation(): string {
        return $this->selectedBase->getCollation();
    }

}
