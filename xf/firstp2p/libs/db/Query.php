<?php
/**
 * Query class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace libs\db;

/**
 * 负责执行数据访问，phoenix的P_Dao_Db本地版本
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Query extends \P_Dao_Db
{
    private $table_name = "";

    /**
     * 初始化时指定表名
     *
     * @return void
     **/
    public function __construct($table_name){
        parent::__construct();
        $this->table_name = $table_name;
    }

    /**
     * 返回符合phoenix格式的表名(不包括prefix的双大括号内的表名)
     *
     * @return string 表名
     **/
    protected function table_name()
    {
        return "{{{$this->table_name}}}";
    }

} // END class Query extends P_Dao_Db
