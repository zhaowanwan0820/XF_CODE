<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 15:29:20
 * @encode UTF-8编码
 */
class P_Db_Mysql extends P_Db_Abstract {

    const CHARSET = 'utf8';
    const DB_NAME = '';
    const PCONNECT = false;

    private $_message = "Errno %d: %s";
    private $_num_rows = 0;
    private $_quote_column = '`';
    private $_quote_table = '`';
    private $_options = array(
        'ALL',
        'DISTINCT',
        'DISTINCTROW',
        'HIGH_PRIORITY',
        'SQL_BIG_RESULT',
        'SQL_BUFFER_RESULT',
        'SQL_CACHE',
        'SQL_CALC_FOUND_ROWS',
        'SQL_NO_CACHE',
        'SQL_SMALL_RESULT',
        'STRAIGHT_JOIN',
    );

    public function affected_rows() {
        return mysql_affected_rows($this->_handle);
    }

    public function check_option($options) {
        $options = preg_split(P_Conf_Db::SQL_PREG_OPTION_SPLIT, trim($options), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($options as $option) {
            if (!in_array(strtoupper($option), $this->_options)) {
                new P_Exception_Db("undefined select option, option={$option}.", P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
                return false;
            }
        }
        return true;
    }

    public function close() {
        if (@mysql_close($this->_handle)) {
            return true;
        }
        new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
        return false;
    }

    public function commit() {
        if (@mysql_query("COMMIT", $this->_handle) && @mysql_query("SET AUTOCOMMIT=1", $this->_handle)) {
            return true;
        }
        new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
        return false;
    }

    protected function connect($args) {
        if (!isset($args[P_Conf_Db::DB_HOST], $args[P_Conf_Db::DB_PORT], $args[P_Conf_Db::DB_USER], $args[P_Conf_Db::DB_PWD])) {
            new P_Exception_Db("arguments don't exist, such as host, port, user, password.", P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
            return false;
        }
        $db_host = $args[P_Conf_Db::DB_HOST];
        $db_port = $args[P_Conf_Db::DB_PORT];
        $db_user = $args[P_Conf_Db::DB_USER];
        $db_pwd = $args[P_Conf_Db::DB_PWD];
        $db_name = isset($args[P_Conf_Db::DB_NAME]) ? $args[P_Conf_Db::DB_NAME] : self::DB_NAME;
        $charset = isset($args[P_Conf_Db::DB_CHARSET]) ? $args[P_Conf_Db::DB_CHARSET] : self::CHARSET;
        $pconnect = isset($args[P_Conf_Db::DB_PCONNECT]) ? $args[P_Conf_Db::DB_PCONNECT] : self::PCONNECT;
        if ($pconnect) {
            if (!($this->_handle = @mysql_pconnect($db_host, $db_user, $db_pwd))) {
                new P_Exception_Db("can't connect db", P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
                return false;
            }
        } else {
            if (!($this->_handle = @mysql_connect($db_host, $db_user, $db_pwd, true))) {
                new P_Exception_Db("can't connect db", P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
                return false;
            }
        }
        if (!@mysql_set_charset($charset, $this->_handle)) {
            new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
            return false;
        }
        if (!empty($db_name)) {
            if (@mysql_select_db($db_name, $this->_handle) === false) {
                new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
                return false;
            }
        }
        return true;
    }

    public function escape_string($value) {
        if ($this->is_valid()) {
            return strval(mysql_real_escape_string(strval($value), $this->_handle));
        } else {
            return strval(mysql_escape_string(strval($value)));
        }
    }

    public function execute($sql) {
        if (!$this->is_valid()) {
            return false;
        }
        $res = @mysql_query($sql, $this->_handle);
        if (is_bool($res)) {
            if (!$res) {
                new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
            }
            $query = $res;
        } else {
            $query = array();
            while ($row = @mysql_fetch_assoc($res)) {
                $query[] = $row;
            }
            if (preg_match('/^select/i', $sql)) {
                $this->_num_rows = mysql_num_rows($res);
            }
            @mysql_free_result($res);
        }
        return $query;
    }

    public function get_error() {
        if ($this->is_valid()) {
            return mysql_error($this->_handle);
        } else {
            return P_Conf_Globalerrno::$message[P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR];
        }
    }

    public function get_errno() {
        if ($this->is_valid()) {
            return mysql_errno($this->_handle);
        } else {
            return P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR;
        }
    }

    private function get_error_message() {
        return sprintf($this->_message, $this->get_errno(), $this->get_error());
    }

    public function last_insert_id() {
        if (false !== ($ret = $this->execute("SELECT LAST_INSERT_ID()")) && is_array($ret)) {
            return reset($ret[0]);
        }
        return 0;
    }

    public function num_rows() {
        return $this->_num_rows;
    }

    public function quote_simple_column_name($name) {
        if (preg_match("/^{$this->_quote_column}.*{$this->_quote_column}$/i", $name)) {
            return $name;
        }
        return $this->_quote_column . $name . $this->_quote_column;
    }

    public function quote_simple_table_name($name) {
        if (preg_match("/^{$this->_quote_table}.*{$this->_quote_table}$/i", $name)) {
            return $name;
        }
        return $this->_quote_table . $name . $this->_quote_table;
    }

    public function quote_value($value) {
        if (is_int($value)) {
            return intval($value);
        }
        if (is_float($value)) {
            return floatval($value);
        }
        return P_Conf_Db::SQL_SINGLE_QUOTE . $this->escape_string(strval($value)) . P_Conf_Db::SQL_SINGLE_QUOTE;
    }

    public function rollback() {
        if (@mysql_query("ROLLBACK", $this->_handle)) {
            return true;
        }
        new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
        return false;
    }

    public function start_transaction() {
        if (@mysql_query("SET AUTOCOMMIT=0", $this->_handle) && @mysql_query("START TRANSACTION", $this->_handle)) {
            return true;
        }
        new P_Exception_Db($this->get_error_message(), P_Conf_Globalerrno::INTERNAL_DATABASE_ERROR);
        return false;
    }

}
