<?php
/**
 * ProxyModel class file.
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

use libs\db\Model;
use libs\db\MysqlDb;

class ProxyModel extends BaseModel
{

    /**
     * 数据库拆分时使用，默认读写都走主库，0-读写都是主数据表，1-双写读主，2-读写都走新表，3-读主写新表
     */
    public $isSplit = 0;

    public function getTable() {
        preg_match_all("/[A-Z]+[a-z]+/", get_class($this), $matches);
        array_pop($matches[0]);

        return strtolower(implode($matches[0], '_'));
    }

    /**
     * 获取model对应的数据库表名
     * 默认会将类名依照规则进行转换
     * 例如：类名DealRepayModel会转换为deal_repay表
     * @return string the table name
     */
    public function tableName($is_split=false, $hash_key=false, $params=array()) {
        $table_name = $this->getTable();
        if ($is_split === true) {
            $i = $this->getDescriptor($hash_key, $table_name, $params);
            if ($i !== false)  {
                return self::$prefix . $table_name . "_" . $i;
            }
        }
        return self::$prefix . $table_name;
    }

    /**
     * 如果此表经过拆分
     * @param string $table_name
     * @return string
     */
    public function getDescriptor($hash_key=false, $table_name=false, $params=array()) {
        $table_name = $table_name !== false ? $table_name : $this->getTable();
        if (isset($GLOBALS['db_hash'][$table_name])) {
            $key = false;
            if ($hash_key) {
                $key = $hash_key;
            } elseif ($this->$GLOBALS['db_hash'][$table_name]['key']) {
                $key = $this->$GLOBALS['db_hash'][$table_name]['key'];
            } else {
                $key = $params[":" . $GLOBALS['db_hash'][$table_name]['key']];
            }
            if ($key !== false) {
                $i = $key % $GLOBALS['db_hash'][$table_name]['cnt'];
                return $i;
            }
        }
        return false;
    }

} // END class ProxyModel
