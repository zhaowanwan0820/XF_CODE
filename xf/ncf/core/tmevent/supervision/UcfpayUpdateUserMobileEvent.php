<?php
/**
 * 超级账户-修改手机号Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\user\UserService;

class UcfpayUpdateUserMobileEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;
    /**
     * 用户手机号
     * @var int
     */
    private $mobile;
    /**
     * 用户手机号编码
     * @var string
     */
    private $mobileCode;

    public function __construct($userId, $mobile, $mobileCode='') {
        $this->userId = intval($userId);
        $this->mobile = addslashes($mobile);
        $this->mobileCode = addslashes($mobileCode);
    }

    /**
     * 超级账户-用户修改银行卡
     */
    public function execute() {
        if (empty($this->userId) || empty($this->mobile)) {
            $this->setError('参数不能为空');
            return false;
        }

        // 更新超级账户手机号
        $ret = UserService::updateUcfpayMobile($this->userId, $this->mobile, $this->mobileCode);
        if (!isset($ret['ret']) || $ret['ret'] != true) {
            $msg = isset($ret['msg']) ? $ret['msg'] : '超级账户手机号更新失败';
            $this->setError($msg);
            return false;
        }
        return true;
    }
}