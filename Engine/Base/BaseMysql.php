<?php

namespace TREngine\Engine\Base;

use TREngine\Engine\Core\CoreLogger;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de transaction utilisant l'extension MySql (ancienne version obselète).
 * Ne supporte que les bases de données MySql.
 *
 * Supprimé depuis la version 7.0 : http://php.net/manual/fr/migration70.incompatible.php#migration70.incompatible.removed-functions.mysql
 * A remplacer par un support de PostgreSQL  et de SQLite3
 * @author Sébastien Villemain
 */
class BaseMysql extends BaseModel {

    /**
     * Le type de la dernière commande.
     * SELECT, DELETE, UPDATE, INSERT, REPLACE.
     *
     * @var string
     */
    private $lastSqlCommand = "";

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    protected function canUse(): bool {
        $rslt = function_exists("mysql_connect");

        if (!$rslt) {
            CoreLogger::addException("MySql function not found");
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netConnect() {
        $link = mysql_connect($this->getTransactionHost(), $this->getTransactionUser(), $this->getTransactionPass());

        if ($link) {
            $this->connId = $link;
        } else {
            $this->connId = null;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function &netSelect(): bool {
        $rslt = false;

        if ($this->netConnected()) {
            $rslt = mysql_select_db($this->getDatabaseName(), $this->connId);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netDeconnect() {
        if ($this->netConnected()) {
            mysql_close($this->connId);
        }

        $this->connId = null;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $sql
     */
    public function query(string $sql = "") {
        $this->queries = mysql_query($sql, $this->connId);

        if ($this->queries === false) {
            CoreLogger::addException("MySql query: " . mysql_error());
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &fetchArray(): array {
        $values = array();

        if (is_resource($this->queries)) {
            $nbRows = $this->affectedRows();

            for ($i = 0; $i < $nbRows; $i++) {
                $values[] = mysql_fetch_array($this->queries, MYSQL_ASSOC);
            }
        }
        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $className
     * @return array
     */
    public function &fetchObject(string $className = null): array {
        $values = array();

        if (is_resource($this->queries)) {
            $nbRows = $this->affectedRows();

            // Vérification avant de rentrer dans la boucle (optimisation)
            if (empty($className)) {
                for ($i = 0; $i < $nbRows; $i++) {
                    $values[] = mysql_fetch_object($this->queries);
                }
            } else {
                for ($i = 0; $i < $nbRows; $i++) {
                    $values[] = mysql_fetch_object($this->queries, $className);
                }
            }
        }
        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $query
     * @return bool
     */
    public function &freeResult($query = null): bool {
        $rslt = false;

        if ($query !== null && is_resource($query)) {
            $rslt = mysql_free_result($query);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function &affectedRows(): int {
        $rslt = -1;

        if ($this->lastSqlCommand === "SELECT" || $this->lastSqlCommand === "SHOW") {
            $rslt = mysql_num_rows($this->queries);
        } else {
            $rslt = mysql_affected_rows($this->connId);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &insertId(): string {
        $lastId = mysql_insert_id($this->connId);
        return $lastId;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &getLastError(): array {
        $error = parent::getLastError();
        $error[] = "<span class=\"text_bold\">MySql response</span> : " . mysql_error();
        return $error;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getVersion(): string {
        // Exemple : 5.6.15-log
        $version = mysql_get_server_info($this->connId);
        $version = ($version !== false) ? $version : "?";
        return $version;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update(string $table, array $values, array $where, array $orderby = array(), string $limit = "") {
        $this->lastSqlCommand = "UPDATE";
        parent::update($table, $values, $where, $orderby, $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select(string $table, array $values, array $where = array(), array $orderby = array(), string $limit = "") {
        $this->lastSqlCommand = "SELECT";
        parent::select($table, $values, $where, $orderby, $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $keys
     * @param array $values
     */
    public function insert(string $table, array $keys, array $values) {
        $this->lastSqlCommand = "INSERT";
        parent::insert($table, $keys, $values);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete(string $table, array $where = array(), array $like = array(), string $limit = "") {
        $this->lastSqlCommand = "DELETE";
        parent::delete($table, $where, $like, $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $str
     * @return string
     */
    protected function converEscapeString(string $str): string {
        if (function_exists("mysql_real_escape_string") && is_resource($this->connId)) {
            $str = mysql_real_escape_string($str, $this->connId);
        } else if (function_exists("mysql_escape_string")) {// WARNING: DEPRECATED
            $str = mysql_escape_string($str);
        } else {
            $str = parent::converEscapeString($str);
        }
        return $str;
    }

}
