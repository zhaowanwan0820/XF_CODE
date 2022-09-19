<?php

/*
 * 初始化AB数据
 */


ini_set('memory_limit', '512M');
set_time_limit(0);


define("TEMP_PATH", dirname(dirname(__FILE__)));
require_once TEMP_PATH . '/app/init.php';
require_once TEMP_PATH . '/libs/utils/Logger.php';
require_once TEMP_PATH . '/libs/common/functions.php';

FP::import("libs.utils.logger");

class ServiceAudit {

    private $serviceType = 0;
    private $pageCount = 1000;
    private $typeTableMatch = array(1 => 'firstp2p_bonus_task');

    public function initOptions() {
        $options = getopt("t:");
        if (!in_array(@$options['t'], array(1))) { // 1 红包审核
            die("usage: php init_service_audit.php -t x\n");
        } else {
            $this->serviceType = $options['t'];
        }
    }

    public function checkExists($data) {
        $query  = "SELECT * FROM firstp2p_service_audit WHERE service_type = {$this->serviceType} AND id = {$data['id']} LIMIT 1";
        $result = $GLOBALS['db']->getALL($query);
        return !empty($result);
    }

    public function doInsertBonusTaskData($data) {
        if ($this->checkExists($data)) {
            logger::error("data exist, data" . json_encode($data));
            return ;
        }

        $time = get_gmtime();
        $insert  = "INSERT INTO firstp2p_service_audit(service_type, service_id, standby_1, standby_2, status, create_time, update_time) VALUES";
        $insert .= " ({$this->serviceType}, {$data['id']}, '{$data['name']}', '{$data['start_time']}', 2, {$time}, {$time})";

        $res = $GLOBALS['db']->query($insert);
        if (!$res) {
            logger::error("init data fail : sql " . $insert);
        }
    }

    public function doAuditInsert($data) {
        switch ($this->serviceType) {
            case 1 :
                $this->doInsertBonusTaskData($data);
                break;
            case 2 :
                break;
        }
    }

    public function doAuditInit() {
        $offset = 0;
        while (true) {
            $query  = "SELECT * FROM " . $this->typeTableMatch[$this->serviceType] . " ORDER BY id ASC LIMIT $offset, {$this->pageCount}";
            $result = $GLOBALS['db']->getALL($query);
            if (empty($result)) {
                break;
            }

            foreach ($result as $item) {
                $this->doAuditInsert($item);
            }
            $offset = $offset + $this->pageCount;
        }
    }

    public function run( ) {
        $this->initOptions();
        $this->doAuditInit();
    }

}

$serviceAudit = new ServiceAudit();
$serviceAudit->run();
