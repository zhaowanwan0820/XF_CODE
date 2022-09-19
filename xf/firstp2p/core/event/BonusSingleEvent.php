<?php
/**
 *-------------------------------------------------------
 * 按照用户组信息以及标签信息批量给用户发送红包
 *-------------------------------------------------------
 * 2014-12-29 17:05:35
 *-------------------------------------------------------
 */

namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\service\BonusService;
use libs\utils\Logger;
use core\event\BaseEvent;

/**
 * BonusBatchEvent
 * 分进程发送红包
 *
 * @uses AsyncEvent
 * @package default
 */
class BonusSingleEvent extends BaseEvent
{

    private $ids = array();
    private $money = 10.00;
    private $count = 5;
    private $send_limit_days = 1;
    private $batch_id = 0;
    private $group_count = 1;

    public function __construct($money = 10, $count = 5, $send_limit_days = 1, $group_count = 1, $batch_id = 0) {
        $this->money = $money;
        $this->count = $count;
        $this->send_limit_days = $send_limit_days;
        $this->group_count = $group_count;
        $this->batch_id = $batch_id;
    }

    public function setSendUserIds($ids) {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $this->ids = $ids;
    }

    /**
     * 执行发送红包
     */
    public function execute() {

        $bonus_service = new BonusService($batch_id);//绑定batch_id
        foreach ($this->ids as $id){
            $real_send_count = 0;
            for($i = 0; $i < $this->group_count; $i++) {
                $res = $bonus_service->generation($id, 0, 0, 0.25, 0, 1, $this->money, $this->count, $this->send_limit_days);
            }
            if ($res) {
                $real_send_count++;
            }
            Logger::wLog(sprintf("id:%s | success:%s", $id, $real_send_count), Logger::INFO, Logger::FILE, $log_file);
        }
        return true;
    }
    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }
}
