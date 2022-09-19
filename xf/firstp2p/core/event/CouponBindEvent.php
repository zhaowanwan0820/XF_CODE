<?php
/**
 * CouponBindEvent.php
 *
 * @date 2015-07-14
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\event;

use core\event\BaseEvent;
use core\service\CouponBindService;


class CouponBindEvent extends BaseEvent {

    private $user_id;

    public function __construct($user_id) {
        $this->user_id = intval($user_id);
    }

    public function execute() {
        if (empty($this->user_id)) {
            return false;
        }

        $coupon_bind_service = new CouponBindService();
        $coupon_bind_service->init($this->user_id);
        return true;
    }

    public function alertMails() {
        return array('liangqiang@ucfgroup.com', 'zhaoxiaoan@ucfgroup.com', 'wangzhen3@ucfgroup.com');
    }

}