<?php
/**
 * Model class file.
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\db;

use libs\utils\Logger;

/**
 * Db Model class
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class Model implements \ArrayAccess
{

    /**
     * 默认的数据库连接，必须是libs\db\MysqlDb类实例
     * 建议子类在构造函数中完成实例化
     * @var MysqlDb
     **/
    public $db = null;

    /**
     * 默认的数据库表名前缀
     * @var string
     **/
    public static $prefix = "";

    /**
     * 对应数据表的一行数据
     * @var array
     **/
    private $_row = array();

    /**
     * 对于修改update的数据，将数据存放到$_row_new中，update时不再取$_row的数据，避免污染数据
     * @var array
     */
    private $_row_new = array();

    /**
     * 用于记录错误详细信息
     *
     * @var array
     **/
    protected $_errors = array();

    /**
     * 标示对象是新数据库记录还是已存在的记录
     *
     * @var string
     **/
    protected $_isNew = false;

    /**
     * 数据库拆分时使用，默认读写都走主库，0-读写都是主数据表，1-双写读主，2-读写都走新表，3-读主写新表
     */
    public $isSplit = 0;



    private static $_instances = array();

    public function __construct(){
        $this->_isNew = true;
    }

    /**
     * 静态单例, 允许以更简单的方式调用实例查询方法
     *
     * @return void
     **/
    public static function instance($params = array()){
        $class_name = get_called_class();
        $key = implode("_", $params);
        if(!isset(self::$_instances[$class_name][$key])){
            if(empty($params)) {
                self::$_instances[$class_name][$key] = new static();
            } else {
                self::$_instances[$class_name][$key] = new static($params);
            }
        }
        return self::$_instances[$class_name][$key];
    }

    /**
     * 销毁静态资源，保证在cli模式下没有内存泄露
     */
    public static function destroyInstance() {
        self::$_instances = array();
    }

    /**
     * 获取第一条或者指定key的一条错误消息
     *
     * @return mixed
     **/
    public function getError($key = null)
    {
        if($key){
            return $this->_errors[$key];
        }else{
            return $this->_errors[0];
        }
    }

    /**
     * 获取全部错误消息
     *
     * @return void
     **/
    public function getAllErrors()
    {
        return $this->_errors;
    }

    /**
     * 获取对应数据表记录的键值数组
     * @return array
     **/
    public function getRow() {
        return $this->_row;
    }

    /**
     * 设置对应数据表记录的键值数组
     * @return void
     **/
    public function setRow($row) {
        $this->_row = $row;
        $this->_row_new = $row;
    }

    /**
     * 获取model对应的数据库表名
     * 默认会将类名依照规则进行转换
     * 例如：类名DealRepay会转换为deal_repay表
     * @return string the table name
     */
    public function tableName() {
        preg_match_all("/[A-Z]+[a-z]+/", get_class($this), $matches);
        return self::$prefix.strtolower(implode($matches[0], '_'));
    }

    /**
     * 魔术方法，使对象能够以访问属性的方式获取字段的值
     * @param string $name column name
     * @return mixed db row value
     **/
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            return isset($this->_row[$name]) ? $this->_row[$name] : null;
        }
    }

    /**
     * 魔术方法，使对象能够以访问属性的方式设置字段的值
     * @param string $name column name
     * @param string $value column value
     * @return void
     **/
    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            $this->_row[$name] = $value;
            $this->_row_new[$name] = $value;
        }
    }

    /**
     * 根据默认主键id查询数据库, 数据表必须存在名称为"id"的字段
     * @param mixed $id 通常是int类型的字段值，也肯能是字符串
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function find($id, $fields="*", $is_slave = false) {
        $sql = "SELECT %s FROM %s WHERE `id` = '%d'";
        $sql = sprintf($sql, $fields, $this->tableName(), (int)$id);
        return $this->findBySql($sql, array(), $is_slave);
    }

    /**
     * find通过从库
     **/
    public function findViaSlave($id, $fields="*") {
        return $this->find($id, $fields, true);
    }

    /**
     * 指定条件语句执行查询
     * @param string $condition 条件语句
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function findBy($condition,$fields="*", $params = array(), $is_slave = false) {
        if (!empty($params)) {
            $condition = $this->bindParams($condition, $params);
        }
        $table_name = in_array($this->isSplit, array(2)) ? $this->tableName(true, false, $params) : $this->tableName();
        $sql = "SELECT {$fields} FROM ".$table_name." WHERE " . $condition;
        return $this->findBySql($sql, array(), $is_slave);
    }

    /**
     * 从从库获取单条数据
     * @param string $sql
     * @return model 返回实体对象
     */
    public function findByViaSlave($condition, $fields='*', $params = array()) {
        if (!empty($params)) {
            $condition = $this->bindParams($condition, $params);
        }
        $table_name = in_array($this->isSplit, array(2)) ? $this->tableName(true, false, $params) : $this->tableName();
        $sql = "SELECT {$fields} FROM ".$table_name." WHERE " . $condition;

        return $this->findBySql($sql, array(), true);
    }

    /**
     * 从库对应方法
     */
    public function findBySqlViaSlave($sql, $params = array()) {
        return $this->findBySql($sql, $params, true);
    }

    /**
     * 执行sql，获取单条数据
     * @param string $sql 要执行的sql语句
     * @param array $params 参数
     * @param bool $is_slave 是否从从库读取
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function findBySql($sql, $params = array(), $is_slave=false) {
        if (!empty($params)) {
            $sql = $this->bindParams($sql, $params);
        }
        if ($is_slave === false) {
            $res = $this->db->getRow($sql);
        } else {
            $res = $this->db->get_slave()->getRow($sql);
        }

        if ($res) {
            $class_name = get_class($this);
            $model = new $class_name();
            $model->_row = $res;
            $model->_isNew = false;
            return $model;
        } else {
            return null;
        }
    }

    /**
     * 指定条件语句查询多条结果
     * @param string $condition 条件语句
     * @param Boole $is_array 是否返回数组
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function findAll($condition = "",$is_array=false,$fields="*", $params = array()){
        if (!empty($params)) {
            $condition = $this->bindParams($condition, $params);
        }

        $table_name = in_array($this->isSplit, array(2)) ? $this->tableName(true, false, $params) : $this->tableName();
        $sql = "SELECT {$fields} FROM ".$table_name;
        if(!empty($condition)){
            $sql .= " WHERE " . $condition;
        }
        return $this->findAllBySql($sql,$is_array);
    }

    /**
     * 从slave获取指定条件语句查询多条结果
     * @param string $condition 条件语句
     * @param Boole $is_array 是否返回数组
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function findAllViaSlave($condition = "",$is_array=false,$fields="*", $params = array()){
        if (!empty($params)) {
            $condition = $this->bindParams($condition, $params);
        }

        $table_name = in_array($this->isSplit, array(2)) ? $this->tableName(true, false, $params) : $this->tableName();
        $sql = "SELECT {$fields} FROM ".$table_name;
        if(!empty($condition)){
            $sql .= " WHERE " . $condition;
        }
        return $this->findAllBySql($sql,$is_array, array(), true);
    }

    /**
     * 从库对应方法
     */
    public function findAllBySqlViaSlave($sql, $is_array = false, $params = array())
    {
        return $this->findAllBySql($sql, $is_array, $params, true);
    }

    /**
     * 执行sql，获取多条数据
     * @param string $sql 要执行的sql语句
     * @param Boole $is_array 是否返回数组
     * @param bool $is_slave 是否从从库读取
     * @return array 返回回实体对象数组
     **/
    public function findAllBySql($sql,$is_array=false, $params = array(), $is_slave=false)
    {
        if (!empty($params)) {
            $sql = $this->bindParams($sql, $params);
        }
        $class_name = get_class($this);
        if ($is_slave === false) {
            $data = $this->db->getAll($sql);
        } else {
            $data = $this->db->get_slave()->getAll($sql);
        }
        $result = array();
        if ($data) {
            if($is_array){
                return $data;
            }
            foreach ($data as $item) {
                $model = new $class_name();
                //$model->setRow($item);
                $model->_row = $item;
                $model->_isNew = false;
                $result[] = $model;
            }
        }
        return $result;
    }

    /**
     * 根据条件统计行数
     * @param string $condition 条件语句
     * @return integer 统计结果
     **/
    public function count($condition, $params = array())
    {
        if (!empty($params)) {
            $condition = $this->bindParams($condition, $params);
        }
        $table_name = in_array($this->isSplit, array(2)) ? $this->tableName(true, false, $params) : $this->tableName();
        $sql = "SELECT count(*) FROM ".$table_name." WHERE " . $condition;
        return $this->countBySql($sql);
    }

    /**
     * 从slave根据条件统计行数
     * @param string $condition 条件语句
     * @return integer 统计结果
     **/
    public function countViaSlave($condition, $params = array())
    {
        if (!empty($params)) {
            $condition = $this->bindParams($condition, $params);
        }
        $table_name = in_array($this->isSplit, array(2)) ? $this->tableName(true, false, $params) : $this->tableName();
        $sql = "SELECT count(*) FROM ".$table_name." WHERE " . $condition;
        return $this->countBySql($sql, array(), true);
    }

    /**
     * 执行count sql返回统计结果
     * @param string $sql 要执行的sql语句
     * @param bool $is_slave 是否从从库读取
     * @return integer
     **/
    public function countBySql($sql, $params = array(), $is_slave=false)
    {
        if (!empty($params)) {
            $sql = $this->bindParams($sql, $params);
        }
        if ($is_slave === false) {
            return $this->db->getOne($sql);
        } else {
            return $this->db->get_slave()->getOne($sql);
        }
    }

    /**
     * 将数据保存到数据库，如果_isNew是true会执行insert，否则执行update
     *
     * @return boolean
     **/
    public function save($querymode = '')
    {
        $row = $this->_row_new;
        if(count($row) == 0){
            return false;
        }
        $condition = "";
        if($this->_isNew){
            $mode = 'INSERT';
        }else{
            $mode = 'UPDATE';
            //$condition = "id=".$row['id'];
            $condition = "id=". $this->_row['id'];
        }

        if (in_array($this->isSplit, array(0, 1))) {
            $res = $this->db->autoExecute($this->tableName(), $row, $mode, $condition,$querymode);
        }
        if (in_array($this->isSplit, array(1, 2, 3))) {
            $res = $this->db->autoExecute($this->tableName(true), $row, $mode, $condition,$querymode);
        }

        if($this->_isNew){
            $this->id = $this->db->insert_id();
            //$this->_isNew = false; // 注释掉，旧的方式中没有这个逻辑，不宜随意修改---By jiansong
        }
        return $res;
    }

    /**
     * 使用对象包含的row插入一条新的数据库记录, 忽略id字段
     *
     * @return boolean
     **/
    public function insert()
    {
        $row = $this->getRow();
        if(count($row) == 0){
            return false;
        }
        unset($row['id']);

        $r1 = $r2 = true;
        if (in_array($this->isSplit, array(0, 1))) {
            $r1 = $this->db->autoExecute($this->tableName(), $row, "INSERT");
            $this->id = $this->db->insert_id();
        }
        if (in_array($this->isSplit, array(1, 2, 3))) {
            $r2 = $this->db->autoExecute($this->tableName(true), $row, "INSERT");
        }

        return $r1 && $r2;
    }

    /**
     * 直接更新指定的字段(原子操作)
     *
     * @param array $params 要更新的字段的键值数组
     * @return boolean
     **/
    public function update($params)
    {
        $row = $this->_row_new;
        return $this->db->autoExecute($this->tableName(), $params, 'UPDATE', "id=".$this->_row['id']);
    }

    public function updateReturnRow($params, $where, $primaryKey = 'id'){
        $sets = array();
        foreach ($params AS $k => $v) {
            $v = stripslashes($v);
            $sets[] = '`'.$k .'`' . " = '" . addslashes($v) . "'";
        }
        //如果update 参数有主键
        if(isset($field_values[$primaryKey])){
            $primaryValue = $field_values[$primaryKey];
        }else{
            $this->execute("SET @update_id :=0");
            $sets[] = "$primaryKey = (SELECT @update_id := $primaryKey)";
        }
        $sql = 'UPDATE ' . $this->tableName() . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        $this->execute($sql);
        if(!$this->db->affected_rows()){
            return false;
        }

        if(!isset($primaryValue)){
            $updateId = $this->findBySql("SELECT @update_id AS update_id");
            $primaryValue = $updateId['update_id'];

        }
        return $this->findBy("$primaryKey = $primaryValue");

    }

    /**
     * 修改单对象数据
     * @param array $data
     * @return bool
     */
    public function updateOne($data) {
        return $this->db->update($this->tableName(), $data, "`id`='{$this->id}'");
    }

    /**
     * 根据条件进行update
     * @param array $data
     * @param string $condition
     * @return bool
     */
    public function updateBy($data, $condition) {
        return $this->db->update($this->tableName(), $data, $condition);
    }

    /**
     * 批量更新
     * @param unknown $params 更新字段的键值数组
     * @param unknown $where  where 条件
     * @return Ambigous <boolean, resource>
     */
    public function updateAll($params,$where, $is_affected_rows = false)
    {
        if(empty($where))
            return false;
        $result = $this->db->autoExecute($this->tableName(), $params, 'UPDATE', $where);
        if ($is_affected_rows) {
            return $this->db->affected_rows();
        }
        return $result;
    }

    /**
     * 过滤参数
     * @param string $string
     * @return string
     */
    public function escape($string) {
        return addslashes($string);
    }

    /**
     * 数组访问接口，设置键值
     *
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_row[] = $value;
            $this->_row_new[] = $value;
        } else {
            $this->_row[$offset] = $value;
            $this->_row_new[$offset] = $value;
        }
    }

    /**
     * 数组访问操作，检查key是否存在
     *
     * @return boolean
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function offsetExists($offset) {
        return isset($this->_row[$offset]);
    }

    /**
     * 数组访问操作，unset
     *
     * @return void
     **/
    public function offsetUnset($offset) {
        unset($this->_row[$offset]);
        unset($this->_row_new[$offset]);
    }

    /**
     * 数组访问操作，取值
     *
     * @return mixed
     **/
    public function offsetGet($offset) {
        return isset($this->_row[$offset]) ? $this->_row[$offset] : null;
    }

    /**
     * bindParams
     * 绑定参数，对敏感字符进行处理
     *
     * @param mixed $condition
     * @param mixed $params
     * @access public
     * @return void
     */
    public function bindParams($condition, $params) {

        // 查找warning的临时代码
        if ($params === true) {

            $log = $this->debug_string_backtrace();
            Logger::debug('Model.bindParams: '.$log);
        }

        if (!$this->checkParamsKeys($params)) {
            throw new \Exception('绑定参数名字必须以":"开头！');
        }
        krsort($params, SORT_STRING);
        array_walk($params, "self::_escapeParams");
        krsort($params, SORT_STRING);
        return str_replace(array_keys($params), array_values($params), $condition);
    }

    /**
     * 获取方法调用栈
     */
    private function debug_string_backtrace() {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

        // Renumber backtrace items.
        $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

        return $trace;
    }
    /**
     * _escapeParams
     * 支持数组传入，对于IN操作避免外部不好转义
     *
     * @param mixed $item
     * @access protected
     * @return void
     */
    protected function _escapeParams(&$item) {
        if (PHP_VERSION >= '4.3') {
            if (is_array($item)) {
                $item = array_map('addslashes', $item);
                $item = "'".implode("','", $item)."'";
            } else {
                $item = addslashes($item);
            }
        } else {
            if (is_array($item)) {
                $item = array_map('addslashes', $item);
                $item = "'".implode("','", $item)."'";
            } else {
                $item = addslashes($item);
            }
        }
    }

    public function checkParamsKeys($params) {
        // 慎重，太底层了
        if (!is_array($params)) {
            return true;
        }

        foreach ($params as $k => $v) {
            if (strpos($k, ':') !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * 执行sql 语句
     * @param $sql
     * @return bool|resource
     */
    public function execute($sql){
        return $this->db->query($sql);
    }

    /**
     * changlu
     * 更新操作 返回影响行数
     * @param $sql
     * @return int
     */
    public function updateRows($sql){
        if($this->db->query($sql)){
            return $this->db->affected_rows();
        }else{
            return false;
        }
    }

    public function remove() {
        return $this->db->query('delete from '.$this->tableName().' where id = '.$this->_row['id']);
    }

} // END class Model
