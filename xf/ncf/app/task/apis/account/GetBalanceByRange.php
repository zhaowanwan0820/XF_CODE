<?php
/**
 * 根据范围获取账户金额
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;
use core\dao\account\AccountModel;
use core\enu\UserAccountEnum;
use libs\utils\Logger;

class GetBalanceByRange extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $minId = !empty($param['minId']) ? (int) $param['minId'] : 0;
        $maxId = !empty($param['maxId']) ? (int) $param['maxId'] : 0;
        $minMoney = !empty($param['minMoney']) ? (int) $param['minMoney'] : 0; //单位分
        $maxMoney = !empty($param['maxMoney']) ? (int) $param['maxMoney'] : 0; //单位分
        $accountType = !empty($param['accountType']) ? (int) $param['accountType'] : UserAccountEnum::ACCOUNT_INVESTMENT;
        if (empty($minId) || empty($maxId)) {
            return false;
        }
        $minMoney = bcdiv($minMoney, 100, 2);
        $maxMoney = bcdiv($maxMoney, 100, 2);
        $result = AccountModel::instance()->getBalanceByRange($minId, $maxId, $minMoney, $maxMoney, $accountType);
        Logger::info('getBalanceByRange api. result: '. json_encode($result));
        $this->json_data = $result;
    }
}
