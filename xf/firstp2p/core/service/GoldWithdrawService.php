<?php
/**
 * 黄金项目变现service
 * @data 2017.06.28
 * @author weiwei12 weiwei12@ucfgroup.com
 */


namespace core\service;

use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use libs\utils\Rpc;
use core\service\GoldService;
use core\service\TransferService;
use core\service\MoneyOrderService;
use core\exception\MoneyOrderException;;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\DealModel;
use core\dao\FinanceQueueModel;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use NCFGroup\Protos\Ptp\Enum\PayQueueEnum;

class GoldWithdrawService extends GoldService
{
    //提现成功状态
    const WITHDRAW_STATUS_SUCCESS = 1;
    //每次处理多少条记录
    const PROCESS_PAGESIZE = 100;

    /**
     * 处理订单列表
     */
    public function processOrderList() {
        $startId = 0;
        $processTime = time();
        if (!$this->checkProcessTime($processTime)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '请在T日17点之后执行脚本, 现在时间' . date('Y-m-d H:i:s'))));
            echo '请在T日17点之后执行脚本, 现在时间' . date('Y-m-d H:i:s') . "\n";
            return false;
        }
        while(true) {
            $withdrawList = $this->getApplyWithdrawList($startId, self::PROCESS_PAGESIZE);
            if (empty($withdrawList)) {
                break;
            }
            foreach ($withdrawList as $withdrawOrder) {
                $startId = $withdrawOrder['id'];
                //检查提现单是否满足条件
                if (!$this->checkWithdrawOrder($withdrawOrder, $processTime)) {
                    continue;
                }
                $ret = $this->asyncProcessOrder($withdrawOrder['order_id']);//添加任务，异步处理
                if (!$ret) {
                    Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, '异步添加黄金变现处理任务失败, orderId: ' . $withdrawOrder['orderId'])));
                }
            }
        }
        return true;
    }

    /**
     * 获取申请中的变现单
     */
    public function getApplyWithdrawList($startId, $pageSize) {
        $request = new RequestCommon();
        $request->setVars(['startId' => $startId, 'pageSize' => $pageSize]);
        $res = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'getApplyWithdrawList', $request);
        if (empty($res) || $res['errCode'] != 0) {
            return false;
        }
        return $res['data'];
    }

    /**
     * 异步处理订单
     */
    public function asyncProcessOrder($orderId) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '异步处理订单，orderId: ' . $orderId)));

        $jobs_model = new JobsModel();
        $function = '\core\service\GoldWithdrawService::processOrder';
        $param = array($orderId);
        $jobs_model->priority = JobsModel::PRIORITY_GOLD_WITHDRAW;
        return $jobs_model->addJob($function, $param,false,0);
    }

    /**
     * 处理订单
     */
    public function processOrder($orderId) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '处理订单，orderId: ' . $orderId)));

        //幂等检查
        $request = new RequestCommon();
        $request->setVars(['orderId' => $orderId]);
        $res = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'getWithdrawByOrderId', $request);
        if (empty($res) || $res['errCode'] != 0) {
            return false;
        }
        $withdrawOrder = $res['data'];
        if ((int) $withdrawOrder['withdrawStatus'] === self::WITHDRAW_STATUS_SUCCESS) {
            return true;
        }

        //业务处理
        $gtmName = 'goldWithdraw';
        $gtm = new GlobalTransactionManager();
        $gtm->setName($gtmName);
        $gtm->addEvent(new \core\tmevent\gold\WithdrawTransferMoney($orderId));
        $gtm->addEvent(new \core\tmevent\gold\WithdrawProcess($orderId));
        return $gtm->execute();
    }

    /**
     * T日17点才可以执行
     */
    public function checkProcessTime($processTime) {
        if (empty($processTime)) {
            return false;
        }
        if (!check_trading_day($processTime) || date('H', $processTime) < 17) {
            return false;
        }
        return true;
    }

    /**
     * T日14点前的订单才处理，14点之后的订单T+1处理
     */
    public function checkWithdrawOrder($withdrawOrder, $processTime) {
        if (empty($withdrawOrder) || empty($withdrawOrder['create_time'])) {
            return false;
        }
        $startTime = $withdrawOrder['create_time'];
        if (date('H', $startTime) >= 14 && $processTime - $startTime < 10 * 60 * 60) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '变现单不满足处理条件，withdrawOrder: %s' . json_encode($withdrawOrder))));
            return false;
        }
        return true;

    }

    /**
     * 资金划转
     * 变现收益由活期变现账户的网信账户转至用户网信账户
     * 变现手续费由用户网信账户转至活期变现账户的网信账户
     */
    public function transferMoney($orderId) {

        //变现单信息
        $request = new RequestCommon();
        $request->setVars(['orderId' => $orderId]);
        $res = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'getWithdrawByOrderId', $request);
        if (empty($res) || $res['errCode'] != 0) {
            return false;
        }
        $userId = $res['data']['userId'];
        $money = $res['data']['money'];//变现金额
        $feeMoney = $res['data']['feeMoney'];//变现手续费
        //$realMoney = bcsub($money, $feeMoney, 2); //实际收益，变现金额 - 变现手续费

        //获取变现账户和变现手续费账户
        $request = new RequestCommon();
        $res = $this->requestGold('NCFGroup\Gold\Services\DealCurrent', 'getInfo', $request);
        if (empty($res) || $res['errCode'] != 0) {
            return false;
        }
        if (empty($res['data']['withdrawUserId']) || empty($res['data']['withdrawFeeUserId'])) {
            return false;
        }
        $withdrawUserId = $res['data']['withdrawUserId'];//变现账户
        $withdrawFeeUserId = $res['data']['withdrawFeeUserId'];//变现手续费账户

        //资金划转，支持幂等
        $negative = 0;//不允许扣负
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
        try {
            $db->startTrans();
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;

            //变现收益由活期变现账户的网信账户转至用户网信账户
            try {
                $payerMessage = $receiverMessage = '黄金变现';
                $payerNote = $receiverNote = '黄金变现，单号' . $orderId;
                $ret = $moneyOrderService->transfer($orderId, GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_WITHDRAW, $withdrawUserId, $userId, $money, PayQueueEnum::BIZTYPE_GOLD_WITHDRAW, $payerMessage, $payerNote, $receiverMessage, $receiverNote);
            } catch (\Exception $e) {
                if (!$e instanceof MoneyOrderException || $e->getCode() != MoneyOrderException::CODE_ORDER_EXIST) {
                    throw new \Exception($e->getMessage(), $e->getCode());
                }
            }

            //变现手续费由用户网信账户转至活期变现账户的网信账户
            try {
                $payerMessage = $receiverMessage = '黄金变现手续费';
                $payerNote = $receiverNote = '黄金变现手续费，单号' . $orderId;
                $ret = $moneyOrderService->transfer($orderId, GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_WITHDRAW_FEE, $userId, $withdrawFeeUserId, $feeMoney, PayQueueEnum::BIZTYPE_GOLD_WITHDRAW, $payerMessage, $payerNote, $receiverMessage, $receiverNote);
            } catch (\Exception $e) {
                if (!$e instanceof MoneyOrderException || $e->getCode() != MoneyOrderException::CODE_ORDER_EXIST) {
                    throw new \Exception($e->getMessage(), $e->getCode());
                }
            }

            $db->commit();
            return true;
         } catch (\Exception $e) {
            \libs\utils\Alarm::push('gold_exception', 'errMsg:黄金变现用户划转资金失败', $orderId);
            $db->rollback();
            return false;
         }
    }

    /**
     * 变现处理
     */
    public function withdrawProcess($orderId) {
        $request = new RequestCommon();
        $request->setVars(['orderId' => $orderId]);
        $res = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'withdrawProcess', $request);
        if (empty($res) || $res['errCode'] != 0) {
            return false;
        }
        return true;
    }
}
