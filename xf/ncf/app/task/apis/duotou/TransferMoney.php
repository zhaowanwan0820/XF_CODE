<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtTransferService;
use core\service\user\UserService;

class TransferMoney extends ApiAction
{
    const DT_TRANSFER_BID = 1;
    const DT_TRANSFER_INTEREST = 2;
    const DT_TRANSFER_REDEEM = 3;
    const DT_TRANSFER_REDEEM_CLEAN = 4;
    const DT_TRANSFER_P2P_REPAY = 7;
    const DT_TRANSFER_REVOKE = 8;

    public function invoke()
    {
        $param = $this->getParam();
        $type = $param['type'];
        $token = $param['orderId'];
        $fee = $param['fee'];
        $manageId = $param['manageId'];
        $dealId = $param['p2pDealId'];
        $dealName = $param['dealName'];
        $minLoanMoney = $param['minLoanMoney'];
        $holdDays = $param['holdDays'];
        $userId = $param['userId'];
        $money = $param['money'];
        $loanId = $param['loanId'];
        $ds = new DtTransferService();
        $user = UserService::getUserById($userId);
        if (!$user) {
            return false;
        }

        $validTypes = array(
            self::DT_TRANSFER_BID,
            self::DT_TRANSFER_INTEREST,
            self::DT_TRANSFER_REDEEM,
            self::DT_TRANSFER_REDEEM_CLEAN,
            self::DT_TRANSFER_P2P_REPAY,
            self::DT_TRANSFER_REVOKE,
        );
        if (!in_array($type, $validTypes)) {
            return false;
        }

        switch ($type) {
            //无效方法
            case self::DT_TRANSFER_BID:
                $res = $ds->transferBidDT($user, $money, $dealId, $token, $dealName);
                break;
            //贴息发红包
            case self::DT_TRANSFER_INTEREST:
                $res = $ds->transferInterestDT($user, $money, $fee, $manageId, $token, $dealId, $dealName, $minLoanMoney);
                break;
            case self::DT_TRANSFER_REDEEM:
                $res = $ds->transferRedeemDT($user, $money, $token, $fee, $manageId, $dealId, $dealName, $minLoanMoney, $holdDays, $loanId);
                break;
            case self::DT_TRANSFER_REDEEM_CLEAN:
                $res = $ds->transferRedeemDT($user, $money, $token, $fee, $manageId, $dealId, $dealName, $minLoanMoney, $holdDays, $loanId, 1);
                break;
            //无效方法
            case self::DT_TRANSFER_P2P_REPAY: //p2p还款
                $res = $ds->transferRepayDT($user, $money, $token, $dealId, $manageId,$loanId);
                break;
            case self::DT_TRANSFER_REVOKE:
                $res = $ds->transferRevokeDT($user, $money, $token, $fee, $manageId, $dealId, $dealName, $minLoanMoney, $holdDays, $loanId);
                break;
        }
        $this->json_data = $res;
    }
}
