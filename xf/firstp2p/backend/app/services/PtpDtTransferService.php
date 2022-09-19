<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RequestDtTransfer;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use core\service\DtTransferService;
use core\dao\UserModel;

/**
 * 多投转账服务
 * Class PtpDtTransferService
 * @package NCFGroup\Ptp\services
 */
class PtpDtTransferService extends ServiceBase {
    const DT_TRANSFER_BID           = 1;
    const DT_TRANSFER_INTEREST      = 2;
    const DT_TRANSFER_REDEEM        = 3;
    const DT_TRANSFER_REDEEM_CLEAN  = 4;
    const DT_TRANSFER_P2P_REPAY     = 7;
    const DT_TRANSFER_REVOKE        = 8;

    public function transferMoney(RequestDtTransfer $request) {
        $ds = new DtTransferService();
        $userId = $request->getUserId();
        $user = UserModel::instance()->find($userId);
        if(!$user) {
            return false;
        }
        $type = $request->getType();
        $validTypes = array(
            self::DT_TRANSFER_BID,
            self::DT_TRANSFER_INTEREST,
            self::DT_TRANSFER_REDEEM,
            self::DT_TRANSFER_REDEEM_CLEAN,
            self::DT_TRANSFER_P2P_REPAY,
            self::DT_TRANSFER_REVOKE,
        );
        if(!in_array($type,$validTypes)) {
            return false;
        }

        $money = $request->getMoney();
        $token = $request->getToken();
        $fee = $request->getFee();
        $manageId = $request->getManageId();
        $dealId = $request->getDealId();
        $dealName = $request->getDealName();
        $minLoanMoney = $request->getMinLoanMoney();
        $holdDays = $request->getHoldDays();

        switch($type) {
            case self::DT_TRANSFER_BID :
                $res = $ds->transferBidDT($user,$money,$dealId,$token,$dealName);
                break;
            case self::DT_TRANSFER_INTEREST :
                $res = $ds->transferInterestDT($user,$money,$fee,$manageId,$token,$dealId,$dealName,$minLoanMoney);
                break;
            case self::DT_TRANSFER_REDEEM :
                $dealLoanId = $request->getDealLoanId();//投资记录ID
                $res = $ds->transferRedeemDT($user,$money,$token,$fee,$manageId,$dealId,$dealName,$minLoanMoney,$holdDays,$dealLoanId);
                break;
            case self::DT_TRANSFER_REDEEM_CLEAN :
                $dealLoanId = $request->getDealLoanId();//投资记录ID
                $res = $ds->transferRedeemDT($user,$money,$token,$fee,$manageId,$dealId,$dealName,$minLoanMoney,$holdDays,$dealLoanId,1);
                break;
            case self::DT_TRANSFER_P2P_REPAY : //p2p还款
                $res = $ds->transferRepayDT($user,$money,$token,$dealId,$manageId);
                break;
            case self::DT_TRANSFER_REVOKE :
                $dealLoanId = $request->getDealLoanId();//投资记录ID
                $res = $ds->transferRevokeDT($user,$money,$token,$fee,$manageId,$dealId,$dealName,$minLoanMoney,$holdDays,$dealLoanId);
                break;
        }
        return $res;
    }
}
