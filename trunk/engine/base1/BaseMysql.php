<?php

namespace TREngine\Engine\Base;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de transaction utilisant l'extension MySql (ancienne version obselète).
 * Ne supporte que les bases de données MySql.
 *
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

    protected function canUse() {
        $rslt = function_exists("mysql_connect");

        if (!$rslt) {
            CoreLogger::addException("MySql function not found");
        }
        return $rslt;
    }

    public function netConnect() {
        $link = mysql_connect($this->getTransactionHost(), $this->getTransactionUser(), $this->getTransactionPass());

        if ($link) {
            $this->connId = $link;
        } else {
            $this->connId = null;
        }
    }

    public function &netSelect() {
        $rslt = false;

        if ($this->netConnected()) {
            $rslt = mysql_select_db($this->getDatabaseName(), $this->connId);
        }
        return $rslt;
    }

    public function netDeconnect() {
        if ($this->netConnected()) {
            mysql_close($this->connId);
        }

        $this->connId = null;
    }

    public function query($sql) {
        $this->queries = mysql_query($sql, $this->connId);

        if ($this->queries === false) {
            CoreLogger::addException("MySql query: " . mysql_error());
        }
    }

    public function &fetchArray() {
        $values = array();

        if (is_resource($this->queries)) {
            $nbRows = $this->affectedRows();

            for ($i = 0; $i < $nbRows; $i++) {
                $values[] = mysql_fetch_array($this->queries, MYSQL_ASSOC);
            }
        }
        return $values;
    }

    public function &fetchObject($className = null) {
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

    public function &freeResult($query) {
        $rslt = false;

        if (is_resource($query)) {
            $rslt = mysql_free_result($query);
        }
        return $rslt;
    }

    public function &affectedRows() {
        $rslt = -1;

        if ($this->lastSqlCommand === "SELECT" || $this->lastSqlCommand === "SHOW") {
            $rslt = mysql_num_rows($this->queries);
        } else {
            $rslt = mysql_affected_rows($this->connId);
        }
        return $rslt;
    }

    public function &insertId() {
        $lastId = mysql_insert_id($this->connId);
        return $lastId;
    }

    public function &getLastError() {
        $error = parent::getLastError();
        $error[] = "<b>MySql response</b> : " . mysql_error();
        return $error;
    }

    public function &getVersion() {
        // Exemple : 5.6.15-log
        $version = mysql_get_server_info($this->connId);
        $version = ($version !== false) ? $version : "?";
        return $version;
    }

    public function update($table, array $values, array $where, array $orderby = array(), $limit = "") {
        $this->lastSqlCommand = "UPDATE";
        parent::update($table, $values, $where, $orderby, $limit);
    }

    public function select($table, array $values, array $where = array(), array $orderby = array(), $limit = "") {
        $this->lastSqlCommand = "SELECT";
        parent::select($table, $values, $where, $orderby, $limit);
    }

    public function insert($table, array $keys, array $values) {
        $this->lastSqlCommand = "INSERT";
        parent::insert($table, $keys, $values);
    }

    public function delete($table, array $where = array(), array $like = array(), $limit = "") {
        $this->lastSqlCommand = "DELETE";
        parent::delete($table, $where, $like, $limit);
    }

    protected function converEscapeString($str) {
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
