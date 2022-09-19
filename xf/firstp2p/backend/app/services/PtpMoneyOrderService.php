<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestMoneyTransfer;
use NCFGroup\Protos\Ptp\RequestChangeMoney;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use \Assert\Assertion as Assert;
use core\service\MoneyOrderService;
use libs\rpc\Rpc;
use NCFGroup\Common\Library\Logger;
use core\exception\MoneyOrderException;;


/**
 * PtpMoneyOrderService
 *
 * @uses ServiceBase
 * @package default
 */
class PtpMoneyOrderService extends ServiceBase {

    /**
     * 转账
     * @param \NCFGroup\Protos\Ptp\RequestBonus $request
     * @return boolean
     */
    public function transfer(RequestMoneyTransfer $request) {

        try {
            $response = new ResponseBase;
            $moneyOrderService = new MoneyOrderService($request->getBizType());
            // 标的类型
            if ($request->getChangeMoneyDealType()) {
                $moneyOrderService->changeMoneyDealType = $request->getChangeMoneyDealType();
            }

            $orderId = $request->getBizOrderId();
            if (empty($orderId)) {
                throw new \Exception('业务订单号不能为空');
            }

            $bizSubtype = $request->getBizSubtype();
            if (empty($bizSubtype)) {
                throw new \Exception('业务子类型不能为空');
            }

            //// 出款方资金变动是否异步
            //if ($request->getPayerChangeMoneyAsyn()) {
            //    $moneyOrderService->payerChangeMoneyAsyn = $request->getPayerChangeMoneyAsyn();
            //}

            //// 收款方资金变动是否异步
            if ($request->getReceiverChangeMoneyAsync()) {
                $moneyOrderService->receiverChangeMoneyAsyn = $request->getReceiverChangeMoneyAsync();
            }

            // 出款方出款类型 （冻结，余额）
            if ($request->getPayerMoneyType()) {
                $moneyOrderService->payerMoneyType = $request->getPayerMoneyType();
            }
            $moneyOrderService->transfer(
                $orderId,
                $bizSubtype,
                $request->getPayerId(),
                $request->getReceiverId(),
                bcdiv($request->getAmount(), 100, 2), // 转换为P2P的元
                $request->getTransferBizType(),
                $request->getPayerMessage(),
                $request->getPayerNote(),
                $request->getReceiverMessage(),
                $request->getReceiverNote()
            );
            $response->resCode = RPCErrorCode::SUCCESS;
        } catch (\Exception $e) {
            if ($e instanceof MoneyOrderException && $e->getCode() == MoneyOrderException::CODE_ORDER_EXIST) {
                $response->resCode = RPCErrorCode::SUCCESS;
            } else {
                $response->resCode = RPCErrorCode::FAILD;
                $response->errorCode = $e->getCode() ? $e->getCode() : RPCErrorCode::FAILD;
                $response->errorMsg = $e->getMessage();
            }
        }

        return $response;
    }

    /**
     * changeMoney
     * 资金操作接口
     * @param RequestChangeMoney $request 
     * @access public
     * @return response
     */
    public function changeMoney(RequestChangeMoney $request)
    {
        try {
            $response = new ResponseBase;
            $moneyOrderService = new MoneyOrderService($request->getBizType());
            // 标的类型
            if ($request->getChangeMoneyDealType()) {
                $moneyOrderService->changeMoneyDealType = $request->getChangeMoneyDealType();
            }

            $orderId = $request->getBizOrderId();
            if (empty($orderId)) {
                throw new \Exception('业务订单号不能为空');
            }

            $bizSubtype = $request->getBizSubtype();
            if (empty($bizSubtype)) {
                throw new \Exception('业务子类型不能为空');
            }

            // 减钱操作不支持异步
            if (!$this->canAsync($request->getAmount(), $request->getMoneyType()) && $request->getAsync()) {
                throw new \Exception('该操作暂不支持异步');
            }

            if ($request->getAsync()) {
                $moneyOrderService->changeMoneyAsyn = true;
            }

            $moneyOrderService->changeUserMoney(
                $orderId,
                $request->getUserId(),
                $bizSubtype,
                bcdiv($request->getAmount(), 100, 2),
                $request->getLogInfo(),
                $request->getLogNote(),
                $request->getMoneyType()
            );
            $response->resCode = RPCErrorCode::SUCCESS;
        } catch (\Exception $e) {
            if ($e instanceof MoneyOrderException && $e->getCode() == MoneyOrderException::CODE_ORDER_EXIST) {
                $response->resCode = RPCErrorCode::SUCCESS;
            } else {
                $response->resCode = RPCErrorCode::FAILD;
                $response->errorCode = $e->getCode() ? $e->getCode() : RPCErrorCode::FAILD;
                $response->errorMsg = $e->getMessage();
            }
        }
        return $response;
    }

    private function canAsync($amount, $moneyType)
    {
        // 所有的减钱操作暂不支持异步执行。 有扣负风险, 出资账户需单独白名单处理
        if ($moneyType == MoneyOrderEnum::OPTYPE_BALANCE && $amount < 0) {
            return false;
        }

        if ($moneyType == MoneyOrderEnum::OPTYPE_FREEZE && $amount > 0) {
            return false;
        }

        if ($moneyType == MoneyOrderEnum::OPTYPE_FREEZE_DECREASE && $amount > 0) {
            return false;
        }

        return true;
    }
}
