<?php
namespace task\apis\supervision;

use task\lib\ApiAction;
use core\service\account\AccountService;
use core\service\supervision\SupervisionWithdrawService;
use core\service\supervision\SupervisionAccountService;

class ModifyCardCheck extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $response['msg'] = '';
        $response['status'] = 0;
        $userId = $param['userId'];
        try {
            $accountId = AccountService::initAccount($userId, $param['userPurpose']);
            if (empty($accountId)) {
                throw new \Exception('该用户不存在');
            }
            $Sws = new SupervisionWithdrawService();
            $Sas = new SupervisionAccountService();
            if (!empty($param['outOrderId'])) {
                $withdrawData = $Sws->getWithdrawByOrderId($param['outOrderId']);
                if (empty($withdrawData)) {
                    throw new \Exception('该订单号不存在');
                }
                if ($withdrawData['user_id'] != $userId) {
                    throw new \Exception('该订单号不是该用户记录');
                }
                if (false == $Sws->canRedoWithdraw($withdrawData)) {
                    throw new \Exception('该提现订单非失败状态');
                }
            } else {
                if (!$Sas->isZeroUserAssets($userId)) {
                    throw new \Exception('资产不为零，不能更换银行卡');
                }
            }
        } catch (\Exception $e) {
            $response['msg'] = $e->getMessage();
            $response['status'] = -1;
        }
        $this->json_data = $response;
    }
}
