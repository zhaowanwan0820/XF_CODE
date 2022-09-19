<?php
/**
 * 网贷拆分数据库拆分脚本
 *
 * 1. ID范围表预执行:    sudo /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/ncfph_db.php -e product -t ncfwx -f init_id  >> /tmp/ncfph_div.log
 * 2. 数据检查:    sudo /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/ncfph_db.php -e product -t ncfwx -f check  >> /tmp/ncfph_div.log
 * 3. 拆分前置:    sudo /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/ncfph_db.php -e product -t ncfwx -f div_before  >> /tmp/ncfph_div.log
 * 4. 拆分:    sudo /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/ncfph_db.php -e product -t ncfwx -f div  >> /tmp/ncfph_div.log
 * 5. 拆分后置:    sudo /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/ncfph_db.php -e product -t ncfwx -f div_after  >> /tmp/ncfph_div.log
 * 6. 拆分后置:    sudo /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/ncfph_db.php -e product -t ncfph -f div_after  >> /tmp/ncfph_div.log
 *
 * @date 2018-10-01
 * @author liangqiang@ucfgroup.com
 */

require(dirname(__FILE__) . '/../app/init.php');

use libs\utils\Logger;

$params = getopt('e:t:f:');
$env = empty($params['e']) ? '' : $params['e']; //环境 test:测试环境; product:生产环境; producttest:生产测试
$system = empty($params['t']) ? 'ncfph' : $params['t']; // 系统名  网信:ncfwx; 普惠:ncfph
$function = empty($params['f']) ? '' : $params['f']; // 方法名

// 参数检查
if (!in_array($function, array('init_id', 'init_id_pre'))) {
    //exit ('生产环境禁止操作'); //生产环境上线时打开，只允许init_id数据准备; 前期脚本提前上线，执行ID范围初始化前置
}
if (empty($function) || empty($env) || !in_array($env, array('test', 'producttest', 'product'))) {
    exit ('执行参数错误');
}

try {
    echo date('Y-m-d H:i:s') . " {$env}-{$system}-{$function} start" . PHP_EOL;
    $handle = new NcfphDiv($system, $env);
    $handle->$function();
    echo date('Y-m-d H:i:s') . " {$env}-{$system}-{$function} done" . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage();
    NcfphDiv::log(array('NcfphDiv', $system, $function, 'error', $e->getMessage()));
}

class NcfphDiv{

    public $db_ncfwx; //网信库实例
    public $db_ncfph; //普惠库实例

    private $table_ncfph = array(); // 普惠应有的表（不删除）
    private $table_all = array(); // 当前库所有表

    private $is_exe = true; //是否执行SQL，测试用
    public $system = 'ncfph'; //当前执行的系统名 ncfwx/ncfph
    public $env = 'test'; //环境 test:测试环境; product:生产环境; producttest:生产测试

    public function __construct($system, $env) {
        $this->db_ncfwx = $GLOBALS["db"]::getInstance('ncfwx_div');
        $this->db_ncfph = $GLOBALS["db"]::getInstance('ncfph_div');
        $this->db = $system == 'ncfph' ? $this->db_ncfph : $this->db_ncfwx;
        $this->system = $system;
        $this->env = $env;

        // 当前库所有表
        $sql = "show tables";
        $result = $this->db->getAll($sql);
        foreach ($result as $table) {
            $this->table_all[] = current($table);
        }

        //额外处理分表表名， firstp2p_deal_loan_repay_calendar添加到"复制表结构"表名列表，firstp2p_user_log添加到"全量复制"表名列表
        $this->addTable();

        //普惠应有表,不在里面的会删掉
        $this->table_ncfph = array_merge(self::$table_todo, self::$table_div, self::$table_div_sub, self::$table_copy, self::$table_move, self::$table_construct);

        //ID范围表，普惠应有表要保留不能删
        $table_wxid = array_merge(self::$table_div, self::$table_div_sub);
        foreach ($table_wxid as $table ) {
            $this->table_ncfph[] = $table . '_wxid';
        }
        //普惠去除邀请返利相关表
        foreach ($this->table_ncfph as $k => $v) {
            if (in_array($v, array('firstp2p_coupon_log', 'firstp2p_coupon_deal', 'firstp2p_coupon_log_wxid'))) {
                unset($this->table_ncfph[$k]);
            }
        }
    }

    /**
     * 检查拆分结果
     */
    public function check() {
        $this->is_exe = false;
        $this->alter_auto_div();
    }

    /**
     * 拆分之前，在网信主库执行一次
     * alter_coupon只能执行一次
     */
    public function div_before() {
        $this->alter_coupon();
        $this->init_id();
    }

    /**
     * 拆分之后
     * 两边分别执行
     */
    public function div_after() {
        $this->remove();
        $this->cons();
    }

    /**
     * 最后一步，跳ID，在一边系统执行一次就可以
     * rename库名只能执行一次
     */
    public function alter_auto() {
        $this->alter_auto_div();
        $this->alter_auto_copy();
        //$this->alter_account();
        //$this->rename_db_ncfph();
    }

    /**
     * 确认没问题，最后修改数据
     * 两边分别执行
     */
    public function update_final() {
        $this->alter_userstat();
        $this->data_update();
    }

    /**
     * 测试环境用
     */
    public function test() {
        $this->div_before();
        $this->div();
        $this->div_after();
    }

    /**
     * 测试环境用
     */
    public function test_final() {
        $this->update_final();
        $this->alter_account();
    }

    /**
     * 建立网信标的ID表 - 部分标的ID特殊处理，单次执行
     */
    public function init_id_pre() {
        return true; // 生产环境已经执行
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        if ($this->env != 'product') {
            $sql_list[] = "drop table if exists `firstp2p_deal_wxid`";
            $sql_list[] = "drop table if exists `firstp2p_deal_project_wxid`";
            $sql_list[] = "CREATE TABLE if not exists `firstp2p_deal_wxid` (`auto_id` int(11) AUTO_INCREMENT PRIMARY KEY, `table_id` int(11) not null, UNIQUE KEY `unk_table_id` (`table_id`)) COMMENT='firstp2p_deal拆分id表'";
            $sql_list[] = "CREATE TABLE if not exists `firstp2p_deal_project_wxid` (`auto_id` int(11) AUTO_INCREMENT PRIMARY KEY, `table_id` int(11) not null, UNIQUE KEY `unk_table_id` (`table_id`)) COMMENT='firstp2p_deal_project拆分id表'";
        }
        $id_values = "('" . implode("'),('", NcfphDiv::$deal_id_ex) . "')";
        $id_str =  "'" . implode("','", NcfphDiv::$deal_id_ex) . "'";
        $sql_list[] = "insert into firstp2p_deal_wxid (`table_id`) values {$id_values}";
        $sql_list[] = "insert into firstp2p_deal_project_wxid (`table_id`) select distinct project_id from firstp2p_deal where id in {$id_str} and project_id>0";
        $this->execute($sql_list);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'finish'));
    }

    /**
     * 建立网信标的ID表
     * 可重复增量执行
     */
    public function init_id() {
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        foreach (self::$table_div as $table) {
            self::log(array(__CLASS__, $this->system, __FUNCTION__, $table, 'start'));
            if ($this->env != 'product') {
                $sql = "CREATE TABLE if not exists `{$table}_wxid` (`auto_id` int(11) AUTO_INCREMENT PRIMARY KEY, `table_id` int(11) not null, UNIQUE KEY `unk_table_id` (`table_id`)) COMMENT='{$table}拆分id表'";
                $this->execute($sql);
            }
            switch ($table) {
                case 'firstp2p_deal':
                    $where = "deal_type>=2";
                    break;
                case 'firstp2p_deal_project':
                    $where = "deal_type>=2";
                    break;
                case 'firstp2p_user_reservation':
                    $where = "deal_type>0";
                    break;
                case 'firstp2p_reservation_deal_load':
                    $where = "reserve_id in (select table_id from firstp2p_user_reservation_wxid)";
                    break;
                default:
                    $where = "deal_id in (select table_id from firstp2p_deal_wxid)";
            }

            $sql = "insert into {$table}_wxid (`table_id`) (select id from {$table} where %s and {$where} )";
            $this->loop($table, $sql, 'id', __FUNCTION__);
            self::log(array(__CLASS__, $this->system, __FUNCTION__, $table, 'finish'));
        }
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 拆分的表 - 跳id，检查记录数(is_exe=false)
     */
    public function alter_auto_div() {
        $log_info = array(__CLASS__, $this->system, __FUNCTION__);
        $time_start = microtime(true);
        $rs[] = array(
                'table_name',
                'ncfph_auto_inc',
                'ncfwx_auto_inc',
                'ncfph_check_auto',
                'ncfwx_check_auto',
                'ncfph_id_max',
                'ncfwx_id_max',
                'id_max_total',
                'ncfph_id_count',
                'ncfwx_id_count',
                'id_count_total',
                'count_ok',
                'ncfwx_auto_id',
                'ncfph_auto_id',
                'wx_rate_count',
                'wx_rate_id',
                );
        $table_list = array_merge(self::$table_div_sub, self::$table_div);
        foreach ($table_list as $table) {
            $rs_item = array();
            $id_info = array();
            foreach(array('ncfph', 'ncfwx') as $system){
                $table_schema_name = $table;
                // coupon表的普惠表在网信库
                if (in_array($table, array('firstp2p_coupon_deal', 'firstp2p_coupon_log'))) {
                    $db = $this->db_ncfwx;
                    $table_schema_name = $system == 'ncfph' ? $table . '_ncfph' : $table;
                } else {
                    $db = ($system == 'ncfwx') ?  $this->db_ncfwx : $this->db_ncfph; // check
                }
                $db_name = $db->dbname;

                //查询最大id和count
                $sql_id_max = in_array($table, self::$table_div_sub) ? 'count(*)' : 'max(id)';
                $sql_id = "select {$sql_id_max} as id_max, count(*) id_count from {$table_schema_name}";
                $rs_id  = $db->getAll($sql_id);
                $id_max = intval($rs_id[0]['id_max']);
                $id_count = intval($rs_id[0]['id_count']);
                $id_info[$system . '_id_max'] = $id_max;
                $id_info[$system . '_id_count'] = $id_count;

                //查询AUTO_INCREMENT
                $sql_id = "select AUTO_INCREMENT from information_schema.tables where table_schema='{$db_name}' and table_name='{$table_schema_name}'";
                self::log(array_merge($log_info, array($table, 'sql select auto', $sql_id)));
                $rs_id  = $db->getAll($sql_id);
                $auto_inc = isset($rs_id[0]['AUTO_INCREMENT']) ? intval($rs_id[0]['AUTO_INCREMENT']) : 0;
                $id_info[$system . '_auto_inc'] = $auto_inc;
                $id_info[$system . '_check_auto'] = $id_max < $auto_inc ? 'ok' : 'no';
            }


            $sql_id = "select {$sql_id_max} as id_max, count(*) id_count from {$table}_divbak";
            $rs_id  = $this->db_ncfwx->getAll($sql_id);
            $id_max_total = intval($rs_id[0]['id_max']);
            $id_count_total = intval($rs_id[0]['id_count']);
            $id_info['id_max_total'] = $id_max_total;
            $id_info['id_count_total'] = $id_count_total;

            $rs_item = array($table, $id_info['ncfph_auto_inc'], $id_info['ncfwx_auto_inc'], $id_info['ncfph_check_auto'], $id_info['ncfwx_check_auto'], $id_info['ncfph_id_max'], $id_info['ncfwx_id_max'], $id_max_total, $id_info['ncfph_id_count'], $id_info['ncfwx_id_count'], $id_count_total);

            if (empty($id_max_total) || $id_max_total <= 1000) {
                $id_max_total = $id_count_total = 1000;
            }

            $count_ok = $id_info['id_count_total'] == $id_info['ncfwx_id_count'] + $id_info['ncfph_id_count'] ? 'ok' : 'no';
            $rs_item[] = $count_ok;
            if ($count_ok != 'ok') {
                self::log(array_merge($log_info, array($table, 'check error', json_encode($rs_item))));
            }

            $wx_rate = round($id_info['ncfwx_id_count'] / $id_count_total, 2);
            $wx_rate_id = round(($id_max_total - min($id_info['ncfwx_id_max'], $id_info['ncfph_id_max'])) / $id_max_total, 2);

            $wx_rate = $wx_rate > 0.1 ? $wx_rate : 0.1;
            $wx_rate = $wx_rate > 0.5 ? 0.5 : $wx_rate;
            $id_len_real = strlen($id_max_total);
            $id_len = $id_len_real >= 4 ? $id_len_real : 4;
            $id_zoom = pow(10, ($id_len-2));
            $zoom_ncfph  = $id_len >= 7 ? 3 : 5; //百万以上普惠增大20%
            $id_ncfwx = ceil(ceil($id_max_total/$id_zoom) * (1.1)) * $id_zoom;
            $id_ncfph = ceil(ceil($id_max_total/$id_zoom) * (1+$wx_rate*$zoom_ncfph)) * $id_zoom;
            if ($id_ncfph < 10000) {
                $id_ncfph = $id_ncfph * 100;
            } else if ($id_ncfph < 100000) {
                $id_ncfph = $id_ncfph * 10;
            }
            if (in_array($table, self::$table_div_sub)) {
                $id_ncfwx = $id_ncfph = 0;
            }
            $rs_item[] = $id_ncfwx;
            $rs_item[] = $id_ncfph;
            $rs_item[] = $wx_rate;
            $rs_item[] = $wx_rate_id;
            // 无主键id不更新
            if (!in_array($table, self::$table_div_sub)) {
                $sql_ncfwx = "alter table {$table} AUTO_INCREMENT={$id_ncfwx}";
                $sql_ncfph = "alter table {$table} AUTO_INCREMENT={$id_ncfph}";
                $this->execute($sql_ncfwx, true , $this->db_ncfwx);
                if (in_array($table, array('firstp2p_coupon_deal', 'firstp2p_coupon_log'))) {
                    $sql_ncfph = "alter table {$table}_ncfph AUTO_INCREMENT={$id_ncfph}";
                    $this->execute($sql_ncfph, true , $this->db_ncfwx);
                } else {
                    $this->execute($sql_ncfph, true , $this->db_ncfph);
                }
            }
            $rs[] = $rs_item;
        }
        $rs_str = $this->print_format($rs);
        self::log(array_merge($log_info, array($rs_str)));
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array_merge($log_info, array("time:{$time_cost}min", 'finish')));
    }

    /**
     * 跳自增ID - 复制表结构 和 全量复制的表
     * 最小1000起，网信增大10%；普惠，百万以上普惠增大20%， 百万以下普惠增大50%
     */
    public function alter_auto_copy(){
        $log_info = array(__CLASS__, $this->system, __FUNCTION__);
        $time_start = microtime(true);
        $rs[] = array('table', 'id_max', 'id_ncfwx', 'id_ncfph');
        $table_list = array_merge(self::$table_copy, self::$table_construct);
        foreach ($table_list as $table) {
            if (in_array($table, array('firstp2p_agency_user', 'firstp2p_money_snapshot', 'firstp2p_user_third_balance'))) {
                continue; // 无主键id
            }

            $sql_id = "select max(id) as id_max from {$table}";
            $rs_id  = $this->db_ncfwx->getAll($sql_id);
            $id_max = intval($rs_id[0]['id_max']);
            $id_max_total = $id_max > 1000 ? $id_max : 1000;

            // 最小id 1000起，网信增大10%，普惠增大20%-50%
            $wx_rate = 0.1;
            $id_len = strlen($id_max_total);
            $id_len = $id_len >= 4 ? $id_len : 4;
            $id_zoom = pow(10, ($id_len-2));
            $zoom_ncfph  = $id_len >= 7 ? 3 : 5; //百万以上普惠增大20%
            $id_ncfwx = ceil(ceil($id_max_total/$id_zoom) * (1.1)) * $id_zoom;
            $id_ncfph = ceil(ceil($id_max_total/$id_zoom) * (1+$wx_rate*$zoom_ncfph)) * $id_zoom;

            $sql_ncfwx = "alter table {$table} AUTO_INCREMENT={$id_ncfwx}";
            $sql_ncfph = "alter table {$table} AUTO_INCREMENT={$id_ncfph}";
            if ($id_ncfph < 10000) {
                $id_ncfph = $id_ncfph * 100;
            } else if ($id_ncfph < 100000) {
                $id_ncfph = $id_ncfph * 10;
            }
            $this->execute($sql_ncfwx, true , $this->db_ncfwx);
            $this->execute($sql_ncfph, true , $this->db_ncfph);

            $rs[] = array($table, $id_max, $id_ncfwx, $id_ncfph);
        }
        $rs_str = $this->print_format($rs);
        self::log(array_merge($log_info, array($rs_str)));
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array_merge($log_info, array("time:{$time_cost}min", 'finish')));
    }

    /**
     * check当前库的AUTO_INCREMENT，TABLE_ROWS，不可信，只参考
     */
    public function check_auto(){
        $log_info = array(__CLASS__, $this->system, __FUNCTION__);
        $time_start = microtime(true);
        $db_name = $this->db->dbname;
        $rs_max = array();
        $table_list = array_merge(self::$table_div, self::$table_copy, self::$table_construct);
        $table_all = $this->get_table_all();
        foreach ($table_list as $table) {
            if (in_array($table, array('firstp2p_coupon_deal', 'firstp2p_coupon_log', 'firstp2p_agency_user', 'firstp2p_money_snapshot')) || !in_array($table, $table_all)) {
                continue; // 无主键id
            }

            $sql_id = "select count(id) as id_max from {$table}";
            $rs_id  = $this->db->getAll($sql_id);
            $id_max = intval($rs_id[0]['id_max']);
            $rs_max[$table]['ID_MAX'] = $id_max;
        }

        $sql_id = "select TABLE_NAME, AUTO_INCREMENT, TABLE_ROWS from information_schema.tables where table_schema='{$db_name}'";
        $rs_schema = $this->db->getAll($sql_id);
        $rs_auto = array();
        foreach($rs_schema as $item){
            $rs_auto[$item['TABLE_NAME']] = $item;
        }

        $rs[] = array('TABLE_NAME', 'AUTO_INCREMENT', 'TABLE_ROWS', 'ID_MAX', 'CHECK');
        foreach ($rs_max as $table => $data_max) {
            $item = array_merge($rs_auto[$table], $data_max);
            $auto_inc = isset($rs_auto[$table]['AUTO_INCREMENT']) ? intval($rs_auto[$table]['AUTO_INCREMENT']) : 0;
            $item['CHECK'] = $auto_inc > $data_max['ID_MAX'] ? 'ok' : 'no';
            $rs[] = $item;
        }

        $rs_str = $this->print_format($rs);
        self::log(array_merge($log_info, array($rs_str)));
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array_merge($log_info, array("time:{$time_cost}min", 'finish')));
    }

    /**
     * 获取库中所有表名
     */
    private function get_table_all() {
        $sql = "show tables";
        $result = $this->db->getAll($sql);
        $tables = array();
        foreach ($result as $table) {
            $tables[] = current($table);
        }
        return $tables;
    }

    /**
     * 数组按mysql命令行样式打印输出
     */
    private function print_format($data) {
        $len = array(1);
        foreach ($data as $row) {
            foreach ($row as $k => $v) {
                $len[$k] = (isset($len[$k]) && strlen($v)<=$len[$k]) ? $len[$k] : strlen($v);
            }
        }
        $len_total = array_sum($len) + count($len)*3;
        $rs = PHP_EOL . PHP_EOL . str_pad('-', $len_total, '-') . PHP_EOL;
        $row_count = 1;
        foreach ($data as $row) {
            $items = array();
            foreach ($row as $k => $v) {
                $items[] = str_pad(strtolower($v), $len[$k]);
            }
            $rs .= ' | ' . implode(" | ", $items) . ' | ' . PHP_EOL ;
            if ($row_count++ == 1) {
                $rs .= str_pad('-', $len_total, '-') . PHP_EOL;
            }
        }
        $note = (count($data)-1) . ' tables';
        $rs .= str_pad('-', $len_total, '-') . PHP_EOL. PHP_EOL . $note . PHP_EOL;
        return $rs;
    }

    /**
     * 数据拆分
     * 普惠能多次执行，网信只能执行一次
     */
    public function div(){
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        $table_list = array_merge(self::$table_div, self::$table_div_sub, array('firstp2p_coupon_deal_ncfph', 'firstp2p_coupon_log_ncfph'));
        foreach ($table_list as $table) {
            $pk = in_array($table, self::$table_div_sub) ? 'deal_id' : 'id';
            $table_wxid = in_array($table, self::$table_div_sub) ? 'firstp2p_deal_wxid' : "{$table}_wxid";
            $sql_list = array();
            //coupon只有ncfwx有表
            if (in_array($table, array('firstp2p_coupon_deal_ncfph', 'firstp2p_coupon_log_ncfph'))) {
                if ($this->system == 'ncfph') {
                    continue;
                }
                $pk = $table == 'firstp2p_coupon_deal_ncfph' ? 'deal_id' : 'id';
                $table_wxid = $table == 'firstp2p_coupon_deal_ncfph' ? 'firstp2p_deal_wxid' : 'firstp2p_coupon_log_wxid';
                //$sql = "delete from {$table} where {$pk} in (select table_id from {$table_wxid} where %s)";
                $sql = "delete {$table} from {$table} inner join {$table_wxid} on {$table}.{$pk}={$table_wxid}.table_id where %s";
            } else {
                if ($this->system == 'ncfph') {
                    if (in_array($table, array('firstp2p_coupon_deal', 'firstp2p_coupon_log'))) {
                        continue;
                    }
                    //$sql = "delete from {$table} where {$pk} in (select table_id from {$table_wxid} where %s)";
                    $sql = "delete {$table} from {$table} inner join {$table_wxid} on {$table}.{$pk}={$table_wxid}.table_id where %s";
                } else {
                    $sql_list[] = "rename table {$table} to {$table}_divbak";
                    $sql_list[] = "create table {$table} like {$table}_divbak";
                    $this->execute($sql_list);
                    $sql = "insert into {$table} (select * from {$table}_divbak where {$pk} in (select table_id from $table_wxid where %s))";
                }
            }
            $this->loop($table_wxid, $sql, 'auto_id', __FUNCTION__);
        }
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 更改普惠资金账户表结构 -- 只能执行一次
     * 拆分后在普惠执行执行
     */
    public function alter_account(){
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        $sql_list = array();
        if ($this->system == 'ncfph') {
            for ($i = 0; $i <= 63; $i++) {
                if (in_array("firstp2p_user_log_{$i}", $this->table_all)) {
                    $sql_list[] = "rename table `firstp2p_user_log_{$i}` to `firstp2p_account_log_{$i}`";
                }
            }

            if (in_array('firstp2p_user_third_balance', $this->table_all)) {
                $sql_list[] = "ALTER TABLE `firstp2p_user_third_balance` RENAME TO firstp2p_account";
                $sql_list[] = "UPDATE `firstp2p_account` SET `id` = `id`+2000000000";
                $sql_list[] = "UPDATE `firstp2p_account` SET `id` = `user_id`, `supervision_balance` = `supervision_balance` * 100, `supervision_lock_money` = `supervision_lock_money` * 100";
                $sql_list[] = "ALTER TABLE `firstp2p_account`
                    MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '账户ID',
                           CHANGE `supervision_balance` `money` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '帐户资金，单位分',
                           CHANGE `supervision_lock_money` `lock_money` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '冻结资金，单位分',
                           ADD `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态，0未开通，1已开通，2未激活',
                           ADD `open_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开通时间'";
                //测试环境有些地方已经提前更新索引 (测试环境放最后一步，执行报错无所谓)
                if (true || $this->env == 'product' || $this->env == 'producttest') {
                    $sql_list[] = "ALTER TABLE `firstp2p_account` DROP INDEX `user_id`";
                    $sql_list[] = "CREATE UNIQUE INDEX `uni_user_account_type_platform` ON `firstp2p_account` (`user_id`,`account_type`,`platform`)";

                }
            } else {
                self::log(array(__CLASS__, $this->system, __FUNCTION__, 'firstp2p_user_third_balance to account not exists'));
            }
        } else {
            if (in_array('firstp2p_user_third_balance', $this->table_all)) {
                //$sql_list[] = "rename table `firstp2p_user_third_balance` to `firstp2p_user_third_balance_divbak`"; // 刷银行存管状态，后期再改名
            }
        }

        $this->execute($sql_list);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * coupon 表结构调整 - 只能执行一次
     * 拆分前执行
     */
    public function alter_coupon() {
        if ($this->system == 'ncfph') {
            return true;
        }
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);

        $sql_list[] = "ALTER TABLE `firstp2p_coupon_deal`
            ADD COLUMN `deal_status`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '标的状态' AFTER `deal_id`,
                ADD COLUMN `deal_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '标类型' AFTER `is_paid`,
                ADD COLUMN `loantype`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '还款类型' AFTER `deal_type` ,
                ADD COLUMN `repay_time`  int(11) NOT NULL DEFAULT 0 COMMENT '标的期限' AFTER `loantype`";
        $sql_list[] = "UPDATE firstp2p_coupon_deal c, firstp2p_deal d SET c.deal_status = d.deal_status, c.deal_type = d.deal_type, c.loantype = d.loantype, c.repay_time = d.repay_time WHERE c.deal_id = d.id";

        $sql_list[] = "create table firstp2p_coupon_deal_ncfph like firstp2p_coupon_deal";
        $sql_list[] = "create table firstp2p_coupon_log_ncfph like firstp2p_coupon_log";

        // 生产环境已添加
        //$sql_list[] = "ALTER TABLE `firstp2p_money_queue` ADD COLUMN `biz_token` varchar(255) NOT NULL DEFAULT '' COMMENT '业务标识，json格式'";
        $this->execute($sql_list);

        $sql = "insert into firstp2p_coupon_deal_ncfph (select * from firstp2p_coupon_deal where %s)";
        $this->loop('firstp2p_coupon_deal', $sql, 'deal_id', __FUNCTION__);
        $sql = "insert into firstp2p_coupon_log_ncfph (select * from firstp2p_coupon_log where %s)";
        $this->loop('firstp2p_coupon_log', $sql, 'id', __FUNCTION__);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 表数据修改 - 用户资产分割 只能执行一次！
     * 拆分后执行
     */
    public function alter_userstat(){
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        $sql_list = array();
        if ($this->system == 'ncfph') {
            $sql_list[] = "UPDATE firstp2p_user_loan_repay_statistics SET load_repay_money = 0, load_earnings = 0, load_tq_impose = 0, load_yq_impose = 0, norepay_principal = cg_norepay_principal, norepay_interest = cg_norepay_earnings, cg_norepay_principal = 0, cg_norepay_earnings = 0, cg_total_earnings = 0";
        } else {
            $sql_list[] = "UPDATE firstp2p_user_loan_repay_statistics SET norepay_principal = norepay_principal - cg_norepay_principal, norepay_interest = norepay_interest - cg_norepay_earnings, dt_norepay_principal = 0, dt_repay_interest = 0, dt_load_money = 0";
        }
        $this->execute($sql_list);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 表数据修改 - 弟兄们各种定制需求
     */
    public function data_update(){
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        $sql_list[] = "DELETE from firstp2p_deal_queue_info where deal_id not IN (select id from firstp2p_deal)"; // 顺序不能错,在SET enddate=20之前
        if ($this->system == 'ncfph') {
            $conf_keys = implode("','", self::$conf_key_ncfph_remove);
            //删除普惠不需要的conf配置
            $sql_list[] = "update firstp2p_conf set is_effect=0 where name in ('{$conf_keys}')";
            //普惠标的募集期修改
            $sql_list[] = "UPDATE  `firstp2p_deal` SET enddate=20  where deal_status=0 AND  id IN (SELECT deal_id FROM `firstp2p_deal_queue_info`)";
        }
        // 随心约配置, sql太大放文后
        //$sql_sxy_key = 'sql_sxy_' . $this->system . '_' . $this->env;
        //$sql_list[] = $this->$sql_sxy_key;
        $env = ($this->env == 'test') ? 'producttest' : $this->env;
        $sql_list = array_merge($sql_list, $this->sql_sxy_delete[$this->system][$env]);
        $this->execute($sql_list);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 普惠库复制表结构的表，清空数据
     */
    public function cons() {
        if ($this->system == 'ncfwx') {
            return true;
        }
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        $sql_list = array();
        foreach (self::$table_construct as $table) {
            $sql_list[] = "truncate table `{$table}`";
        }
        $this->execute($sql_list);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 删除不应有的表，网信库表重命名备份，普惠删表
     */
    public function remove() {
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        $sql_list = array();
        if ($this->system == 'ncfwx') {
            foreach (self::$table_move as $table) {
                if (in_array($table, $this->table_all)) {
                    //$sql_list[] = "drop table if exists `{$table}_divbak`";
                    $sql_list[] = "rename table {$table} to {$table}_divbak";
                }
            }
        } else {
            foreach ($this->table_all as $table) {
                if (!in_array($table, $this->table_ncfph)) {
                    $sql_list[] = "drop table if exists `{$table}`";
                }
            }
        }
        $this->execute($sql_list);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 普惠库重命名，网信同步过来的库名是firstp2p， 新普惠库名ncfph
     * 只能执行一次，只在普惠执行
     */
    public function rename_db_ncfph() {
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $time_start = microtime(true);
        //$dbname_ncfph_old_list = array('test' => 'ncfph_div1', 'producttest' => 'firstp2p_tmp', 'product' => 'firstp2p');
        //$dbname_now = $dbname_ncfph_old_list[$this->env];
        $dbname_now = $this->db_ncfph->dbname;
        $dbname_new = ($this->env == 'test') ? $dbname_now . '_new' : 'ncfph';
        $sql = "show tables";
        $result = $this->db_ncfph->getAll($sql);
        foreach ($result as $table) {
            $table = current($table);
            if (in_array($table, self::$table_todo) && $table != 'firstp2p_deal_wxid') {
                continue;
            }
            $sql_list[] = "rename table {$dbname_now}.{$table} to {$dbname_new}.{$table}";
        }
        $this->execute($sql_list, true, $this->db_ncfph);
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, "time:{$time_cost}min", 'finish'));
    }

    /**
     * 循环大表执行 - 大表粉碎机
     */
    private function loop($table, $sql, $pk='id', $function_name) {
        $log_info = array(__CLASS__, $this->system, __FUNCTION__, $function_name, $table);
        self::log(array_merge($log_info, array('start')));
        $time_start = microtime(true);

        $sql_id = "select min({$pk}) as id_start, (max({$pk})+1) as id_end from {$table}";
        $rs_id  = $this->db->getAll($sql_id);
        if (empty($rs_id)) {
            throw new \Exception(implode(" | ", $log_info) . 'error');
        }
        $id_start = intval($rs_id[0]['id_start']);
        $id_end = intval($rs_id[0]['id_end']);

        // 用于循环初始化ID表,可重复增量叠加
        if ($function_name == 'init_id') {
            $sql_id = "select (max(table_id)+1) as id_start from {$table}_wxid";
            $rs_id  = $this->db->getAll($sql_id);
            if (empty($rs_id)) {
                throw new \Exception(implode(" | ", $log_info) . 'error');
            }
            $id_start_wxid = intval($rs_id[0]['id_start']);
            if ($id_start_wxid > 0) {
                $id_start = $id_start_wxid;
            }
        }
        $log_info[] = "[$id_start-$id_end]";
        self::log(array_merge($log_info, array(json_encode($rs_id))));

        $page_size = 5000;
        $id_end_page = $id_start;
        $log_limit = 1;

        $sql_run = '';
        while ($id_start < $id_end) {
            $id_start = $id_end_page;
            $id_end_page = $id_start + $page_size;
            $where_id = " {$table}.{$pk}>='{$id_start}' and {$table}.{$pk}<'{$id_end_page}'";
            $sql_run = sprintf($sql, $where_id);
            $this->execute($sql_run, ($log_limit++ < 3));
        }
        self::log(array(__CLASS__, $this->system, 'execute', $sql_run, 'done')); //打印最后一条sql
        $time_cost = number_format((microtime(true) - $time_start)/60, 0);
        self::log(array_merge($log_info, array("loop:" . ($log_limit-1)*$page_size, "time:{$time_cost}min", 'finish')));
    }

    /**
     * 执行sql
     * 统一打日志，控制用
     */
    private function execute($sql, $log_ncfph = true, $db_handle = false) {
        // is_exe=false 不执行 sql，供检查测试用
        if (empty($this->is_exe)) {
            return true;
        }
        if (empty($sql)) {
            self::log(array(__CLASS__, $this->system, __FUNCTION__, 'error', 'empty sql'));
            return false;
        }
        $sql_list = is_array($sql) ? $sql : array($sql);
        foreach ($sql_list as $sql) {
            $time_start = microtime(true);
            $db_handle = empty($db_handle) ? $this->db : $db_handle;
            $db_handle->query($sql);
            $time_cost = microtime(true) - $time_start;
            if ($time_cost > 10) { // 关注批量执行的慢查询
                self::log(array(__CLASS__, $this->system, __FUNCTION__, 'slow sql', "{$time_cost}s", $sql));
            } else {
                self::log(array(__CLASS__, $this->system, __FUNCTION__, $sql), $log_ncfph);
            }
        }
        $sql = null;
        $sql_list = null;
    }

    /**
     * 日志
     */
    public static function log($log_info, $log_ncfph = true) {
        if ($log_ncfph) {
            $log_info = array_merge(array(date('Y-m-d H:i:s')),  $log_info);
            $msg = implode(" | ", $log_info);
            file_put_contents(dirname(__FILE__). "/../log/ncfph_" . date("Ymd") . ".log", $msg . PHP_EOL, FILE_APPEND);
            Logger::info(implode(" | ", $log_info));
        }
        $log_info = null;
    }

    /**
     * 添加分表
     */
    private function addTable() {
        for ($i = 0; $i <= 63; $i++) {
            self::$table_copy[] = 'firstp2p_user_log_' . $i;
        }
        for ($i = 2013; $i <= 2023; $i++) {
            self::$table_construct[] = 'firstp2p_deal_loan_repay_calendar_' . $i;
        }
    }

    /**
     * 慎用！仅用于校正测试环境数据库，删除网信库手工建的account表
     */
    public function fix(){
        return true;
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'start'));
        $sql_list[] = "drop table if exists `firstp2p_account`";
        for ($i = 0; $i <= 63; $i++) {
            $sql_list[] = "drop table if exists `firstp2p_account_log_{$i}`";
        }
        $this->execute($sql_list);
        self::log(array(__CLASS__, $this->system, __FUNCTION__, 'finish'));
    }

    /**
     * 要在两边的库都忽略的表，mark这些无用表
     */
    private static $table_todo = array(
            'firstp2p_deal_wxid',
            // 以下生产环境不存在, 可忽略. 测试环境有，有外键，没研究删除顺序
            'auth_group',
            'auth_group_permissions',
            'auth_permission',
            'auth_user',
            'content_type',
            );

    /**
     * 拆分的表-有主键id
     */
    private static $table_div = array(
            'firstp2p_user_reservation',
            'firstp2p_reservation_deal_load',
            'firstp2p_deal', // 顺序在project之前
            'firstp2p_deal_project',
            'firstp2p_credit_loan',
            'firstp2p_coupon_log',
            'firstp2p_loan_oplog',
            'firstp2p_deal_contract',
            'firstp2p_deal_load',
            'firstp2p_deal_loan_repay',
            'firstp2p_deal_prepay',
            'firstp2p_deal_repay',
            'firstp2p_deal_repay_oplog',
            'firstp2p_deal_site',
            );

    /**
     * 拆分的表-无主键id，通过deal_id处理
     */
    private static $table_div_sub = array(
            'firstp2p_deal_tag',
            'firstp2p_deal_ext',
            'firstp2p_coupon_deal',
            );

    /**
     * 全量
     */
    private static $table_copy= array(
            'firstp2p_api_conf',
            'firstp2p_order_notify',
            'firstp2p_thirdparty_dk',
            'firstp2p_user_loan_repay_statistics',
            'firstp2p_user_third_balance',
            'firstp2p_oto_trigger_rule',
            'firstp2p_service_audit',
            'firstp2p_platform_management',
            'firstp2p_product_management',
            'firstp2p_deal_queue',
            'firstp2p_deal_queue_info',
            'firstp2p_deal_loan_type',
            'firstp2p_deal_cate',
            'firstp2p_agency_user',
            'firstp2p_deal_guarantor',
            'firstp2p_tag',
            'firstp2p_delivery_region',
            'firstp2p_region_conf',
            'firstp2p_withdraw_limit',
            'firstp2p_withdraw_limit_record',
            'firstp2p_admin',
            'firstp2p_article',
            'firstp2p_article_cate',
            'firstp2p_conf',
            'firstp2p_reservation_card',
            'firstp2p_reservation_conf',
            'firstp2p_reservation_match',
            'firstp2p_reservation_money_assign_ratio',
            'firstp2p_role',
            'firstp2p_role_access',
            'firstp2p_role_group',
            'firstp2p_role_module',
            'firstp2p_role_nav',
            'firstp2p_role_node',
            'firstp2p_adv',
            'firstp2p_deal_agency',
            'firstp2p_contract_sign_switch',
            'firstp2p_dictionary',
            'firstp2p_dictionary_value',
            'firstp2p_duotou_entrance',
            'firstp2p_nav',
            'common_idemportent',
            'firstp2p_agency_image',
            );

    private static $table_construct = array(
            'firstp2p_attachment',
            'firstp2p_money_queue',
            'firstp2p_money_snapshot',
            'firstp2p_batch_job',
            'firstp2p_deal_custom_user',
            'firstp2p_deal_loan_repay_calendar_2018',
            'firstp2p_deal_params_conf',
            'firstp2p_idempotent',
            'firstp2p_jobs',
            'firstp2p_log',
            'firstp2p_log_reg_login',
            'firstp2p_supervision_idempotent',
            'oauth_code',
            'oauth_token',
            );

    private static $table_move = array(
            'firstp2p_nongdan',
            'firstp2p_supervision_backend_transfer',
            'firstp2p_supervision_charge',
            'firstp2p_supervision_transfer',
            'firstp2p_supervision_withdraw',
            'firstp2p_partial_repay_detail',
            );

    // 99标的除外
    private static $deal_id_ex = array('66','341','387','392','531','801','803','1989','4467','5435','5437','6780','6781','14528','14529','14534','14539','14547','19075','19076','19078','19079','19080','19389','19577','19718','19731','25894','44160','48144','48465','51824','51825','51826','66081','66082','67671','70852','75303','75348','75363','75374','75393','75405','75427','75428','75429','75479','75499','75500','75511','75512','75534','75549','75560','75571','75587','75588','75598','75599','75611','75612','75613','75629','75630','75631','75632','76211','76212','76213','76214','76217','76227','76233','76243','76945','77336','80210','80211','80212','80213','86668','87329','88590','88591','88592','88593','88752','88753','88754','88755','101549','101550','101551','101552','111715','111717','111718','111721');

    // 随心约配置拆分sql
    private $sql_sxy_delete = array(
            'ncfwx' => array(
                'product' => array(
                    "DELETE FROM `firstp2p_reservation_match` where `id` in (1, 3, 4, 5, 6, 7, 8)",
                    "DELETE FROM `firstp2p_reservation_card` where `deal_type` = 0"
                    ),
                'producttest' => array(
                    "DELETE FROM `firstp2p_reservation_match`",
                    "DELETE FROM `firstp2p_reservation_card` where `deal_type` = 0"
                    ),
                ),
            'ncfph' => array(
                'product' => array(
                    "DELETE FROM `firstp2p_reservation_match` where `id` in (2)",
                    "DELETE FROM `firstp2p_reservation_card` where `deal_type` != 0"
                    ),
                'producttest' => array(
                    "DELETE FROM `firstp2p_reservation_match`",
                    "DELETE FROM `firstp2p_reservation_card` where `deal_type` != 0"
                    ),
                ),
            );

    // sql有双引号，谨慎
    // 网信-生产环境
    private $sql_sxy_ncfwx_product = <<<SQL
UPDATE `firstp2p_reservation_conf` SET `invest_conf` = '[{\"deadline\":6,\"deadline_unit\":2,\"deal_type\":\"2\",\"rate\":\"8.5\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[\"\\u76c8\\u76ca\"],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":12,\"deadline_unit\":2,\"deal_type\":\"2\",\"rate\":\"9.3\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[\"\\u76c8\\u76ca\"],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":3,\"deadline_unit\":2,\"deal_type\":\"2\",\"rate\":\"7.7\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[\"\\u76c8\\u76ca\"],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":9,\"deadline_unit\":2,\"deal_type\":\"2\",\"rate\":\"8.9\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[\"\\u76c8\\u76ca\"],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":45,\"deadline_unit\":1,\"deal_type\":\"2\",\"rate\":\"7.0\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[\"\\u76c8\\u76ca\"],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"}]', `amount_conf` = '[{\"deal_type\":\"2\",\"min_amount\":\"3000000\",\"max_amount\":\"0\"},{\"deal_type\":\"3\",\"min_amount\":\"100000\",\"max_amount\":\"0\"}]' where `type` = 2
SQL;

    // 普惠-生产环境
    private $sql_sxy_ncfph_product = <<<SQL
UPDATE `firstp2p_reservation_conf` SET `invest_conf` = '[{\"deadline\":6,\"deadline_unit\":2,\"deal_type\":\"0\",\"rate\":\"9.0\",\"rate_factor\":\"0.56\",\"firstGradeName\":[],\"secondGradeName\":[],\"thirdGradeName\":[\"\\u529f\\u592b\\u8d37\"],\"visiableGroupIds\":\"\"},{\"deadline\":12,\"deadline_unit\":2,\"deal_type\":\"0\",\"rate\":\"8.9\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[\"\\u4f9b\\u5e94\\u94fe\"],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"}]', `amount_conf` = '[{\"deal_type\":\"0\",\"min_amount\":\"10000\",\"max_amount\":\"0\"}]' where `type` = 2
SQL;

    // 网信 - 生产测试
    private $sql_sxy_ncfwx_producttest = <<<SQL
UPDATE `firstp2p_reservation_conf` SET `invest_conf` = '[{\"deadline\":3,\"deadline_unit\":2,\"deal_type\":\"3\",\"rate\":\"5\",\"rate_factor\":\"1\",\"firstGradeName\":[\"\\u8d44\\u4ea7\\u7ba1\\u7406\"],\"secondGradeName\":[],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":60,\"deadline_unit\":1,\"deal_type\":\"2\",\"rate\":\"8.8\",\"rate_factor\":\"1\",\"firstGradeName\":[\"\\u4ea4\\u6613\\u6240\"],\"secondGradeName\":[],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":6,\"deadline_unit\":2,\"deal_type\":\"3\",\"rate\":\"9.0\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[],\"thirdGradeName\":[\"\\u9996\\u4fe16\\u53f7\\uff082\\uff09\"],\"visiableGroupIds\":\"\"},{\"deadline\":14,\"deadline_unit\":1,\"deal_type\":\"3\",\"rate\":\"7.9\",\"rate_factor\":\"1\",\"firstGradeName\":[\"\\u8d44\\u4ea7\\u7ba1\\u7406\"],\"secondGradeName\":[],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"}]', `amount_conf` = '[{\"deal_type\":\"2\",\"min_amount\":\"100000\",\"max_amount\":\"0\"},{\"deal_type\":\"3\",\"min_amount\":\"100000\",\"max_amount\":\"0\"}]' where `type` = 2
SQL;

    // 普惠-生产测试
    private $sql_sxy_ncfph_producttest = <<<SQL
UPDATE `firstp2p_reservation_conf` SET `invest_conf` = '[{\"deadline\":60,\"deadline_unit\":1,\"deal_type\":\"0\",\"rate\":\"13\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[],\"thirdGradeName\":[\"\\u9996\\u4fe13\\u53f7\\uff082\\uff09\",\"\\u9996\\u4fe16\\u53f7\\uff082\\uff09\"],\"visiableGroupIds\":\"\"},{\"deadline\":21,\"deadline_unit\":1,\"deal_type\":\"0\",\"rate\":\"4.0\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[],\"thirdGradeName\":[\"\\u9996\\u4fe11\\u53f7\"],\"visiableGroupIds\":\"\"},{\"deadline\":6,\"deadline_unit\":2,\"deal_type\":\"0\",\"rate\":\"4.0\",\"rate_factor\":\"1\",\"firstGradeName\":[\"P2P\"],\"secondGradeName\":[],\"thirdGradeName\":[\"\\u623f\\u79df\\u5206\\u671f\"],\"visiableGroupIds\":\"\"},{\"deadline\":3,\"deadline_unit\":2,\"deal_type\":\"0\",\"rate\":\"7\",\"rate_factor\":\"1\",\"firstGradeName\":[\"P2P\"],\"secondGradeName\":[],\"thirdGradeName\":[],\"visiableGroupIds\":\"\"},{\"deadline\":10,\"deadline_unit\":2,\"deal_type\":\"0\",\"rate\":\"13\",\"rate_factor\":\"1\",\"firstGradeName\":[],\"secondGradeName\":[],\"thirdGradeName\":[\"\\u623f\\u79df\\u5206\\u671f\"],\"visiableGroupIds\":\"\"}]', `amount_conf` = '[{\"deal_type\":\"0\",\"min_amount\":\"10000\",\"max_amount\":\"0\"}]' where `type` = 2
SQL;

    private static $conf_key_ncfph_remove = array(
            'GOLD_LOAN_FEE_USER_ID',
            'GOLD_CURRENT_SWITCH',
            'GOLD_INVENTORY',
            'GOLD_SALE_CURRENT_USERID',
            'GOLD_SALE_CURRENT_SWITCH',
            'GOLD_TRADE_TIME',
            'GOLD_EVENT_PAY_USER_ID',
            'GOLD_SWITCH',
            'GOLD_SALE_WHITE_USER',
            'GOLD_SALE_SWITCH',
            'GOLD_WITHDRAW_MONEY',
            'GOLD_MAX_WITHDRAW_PER_DAY',
            'GOLD_WITHDRAW_MIN_FEE',
            'GOLD_PRICE_RATE',
            'GOLD_INTEREST_MONEY',
            'CREDIT_LOAN_BLACKLIST_SWITCH',
            'CREDIT_LOAN_BLACKLIST',
            'CREDIT_LOAN_HOLD_TERM_GE_3',
            'CREDIT_LOAN_SUMMARY',
            'CREDIT_LOAN_SWITCH',
            'CREDIT_LOAN_SERVICE_FEE_UID',
            'CREDIT_LOAN_REMAINNING_DAYS',
            'CREDIT_LOAN_BORROW_RATE',
            'COUPON_ZHANKENG_C',
            'COUPON_ZHANKENG_B',
            'COUPON_PAY_OFFLINE_GROUP_ID',
            'COUPON_GROUP_ID_REFERER_REBATE_GOLD',
            'COUPON_GROUP_ID_REFERER_REBATE_P2P',
            'COUPON_PAYER_ID',
            'COUPON_DUOTOU_REBATE_RATIO_ENABLE',
            'COUPON_JOBS_SIZE_GETUNPAIDLIST',
            'COUPON_JOBS_SIZE_GETAUTOPAYLIST',
            'COUPON_BY_REALNAME_MAX',
            'IS_COUPON_LOG_ASYNCHRONOUS',
            'COUPON_LOCK_USER_ID_START',
            'COUPON_LOCK_DAYS',
            'COUPON_LOCK_DATE_START',
            'COUPON_CONSUME_ENABLE',
            'COUPON_LATEST_EXCLUDE',
            'INVITE_REBATE_MONEY',
            'SPEED_LOAN_MORTGAGE_RATE',
            'SPEED_LOAN_USER_LIMIT_AMOUNT',
            'SPEED_LOAN_SERVICE_HOUR_END',
            'SPEED_LOAN_SERVICE_HOUR_START',
            'SPEED_LOAN_MORTGAGE_TYPE',
            'SPEED_LOAN_DEAL_REPAY_TYPE',
            'SPEED_LOAN_DEAL_TYPE',
            'SPEED_LOAN_PAWN_HOLD_DAYS',
            'SPEED_LOAN_PAWN_REMAIN_DAYS_END',
            'SPEED_LOAN_PAWN_REMAIN_DAYS_START',
            'SPEED_LOAN_PRODUCT_TYPE',
            'SPEED_LOAN_SERVICE_RATE',
            'SPEED_LOAN_MIN_AMOUNT',
            'SPEED_LOAN_MAX_AMOUNT',
            'SPEED_LOAN_OTHER_DEAL_TYPE',
            'SPEED_LOAN_OTHER_DEAL_REPAY_TYPE',
            'SPEED_LOAN_OTHER_PRODUCT_TYPE',
            'SPEED_LOAN_OTHER_DEAL_TAG',
            'SPEED_LOAN_DAILY_RATE',
            'DEAL_ID_FORBIDDEN_REPAY',
            'COUPON_GROUP_ID_REFERER_REBATE_GOLDC',
            'COUPON_REBATE_POLICY_SWITCH',
            'RUNTIME_WITHDRAW_DELAY_MAIL_RECEIVER',
            'RUNTIME_WITHDRAW_DELAY_SECONDS',
            'FINANCE_QUEUE_ONCE_COUNT',
            'BONUS_PUSH_GROUP_CHEDAI_LICAISHI',
            'INTEREST_EXTRA_LOG_EXPROT_MAX_NUM',
            'FINANCE_BLACKLIST_KEYWORDS',
            'BONUS_MARKETING_MONITOR_MOBILES_SUPER',
            'BONUS_MARKETING_ACCOUNT_CONFIG_SUPER',
            'BONUS_MARKETING_ACCOUNT_CONFIG',
            'BONUS_MARKETING_MONITOR_MOBILES',
            'BONUS_TEST_USERID',
            'BONUS_BID_PAY_USER_ID',
            'SPEED_LOAN_SERVICE_FEE_STEP_TERM',
            'PROJECT_YJ_REPAY_TIME_LIMIT',
            'PROJECT_YJ_OFFLINE_REPAY_IDS',
            'PROJECT_YJ_MERCHANT_ID',
            'PAYMENT_USER_TOTAL_MAIL',
            'APP_STEPS_BONUS_CONF',
            'DEAL_REPAY_NEGATIVE',
            'PAYMENT_USE_XFJR',
            'PAYMENT_BIND_ENABLE',
            'PAYMENT_ENABLE',
            'PAYMENT_SERVICE_CHANNEL',
            'WESHARE_ENABLE',
            'TURN_ON_DEPOSIT_INTEREST_DEAL_LOAN',
            'HOUSE_LOAN_SERVICE_SWITCH',
            'HAPPY_NEW_YEAR_2016_DISCOUNT_30',
            'HAPPY_NEW_YEAR_2016_DISCOUNT_20_30',
            'HAPPY_NEW_YEAR_2016_DISCOUNT_10_20',
            'HAPPY_NEW_YEAR_2016_DISCOUNT_0_10',
            'HAPPY_NEW_YEAR_2016_COUPON_100000',
            'CURRENT_COUPON_PREFIX',
            'PAYMENT_AUTO_AUDIT',
            'COUPON_PAYER_DUOTOU_ID',
            'CLOUD_PIC_USER_ID',
            'COUPON_PAY_DISABLE',
            'BONUS_END_TIME',
            'BONUS_START_TIME',
            'AGENCY_ID_JF',
            'AGENCY_ID_JF_REPAY',
            'PAYMENT_SERVICE_LEVEL',
            'PAYMENT_SERVICE_MAINTAINCE_MESSAGE',
            'PAYMENT_TRANSFER_MODE',
            'UCF_PAY_UPGRADE_TIPS',
            'BONUS_UCFPAY_USER_ID',
            'CANDY_BONUS_ON',
            'BONUS_USE_LIMIT',
            'BONUS_BID_MIN_MONEY',
            'TECH_FEE_USER_ID',
            'COUPON_LOG_EXPORT_SIZE_MAX',
            'COUPON_LOG_EXPORT_PAGE_SIZE',
            'COUPON_APP_ACCOUNT_COUPON_MSG',
            'COUPON_APP_ACCOUNT_COUPON_TIPS',
            'TURN_ON_COUPON_AUTO_PAY',
            'TURN_ON_COUPON_BEGINNER',
            'COUPON_LEVEL_STAT_DAYS',
            'WORLDCUP_SCORE_COUPONGROUPID',
            'PROJECT_REPAY_NEGATIVE',
            'COUPON_GROUP_ID_VIP_REBATE_GOLD',
            'VIP_UPDATE_POINT_START',
            'VIP_POINT_DESC_URL',
            'VIP_SERVICE_WHITELIST',
            'COUPON_GROUP_ID_VIP_REBATE_DT',
            'VIP_SERVICE_SWITCH',
            'COUPON_GROUP_ID_VIP_REBATE_P2P',
            'VIP_EXCLUDE_USER_GROUPID',
            'COUPON_GROUP_ID_VIP_REBATE_YJB',
            'INTEREST_DEAL_MAX_NUM',
            'INTEREST_EXTRA_OUT_USER_ID',
            'O2O_WITH_REDEEM',
            'O2O_SELLER_COUPON_LIST_URL',
            'O2O_CUSTOMER_LIST_URL',
            'BONUS_XQL_GROUP_MONEY',
            'BONUS_XQL_SEND_LIMIT_DAYS',
            'BONUS_XQL_COUNT',
            'BONUS_XQL_RANGE_DATE',
            'BONUS_XQL_RANGE_HOURS',
            'BONUS_XQL_BID_MIN_MONEY',
            'BONUS_XQL_GET_LIMIT_DAYS',
            'BONUS_XQL_TIMES',
            'VIP_BIRTHDAY_SMS',
            'O2O_GOLD_DISCOUNT_SWITCH',
            'IS_O2O_OPEN',
            );

}

