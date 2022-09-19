<?php

/**
 * BidProfileEvent.php
 * 
 * Filename: BidProfileEvent.php
 * Descrition: 
 * Author: yutao@ucfgroup.com
 * Date: 16-6-16 下午3:41
 */

namespace core\event\UserProfile;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\UserProfileService;

class BidProfileEvent extends BaseEvent {

    private $_userId;
    private $_dealId;
    private $_money;

    public function __construct($userId, $dealId, $money) {
        $this->_userId = $userId;
        $this->_dealId = $dealId;
        $this->_money = $money;
    }

    public function execute() {
        if (empty($this->_userId) || empty($this->_dealId) || empty($this->_money)) {
            throw new \Exception("投资用户分析执行失败,参数传递错误:userId:{$this->_userId},dealId:{$this->_dealId},money:{$this->_money}");
        }
        $userProfileService = new UserProfileService();
        $ret = $userProfileService->updateInvest($this->_userId, $this->_dealId, $this->_money);
        if (!$ret) {
            throw new \Exception("投资用户分析执行失败,updateInvest结果:{$ret},参数:userId:{$this->_userId},dealId:{$this->_dealId},money:{$this->_money}");
        }
        $userProfileService->userProfileLog(array('userId' => $this->_userId, 'dealId' => $this->_dealId, 'money' => $this->_money), __FUNCTION__, __CLASS__, 'event success');
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
