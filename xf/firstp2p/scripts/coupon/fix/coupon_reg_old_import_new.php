<?php
/**
 *
 * /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/coupon_reg_old_import_new.php -t del_reg -w 4 -s1 -p 5000 -c 70000000 -d 2
 *
 * coupon_log reg导入到新表
 *
 * @date 2016-02-02
 * @author <zhaoxiaoan@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use core\event\CouponLog\CouponNewRegEvent;
use NCFGroup\Task\Services\TaskService AS GTaskService;

set_time_limit(0);

class importCouponReg
{
    public $pagesize = 1000;

    public $page = 0;

    public $total = 0;

    public $errMsg = '';

    public $sleep = 10;// 单位秒

    public $type = 'reg';

    public function run($argv)
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(" | ", array_merge($log_info, array('script start'))));
        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
        //t为type，u为更新type值，w为where type=的值，p为pagesize，c为统计的总数,s为开始id,d为sleep秒数
        $update_type = getopt('t:u:w:p:c:s:d:');
        $update_type_value = empty($update_type['u']) ? 0 : $update_type['u'];
        $update_type_where = empty($update_type['w']) ? 0 : $update_type['w'];
        $this->type = empty($update_type['t']) ? $this->type : $update_type['t'];
        $this->pagesize = empty($update_type['p']) ? $this->pagesize : $update_type['p'];
        $this->sleep = empty($update_type['d']) ? $this->sleep : $update_type['d'];
        if (!empty($update_type)){
            // 如果这种方式有效，其他接受参数方式必须无效
            $argv = array();
            if ((!empty($update_type_where) && $update_type_where ==2) || (!empty($update_type_value) && $update_type_value==2 )){
                
                Logger::error(implode(" | ", array_merge($log_info, array('script end','type =2',json_encode($update_type)))));

                return false;
            }
        }

        if (!empty($argv) && !empty($argv[1])) {
            $this->type = $argv[1];
        }
        switch ( $this->type){
            case 'pay' : $coupon_db_name = 'coupon_pay_log';
                break;
            case 'user': $coupon_db_name = 'user';
                break;
            case 'del_reg':
            case 'update_type':
            default :  $coupon_db_name = 'coupon_log';
                break;
        }
        $count_sql = 'SELECT MAX(`id`) FROM ' . $db_prefix . $coupon_db_name;
        $list_count = $GLOBALS['db']->get_slave()->getOne($count_sql);
        if (empty($list_count)) {
            Logger::info(implode(" | ", array_merge($log_info, array('data is empty'))));
            return false;
        }
        $start_id = 1;
        $start_id = empty($update_type['s']) ? $start_id : $update_type['s'];
        if (!empty($argv) && is_numeric($argv[2]) && $argv[2] > 0) {
            $start_id = $argv[2];
        }
        $current_log_id = $start_id;
        // 尽量减少漏掉的新用户
        if (!empty($argv) && is_numeric($argv[3]) && $argv[3] > 0) {
            $list_count = $argv[3];
        }
        $list_count = empty($update_type['c']) ? $list_count : $update_type['c'];
        $list_count = $list_count + 4000;

        if (!empty($argv) && is_numeric($argv[4]) && $argv[4] > 0) {
            $this->pagesize = $argv[4];
        }
        if (!empty($argv) && is_numeric($argv[5]) && $argv[5] > 0) {
            $this->sleep = $argv[5];
        }
        $data = array(
        'start' => $start_id,
        'end' => $start_id+$this->pagesize,
        'type'=>$this->type,
        'update_type_value' => $update_type_value,
        'update_type_where' => $update_type_where
    );
        for($i=$start_id;$i<=$list_count;$i+=$this->pagesize) {

            $data['start'] = $i;
            $data['end'] = $i+$this->pagesize;
            $event = new CouponNewRegEvent($data);
            $task_service = new GTaskService();
            $rs = $task_service->doBackground($event);
            Logger::info(implode(" | ", array_merge($log_info, array(' result '.$rs,'pages info'.json_encode($data)))));
            sleep($this->sleep);
        }
        Logger::info(implode(" | ", array_merge($log_info, array('script end'))));
    }
}
$initCouponBindObj = new importCouponReg();
$initCouponBindObj->run($argv);
exit;
