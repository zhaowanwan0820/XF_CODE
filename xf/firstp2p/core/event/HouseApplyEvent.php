<?php

namespace core\event;

use core\event\BaseEvent;
use core\service\house\HouseService;

class HouseApplyEvent extends BaseEvent {
    private $commitInfo;

    public function __construct(array $commitInfo) {
        $this->commitInfo = $commitInfo;
    }

    public function execute() {
        $houseService = new HouseService();
        $houseService->pushLoanApplyToPartner($this->commitInfo);
        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'sunxuefeng@ucfgroup.com');
    }
}