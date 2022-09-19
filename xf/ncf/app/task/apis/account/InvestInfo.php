<?php
/**
 * 查询用户的投资相关资产
 */
namespace task\apis\account;

use task\lib\ApiAction;
use libs\db\Db;

class InvestInfo extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $res = [
            'repay_money'=>0,
            'repay_count'=>0
        ];
        if (empty($param['userId'])) {
            $this->json_data = $res;
            return;
        }

        $db = Db::getInstance('firstp2p', 'slave');
        $loanUserId = intval($param['userId']);
        $repayTime = time() + 30 * 86400 - 8 * 3600;
        $repayMoneySql = "SELECT sum(`money`) AS repay_money FROM `firstp2p_deal_loan_repay` WHERE `loan_user_id`={$loanUserId} AND `status`=0 AND `time`<={$repayTime}";
        $repayMoneyRes = $db->getRow($repayMoneySql);
        $repayMoney = 0;
        if ($repayMoneyRes && !empty($repayMoneyRes['repay_money'])) {
            $repayMoney = $repayMoneyRes['repay_money'];
        }
        $res['repay_money'] = $repayMoney;

        $repayCount = 0;
        $repayCountSql = "SELECT COUNT(DISTINCT `deal_id`) AS repay_count FROM `firstp2p_deal_loan_repay` WHERE `loan_user_id`={$loanUserId} AND `status`=0 AND `time`<={$repayTime}";
        $repayCountRes = $db->getRow($repayCountSql);
        if ($repayCountRes) {
            $repayCount = $repayCountRes['repay_count'];
        }
        $res['repay_count'] = $repayCount;

        $this->json_data = $res;
    }
}
