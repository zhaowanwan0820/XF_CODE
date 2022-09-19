<?php
/**
 * 存管-提现订单未落单进行补单的脚本
 *
 * 冻结用户余额，并且提现订单落库
 *
 * @package     scripts
 * @author      guofeng3
 ********************************** 80 Columns *********************************
 */
require_once(dirname(__FILE__) . '/../app/init.php');

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);

use \libs\utils\Script;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\common\WXException;
use core\service\SupervisionAccountService;
use core\service\SupervisionFinanceService;
use core\service\SupervisionOrderService;
use core\dao\SupervisionWithdrawModel;

class RepairSupervisionWithdraw {
    private $supervisionFinanceObj;
    private $outOrderId;
    private $dealId;
    public function __construct($outOrderId, $dealId = 0) {
        $this->outOrderId = $outOrderId;
        $this->dealId = $dealId;
        $this->supervisionFinanceObj = new SupervisionFinanceService();
    }

    public function run() {
        // 查询存管系统，提现订单记录
        $orderSearchResult = $this->supervisionFinanceObj->orderSearch($this->outOrderId);
        // 判断订单查询结果
        if (empty($orderSearchResult['respCode']) || $orderSearchResult['respCode'] != SupervisionFinanceService::RESPONSE_CODE_SUCCESS) {
            Script::log(sprintf('%s::%s|%s', __CLASS__, __FUNCTION__, sprintf('orderId:%s, supervisionOrderSearch is error, errorMsg:%s', $this->outOrderId, $orderSearchResult['respMsg'])));
            return false;
        }
        if (empty($orderSearchResult['data']['bidId']) && empty($this->dealId)) {
            Script::log(sprintf('%s::%s|%s', __CLASS__, __FUNCTION__, sprintf('orderId:%s, supervisionBidId and dealId is empty', $this->outOrderId)));
            return false;
        }

        // 组织提现数据
        $withdrawParams = [
            'orderId' => (int)$orderSearchResult['data']['orderId'], // 提现订单
            'userId' => (int)$orderSearchResult['data']['payUserId'], // 提现用户ID
            'amount' => (int)$orderSearchResult['data']['amount'], // 提现金额，单位分
            'bidId' => !empty($orderSearchResult['data']['bidId']) ? (int)$orderSearchResult['data']['bidId'] : $this->dealId,
        ];
        $result = [];
        try{
            Script::log(sprintf('%s::%s|outOrderId:%s, withdrawParams:%s, supervisionOrderSearch:%s', __CLASS__, __FUNCTION__, $withdrawParams['orderId'], json_encode($withdrawParams), json_encode($orderSearchResult)));
            $res = $this->_withdrawRepair('bankpayupWithdraw', $withdrawParams, false, SupervisionWithdrawModel::TYPE_LOCKMONEY);
            $result = $this->supervisionFinanceObj->responseSuccess($res);
        } catch (\Exception $e) {
            Script::log(sprintf('%s::%s|outOrderId:%s, exceptionMsg:%s', __CLASS__, __FUNCTION__, $withdrawParams['orderId'], $e->getMessage()));
            $result = $this->supervisionFinanceObj->responseFailure($e->getCode(), $e->getMessage());
        }
        Script::log(sprintf('%s::%s|outOrderId:%s, withdrawResult:%s', __CLASS__, __FUNCTION__, $withdrawParams['orderId'], json_encode($result)));
        return $result;
    }

    /**
     * 提现准备
     * 免密提现和验密提现使用
     * @param string $service
     * @param array $params
     * @param boolean $checkPrivileges 检查权限
     * @param int $withdrawType
     * @param boolean $checkBalance 检查余额
     * @return array $params
     */
    private function _withdrawRepair($service, $params, $checkPrivileges = false, $withdrawType = SupervisionWithdrawModel::TYPE_TO_BANKCARD, $checkBalance = false) {
        // 如果是放款提现，则根据用户ID、标的ID，检查提现记录是否存在、状态是否终态
        $withdrawModel = SupervisionWithdrawModel::instance();
        if (!empty($params['bidId'])) {
            $orderInfo = $withdrawModel->getWithdrawSuccessByUserIdBid($params['userId'], $params['bidId']);
            if (!empty($orderInfo)) {
                Script::log(sprintf('%s::%s|outOrderId:%s, bidId:%d, getWithdrawByBid has exists', __CLASS__, __FUNCTION__, $params['orderId'], $params['bidId']));
                return true;
            }
        }
        // 检查该存管提现订单，是否已存在
        $withdrawData = $withdrawModel->getWithdrawRecordByOutId($params['orderId']);
        if (!empty($withdrawData)) {
            Script::log(sprintf('%s::%s|outOrderId:%s, getWithdrawByOutId has exists', __CLASS__, __FUNCTION__, $params['orderId']));
            return true;
        }

        $supervisionApi = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $supervisionApi->checkParams($service, $params);
        $supervisionAccountService = new SupervisionAccountService();
        //检查用户权限
        if ($checkPrivileges) {
            if (!$supervisionAccountService->checkUserPrivileges($params['userId'], [self::GRANT_WITHDRAW])) {
                throw new WXException('ERR_HAVE_NO_PRIVILEGES');
            }
        }

        //检查用户是否开户
        $isSvUser = $supervisionAccountService->isSupervisionUser($params['userId']);
        if (!$isSvUser) {
            throw new WXException('ERR_NOT_OPEN_ACCOUNT');
        }

        //检查用户存管余额
        if ($checkBalance) {
            $balanceResult = $supervisionAccountService->balanceSearch($params['userId']);
            if (empty($balanceResult) || $balanceResult['status'] != self::RESPONSE_SUCCESS || $balanceResult['respCode'] != self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BALANCE_SEARCH');
            }
            if ($params['amount'] > $balanceResult['data']['availableBalance']) {
                throw new WXException('ERR_BALANCE_NOT_ENOUGHT');
            }
        }

        try{
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();

            //生成提现单
            $bidId = 0;
            if (!empty($params['bidId'])) {
                $bidId = $params['bidId'];
            }
            $createOrderResult = $withdrawModel->createOrder($params['userId'], $params['amount'], $params['orderId'], $bidId, $withdrawType);
            if (empty($createOrderResult)) {
                throw new WXException('ERR_CARRY_ORDER_CREATE');
            }

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_WITHDRAW, $params);

            // 上报 ITIL
            \libs\utils\Monitor::add('sv_withdraw_apply');
            $db->commit();
        } catch(\Exception $e) {
            $db->rollback();
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return true;
    }
}

Script::start();
if (empty($argv[1])) {
    exit("params[outOrderId] is not found!\n");
}
// 同时仅允许一个脚本运行
$cmd = sprintf('ps aux | grep \'%s\' | grep -v grep | grep -v vim | grep -v %d', basename(__FILE__), posix_getpid());
$handle = popen($cmd, 'r');
$scriptCmd = fread($handle, 1024);
if ($scriptCmd) {
    exit("repair_supervision_withdraw is running!\n");
}

$outOrderId = (int)$argv[1];
$dealId = !empty($argv[2]) ? (int)$argv[2] : 0;
$obj = new RepairSupervisionWithdraw($outOrderId, $dealId);
$obj->run();
Script::end();
