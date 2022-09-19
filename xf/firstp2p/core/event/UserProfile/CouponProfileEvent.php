<?php

/**
 * CouponProfileEvent.php
 * 
 * Filename: CouponProfileEvent.php
 * Descrition: 
 * Author: yutao@ucfgroup.com
 * Date: 16-6-21 下午2:14
 */

namespace core\event\UserProfile;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\UserProfileService;

class CouponProfileEvent extends BaseEvent {

    private $_userId;

    public function __construct($userId) {
        $this->_userId = $userId;
    }

    public function execute() {
        if (empty($this->_userId)) {
            throw new \Exception("改码用户分析执行失败,参数传递错误:userId:{$this->_userId}");
        }
        $userProfileService = new UserProfileService();
        $ret = $userProfileService->changeCoupon($this->_userId);
        if (!$ret) {
            throw new \Exception("改码用户分析执行失败,changeCoupon结果:{$ret},参数:userId:{$this->_userId}");
        }
        $userProfileService->userProfileLog(array('userId' => $this->_userId), __FUNCTION__, __CLASS__);
        return true;
    }

    public function alertMails() {
        return array('yutao@ucfgroup.com', 'wangfei5@ucfgroup.com');
    }

    public function after() {
        \libs\db\Db::destroyInstance('firstp2p', 'vipslave');
        parent::after();
    }

}
