<?php

namespace core\event\UserProfile;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\UserProfileService;

class CouponLogAddProfileEvent extends BaseEvent {

    private $_userId;
    private $_money;

    public function __construct($userId, $money, $isTzd) {
        $this->_userId = $userId;
        $this->_money = $money;
        $this->_isTzd = $isTzd;
    }

    public function execute() {
        $userProfileService = new UserProfileService();
        if (empty($this->_userId)) {
            throw new \Exception("邀请码返利用户分析执行失败,参数传递错误:userId:{$this->_userId},money:{$this->_money},isTzd:{$this->_isTzd}");
        }
        //由于通知贷返利问题 去掉money判断
        //if (empty($this->_money)) {
        //    $userProfileService->userProfileLog(array('userId' => $this->_userId, 'money' => $this->_money, 'isTzd' => $this->_isTzd), __FUNCTION__, __CLASS__, 'event not execute');
        //    return true;
        //}

        $ret = $userProfileService->updateCouponLog($this->_userId, $this->_money, $this->_isTzd);
        if (!$ret) {
            throw new \Exception("邀请码返利用户分析执行失败,updateCouponLog:{$ret},参数:userId:{$this->_userId},money:{$this->_money}");
        }
        $userProfileService->userProfileLog(array('userId' => $this->_userId, 'money' => $this->_money, 'ret' => $ret), __FUNCTION__, __CLASS__);
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
