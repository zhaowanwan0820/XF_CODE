<?php
/**
 * BaseModel class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace core\dao;

use libs\db\Model;
use libs\db\MysqlDb;

/**
 * Base Model
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class BaseModel extends Model
{
    /**
     * 初始化db连接以及设置表名前缀
     *
     * @return void
     **/
    public function __construct(){
        parent::__construct();
        if(empty($this->db) || !($this->db instanceof MysqlDb)){
            $this->db = $GLOBALS['db'];
        }
        self::$prefix = DB_PREFIX;
    }
    /**
     * 获取model对应的数据库表名
     * 默认会将类名依照规则进行转换
     * 例如：类名DealRepayModel会转换为deal_repay表
     * @return string the table name
     */
    public function tableName() {
        preg_match_all("/[A-Z]+[a-z]+/", get_class($this), $matches);
        array_pop($matches[0]);
        return self::$prefix.strtolower(implode($matches[0], '_'));
    }

    public static function escape_string($string) {
        return MysqlDb::escape_string($string);
    }


} // END class BaseModel
