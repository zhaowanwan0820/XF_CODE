<?php
/**
 * 享花等第三方还款划扣申请失败，进行补单的脚本
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
use core\service\DealService;
use core\service\LoanThirdService;
use core\service\UniteBankPaymentService;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\LoanThirdMapModel;
use core\dao\LoanThirdModel;

class RepairLoanThirdApply {
    private $outOrderId;
    public function __construct($outOrderId) {
        $this->outOrderId = $outOrderId;
    }

    /**
     * 查询所有已受理的还款划扣列表
     */
    public function getList() {
        $loanThirdList = LoanThirdModel::instance()->findAllViaSlave(sprintf("`status`='%d'", LoanThirdModel::STATUS_ACCEPT), true);
        if (empty($loanThirdList)) {
            echo '没有已受理的还款划扣申请列表' . PHP_EOL;
        }

        $list = [];
        $list[] = ['id', 'user_id', 'deal_id', 'repay_id', 'repay_type', 'out_order_id',
            'type', 'bankcard', 'repay_money', 'total_money', 'principal', 'interest',
            'service_fee', 'status', 'loan_time', 'create_time', 'update_time'
        ];
        $loanTime = !empty($item['loan_time']) ? date('Y-m-d H:i:s', $item['loan_time']) : '暂无时间';
        $updateTime = !empty($item['update_time']) ? date('Y-m-d H:i:s', $item['update_time']) : '暂无时间';
        foreach ($loanThirdList as $item) {
            $tmp = [$item['id'], $item['user_id'], $item['deal_id'], $item['repay_id'],
                $item['repay_type'], $item['out_order_id'], $item['type'], $item['bankcard'],
                $item['repay_money'], $item['total_money'], $item['principal'], $item['interest'],
                $item['service_fee'], $item['status'], $loanTime,
                date('Y-m-d H:i:s', $item['create_time']), $updateTime,
            ];
            $list[] = $tmp;
        }
        foreach ($list as $item) {
            echo join("\t", $item) . "\n";
        }
    }

    public function run() {
        $result = [];
        if (empty($this->outOrderId)) {
            // 查询所有已受理的还款划扣记录
            $loanThirdList = LoanThirdModel::instance()->findAllViaSlave(sprintf("`status`='%d'", LoanThirdModel::STATUS_ACCEPT), true);
            if (empty($loanThirdList)) {
                return ['ret'=>false, 'errorMsg'=>'没有已受理的还款划扣申请记录'];
            }

            foreach ($loanThirdList as $item) {
                $result[$item['out_order_id']] = $this->_repayApply($item['out_order_id']);
            }
        } else {
            $result[$this->outOrderId] = $this->_repayApply($this->outOrderId);
        }
        Script::log(sprintf('%s::%s|result:%s', __CLASS__, __FUNCTION__, json_encode($result)));
        var_dump('result=>', $result);
    }

    /**
     * 享花等第三方还款划扣申请
     * @param string $service
     * @param array $params
     * @param boolean $checkPrivileges 检查权限
     * @param int $withdrawType
     * @param boolean $checkBalance 检查余额
     * @return array $params
     */
    private function _repayApply($outOrderId) {
        $startTrans = false;
        try{
            if (empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }

            // 查询标的订单号关系记录
            $loanThirdMapInfo = LoanThirdMapModel::instance()->getLoanThirdMapByOrderId($outOrderId);
            if (empty($loanThirdMapInfo)) {
                throw new WXException('ERR_REPAYORDER_NO_EXIST');
            }

            // 查询还款申请记录
            $loanThirdData = LoanThirdModel::instance()->getLoanThirdByOrderId($outOrderId);
            if (empty($loanThirdData)) {
                throw new WXException('ERR_REPAYDATA_NO_EXIST');
            }
            // 还款划扣已终态
            if (in_array($loanThirdData['status'], [LoanThirdModel::STATUS_SUCCESS, LoanThirdModel::STATUS_FAIL])) {
                return ['ret'=>true, 'errorMsg'=>sprintf('该还款订单号：%s，划扣状态已经终态，状态是：%d', $outOrderId, $loanThirdData['status'])];
            }

            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();
            $startTrans = true;

            // 用户ID
            $userId = (int)$loanThirdData['user_id'];
            // 标的ID
            $dealId = (int)$loanThirdData['deal_id'];
            // 申请划扣金额，单位分
            $ytMoneyCent = bcmul($loanThirdData['repay_money'], 100);
            // 银行卡号
            $bankcard = $loanThirdData['bankcard'];

            $dealService = new DealService();
            // 云图传过来的还款金额为0时，直接解冻
            if ($ytMoneyCent == 0) {
                $user = UserModel::instance()->find($userId);
                if(empty($user)) {
                    throw new \Exception('用户ID不存在，uid:' . $userId);
                }
                $dealInfo = DealModel::instance()->find($dealId, '*', true);
                $user->changeMoneyDealType = $dealService->getDealType($dealInfo);
                $user->changeMoney(-$loanThirdData['total_money'], '享花还款', '享花还款余额解冻', 0, 0, UserModel::TYPE_LOCK_MONEY);

                // 更新还款申请记录
                LoanThirdModel::instance()->updateLoanThirdByOrderId($userId, $outOrderId, ['status'=>LoanThirdModel::STATUS_SUCCESS]);

                $db->commit();
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('享花还款余额解冻成功,无需请求划扣接口, userId:%d, outOrderId:%s, loanThirdData:%s', $userId, $outOrderId, json_encode($loanThirdData->getRow())))));
                return ['ret'=>true, 'errorMsg'=>'还款金额为0，直接解冻成功', 'data'=>$loanThirdData];
            }

            // 通知支付提现到银行
            $bankParams = array(
                'userId' => $userId,
                'dealId' => $dealId,
                'amount' => $ytMoneyCent,
                'totalAmount' => bcmul($loanThirdData['total_money'], 100),
                'outOrderId' => $outOrderId,
                'pAccount' => $bankcard,
            );
            // 判断标的是否走p2p存管流程
            $isP2pPath = $dealService->isP2pPath($dealId);
            if($isP2pPath) {
                $loanThirdObj = new LoanThirdService();
                $loanThirdObj->repayLoanThirdSupervision($bankParams);
            }else{
                $bankParams['callbackUrl'] = '/payment/withdrawTrustThirdNotify';
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '通知支付提现,params:' . json_encode($bankParams))));
                $bankService = new UniteBankPaymentService();
                $bankRes = $bankService->withdrawTrustBank($bankParams, false);
                if(!$bankRes) {
                    throw new \Exception('先锋支付资金划拨处理异常');
                }
            }

            $db->commit();
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('请求划扣受理接口成功, userId:%d, outOrderId:%s, loanThirdData:%s, bankParams:%s', $userId, $outOrderId, json_encode($loanThirdData->getRow()), json_encode($bankParams)))));
            return ['ret'=>true];
        } catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('请求划扣受理接口失败, userId:%d, outOrderId:%s, loanThirdData:%s, exceptionMsg:%s（%d）', $userId, $outOrderId, json_encode($loanThirdData->getRow()), $e->getMessage(), $e->getCode()))));
            $startTrans && $db->rollback();
            return ['ret'=>false, 'errorMsg'=>sprintf('%s', $e->getMessage())];
        }
    }
}

Script::start();
if (empty($argv[1])) {
    exit("params[method] is not input!\n");
}
$method = addslashes($argv[1]);
$outOrderId = !empty($argv[2]) ? addslashes($argv[2]) : 0;
$obj = new RepairLoanThirdApply($outOrderId);

if (!method_exists($obj, $method)) {
    exit("params[method] Is Not Found!\n");
}
// 同时仅允许一个脚本运行
$cmd = sprintf('ps aux | grep \'%s\' | grep -v grep | grep -v vim | grep -v %d', basename(__FILE__), posix_getpid());
$handle = popen($cmd, 'r');
$scriptCmd = fread($handle, 1024);
if ($scriptCmd) {
    exit("repair_loanthird_apply is running!\n");
}

$obj->$method();
Script::end();