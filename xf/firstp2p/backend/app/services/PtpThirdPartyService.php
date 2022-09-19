<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\daos\ThirdPartyDAO;
use NCFGroup\Protos\Ptp\Enum\UserEnum;
use NCFGroup\Protos\Ptp\RequestThirdPartyInvest;
use NCFGroup\Protos\Ptp\ResponseThirdPartyInvest;
use NCFGroup\Protos\Ptp\RequestGetThirdPartyOrder;
use NCFGroup\Protos\Ptp\ResponseGetThirdPartyOrder;

/**
 * 第三方交互项目相关service
 * @uses ServiceBase
 * @package backend
 */
class PtpThirdPartyService extends ServiceBase {

    /**
     * 投资服务
     * @param \NCFGroup\Protos\Ptp\RequestThirdPartyInvest $request
     * @return \NCFGroup\Protos\Ptp\ResponseThirdPartyInvest $response
     */
    public function investService(RequestThirdPartyInvest $request) {
        //获取request参数
        $merchantId = $request->getMerchantId();
        $merchantNo = $request->getMerchantNo();
        $userId = $request->getUserId();
        $amount = $request->getAmount();
        $outOrderId = $request->getOutOrderId();
        $case = $request->getCase();

        //校验数据类型、是否为空等
        Assert::integer($merchantId, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_MERCHANTID]);
        Assert::notEmpty($merchantNo, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_MERCHANTNO]);
        Assert::integer($userId, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_USERID]);
        Assert::integer($amount, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_AMOUNT]);
        Assert::notEmpty($outOrderId, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_OUTORDERID]);
        Assert::notEmpty($case, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_CASE]);

        //处理[投资]的业务逻辑
        $message = '第三方投资冻结';
        $investData = ThirdPartyDAO::invest($merchantId, $merchantNo, $userId, $amount, $outOrderId, $case, $message);

        //组织数据
        $outOrderId = isset($investData['outOrderId']) ? $investData['outOrderId'] : $outOrderId;
        $orderStatus = isset($investData['orderStatus']) ? $investData['orderStatus'] : UserEnum::ERROR_ASYNC_NOTIFY_N;
        $respCode = isset($investData['respCode']) ? $investData['respCode'] : UserEnum::ERROR_COMMON_FAILED;
        $respMsg = isset($investData['respMsg']) ? $investData['respMsg'] : UserEnum::$ERROR_MSG[$respCode];

        //返回response对象
        $response = new ResponseThirdPartyInvest();
        $response->setOutOrderId((string)$outOrderId);
        $response->setOrderStatus((string)$orderStatus);
        $response->setRespCode((string)$respCode);
        $response->setRespMsg($respMsg);
        return $response;
    }

    /**
     * 获取投资订单列表的服务
     * @param \NCFGroup\Protos\Ptp\RequestGetThirdPartyOrder $request
     * @return \NCFGroup\Protos\Ptp\ResponseGetThirdPartyOrder $response
     */
    public function getInvestOrderListService(RequestGetThirdPartyOrder $request) {
        //获取request参数
        $merchantId = (int)$request->getMerchantId();
        $outOrderId = trim($request->getOutOrderId());
        $startTime = (int)$request->getStartTime();
        $endTime = max($startTime, (int)$request->getEndTime());
        $pageNo = max(1, (int)$request->getPageNo());
        $pageLimit = min(100, (int)$request->getPageLimit());

        //校验数据类型、是否为空等
        Assert::integer($merchantId, UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_MERCHANTID]);
        if ($merchantId <= 0) {
            Assert::notEmpty($merchantId, sprintf(UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_EMPTY], 'merchantId'));
        }

        //如果第三方订单号、开始时间戳、结束时间戳都没有传，则报错
        if (empty($outOrderId) && $startTime <= 0 && $endTime <= 0) {
            Assert::notEmpty('', sprintf(UserEnum::$ERROR_MSG[UserEnum::ERROR_THIRD_PARTY_PARAM_EMPTY_SAME], 'outOrderId|startTime|endTime'));
        }

        //获取[投资订单列表]的业务逻辑
        $investOrderList = $merchantId > 0 ? ThirdPartyDAO::getInvestOrderList($merchantId, $outOrderId, $startTime, $endTime, $pageNo, $pageLimit) : array();

        //组织数据
        $tradeCount = isset($investOrderList['tradeCount']) ? $investOrderList['tradeCount'] : 0;
        $tradeSum = isset($investOrderList['tradeSum']) ? $investOrderList['tradeSum'] : 0;
        $tradeList = isset($investOrderList['tradeList']) ? json_encode($investOrderList['tradeList']) : '[]';
        $respCode = isset($investOrderList['respCode']) ? $investOrderList['respCode'] : UserEnum::ERROR_COMMON_FAILED;
        $respMsg = isset($investOrderList['respMsg']) ? $investOrderList['respMsg'] : UserEnum::$ERROR_MSG[$respCode];

        //返回response对象
        $response = new ResponseGetThirdPartyOrder();
        $response->setTradeCount((int)$tradeCount);
        $response->setTradeSum((string)$tradeSum);
        $response->setTradeList((string)$tradeList);
        $response->setPageNo((int)$pageNo);
        $response->setRespCode((string)$respCode);
        $response->setRespMsg((string)$respMsg);
        return $response;
    }

}
