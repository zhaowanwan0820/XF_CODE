<?php
/**
 * 初始化账户授权表
 * author: weiwei12@ucfgroup.com
 */

require(dirname(__FILE__) . '/../../app/init.php');
use libs\utils\Logger;
use libs\db\Db;
use core\service\UserService;
use core\dao\UserModel;
use core\dao\AccountAuthorizationModel;
use core\dao\EnterpriseModel;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

class init_account_auth {

    public function execute($startId, $endId) {
        Logger::info(sprintf('start init_account_auth,startId:%d,endId:%d', $startId, $endId));
        $userModel = UserModel::instance();
        $accountAuthModel = AccountAuthorizationModel::instance();
        $db = Db::getInstance('firstp2p_payment');
        for (; $startId < $endId; $startId ++) {
            $userId = $startId;
            $accountId = $startId;

            $userInfo = $userModel->find($userId);
            if (empty($userInfo) || (int)$userInfo['supervision_user_id'] == 0) {
                continue;
            }

            $authList = $accountAuthModel->getAuthListByAccountId($accountId);
            if (!empty($authList)) {
                Logger::info(sprintf('已添加过用户授权,id:%d', $accountId));
                continue;
            }

            if (!in_array($userInfo['user_purpose'], [EnterpriseModel::COMPANY_PURPOSE_INVESTMENT, EnterpriseModel::COMPANY_PURPOSE_FINANCE, EnterpriseModel::COMPANY_PURPOSE_RECHARGE, EnterpriseModel::COMPANY_PURPOSE_GUARANTEE, EnterpriseModel::COMPANY_PURPOSE_REPLACEPAY, EnterpriseModel::COMPANY_PURPOSE_PURCHASE])) {
                Logger::info(sprintf('此账户无需初始化授权,id:%d,user_purpose:%d', $accountId, $userInfo['user_purpose']));
                continue;
            }

            $params = [
                'accountId'         => $accountId,
                'userId'            => $userId,
                'grantAmount'       => 0, //无限制
                'grantTime'         => 0, //无限制
            ];

            $db->startTrans();
            try {
                switch ($userInfo['user_purpose']) {
                    //投资户业务授权初始化为：出借授权&缴费授权，授权期限和授权金额分别为长期有效、不限金额；
                    case EnterpriseModel::COMPANY_PURPOSE_INVESTMENT:
                        //出借授权
                        $params['grantType'] = AccountAuthorizationModel::GRANT_TYPE_INVEST;
                        $accountAuthModel->addAuth($params);

                        //缴费授权
                        $params['grantType'] = AccountAuthorizationModel::GRANT_TYPE_PAYMENT;
                        $accountAuthModel->addAuth($params);

                        break;

                    //借款户业务授权初始化为：还款授权&缴费授权，授权期限和授权金额分别为长期有效、不限金额；
                    //代充值户/担保户/代垫户业务授权初始化为：还款授权&缴费授权，授权期限和授权金额分别为长期有效、不限金额；
                    //资产收购户 业务授权初始化为：还款授权&缴费授权
                    case EnterpriseModel::COMPANY_PURPOSE_FINANCE:
                    case EnterpriseModel::COMPANY_PURPOSE_RECHARGE:
                    case EnterpriseModel::COMPANY_PURPOSE_GUARANTEE:
                    case EnterpriseModel::COMPANY_PURPOSE_REPLACEPAY:
                    case EnterpriseModel::COMPANY_PURPOSE_PURCHASE:
                        //还款授权
                        $params['grantType'] = AccountAuthorizationModel::GRANT_TYPE_REPAY;
                        $accountAuthModel->addAuth($params);

                        //缴费授权
                        $params['grantType'] = AccountAuthorizationModel::GRANT_TYPE_PAYMENT;
                        $accountAuthModel->addAuth($params);

                        break;
                    default:
                        throw new \Exception(sprintf('未知账户类型,user_purpose:%d', $userInfo['user_purpose']));
                }
                $db->commit();
                Logger::info(sprintf('添加账户授权成功,id:%d', $accountId));
            } catch (\Exception $e) {
                Logger::error(sprintf('添加账户授权失败,id:%d,err:%s', $accountId, $e->getMessage()));
                $db->rollback();
            }
        }
        Logger::info(sprintf('end init_account_auth,startId:%d,endId:%d', $startId, $endId));
    }
}


if(!isset($argv[1]) || !isset($argv[2])){
    die("参数错误");
}

$obj = new init_account_auth();
$obj->execute($argv[1], $argv[2]);
