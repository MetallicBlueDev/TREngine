<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de la communication SQL.
 *
 * @author Sébastien Villemain
 */
class Core_Sql extends Base_Model {

    /**
     * Gestionnnaire SQL.
     *
     * @var Core_Sql
     */
    private static $coreSql = null;

    /**
     * Modèle de base sélectionné.
     *
     * @var Base_Model
     */
    private $selectedBase = null;

    /**
     * Nouveau gestionnaire.
     */
    protected function __construct() {
        parent::__construct();
        $baseClassName = "";
        $loaded = false;
        $databaseConfig = Core_Main::getInstance()->getConfigDatabase();

        if (!empty($databaseConfig) && isset($databaseConfig['type'])) {
            // Chargement des drivers pour la base
            $baseClassName = "Base_" . ucfirst($databaseConfig['type']);
            $loaded = Core_Loader::classLoader($baseClassName);
        }

        if (!$loaded) {
            Core_Secure::getInstance()->throwException("sqlType", null, array(
                $databaseConfig['type']));
        }

        if (!Core_Loader::isCallable($baseClassName, "initializeBase")) {
            Core_Secure::getInstance()->throwException("sqlCode", null, array(
                $baseClassName));
        }

        try {
            $this->selectedBase = new $baseClassName();
            $this->selectedBase->initializeBase($databaseConfig);
        } catch (Exception $ex) {
            $this->selectedBase = null;
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    public function initializeBase(array &$database) {
        // NE RIEN FAIRE
        unset($database);
    }

    public function __destruct() {
        parent::__destruct();
    }

    /**
     * Retourne l'instance du gestionnaire SQL.
     *
     * @return Core_Sql
     */
    public static function &getInstance() {
        self::checkInstance();
        return self::$coreSql;
    }

    /**
     * Vérification de l'instance du gestionnaire SQL.
     */
    public static function checkInstance() {
        if (self::$coreSql === null) {
            self::$coreSql = new self();
        }
    }

    /**
     * Détermine si une connexion est disponible.
     *
     * @return boolean
     */
    public static function hasConnection() {
        return self::$coreSql !== null && self::$coreSql->selectedBase !== null;
    }

    /**
     * Retourne la liste des types de base supporté
     *
     * @return array
     */
    public static function &listBases() {
        $baseList = array();
        $files = Core_CacheBuffer::listNames("engine/base");

        foreach ($files as $fileName) {
            // Nettoyage du nom de la page
            $pos = strpos($fileName, ".class");

            // Si c'est une page administrable
            if ($pos !== false && $pos > 0) {
                $baseList[] = substr($fileName, 0, $pos);
            }
        }
        return $baseList;
    }

    protected function canUse() {
        // NE RIEN FAIRE
        return false;
    }

    /**
     * Etablie une connexion à la base de données.
     */
    public function dbConnect() {
        $this->selectedBase->dbConnect();
    }

    /**
     * Déconnexion de la base de données.
     */
    public function dbDeconnect() {
        if (self::hasConnection()) {
            $this->selectedBase->dbDeconnect();
        }
    }

    /**
     * Sélectionne la base de données.
     *
     * @return boolean
     */
    public function &dbSelect() {
        return $this->selectedBase->dbSelect();
    }

    /**
     * Retourne le nombre de ligne affectées par la dernière commande.
     * (SELECT, SHOW, DELETE, INSERT, REPLACE and UPDATE).
     *
     * @return int
     */
    public function &affectedRows() {
        return $this->selectedBase->affectedRows();
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getDatabaseHost() {
        return $this->selectedBase->getDatabaseHost();
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getDatabaseUser() {
        return $this->selectedBase->getDatabaseUser();
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getDatabasePass() { // TODO NE PAS METTRE EN PUBLIC.
        return $this->selectedBase->getDatabasePass();
    }

    /**
     * Retourne le nom de la base de données.
     *
     * @return string
     */
    public function &getDatabaseName() {
        return $this->selectedBase->getDatabaseName();
    }

    /**
     * Retourne le type de base (exemple mysqli).
     *
     * @return string
     */
    public function &getDatabaseType() {
        return $this->selectedBase->getDatabaseType();
    }

    /**
     * Retourne le préfixe à utiliser sur les tables.
     *
     * @return string
     */
    public function &getDatabasePrefix() {
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
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Retourne un tableau contenant toutes les lignes demandées.
     *
     * @return array
     */
    public function &fetchArray() {
        return $this->selectedBase->fetchArray();
    }

    /**
     * Retourne un tableau contenant tous les objets demandés.
     *
     * @return array(object)
     */
    public function &fetchObject($className = null) {
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
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
        }
    }

    /**
     * Retourne l'identifiant de la dernière ligne inserée.
     *
     * @return string
     */
    public function &insertId() {
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
        if (Core_Secure::debuggingMode()) {
            Core_Logger::addSqlRequest($sql);
        }

        // Création d'une exception si une réponse est négative (false)
        if ($this->getQueries() === false) {
            throw new Fail_Sql("sqlReq");
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
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
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
            Core_Secure::getInstance()->throwException($ex->getMessage(), $ex);
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
    public function &getSql() {
        return $this->selectedBase->getSql();
    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function connected() {
        return $this->selectedBase !== null && $this->selectedBase->connected();
    }

    /**
     * Libère la mémoire du résultat.
     *
     * @param resource $query
     * @return boolean
     */
    public function &freeResult($query = null) {
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
    public function &getLastError() {
        return $this->selectedBase->getLastError();
    }

    /**
     * Marquer une clé comme déjà quotée.
     *
     * @param string $key
     * @param boolean $value
     */
    public function addQuoted($key, $value = true) {
        $this->selectedBase->addQuoted($key, $value);
    }

    public function resetQuoted() {
        // NE RIEN FAIRE
    }

    /**
     * Retourne la version de la base de données.
     *
     * @return string
     */
    public function &getVersion() {
        return $this->selectedBase->getVersion();
    }

    /**
     * Retourne le type d'encodage de la base de données.
     *
     * @return string
     */
    public function &getCollation() {
        return $this->selectedBase->getCollation();
    }

}
