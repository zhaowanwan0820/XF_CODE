<?php

/**
 * 批量更新
 * 
 * 以往有大表的字段修改等，不能通过sql直接执行的，都需要手写代码分批更新，方法五花八门，正确性也是问题，工作重复。可抽象基础工具类，提高效率：
 * 
 * 例1：update test01 set note=0 where id>='100' and id<'1000000' and deal_type>=2
 * $system = test01 所在的数据库（database01）（必填参数）
 * $table = test01;（必填参数）
 * $sql = update test01 set note=0 where %s and deal_type>=2 或者 update test01 set note=0 where deal_type>=2 and %s; （必填参数）
 * $sql中的%s：替换操作批量表(test01)的范围条件，这里替换id>='100' and id<'1000000'。范围由$id_start和$id_end指定，当$id_start小于等于0时默认id的最小值，$id_end小于等于0时默认id的最大值
 * $sleep_time = 1000, 执行一次批量sql的延迟时间,单位毫秒,（可选参数，默认1000）
 * $id_start = 100, test01的开始主键值（可选参数，当$id_start小于等于0时默认id的最小值).
 * $id_end = 1000000, test01的结束主键值（可选参数,当$id_end小于等于0时默认id的最大值）
 * $pk test01的主键名称,（可选参数，默认为id）
 * $page_size 每次执行test01的id增量（可选参数，默认5000）
 * 
 * 例子2：update test01 set note=0 where id in (select id2 from test02 where id2>='5000' and id2<'10000' and deal_type>=2)
 * $system test01 所在的数据库(database01)（必填参数）
 * $table = test02;（必填参数）
 * $sql = update test01 set note=0 where id in (select id from test02 where %s and deal_type>=2)（必填参数）
 * $sql中的%s：替换操作批量表（test02）的范围条件，这里替换id2>='5000' and id2<'10000'。范围由$id_start和$id_end指定，当$id_start小于等于0时默认id的最小值，$id_end小于等于0时默认id的最大值
 * $sleep_time = 1000, 执行一次sql的延迟时间,单位毫秒,（可选参数，默认1000）
 * $id_start=5000 test02的开始主键值（可选参数，当id_start小于等于0时默认id的最小值）
 * $id_end=10000 test02的结束主键值（可选参数,当id_end小于等于0时默认id的最大值）
 * $pk="id2" test02的主键名称,（可选参数，默认为id）
 * $page_size 每次执行test02的id增量（可选参数，默认5000）
 * 
 * @date 2019-3-07
 * @author majunliang@ucfgroup.com
 */

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\CommonLogger as Logger;

class UpdateBatch
{

    private $is_exe = true;     //是否执行SQL，测试用
    private $system;            //当前执行的系统名
    private $sql;               //执行的sql模板
    private $table;             //操作批量表的名称
    private $pk = 'id';         //操作批量表的的主键名称
    private $page_size = 5000;  //每次执行操作批量表的主键增量
    private $id_start = 0;      //操作批量表的开始主键索引值
    private $id_end = 0;        //操作批量表的结束主键索引值
    private $sleep_time = 1000; //执行一次批量sql的延迟时间,单位毫秒

    public function __construct($system, $table, $sql, $sleep_time = 1000, $id_start = 0, $id_end = 0, $pk = 'id', $page_size = 5000)
    {
        $this->system = $system;
        $this->db = $GLOBALS["db"]::getInstance($system);
        $this->sql = $sql;
        $this->table = $table;
        $this->pk = $pk;
        $this->page_size = $page_size > 0 ? $page_size : 5000;
        // 执行一次批量sql的延迟时间，单位毫秒
        $this->sleep_time = $sleep_time;
        //是否设置$id_end
        $this->isRange = $id_end <= 0 ? false : true;

        //选出当前表pk的最大值和最小值，用于初始化
        $sql_id = "select min({$this->pk}) as id_min, (max({$this->pk})+1) as id_max from {$this->table}";
        $rs_id = $this->db->getAll($sql_id);
        if (empty($rs_id)) {
            $log_info = array(__CLASS__, $this->system, __FUNCTION__, $this->table);
            throw new \Exception(implode(" | ", $log_info) . 'error');
        }
        $id_min = intval($rs_id[0]['id_min']);
        $id_max = intval($rs_id[0]['id_max']);

        $this->id_start = $id_start <= 0 ? $id_min : $id_start;
        $this->id_end = $id_end <= 0 ? $id_max : $id_end;
    }

    /**
     * 测试执行，打印要运行的sql
     */
    public function check()
    {
        $this->is_exe = false;
        $this->run();
        $this->is_exe = true;
    }

    /**
     * 表数据修改执行
     */
    public function run()
    {
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);

        //初始化循环数据
        $id_start = $this->id_start;
        $id_end_page = ($id_start + $this->page_size >= $this->id_end) ? $this->id_end : ($id_start + $this->page_size);
        $log_limit = 1;
        
        while ($id_start < $this->id_end) {
            $where_id = "{$this->table}.{$this->pk}>='{$id_start}' and {$this->table}.{$this->pk}<'{$id_end_page}'";
            $sql_run = sprintf($this->sql, $where_id);
            $this->execute($sql_run, ($log_limit++ <= 3));
            $id_start = $id_end_page;
            //边界条件:如果设置了id最大范围，则边界值为设置的id，如果没有设置，边界值可以超出表最大id。
            $id_end_page = (($id_start + $this->page_size >= $this->id_end) && $this->isRange) ? $this->id_end : ($id_start + $this->page_size);

            //如果没有设id范围，在用户更新数据的过程中，可能会增加数据，在更新结束后，需要实时检测表最大id，保证数据全部更新。
            if ($id_start >= $this->id_end) {
                if ($this->isRange) {
                    break;
                }

                //获取当前时刻最大id ，如果$id_start<=$id_max,则结束，否则id_start回归到$this->id_end，保证新增的数据全部更新
                $sql_id = "select (max({$this->pk})+1) as id_max from {$this->table}";
                $rs_id = $this->db->getAll($sql_id);
                if (empty($rs_id)) {
                    $log_info = array(__CLASS__, $this->system, __FUNCTION__, $this->table);
                    throw new \Exception(implode(" | ", $log_info) . 'error');
                }
                $id_max = intval($rs_id[0]['id_max']);
                if ($id_start >= $id_max) {
                    break;
                }
                
                $id_start = $this->id_end;
                $this->id_end = $id_max;
                $id_end_page = $id_start + $this->page_size;
            }
            // 执行一次批量sql的延迟时间
            usleep($this->sleep_time * 1000);
        }

        $time_cost = number_format((microtime(true) - $time_start) / 60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 执行sql
     * 统一打日志，控制用
     */
    private function execute($sql, $is_print = true)
    {
        // is_exe=false 不执行 sql，供检查测试用
        if (empty($this->is_exe)) {
            self::log(array(__CLASS__, $this->system, __FUNCTION__, $sql), $is_print);
            return true;
        }
        if (empty($sql)) {
            self::log(array(__CLASS__, $this->system, __FUNCTION__, 'error', 'empty sql'));
            return false;
        }
        $sql_list = is_array($sql) ? $sql : array($sql);
        foreach ($sql_list as $sql) {
            $time_start = microtime(true);
            $this->db->query($sql);
            $time_cost = round(microtime(true) - $time_start,1);
            if ($time_cost > 10) { // 关注批量执行的慢查询
                self::log(array(__CLASS__, $this->system, __FUNCTION__, $sql, 'slow sql', "{$time_cost}s"));
            } else {
                self::log(array(__CLASS__, $this->system, __FUNCTION__, $sql), $is_print);
            }
        }
        $sql = null;
        $sql_list = null;
    }

    /**
     * 日志
     */
    public static function log($log_info, $is_print = true)
    {
        if ($is_print) {
            Logger::info(implode(" | ", $log_info));
        }
        $log_info = null;
    }

}
