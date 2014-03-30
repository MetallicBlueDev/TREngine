<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de la communication SQL
 *
 * @author Sébastien Villemain
 */
class Core_Sql {

    /**
     * Instance de la base
     *
     * @var Base_xxxx
     */
    private static $base = null;

    /**
     * Donnée de la base
     *
     * @var array
     */
    private static $dataBase = array();

    /**
     * Démarre une instance de communication avec la base
     */
    public static function makeInstance() {
        if (self::$base == null && !empty(self::$dataBase)) {
            // Chargement des drivers pour la base
            $BaseClass = "Base_" . ucfirst(self::$dataBase['type']);
            Core_Loader::classLoader($BaseClass);

            // Si la classe peut être utilisée
            if (Core_Loader::isCallable($BaseClass)) {
                try {
                    self::$base = new $BaseClass();
                } catch (Exception $ie) {
                    Core_Secure::getInstance()->throwException($ie);
                }
            } else {
                Core_Secure::getInstance()->throwException("sqlCode", $BaseClass);
            }

            // Charge les constantes de table
            Core_Loader::classLoader("Core_Table");
        }
    }

    /**
     * Retourne la liste des types de base supporté
     *
     * @return array
     */
    public static function &listBases() {
        $baseList = array();
        $files = Core_CacheBuffer::listNames("engine/base");
        foreach ($files as $key => $fileName) {
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
     * Etablie une connexion à la base de données
     */
    public static function dbConnect() {
        self::$base->dbConnect();
    }

    /**
     * Sélectionne une base de données
     *
     * @return boolean true succes
     */
    public static function dbSelect() {
        return self::$base->dbSelect();
        ;
    }

    /**
     * Get number of LAST affected rows (for all : SELECT, SHOW, DELETE, INSERT, REPLACE and UPDATE)
     *
     * @return int
     */
    public static function affectedRows() {
        return self::$base->affectedRows();
    }

    /**
     * Supprime des informations
     *
     * @param $table string Nom de la table
     * @param $where array
     * @param $like array
     * @param $limit string
     */
    public static function delete($table, $where = array(), $like = array(), $limit = false) {
        self::$base->delete($table, $where, $like, $limit);

        try {
            self::query();
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Retourne un tableau qui contient la ligne demandée
     *
     * @return array
     */
    public static function fetchArray() {
        return self::$base->fetchArray();
    }

    /**
     * Retourne un objet qui contient le ligne demandée
     *
     * @return object
     */
    public static function fetchObject() {
        return self::$base->fetchObject();
    }

    /**
     * Insere une ou des valeurs dans une table
     *
     * @param $table string Nom de la table
     * @param $keys array
     * @param $values array
     */
    public static function insert($table, $keys, $values) {
        self::$base->insert($table, $keys, $values);

        try {
            self::query();
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Retourne l'id de la dernière ligne inserée
     *
     * @return int
     */
    public static function insertId() {
        return self::$base->insertId();
    }

    /**
     * Envoie une requête Sql
     *
     * @param $Sql
     */
    public static function query($sql = "") {
        $sql = (!empty($sql)) ? $sql : self::getSql();
        self::$base->query($sql);
        self::$base->resetQuoted();

        // Ajout la requête au log
        if (Core_Secure::isDebuggingMode())
            Core_Logger::addSqlRequest($sql);

        // Création d'une exception si une réponse est négative (false)
        if (self::getQueries() === false)
            throw new Exception("sqlReq");
    }

    /**
     * Sélection d'information
     *
     * @param $table string
     * @param $values array
     * @param $where array
     * @param $orderby array
     * @param $limit string
     */
    public static function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
        self::$base->select($table, $values, $where, $orderby, $limit);

        try {
            self::query();
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Mise à jour d'une table
     *
     * @param $table Nom de la table
     * @param $values array Sous la forme array("keyName" => "newValue")
     * @param $where array
     * @param $orderby array
     * @param $limit string
     */
    public static function update($table, $values, $where, $orderby = array(), $limit = false) {
        self::$base->update($table, $values, $where, $orderby, $limit);

        try {
            self::query();
        } catch (Exception $ie) {
            Core_Secure::getInstance()->throwException($ie);
        }
    }

    /**
     * Retourne dernier résultat de la dernière requête executée
     *
     * @return Ressource ID
     */
    public static function getQueries() {
        return self::$base->getQueries();
    }

    /**
     * Retourne la dernière requête sql
     *
     * @return string
     */
    public static function getSql() {
        return self::$base->getSql();
    }

    /**
     * Libere la mémoire du resultat
     *
     * @param $querie Resource Id
     * @return boolean
     */
    public static function freeResult($querie = "") {
        $querie = (!empty($querie)) ? $querie : self::getQueries();
        return self::$base->freeResult($querie);
    }

    /**
     * Ajoute un bout de donnée dans le buffer
     *
     * @param $key string cles a utiliser
     * @param $name string
     */
    public static function addBuffer($name, $key = "") {
        self::$base->addBuffer($name, $key);
        self::freeResult();
    }

    /**
     * Retourne le buffer courant puis l'incremente
     *
     * @param $name string
     * @return array - object
     */
    public static function fetchBuffer($name) {
        return self::$base->fetchBuffer($name);
    }

    /**
     * Retourne le buffer complet choisi
     * Retourne un tableau Sdt object
     *
     * @param $name string
     * @return array - object
     */
    public static function getBuffer($name) {
        return self::$base->getBuffer($name);
    }

    /**
     * Retourne les dernières erreurs
     *
     * @return array
     */
    public static function getLastError() {
        return self::$base->getLastError();
    }

    /**
     * Marqué une cles comme déjà quoté
     *
     * @param $key string
     */
    public static function addQuoted($key, $value = 1) {
        self::$base->addQuoted($key, $value);
    }

    /**
     * Retourne la version de la base de données
     *
     * @return string
     */
    public static function getVersion() {
        return self::$base->getVersion();
    }

    /**
     * Retourne le type d'encodage de la base de données
     *
     * @return string
     */
    public static function getCollation() {
        return self::$base->getCollation();
    }

    /**
     * Récupération des données de la base
     *
     * @return array
     */
    public static function getDatabase() {
        return self::$dataBase;
    }

    /**
     * Injection des données de la base
     *
     * @param array
     */
    public static function setDatabase($database = array()) {
        self::$dataBase = $database;
    }

}

?>