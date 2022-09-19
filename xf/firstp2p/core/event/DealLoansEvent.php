<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\service\TaskService;
use libs\event\SubsidyEvent;

// 负责放款时的生成回款计划、投资人扣款、优惠码结算、补贴利率结算
class DealLoansEvent extends BaseEvent {
    private $_deal_id;

    public function __construct($deal_id) {
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        $deal_data = \core\dao\DealModel::instance()->find($this->_deal_id);
  
        $GLOBALS['db']->startTrans();
        try {
            $result = \app\models\dao\Deal::instance()->find($deal_data['id'])->createDealRepayList();
            if ($result === false) {
                throw new \Exception("生成回款与还款计划失败");
            }       

            //放款时，自动结算优惠券及邀请返利
            $coupon = new \core\service\CouponService();
            $coupon_result = $coupon->updateLogStatusByDealId($deal_data['id'], 1);
            if($coupon_result == false){
                throw new \Exception("coupon do updateLogStatusByDealId failure");
            }

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;       
        }
    }

    public function alertMails() {
        return array('wangyiming@ucfgroup.com');
    }
}
