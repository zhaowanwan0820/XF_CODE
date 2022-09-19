<?php
/**
 * 用户信用借款服务类
 * @data 2017.09.13
 * @author weiwei12 weiwei12@ucfgroup.com
 */

namespace core\service\speedLoan;

use libs\db\Db;
use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\RequestCommon;
use libs\utils\Rpc;
use libs\utils\Monitor;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;
use NCFGroup\Protos\Creditloan\Enum\CreditRepayEnum;
use NCFGroup\Protos\Creditloan\Enum\IdemportentEnum;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\tmevent\speedLoan\ApplyRepayEvent;
use core\tmevent\speedLoan\FreezeRepayMoneyEvent;
use core\dao\UserModel;
use core\service\SupervisionFinanceService;
use NCFGroup\Common\Library\Idemportent;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;
use libs\utils\Alarm;

class RepayService extends BaseService
{

    /**
     * 在线申请还款
     * @param array $params 参数列表
     * 必传参数：
     *     userId:用户ID
     *     orderId:订单号
     *     loanId:借款id
     *     principal:本金
     *     interest:利息
     *     serviceFee:服务费
     */
    public function doApplyRepay($params)
    {
        if (empty($params)) {
            return false;
        }
        $gtm = new GlobalTransactionManager();

        //冻结资金
        $gtm->addEvent(new FreezeRepayMoneyEvent($params));

        //在线申请还款
        $gtm->addEvent(new ApplyRepayEvent($params));

        //执行
        $result = $gtm->execute();
        return $result;
    }

    /**
     * 还款申请
     * 必传参数：
     *     userId:用户ID
     *     orderId:订单号
     *     loanId:借款id
     *     principal:本金
     *     interest:利息
     *     serviceFee:服务费
     */
    public function repayApply($params) {
        if (empty($params)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditRepay', 'repayApply', $request);
        return $response;
    }

    /**
     * 冻结还款资金
     * @param array $params 参数列表
     * 必传参数：
     *     userId:用户ID
     *     orderId:订单号
     *     loanId:借款id
     *     principal:本金
     *     interest:利息
     *     serviceFee:服务费
     */
    public function freezeRepayMoney($params) {
        if (empty($params)) {
            return false;
        }

        $userId = isset($params['userId']) ? (int) $params['userId'] : 0;
        $orderId = isset($params['orderId']) ? (int) $params['orderId'] : 0; //订单
        $loanId = isset($params['loanId']) ? (int) $params['loanId'] : 0; //订单
        $principal = isset($params['principal']) ? (int) $params['principal'] : 0; //本金
        $interest = isset($params['interest']) ? (int) $params['interest'] : 0; //利息
        $serviceFee = isset($params['serviceFee']) ? (int) $params['serviceFee'] : 0; //服务费
        $repayAmount = $principal + $interest + $serviceFee; //还款总金额

        $repayMoney = bcdiv($repayAmount, 100, 2); //转成元
        $supervisionFinanceService = new SupervisionFinanceService();

        $db = Db::getInstance('firstp2p');
        //幂等检查
        $res = Idemportent::get($db->link_id, IdemportentEnum::TYPE_SPEEDLOAN_REPAY_APPLY, $orderId, IdemportentEnum::STATUS_REPAY_FREEZE);
        if ($res === Idemportent::EXISTS) {
            return true;
        }

        // 自动划转至超级账户  关闭
        // if (!$supervisionFinanceService->autoTransferToSuper($userId, $repayMoney, $orderId)) {
        //     return false;
        // }

        $user = UserModel::instance()->find($userId);
        try{
            $db->startTrans();
            //幂等
            Idemportent::set($db->link_id, IdemportentEnum::TYPE_SPEEDLOAN_REPAY_APPLY, $orderId, IdemportentEnum::STATUS_REPAY_FREEZE);

            $bizToken = [
                'dealLoadId' => $loanId,
                'orderId' => $orderId,
            ];

            //冻结资金
            $message = '网信速贷还款冻结';
            $memo = sprintf('借款编号%d，在线还款冻结', $loanId);
            $result = $user->changeMoney($repayMoney, $message, $memo, 0, 0, UserModel::TYPE_LOCK_MONEY,0,$bizToken);
            $db->commit();
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP,'还款冻结失败 userId:'.$userId . " errMsg:".$e->getMessage())));
            $db->rollback();
            return false;
        }
        return true;
    }

    /**
     * 解冻还款资金
     * @param array $params 参数列表
     * 必传参数：
     *     userId:用户ID
     *     orderId:订单号
     *     loanId:借款id
     *     principal:本金
     *     interest:利息
     *     serviceFee:服务费
     */
    public function unfreezeRepayMoney($params) {
        if (empty($params)) {
            return false;
        }

        $userId = isset($params['userId']) ? (int) $params['userId'] : 0;
        $orderId = isset($params['orderId']) ? (int) $params['orderId'] : 0; //订单
        $loanId = isset($params['loanId']) ? (int) $params['loanId'] : 0; //订单
        $principal = isset($params['principal']) ? (int) $params['principal'] : 0; //本金
        $interest = isset($params['interest']) ? (int) $params['interest'] : 0; //利息
        $serviceFee = isset($params['serviceFee']) ? (int) $params['serviceFee'] : 0; //服务费
        $repayAmount = $principal + $interest + $serviceFee; //还款总金额
        $repayMoney = bcdiv($repayAmount, 100, 2); //转成元

        $user = UserModel::instance()->find($userId);
        $db = Db::getInstance('firstp2p');
        //幂等检查
        $res = Idemportent::get($db->link_id, IdemportentEnum::TYPE_SPEEDLOAN_REPAY_APPLY, $orderId, IdemportentEnum::STATUS_REPAY_UNFREEZE);
        if ($res === Idemportent::EXISTS) {
            return true;
        }
        try{
            $db->startTrans();
            //幂等
            Idemportent::set($db->link_id, IdemportentEnum::TYPE_SPEEDLOAN_REPAY_APPLY, $orderId, IdemportentEnum::STATUS_REPAY_UNFREEZE);
            //解冻资金
            $message = '网信速贷还款解冻';
            $memo = sprintf('借款编号%d，在线还款解冻', $loanId);
            $bizToken = [
                'dealLoadId' => $loanId,
                'orderId' => $orderId,
            ];
            $result = $user->changeMoney(-$repayMoney, $message, $memo, 0, 0, UserModel::TYPE_LOCK_MONEY,0,$bizToken);
            $db->commit();
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP,'还款解冻失败 userId:'.$userId . " errMsg:".$e->getMessage())));
            $db->rollback();
            return false;
        }
        return true;
    }


    /**
     * 即富还款申请
     */
    public function platformRepay($params) {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'platformRepay', $request);
        return $response;
    }

    /**
     * 获取待还利息
     */
    public function getInterestWaiting($params) {
        $repayCaculate = $this->repayCaculate($params);
        if ($repayCaculate['data']['code'] !== '0000') {
            Alarm::push('speedloan_exception', '计算待还利息失败', 'params:' . json_encode($params));
            throw new \Exception('计算待还利息失败');
        }
        $interestAmt = $repayCaculate['data']['respData']['interestAmt'];
        if ($interestAmt < 0) {
            Alarm::push('speedloan_exception', '计算待还利息为负', 'params:' . json_encode($params));
            throw new \Exception('计算待还利息为负');
        }
        return $interestAmt;
    }

    /**
     * 还款计划查询
     */
    public function repayCaculate($params) {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'repayCaculate', $request);
        return $response;
    }

}
