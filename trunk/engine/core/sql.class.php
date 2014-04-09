<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

Core_Loader::classLoader("Base_Model");
Core_Loader::classLoader("Core_Table");

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
     *
     * @param array $databaseConfig Injection des données de la base
     */
    private function __construct(array $databaseConfig) {
        parent::__construct();
        $baseClassName = "";

        if (!empty($databaseConfig) && isset($databaseConfig['type'])) {
            // Chargement des drivers pour la base
            $baseClassName = "Base_" . ucfirst($databaseConfig['type']);
            Core_Loader::classLoader($baseClassName);
        }

        // Si la classe peut être utilisée
        if (Core_Loader::isCallable($baseClassName)) {
            try {
                $this->selectedBase = new $baseClassName();
                $this->selectedBase->initializeBase($databaseConfig);
            } catch (Exception $ie) {
                Core_Secure::getInstance()->throwException($ie);
            }
        } else {
            Core_Secure::getInstance()->throwException("sqlCode", $baseClassName);
        }
    }

    public function initializeBase(array $database) {
        unset($database);
    }

    public function __destruct() {
        parent::__destruct();
    }

    /**
     * Instance du gestionnaire SQL.
     *
     * @param array $database Injection des données de la base
     * @return Core_Sql
     */
    public static function &getInstance(array $database = array()) {
        if (self::$coreSql == null) {
            self::$coreSql = new self($database);
        }
        return self::$coreSql;
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
        $this->selectedBase->dbDeconnect();
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
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Retourne un tableau qui contient la ligne demandée.
     *
     * @return array
     */
    public function &fetchArray() {
        return $this->selectedBase->fetchArray();
    }

    /**
     * Retourne un objet qui contient la ligne demandée.
     *
     * @return object
     */
    public function &fetchObject() {
        return $this->selectedBase->fetchObject();
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
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Retourne l'id de la dernière ligne inserée.
     *
     * @return int
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
        $sql = (!empty($sql)) ? $sql : self::getSql();
        $this->selectedBase->query($sql);
        $this->selectedBase->resetQuoted();

        // Ajout la requête au log
        if (Core_Secure::isDebuggingMode()) {
            Core_Logger::addSqlRequest($sql);
        }

        // Création d'une exception si une réponse est négative (false)
        if (self::getQueries() === false) {
            throw new Exception("sqlReq");
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
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
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
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Retourne le dernier résultat de la dernière requête executée.
     *
     * @return resource
     */
    public static function &getQueries() {
        return $this->selectedBase->getQueries();
    }

    /**
     * Retourne la dernière requête sql.
     *
     * @return string
     */
    public static function &getSql() {
        return $this->selectedBase->getSql();
    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function &isConnected() {
        return $this->selectedBase->isConnected();
    }

    /**
     * Libere la mémoire du resultat
     *
     * @param $querie Resource Id
     * @return boolean
     */
    public static function &freeResult($querie = "") {
        $querie = (!empty($querie)) ? $querie : self::getQueries();
        return $this->selectedBase->freeResult($querie);
    }

    /**
     * Ajoute un bout de donnée dans le buffer
     *
     * @param $key string cles a utiliser
     * @param $name string
     */
    public static function addBuffer($name, $key = "") {
        $this->selectedBase->addBuffer($name, $key);
        self::freeResult();
    }

    /**
     * Retourne le buffer courant puis l'incremente
     *
     * @param $name string
     * @return array - object
     */
    public static function &fetchBuffer($name) {
        return $this->selectedBase->fetchBuffer($name);
    }

    /**
     * Retourne le buffer complet choisi
     * Retourne un tableau Sdt object
     *
     * @param $name string
     * @return array - object
     */
    public static function &getBuffer($name) {
        return $this->selectedBase->getBuffer($name);
    }

    /**
     * Vérifie si la plateform est disponible.
     *
     * @return boolean
     */
    public function &test() {
        return false;
    }

    /**
     * Retourne les dernières erreurs
     *
     * @return array
     */
    public static function &getLastError() {
        return $this->selectedBase->getLastError();
    }

    /**
     * Marqué une cles comme déjà quoté
     *
     * @param $key string
     */
    public static function addQuoted($key, $value = 1) {
        $this->selectedBase->addQuoted($key, $value);
    }

    /**
     * Remise à zéro du tableau de cles déjà quoté.
     */
    public function resetQuoted() {
        $this->selectedBase->resetQuoted();
    }

    /**
     * Retourne la version de la base de données
     *
     * @return string
     */
    public static function &getVersion() {
        return $this->selectedBase->getVersion();
    }

    /**
     * Retourne le type d'encodage de la base de données
     *
     * @return string
     */
    public static function &getCollation() {
        return $this->selectedBase->getCollation();
    }

    /**
     * Récupération des données de la base
     *
     * @return array
     */
    public static function &getDatabase() {
        return self::$dataBase;
    }

}
