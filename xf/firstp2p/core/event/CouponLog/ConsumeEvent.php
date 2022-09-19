<?php
namespace core\event\CouponLog;

use NCFGroup\Task\Events\AsyncEvent;
use core\event\BaseEvent;
use core\service\CouponService;
use libs\utils\Logger;
/**
 * 邀请记录异步
 *
 * @uses AsyncEvent
 */
class ConsumeEvent extends BaseEvent
{
    private $_data = array();

    public function __construct($data) {
        $this->_data = $data;
    }

    public function execute() {
        extract($this->_data);
        $couponService = new CouponService($module);
        $couponService->consumeSynchronous($deal_load_id, $short_alias, $consume_user_id, $coupon_fields);
        return true;
    }

    public function alertMails(){

        return array('liangqiang@ucfgroup.com','zhaoxiaoan@ucfgroup.com','wangzhen3@ucfgroup.com');
    }

}

