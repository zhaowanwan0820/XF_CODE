<?php
/**
 * BaseModel class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace app\models\dao;

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
} // END class BaseModel
