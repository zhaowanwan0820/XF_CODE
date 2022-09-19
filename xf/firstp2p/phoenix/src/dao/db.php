<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 16:18:29
 * @encode UTF-8编码
 */
abstract class P_Dao_Db extends P_Dao_Abstract {

    private $_last_query = array();
    private $_last_sql = false;
    private $_params = array();
    private $_query = array();
    private $_sql = false;

    public function __construct() {
        parent::__construct(P_Conf_Dao::DAO_DATABASE);
    }

    private function _bind_params($sql) {
        if (is_array($this->_params) && !empty($this->_params)) {
            $keys = array_keys($this->_params);
            $values = array_values($this->_params);
            $sql = preg_replace($keys, $values, $sql);
        }
        return $sql;
    }

    private function _bind_prefix($sql) {
        return preg_replace(P_Conf_Db::SQL_PREG_TABLE_PREFIX, $this->add_prefix(P_Conf_Db::SQL_PREG_TABLE_PREFIX_INDEX), $sql);
    }

    private function _build_obj($request = array()) {
        $columns = isset($this->_query[P_Conf_Db::SQL_COLUMNS]) ? $this->_query[P_Conf_Db::SQL_COLUMNS] : (isset($request[P_Conf_Db::SQL_COLUMNS]) ? $request[P_Conf_Db::SQL_COLUMNS] : P_Conf_Db::DEFAULT_COLUMNS);
        $option = isset($this->_query[P_Conf_Db::SQL_SELECT_OPTION]) ? $this->_query[P_Conf_Db::SQL_SELECT_OPTION] : (isset($request[P_Conf_Db::SQL_SELECT_OPTION]) ? $request[P_Conf_Db::SQL_COLUMNS] : P_Conf_Db::SQL_EMPTY);
        $from = isset($this->_query[P_Conf_Db::SQL_FROM]) ? $this->_query[P_Conf_Db::SQL_FROM] : (isset($request[P_Conf_Db::SQL_FROM]) ? $request[P_Conf_Db::SQL_FROM] : P_Conf_Db::SQL_EMPTY);
        $where = isset($this->_query[P_Conf_Db::SQL_WHERE]) ? $this->_query[P_Conf_Db::SQL_WHERE] : (isset($request[P_Conf_Db::SQL_WHERE]) ? $request[P_Conf_Db::SQL_WHERE] : array());
        $params = isset($this->_query[P_Conf_Db::SQL_PARAMS]) ? $this->_query[P_Conf_Db::SQL_PARAMS] : (isset($request[P_Conf_Db::SQL_PARAMS]) ? $request[P_Conf_Db::SQL_PARAMS] : array());
        $group = isset($this->_query[P_Conf_Db::SQL_GROUP]) ? $this->_query[P_Conf_Db::SQL_GROUP] : (isset($request[P_Conf_Db::SQL_GROUP]) ? $request[P_Conf_Db::SQL_GROUP] : P_Conf_Db::SQL_EMPTY);
        $having = isset($this->_query[P_Conf_Db::SQL_HAVING]) ? $this->_query[P_Conf_Db::SQL_HAVING] : (isset($request[P_Conf_Db::SQL_HAVING]) ? $request[P_Conf_Db::SQL_HAVING] : P_Conf_Db::SQL_EMPTY);
        $union = isset($this->_query[P_Conf_Db::SQL_UNION]) ? $this->_query[P_Conf_Db::SQL_UNION] : (isset($request[P_Conf_Db::SQL_UNION]) ? $request[P_Conf_Db::SQL_UNION] : P_Conf_Db::SQL_EMPTY);
        $order = isset($this->_query[P_Conf_Db::SQL_ORDER]) ? $this->_query[P_Conf_Db::SQL_ORDER] : (isset($request[P_Conf_Db::SQL_ORDER]) ? $request[P_Conf_Db::SQL_ORDER] : P_Conf_Db::SQL_EMPTY);
        $limit = isset($this->_query[P_Conf_Db::SQL_LIMIT]) ? $this->_query[P_Conf_Db::SQL_LIMIT] : (isset($request[P_Conf_Db::SQL_LIMIT]) ? $request[P_Conf_Db::SQL_LIMIT] : false);
        $offset = isset($this->_query[P_Conf_Db::SQL_OFFSET]) ? $this->_query[P_Conf_Db::SQL_OFFSET] : (isset($request[P_Conf_Db::SQL_OFFSET]) ? $request[P_Conf_Db::SQL_OFFSET] : false);
        $obj = $this->select($columns, $option)->from($from)->where($where, $params)->group($group)->having($having)->union($union)->order($order);
        if (false === $limit) {
            return $obj;
        } else {
            return $obj->limit($limit, $offset);
        }
    }

    private function _build_query() {
        if (false !== $this->_sql) {
            $sql = $this->_sql;
        } else {
            $sql = $this->_build_sql(false);
        }
        return $this->_set_sql($sql);
    }

    public function build_sql($request = array()) {
        return $this->_build_obj($request)->_build_sql(true);
    }

    private function _build_sql($need_bind = true) {
        $sql = P_Conf_Db::SQL_SELECT . P_Conf_Db::SQL_BLANK_SPLIT . (!empty($this->_query[P_Conf_Db::SQL_COLUMNS]) ? $this->_query[P_Conf_Db::SQL_COLUMNS] : P_Conf_Db::DEFAULT_COLUMNS);
        if (isset($this->_query[P_Conf_Db::SQL_FROM]) && !empty($this->_query[P_Conf_Db::SQL_FROM])) {
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_FROM . P_Conf_Db::SQL_BLANK_SPLIT . $this->_query[P_Conf_Db::SQL_FROM];
        } else {
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_FROM . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->escape_string($this->table_name());
        }
        if (isset($this->_query[P_Conf_Db::SQL_JOIN]) && !empty($this->_query[P_Conf_Db::SQL_JOIN])) {
            $sql .= P_Conf_Db::SQL_SPLIT . (is_array($this->_query[P_Conf_Db::SQL_JOIN]) ? implode(P_Conf_Db::SQL_SPLIT, $this->_query[P_Conf_Db::SQL_JOIN]) : $this->_query[P_Conf_Db::SQL_JOIN]);
        }
        if (isset($this->_query[P_Conf_Db::SQL_WHERE]) && !empty($this->_query[P_Conf_Db::SQL_WHERE])) {
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_WHERE . P_Conf_Db::SQL_BLANK_SPLIT . $this->_query[P_Conf_Db::SQL_WHERE];
        }
        if (isset($this->_query[P_Conf_Db::SQL_GROUP]) && !empty($this->_query[P_Conf_Db::SQL_GROUP])) {
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_GROUP . P_Conf_Db::SQL_BLANK_SPLIT . $this->_query[P_Conf_Db::SQL_GROUP];
        }
        if (isset($this->_query[P_Conf_Db::SQL_HAVING]) && !empty($this->_query[P_Conf_Db::SQL_HAVING])) {
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_HAVING . P_Conf_Db::SQL_BLANK_SPLIT . $this->_query[P_Conf_Db::SQL_HAVING];
        }
        if (isset($this->_query[P_Conf_Db::SQL_UNION]) && !empty($this->_query[P_Conf_Db::SQL_UNION])) {
            $glue = implode(array(P_Conf_Db::SQL_SPLIT, P_Conf_Db::SQL_RIGHT_BRACKET, P_Conf_Db::SQL_BLANK_SPLIT, P_Conf_Db::SQL_UNION, P_Conf_Db::SQL_BLANK_SPLIT, P_Conf_Db::SQL_LEFT_BRACKET, P_Conf_Db::SQL_SPLIT));
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_UNION . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_LEFT_BRACKET . P_Conf_Db::SQL_SPLIT . (is_array($this->_query[P_Conf_Db::SQL_UNION]) ? implode($glue, $this->_query[P_Conf_Db::SQL_UNION]) : $this->_query[P_Conf_Db::SQL_UNION]) . P_Conf_Db::SQL_RIGHT_BRACKET;
        }
        if (isset($this->_query[P_Conf_Db::SQL_ORDER]) && !empty($this->_query[P_Conf_Db::SQL_ORDER])) {
            $sql .= P_Conf_Db::SQL_SPLIT . P_Conf_Db::SQL_ORDER . P_Conf_Db::SQL_BLANK_SPLIT . $this->_query[P_Conf_Db::SQL_ORDER];
        }
        if (isset($this->_query[P_Conf_Db::SQL_LIMIT]) && ($limit = intval($this->_query[P_Conf_Db::SQL_LIMIT])) >= 0) {
            $sql .= P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_LIMIT . P_Conf_Db::SQL_BLANK_SPLIT . $limit;
        }
        if (isset($this->_query[P_Conf_Db::SQL_OFFSET]) && ($offset = intval($this->_query[P_Conf_Db::SQL_OFFSET])) > 0) {
            $sql .= P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_OFFSET . P_Conf_Db::SQL_BLANK_SPLIT . $offset;
        }
        if ($need_bind) {
            $sql = $this->_bind_params($this->_bind_prefix($sql));
        }
        return $sql;
    }

    public function commit() {
        return $this->_handle->commit();
    }

    public function count($column = P_Conf_Db::DEFAULT_COLUMNS, $conditions = array(), $params = array()) {
        if (is_object($column)) {
            $column[$i] = strval($column);
        } else if (strpos($column, P_Conf_Db::SQL_LEFT_BRACKET) === false) {
            if (preg_match(P_Conf_Db::SQL_PREG_ALIAS, $column, $matches)) {
                $column = P_Conf_Db::SQL_COUNT . P_Conf_Db::SQL_LEFT_BRACKET . $this->_handle->quote_column_name($matches[1]) . P_Conf_Db::SQL_RIGHT_BRACKET . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_AS . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_column_name($matches[2]);
            } else {
                $column = P_Conf_Db::SQL_COUNT . P_Conf_Db::SQL_LEFT_BRACKET . $this->_handle->quote_column_name($column) . P_Conf_Db::SQL_RIGHT_BRACKET;
            }
        }
        $req = array(
            P_Conf_Db::SQL_COLUMNS => $column,
            P_Conf_Db::SQL_WHERE => $conditions,
            P_Conf_Db::SQL_PARAMS => $params,
        );
        return intval($this->get_list($req, P_Conf_Db::SQL_QUERY_SCALAR));
    }

    public function delete($conditions, $params = array(), $table = P_Conf_Db::SQL_EMPTY) {
        if (empty($conditions)) {
            new P_Exception_Dao("条件不可为空", P_Conf_Globalerrno::INTERNAL_DAO_ERROR);
            return false;
        }
        $table = trim(strval($table));
        if (empty($table)) {
            $table = $this->table_name();
        }
        $sql = P_Conf_Db::SQL_DELETE . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_FROM . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_table_name($table);
        $where = $this->_process_conditions($conditions);
        if (empty($where)) {
            new P_Exception_Dao("条件不可为空", P_Conf_Globalerrno::INTERNAL_DAO_ERROR);
            return false;
        }
        $sql .= P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_WHERE . P_Conf_Db::SQL_BLANK_SPLIT . $where;
        $this->_set_params($params);
        $this->_set_sql($sql);
        if (false === $this->query()) {
            return false;
        } else {
            return $this->_handle->affected_rows();
        }
    }

    public function exist($key, $value) {
        $this->get_by_attribute($key, $value);
        return (bool) $this->_handle->num_rows();
    }

    protected function from($tables = P_Conf_Db::SQL_EMPTY) {
        if (empty($tables)) {
            $tables = array($this->_handle->escape_string($this->table_name()));
        }
        if (!is_array($tables)) {
            $tables = preg_split(P_Conf_Db::SQL_PREG_COLUMN_SPLIT, trim($tables), -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($tables as $i => $table) {
            if (strpos($table, P_Conf_Db::SQL_LEFT_BRACKET) === false && strpos($table, P_Conf_Db::SQL_RIGHT_BRACKET) === false && !is_numeric($table) && "''" != $table) {
                if (preg_match(P_Conf_Db::SQL_PREG_ALIAS, $table, $matches)) {
                    $tables[$i] = $this->_handle->quote_table_name($matches[1]) . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_AS . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_table_name($matches[2]);
                } else {
                    $tables[$i] = $this->_handle->quote_table_name($table);
                }
            }
        }
        $this->_query[P_Conf_Db::SQL_FROM] = implode(P_Conf_Db::SQL_FROM_SPLIT, $tables);
        return $this;
    }

    public function get_by_attribute($key, $value, $columns = P_Conf_Db::DEFAULT_COLUMNS, $type = P_Conf_Db::SQL_QUERY_ROW, $op = P_Conf_Db::SQL_AND) {
        if (!is_array($key) && !is_array($value)) {
            $req = array(
                P_Conf_Db::SQL_COLUMNS => $columns,
                P_Conf_Db::SQL_WHERE => "{$key} = :value",
                P_Conf_Db::SQL_PARAMS => array('value' => $value),
            );
        } else if (!is_array($key)) {
            $req = array(
                P_Conf_Db::SQL_COLUMNS => $columns,
                P_Conf_Db::SQL_WHERE => array(P_Conf_Db::SQL_IN, $key, $value),
            );
        } else {
            $conditions = array();
            $params = array();
            foreach ($key as $k => $v) {
                $v = trim(strval($v));
                if (empty($v)) {
                    continue;
                }
                if (isset($value[$k])) {
                    if (!is_array($value[$k])) {
                        $conditions[] = $v . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_EQUAL . P_Conf_Db::SQL_BLANK_SPLIT . sprintf(P_Conf_Db::SQL_BIND_PARAMS, $v);
                        $params[$v] = $value[$k];
                    } else {
                        $conditions[] = array(P_Conf_Db::SQL_IN, $v, $value[$k]);
                    }
                }
            }
            if (!empty($conditions)) {
                array_unshift($conditions, $op);
            }
            $req = array(
                P_Conf_Db::SQL_COLUMNS => $columns,
                P_Conf_Db::SQL_WHERE => $conditions,
                P_Conf_Db::SQL_PARAMS => $params,
            );
        }
        return $this->get_list($req, $type);
    }

    public function get_list($request = array(), $type = P_Conf_Db::SQL_QUERY_ALL) {
        $ret = $this->_build_obj($request)->query();
        if (false === $ret) {
            return $ret;
        }
        if (empty($ret)) {
            $ret = $type != P_Conf_Db::SQL_QUERY_ALL ? array(array()) : array();
        }
        switch ($type) {
            case P_Conf_Db::SQL_QUERY_ROW:
            default:
                return $ret[0];
                break;
            case P_Conf_Db::SQL_QUERY_SCALAR:
                return reset($ret[0]);
                break;
            case P_Conf_Db::SQL_QUERY_ALL:
                return $ret;
                break;
        }
    }

    protected function _get_connect($args) {
        $engine = isset($args[P_Conf_Db::DB_ENGINE]) ? $args[P_Conf_Db::DB_ENGINE] : P_Conf_Db::ENGINE_MYSQL;
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Db::DEFAULT_INFFIX, ucfirst(strtolower($engine))));
        if (!class_exists($class)) {
            new P_Exception_Dao(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::INVALID_DATABASE_ENGINE], P_Conf_Globalerrno::INVALID_DATABASE_ENGINE);
            return false;
        }
        isset($args[P_Conf_Db::DB_CHARSET]) ? null : $args[P_Conf_Db::DB_CHARSET] = P_Conf_Db::DEFAULT_CHARSET;
        $prefix = isset($args[P_Conf_Db::DB_PREFIX]) ? $args[P_Conf_Db::DB_PREFIX] : P_Conf_Db::DEFAULT_PREFIX;
        return array(new $class($args), $prefix);
    }

    protected function get_query() {
        return !empty($this->_query) ? $this->_query : $this->_last_query;
    }

    protected function get_sql() {
        return (false !== $this->_sql) ? $this->_sql : $this->_last_sql;
    }

    protected function group($columns) {
        if (!is_array($columns)) {
            $columns = preg_split(P_Conf_Db::SQL_PREG_COLUMN_SPLIT, trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($columns as $i => $column) {
            if (is_object($column)) {
                $columns[$i] = (string) $column;
            } else if (strpos($column, P_Conf_Db::SQL_LEFT_BRACKET) === false) {
                $columns[$i] = $this->_handle->quote_column_name($column);
            }
        }
        $this->_query[P_Conf_Db::SQL_GROUP] = implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $columns);
        return $this;
    }

    protected function having($conditions) {
        $this->_query[P_Conf_Db::SQL_HAVING] = $this->_process_conditions($conditions);
        return $this;
    }

    public function insert($columns, $table = P_Conf_Db::SQL_EMPTY) {
        $names = array();
        $values = array();
        $params = array();
        $table = trim(strval($table));
        if (empty($table)) {
            $table = $this->table_name();
        }
        foreach ($columns as $name => $value) {
            $names[] = $this->_handle->quote_column_name($name);
            if ($value instanceof P_Db_Expression) {
                $values[] = $value->expression;
                foreach ($value->params as $n => $v) {
                    $params[$n] = $v;
                }
            } else {
                $values[] = sprintf(P_Conf_Db::SQL_BIND_PARAMS, $name);
                $params[$name] = $value;
            }
        }
        $sql = P_Conf_Db::SQL_INSERT . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_table_name($table) . P_Conf_Db::SQL_BLANK_SPLIT
                . P_Conf_Db::SQL_LEFT_BRACKET . implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $names) . P_Conf_Db::SQL_RIGHT_BRACKET . P_Conf_Db::SQL_BLANK_SPLIT
                . P_Conf_Db::SQL_VALUES . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_LEFT_BRACKET . implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $values) . P_Conf_Db::SQL_RIGHT_BRACKET;
        $this->_set_params($params);
        $this->_set_sql($sql);
        if (false === $this->query()) {
            return false;
        } else {
            return $this->_handle->affected_rows();
        }
    }

    protected function join($type, $table, $conditions = P_Conf_Db::SQL_EMPTY) {
        $type = strtoupper($type);
        if (!in_array($type, P_Conf_Db::$join_type)) {
            return P_Conf_Db::SQL_EMPTY;
        }
        if (strpos($table, P_Conf_Db::SQL_LEFT_BRACKET) === false) {
            if (preg_match(P_Conf_Db::SQL_PREG_ALIAS, $table, $matches)) {
                $table = $this->_handle->quote_table_name($matches[1]) . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_table_name($matches[2]);
            } else {
                $table = $this->_handle->quote_table_name($table);
            }
        }
        $conditions = $this->_process_conditions($conditions);
        if (!empty($conditions)) {
            $conditions = P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_ON . P_Conf_Db::SQL_BLANK_SPLIT . $conditions;
        }
        if (isset($this->_query[P_Conf_Db::SQL_JOIN]) && is_string($this->_query[P_Conf_Db::SQL_JOIN])) {
            $this->_query[P_Conf_Db::SQL_JOIN] = array($this->_query[P_Conf_Db::SQL_JOIN]);
        }
        $this->_query[P_Conf_Db::SQL_JOIN][] = $type . P_Conf_Db::SQL_BLANK_SPLIT . $table . $conditions;
        return $this;
    }

    public function last_insert_id() {
        return $this->_handle->last_insert_id();
    }

    protected function limit($limit, $offset = false) {
        $this->_query[P_Conf_Db::SQL_LIMIT] = intval($limit);
        if (false !== $offset) {
            $this->offset($offset);
        }
        return $this;
    }

    protected function offset($offset) {
        $this->_query[P_Conf_Db::SQL_OFFSET] = intval($offset);
        return $this;
    }

    protected function order($columns) {
        if (!is_array($columns)) {
            $columns = preg_split(P_Conf_Db::SQL_PREG_COLUMN_SPLIT, trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($columns as $i => $column) {
            if (is_object($column)) {
                $columns[$i] = strval($column);
            } else if (strpos($column, P_Conf_Db::SQL_LEFT_BRACKET) === false) {
                if (preg_match(P_Conf_Db::SQL_PREG_ORDER, $column, $matches)) {
                    $columns[$i] = $this->_handle->quote_column_name($matches[1]) . P_Conf_Db::SQL_BLANK_SPLIT . strtoupper($matches[2]);
                } else {
                    $columns[$i] = $this->_handle->quote_column_name($column);
                }
            }
        }
        $this->_query[P_Conf_Db::SQL_ORDER] = implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $columns);
        return $this;
    }

    private function _process_conditions($conditions) {
        if (!is_array($conditions)) {
            return $this->_handle->escape_string($conditions);
        } else if (is_array($conditions) && empty($conditions)) {
            return P_Conf_Db::SQL_EMPTY;
        }
        $n = count($conditions);
        $operator = strtoupper($conditions[0]);
        if ($operator === P_Conf_Db::SQL_OR || $operator === P_Conf_Db::SQL_AND) {
            $parts = array();
            for ($i = 1; $i < $n; ++$i) {
                $condition = $this->_process_conditions($conditions[$i]);
                if ($condition !== P_Conf_Db::SQL_EMPTY) {
                    $parts[] = P_Conf_Db::SQL_LEFT_BRACKET . $condition . P_Conf_Db::SQL_RIGHT_BRACKET;
                }
            }
            return $parts === array() ? P_Conf_Db::SQL_EMPTY : implode(P_Conf_Db::SQL_BLANK_SPLIT . $operator . P_Conf_Db::SQL_BLANK_SPLIT, $parts);
        }
        if (!isset($conditions[1], $conditions[2])) {
            return P_Conf_Db::SQL_EMPTY;
        }
        $column = $conditions[1];
        if (strpos($column, P_Conf_Db::SQL_LEFT_BRACKET) === false) {
            $column = $this->_handle->quote_column_name($column);
        }
        $values = $conditions[2];
        if (!is_array($values)) {
            $values = array($values);
        }
        if ($operator === P_Conf_Db::SQL_IN || $operator === P_Conf_Db::SQL_NOT_IN) {
            if ($values === array()) {
                return $operator === P_Conf_Db::SQL_IN ? P_Conf_Db::SQL_ALWAYS_FALSE : P_Conf_Db::SQL_EMPTY;
            }
            foreach ($values as $i => $value) {
                if (is_string($value)) {
                    $values[$i] = $this->_handle->quote_value($value);
                } else {
                    $values[$i] = strval($value);
                }
            }
            return $column . P_Conf_Db::SQL_BLANK_SPLIT . $operator . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_LEFT_BRACKET . implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $values) . P_Conf_Db::SQL_RIGHT_BRACKET;
        }
        if ($operator === P_Conf_Db::SQL_LIKE || $operator === P_Conf_Db::SQL_NOT_LIKE || $operator === P_Conf_Db::SQL_OR_LIKE || $operator === P_Conf_Db::SQL_OR_NOT_LIKE) {
            if ($values === array()) {
                return $operator === P_Conf_Db::SQL_LIKE || $operator === P_Conf_Db::SQL_OR_LIKE ? P_Conf_Db::SQL_ALWAYS_FALSE : P_Conf_Db::SQL_EMPTY;
            }
            if ($operator === P_Conf_Db::SQL_LIKE || $operator === P_Conf_Db::SQL_NOT_LIKE) {
                $andor = P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_AND . P_Conf_Db::SQL_BLANK_SPLIT;
            } else {
                $andor = P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_OR . P_Conf_Db::SQL_BLANK_SPLIT;
                $operator = ($operator === P_Conf_Db::SQL_OR_LIKE) ? P_Conf_Db::SQL_LIKE : P_Conf_Db::SQL_NOT_LIKE;
            }
            $expressions = array();
            foreach ($values as $value) {
                $expressions[] = $column . P_Conf_Db::SQL_BLANK_SPLIT . $operator . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_value($value);
            }
            return implode($andor, $expressions);
        }
    }

    protected function query() {
        $this->_set_data($this->_handle->execute($this->_build_query()));
        $this->_reset();
        return $this->_get_data();
    }

    private function _reset() {
        $this->_last_sql = $this->_sql;
        $this->_last_query = $this->_query;
        $this->_params = array();
        $this->_query = array();
        $this->_sql = false;
    }

    public function rollback() {
        return $this->_handle->rollback();
    }

    protected function select($columns = P_Conf_Db::DEFAULT_COLUMNS, $option = P_Conf_Db::SQL_EMPTY) {
        if (!is_array($columns)) {
            $columns = preg_split(P_Conf_Db::SQL_PREG_COLUMN_SPLIT, trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($columns as $i => $column) {
            if (is_object($column)) {
                $columns[$i] = strval($column);
            } else if (strpos($column, P_Conf_Db::SQL_LEFT_BRACKET) === false) {
                if (preg_match(P_Conf_Db::SQL_PREG_ALIAS, $column, $matches)) {
                    $columns[$i] = $this->_handle->quote_column_name($matches[1]) . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_AS . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_column_name($matches[2]);
                } else {
                    $columns[$i] = $this->_handle->quote_column_name($column);
                }
            }
        }
        $this->_query[P_Conf_Db::SQL_COLUMNS] = implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $columns);
        if (!empty($option) && $this->_handle->check_option($option)) {
            $this->_query[P_Conf_Db::SQL_COLUMNS] = strval($option) . P_Conf_Db::SQL_BLANK_SPLIT . $this->_query[P_Conf_Db::SQL_COLUMNS];
        }
        return $this;
    }

    private function _set_params($params = array()) {
        if (is_array($params)) {
            foreach ($params as $name => $value) {
                $name = trim(strval($name));
                if (!strlen($name)) {
                    continue;
                }
                $this->_params[sprintf(P_Conf_Db::SQL_PREG_BIND_PARAMS, preg_quote($name))] = $this->_handle->quote_value($value);
            }
        }
    }

    private function _set_sql($sql) {
        $this->_last_sql = $this->_sql;
        $this->_sql = $this->_bind_params($this->_bind_prefix($sql));
        if (M::D('DEBUG')) {
            P_Log_Slogs::at($this->_sql);
        }
        return $this->_sql;
    }

    public function start_transaction() {
        return $this->_handle->start_transaction();
    }

    abstract protected function table_name();

    protected function union($sql) {
        $sql = strval($sql);
        if (isset($this->_query[P_Conf_Db::SQL_UNION]) && is_string($this->_query[P_Conf_Db::SQL_UNION])) {
            $this->_query[P_Conf_Db::SQL_UNION] = array($this->_query[P_Conf_Db::SQL_UNION]);
            $this->_query[P_Conf_Db::SQL_UNION][] = $sql;
        } else {
            $this->_query[P_Conf_Db::SQL_UNION] = $sql;
        }
        return $this;
    }

    public function update($columns, $conditions = P_Conf_Db::SQL_EMPTY, $params = array(), $table = P_Conf_Db::SQL_EMPTY) {
        $lines = array();
        $table = trim(strval($table));
        if (empty($table)) {
            $table = $this->table_name();
        }
        foreach ($columns as $name => $value) {
            if ($value instanceof P_Db_Expression) {
                $lines[] = $this->_handle->quote_column_name($name) . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_EQUAL . P_Conf_Db::SQL_BLANK_SPLIT . $value->expression;
                foreach ($value->params as $n => $v) {
                    $params[$n] = $v;
                }
            } else {
                $lines[] = $this->_handle->quote_column_name($name) . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_EQUAL . P_Conf_Db::SQL_BLANK_SPLIT . sprintf(P_Conf_Db::SQL_BIND_PARAMS, $name);
                $params[$name] = $value;
            }
        }
        $sql = P_Conf_Db::SQL_UPDATE . P_Conf_Db::SQL_BLANK_SPLIT . $this->_handle->quote_table_name($table) . P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_SET . P_Conf_Db::SQL_BLANK_SPLIT . implode(P_Conf_Db::SQL_COLUMNS_SPLIT, $lines);
        $where = $this->_process_conditions($conditions);
        if (!empty($where)) {
            $sql .= P_Conf_Db::SQL_BLANK_SPLIT . P_Conf_Db::SQL_WHERE . P_Conf_Db::SQL_BLANK_SPLIT . $where;
        }
        $this->_set_params($params);
        $this->_set_sql($sql);
        if (false === $this->query()) {
            return false;
        } else {
            return $this->_handle->affected_rows();
        }
    }

    protected function where($conditions, $params = array()) {
        $this->_query[P_Conf_Db::SQL_WHERE] = $this->_process_conditions($conditions);
        $this->_set_params($params);
        return $this;
    }

}
