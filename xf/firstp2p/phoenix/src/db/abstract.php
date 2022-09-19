<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-20 15:10:16
 * @encode UTF-8编码
 */
abstract class P_Db_Abstract {

    protected $_handle = NULL;

    public function __construct($args) {
        return $this->connect($args);
    }

    public function __destruct() {
        $this->close();
    }

    abstract public function affected_rows();

    abstract public function check_option($option);

    abstract public function close();

    abstract public function commit();

    abstract protected function connect($args);

    abstract public function escape_string($value);

    abstract public function execute($sql);

    abstract public function get_errno();

    abstract public function get_error();

    public function is_valid() {
        if (!$this->_handle) {
            return false;
        }
        return true;
    }

    abstract public function last_insert_id();

    abstract public function num_rows();

    public function quote_column_name($name) {
        if (($pos = strrpos($name, P_Conf_Db::SQL_DOT_SPLIT)) !== false) {
            $prefix = $this->quote_table_name(substr($name, 0, $pos)) . P_Conf_Db::SQL_DOT_SPLIT;
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }
        return $prefix . ($name === '*' ? $name : $this->quote_simple_column_name($name));
    }

    abstract public function quote_simple_column_name($name);

    abstract public function quote_simple_table_name($name);

    public function quote_table_name($name) {
        if (strpos($name, P_Conf_Db::SQL_DOT_SPLIT) === false) {
            return $this->quote_simple_table_name($name);
        }
        $parts = explode(P_Conf_Db::SQL_DOT_SPLIT, $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quote_simple_table_name($part);
        }
        return implode(P_Conf_Db::SQL_DOT_SPLIT, $parts);
    }

    abstract public function quote_value($value);

    abstract public function rollback();

    abstract public function start_transaction();
}
