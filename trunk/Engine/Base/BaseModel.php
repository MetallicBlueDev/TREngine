<?php

namespace TREngine\Engine\Base;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreTransaction;
use TREngine\Engine\Exec\ExecUtils;
use TREngine\Engine\Fail\FailSql;

/**
 * Modèle de base de la communication SQL.
 *
 * @author Sébastien Villemain
 */
abstract class BaseModel extends CoreTransaction {

    /**
     * Dernier resultat de la dernière requête SQL.
     *
     * @var mixed
     */
    protected $queries = "";

    /**
     * Dernière requête SQL.
     *
     * @var string
     */
    protected $sql = "";

    /**
     * Mémoire tampon: tableau array contenant des objets standards.
     *
     * @var array
     */
    protected $buffer = array();

    /**
     * Quote pour les objects, champs...
     *
     * @var string
     */
    protected $quoteKey = "`";

    /**
     * Quote pour les valeurs uniquement.
     *
     * @var string
     */
    protected $quoteValue = "'";

    /**
     * Tableau contenant les clés et valeurs déjà protégées.
     *
     * @var array
     */
    protected $quoted = array();

    /**
     *
     * {@inheritdoc}
     *
     * @param string $message
     * @throws FailSql
     */
    protected function throwException(string $message) {
        throw new FailSql("sql" . $message);
    }

    /**
     * Retourne le nombre de lignes affectées à la dernière requête.
     *
     * @return int
     */
    public function &affectedRows(): int {
        $rslt = -1;
        return $rslt;
    }

    /**
     * Retourne le nom de la base de données.
     *
     * @return string
     */
    public function &getDatabaseName(): string {
        return $this->getStringValue("name");
    }

    /**
     * Retourne le préfixe à utiliser sur les tables.
     *
     * @return string
     */
    public function &getDatabasePrefix(): string {
        return $this->getStringValue("prefix");
    }

    /**
     * Supprime des informations.
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete(string $table, array $where = array(), array $like = array(), string $limit = "") {
        // Nom complet de la table
        $table = $this->getTableName($table);
        // Mise en place du WHERE
        $whereValue = empty($where) ? "" : " WHERE " . implode(" ", $where);
        // Mise en place du LIKE
        $likeValue = empty($like) ? "" : " LIKE " . implode(" ", $like);
        // Fonction ET entre WHERE et LIKE
        if (!empty($whereValue) && !empty($likeValue)) {
            $whereValue .= "AND";
        }
        $limit = empty($limit) ? "" : " LIMIT " . $limit;
        $this->sql = "DELETE FROM " . $table . $whereValue . $likeValue . $limit;
    }

    /**
     * Retourne un tableau contenant toutes les lignes demandées.
     *
     * @return array
     */
    public function &fetchArray(): array {
        $rslt = array();
        return $rslt;
    }

    /**
     * Retourne un tableau contenant tous les objets demandés.
     *
     * @param string $className Nom de la classe
     * @return array
     */
    public function &fetchObject(string $className = null): array {
        unset($className);
        $rslt = array();
        return $rslt;
    }

    /**
     * Insère une ou des valeurs dans une table.
     *
     * @param string $table Nom de la table
     * @param array $keys
     * @param array $values
     */
    public function insert(string $table, array $keys, array $values) {
        // Nom complet de la table
        $table = $this->getTableName($table);
        $this->sql = "INSERT INTO " . $table . " (" . implode(", ", $this->converKey($keys)) . ") VALUES (" . implode(", ", $this->converValue($values)) . ")";
    }

    /**
     * Retourne l'id de la dernière ligne inserée.
     *
     * @return string
     */
    public function &insertId(): string {
        $rslt = "0";
        return $rslt;
    }

    /**
     * Envoi une requête Sql.
     *
     * @param string $sql
     */
    public function query(string $sql = "") {
        unset($sql);
    }

    /**
     * Sélection d'information.
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select(string $table, array $values, array $where = array(), array $orderby = array(), string $limit = "") {
        // Nom complet de la table
        $table = $this->getTableName($table);
        // Mise en place des valeurs sélectionnées
        $valuesValue = implode(", ", $values);
        // Mise en place du where
        $whereValue = empty($where) ? "" : " WHERE " . implode(" ", $where);
        // Mise en place de la limite
        $limit = empty($limit) ? "" : " LIMIT " . $limit;
        // Mise en place de l'ordre
        $orderbyValue = empty($orderby) ? "" : " ORDER BY " . implode(", ", $orderby);
        // Mise en forme de la requête finale
        $this->sql = "SELECT " . $valuesValue . " FROM " . $table . $whereValue . $orderbyValue . $limit;
    }

    /**
     * Mise à jour d'une table.
     *
     * @param string $table Nom de la table
     * @param array $values Sous la forme array("keyName" => "newValue")
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update(string $table, array $values, array $where, array $orderby = array(), string $limit = "") {
        // Nom complet de la table
        $table = $this->getTableName($table);
        // Affectation des clès à leurs valeurs
        $valuesString = array();
        foreach ($values as $key => $value) {
            $valuesString[] = $this->converKey($key) . " = " . $this->converValue($value);
        }
        $whereValue = empty($where) ? "" : " WHERE " . implode(" ", $where);
        // Mise en place de la limite
        $limit = empty($limit) ? "" : " LIMIT " . $limit;
        // Mise en place de l'ordre
        $orderbyValue = empty($orderby) ? "" : " ORDER BY " . implode(", ", $orderby);
        // Mise en forme de la requête finale
        $this->sql = "UPDATE " . $table . " SET " . implode(", ", $valuesString) . $whereValue . $orderbyValue . $limit;
    }

    /**
     * Retourne le dernier résultat de la dernière requête executée.
     *
     * @return mixed
     */
    public function &getQueries() {
        return $this->queries;
    }

    /**
     * Retourne la dernière requête sql.
     *
     * @return string
     */
    public function &getSql(): string {
        return $this->sql;
    }

    /**
     * Libère la mémoire du resultat.
     *
     * @param mixed $query
     * @return bool
     */
    public function &freeResult($query = null): bool {
        if ($query !== null) {
            unset($query);
        }
        $rslt = false;
        return $rslt;
    }

    /**
     * Ajoute le dernier résultat dans la mémoire tampon typé en tableau.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addArrayBuffer(string $name, string $key = "") {
        if (!isset($this->buffer[$name])) {
            foreach ($this->fetchArray() as $row) {
                if (!empty($key)) {
                    $this->buffer[$name][$row[$key]] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }
            reset($this->buffer[$name]);
        }
    }

    /**
     * Ajoute le dernier résultat dans la mémoire tampon typé en object.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addObjectBuffer(string $name, string $key = "") {
        if (!isset($this->buffer[$name])) {
            foreach ($this->fetchObject() as $row) {
                if (!empty($key)) {
                    $this->buffer[$name][$row->$key] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }
            reset($this->buffer[$name]);
        }
    }

    /**
     * Retourne la mémoire tampon courant puis incrémente le pointeur interne.
     *
     * @param string $name
     * @return array
     */
    public function &fetchBuffer(string $name): array {
        $buffer = array(
            current($this->buffer[$name])
        );
        next($this->buffer[$name]);
        return $buffer;
    }

    /**
     * Retourne la mémoire tampon complète.
     *
     * @param string $name
     * @return mixed
     */
    public function &getBuffer(string $name) {
        return $this->buffer[$name];
    }

    /**
     * Retourne les dernières erreurs.
     *
     * @return array
     */
    public function &getLastError(): array {
        $rslt = array(
            "<span class=\"text_bold\">Last Sql query</span> : " . $this->getSql()
        );
        return $rslt;
    }

    /**
     * Marquer une clé comme déjà protégée.
     *
     * @param string $key
     */
    public function addQuotedKey(string $key) {
        if (!empty($key)) {
            $this->quoted[$key] = true;
        }
    }

    /**
     * Marquer une valeur comme déjà protégée.
     *
     * @param string $value
     */
    public function addQuotedValue(string $value) {
        if (!empty($value)) {
            $this->quoted[] = $value;
        }
    }

    /**
     * Remise à zéro du tableau de clés déjà quoté.
     */
    public function resetQuoted() {
        $this->quoted = array();
    }

    /**
     * Retourne la version de la base.
     *
     * @return string
     */
    public function &getVersion(): string {
        return "?";
    }

    /**
     * Retourne le type d'encodage de la base de données.
     *
     * @return string
     */
    public function &getCollation(): string {
        $this->query("SHOW FULL COLUMNS FROM " . $this->getTableName(CoreTable::CONFIG_TABLE));
        $info = $this->fetchArray();
        return !empty($info['Collation']) ? $info['Collation'] : "?";
    }

    /**
     * Quote les identifiants et les valeurs.
     *
     * @param string $s
     * @param bool $isValue
     * @return string
     */
    protected function &addQuote(string $s, bool $isValue = false): string {
        // Ne pas quoter les champs avec la notation avec les point
        if (($isValue && !ExecUtils::inArray($s, $this->quoted, true)) || (!$isValue && strpos($s, ".") === false && !isset($this->quoted[$s]))) {
            if ($isValue) {
                $q = $this->quoteValue;
            } else {
                $q = $this->quoteKey;
            }
            $s = $q . $s . $q;
        }
        return $s;
    }

    /**
     * Conversion des valeurs dite PHP en valeurs semblable SQL.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function &converValue($value) {
        if (is_array($value)) {
            foreach ($value as $realKey => $realValue) {
                $value[$realKey] = $this->converValue($realValue);
            }
        }
        if (is_bool($value)) {
            $value = ($value === true) ? 1 : 0;
        } else if (is_null($value)) {
            $value = "NULL";
        } else if (is_string($value)) {
            $value = $this->converEscapeString($value);
        }
        if (!is_array($value)) {
            $value = $this->addQuote($value, true);
        }
        return $value;
    }

    /**
     * Conversion des clès.
     *
     * @param mixed $key
     * @return mixed
     */
    protected function &converKey($key) {
        if (is_array($key)) {
            foreach ($key as $realKey => $keyValue) {
                $key[$realKey] = $this->converKey($keyValue);
            }
        } else {
            $key = $this->addQuote($key);
        }
        // Converti les multiples espaces (tabulation, espace en trop) en espace simple
        $key = preg_replace("/[\t ]+/", " ", $key);
        return $key;
    }

    /**
     * Protège les caractères spéciaux SQL.
     *
     * @param string $str
     * @return string
     */
    protected function converEscapeString(string $str): string {
        return addslashes($str);
    }

    /**
     * Retourne le nom de la table avec le préfixage.
     *
     * @param string $table
     */
    protected function getTableName(string $table): string {
        return $this->getDatabasePrefix() . "_" . $table;
    }

}
