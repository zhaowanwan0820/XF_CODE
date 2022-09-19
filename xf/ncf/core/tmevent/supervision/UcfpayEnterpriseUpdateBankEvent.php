<?php
/**
 * 超级账户-企业用户银行卡修改Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class UcfpayEnterpriseUpdateBankEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;
    /**
     * 用户银行卡数组
     * @var array
     */
    private $bankcardInfo;
    /**
     * 用户银行卡数组-旧
     * @var array
     */
    private $bankcardInfoOld;
    /**
     * 用户基本信息数组
     * @var array
     */
    private $userBaseInfo;

    public function __construct($userId, $bankcardInfo, $userBaseInfo, $bankcardInfoOld = array()) {
        $this->userId = $userId;
        $this->bankcardInfo = $bankcardInfo;
        $this->bankcardInfoOld = $bankcardInfoOld;
        $this->userBaseInfo = $userBaseInfo;
    }

    /**
     * 超级账户-企业用户银行卡修改
     */
    public function execute() {
        try{
            $paymentService = new \core\service\PaymentService();
            $result = $paymentService->bankcardSync($this->userId, $this->bankcardInfo, $this->userBaseInfo);
            if (true !== $result) {
                throw new \Exception('企业用户修改银行卡信息失败');
            }
            return true;
        }catch (\Exception $e) {
            $this->setError('超级账户：' . $e->getMessage());
            return false;
        }
    }
}