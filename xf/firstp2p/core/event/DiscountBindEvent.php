<?php

namespace core\event;

use core\event\BaseEvent;
use core\service\marketing\MarketingService;

class DiscountBindEvent extends BaseEvent {

    private $userId;
    private $mobile;

    public function __construct($userId, $mobile) {
        $this->userId = intval($userId);
        $this->mobile = $mobile;
    }

    public function execute() {
        if (empty($this->userId) || empty($this->mobile)) {
            return false;
        }

        $marketingService = new MarketingService();
        $result = $marketingService->discountBind($this->userId, $this->mobile);
        if ($result == false) {
            return false;
        }

        return true;
    }

    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }

}
