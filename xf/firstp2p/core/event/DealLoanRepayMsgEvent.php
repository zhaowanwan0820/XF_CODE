<?php
namespace core\event;

use core\dao\DealLoanPartRepayModel;
use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\dao\DealLoanRepayModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealLoadModel;
use core\dao\DealRepayModel;

use core\service\DealService;

use libs\utils\Aes;

require_once APP_ROOT_PATH . 'system/libs/msgcenter.php';

class DealLoanRepayMsgEvent extends BaseEvent {
    private $_deal_id;
    private $_repay_id;
    private $_next_repay_id;

    public function __construct($deal_id, $repay_id, $next_repay_id) {
        $this->_deal_id = $deal_id;
        $this->_repay_id = $repay_id;
        $this->_next_repay_id = $next_repay_id;
    }

    public function execute() {
        $deal = DealModel::instance()->find($this->_deal_id);
        $deal['share_url'] = get_deal_domain($deal['id']) . '/d/'. Aes::encryptForDeal($deal['id']); // 向出借人发送站内信和邮件
        // 获取标的期数信息
        $deal['repay_periods_sum'] = DealRepayModel::instance()->getDealRepayPeriodsSumByUserId($deal['id'], $deal['user_id']);
        $deal['repay_periods_order'] = DealRepayModel::instance()->getDealRepayPeriodsOrderByUserId($deal['id'], $deal['user_id']);

        // 获取非预约投标用户的 id 集合
        $loan_user_id_collection = DealLoadModel::instance()->getDealLoanUserIdsExReservation($this->_deal_id);
        $deal_service = new DealService();
        if($deal_service->isDealDT($deal['id'])){//多投不发送站内信
            return true;
        }

        $dlpr_model = new DealLoanPartRepayModel();
        foreach ($loan_user_id_collection as $loan_user_id) {
            if($dlpr_model->isPartRepay($this->_repay_id)){
                if($dlpr_model->isPartNotRepayUser($this->_repay_id,$loan_user_id)){
                    continue;
                }
            }
            $user = UserModel::instance()->find($loan_user_id);
            DealLoanRepayModel::instance()->sendMsg($deal, $user, $this->_repay_id, $this->_next_repay_id);
        }
        return true;
    }

    public function alertMails() {
        return array('fanjingwen@ucfgroup.com');
    }
}
