<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-20 14:48:19
 * @encode UTF-8编码
 */
class P_Conf_Db {

    const DB_HOST = 0;
    const DB_PORT = 1;
    const DB_USER = 2;
    const DB_PWD = 3;
    const DB_NAME = 4;
    const DB_PREFIX = 5;
    const DB_ENGINE = 6;
    const DB_CHARSET = 7;
    const DB_PCONNECT = 8;
    const DEFAULT_CHARSET = 'utf8';
    const DEFAULT_COLUMNS = '*';
    const DEFAULT_INFFIX = 'Db';
    const DEFAULT_PREFIX = '';
    const ENGINE_MYSQL = 'mysql';
    const ENGINE_MYSQLI = 'mysqli';
    const SQL_ALWAYS_FALSE = '0=1';
    const SQL_AND = 'AND';
    const SQL_AS = 'AS';
    const SQL_BLANK_SPLIT = ' ';
    const SQL_BIND_PARAMS = ":%s";
    const SQL_COLUMNS = 'COLUMNS';
    const SQL_COLUMNS_SPLIT = ', ';
    const SQL_COUNT = 'COUNT';
    const SQL_DELETE = 'DELETE';
    const SQL_DOT_SPLIT = '.';
    const SQL_EMPTY = '';
    const SQL_EQUAL = '=';
    const SQL_FROM = 'FROM';
    const SQL_FROM_SPLIT = ', ';
    const SQL_GROUP = 'GROUP BY';
    const SQL_HAVING = 'HAVING';
    const SQL_IN = 'IN';
    const SQL_INSERT = 'INSERT INTO';
    const SQL_JOIN = 'JOIN';
    const SQL_JOIN_CROSS = 'CROSS JOIN';
    const SQL_JOIN_LEFT = 'LEFT JOIN';
    const SQL_JOIN_NATURAL = 'NATURAL JOIN';
    const SQL_JOIN_RIGHT = 'RIGHT JOIN';
    const SQL_LEFT_BRACKET = '(';
    const SQL_LIKE = 'LIKE';
    const SQL_LIMIT = 'LIMIT';
    const SQL_NOT_IN = 'NOT IN';
    const SQL_NOT_LIKE = 'NOT LIKE';
    const SQL_OFFSET = 'OFFSET';
    const SQL_ON = 'ON';
    const SQL_OR = 'OR';
    const SQL_OR_LIKE = 'OR LIKE';
    const SQL_OR_NOT_LIKE = 'OR NOT LIKE';
    const SQL_ORDER = 'ORDER BY';
    const SQL_PARAMS = 'PARAMS';
    const SQL_PREG_ALIAS = '/^(.*?)(?i:\s+as\s+|\s+)(.*)$/';
    const SQL_PREG_BIND_PARAMS = "/\:%s\b/";
    const SQL_PREG_COLUMN_SPLIT = '/\s*,\s*/';
    const SQL_PREG_OPTION_SPLIT = '/\s* \s*/';
    const SQL_PREG_ORDER = '/^(.*?)\s+(asc|desc)$/i';
    const SQL_PREG_TABLE_PREFIX = '/\{\{(.*?)\}\}/';
    const SQL_PREG_TABLE_PREFIX_INDEX = '$1';
    const SQL_QUERY_SCALAR = 0;
    const SQL_QUERY_ROW = 1;
    const SQL_QUERY_ALL = 2;
    const SQL_RIGHT_BRACKET = ')';
    const SQL_SELECT = 'SELECT';
    const SQL_SELECT_OPTION = 'SELECT_OPTION';
    const SQL_SET = 'SET';
    const SQL_SINGLE_QUOTE = "'";
    const SQL_SPLIT = "\n";
    const SQL_UNION = 'UNION';
    const SQL_UPDATE = 'UPDATE';
    const SQL_VALUES = 'VALUES';
    const SQL_WHERE = 'WHERE';

    public static $join_type = array(
        self::SQL_JOIN,
        self::SQL_JOIN_CROSS,
        self::SQL_JOIN_LEFT,
        self::SQL_JOIN_NATURAL,
        self::SQL_JOIN_RIGHT,
    );

}
