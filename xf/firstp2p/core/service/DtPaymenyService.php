<?php
namespace core\service;

use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\common\WXException;
use core\service\SupervisionBaseService; // 存管资金相关服务
use core\service\DtDepositoryService;
use core\service\SupervisionOrderService; // 存管订单相关服务
use core\service\SupervisionFinanceService; // 存管资金相关服务

/**
 * 智多鑫-存管相关服务
 *
 */
class DtPaymenyService extends SupervisionBaseService {
    //忽略请求异常，默认不忽略
    public $ignoreReqExc = false;

    /**
     * 生成[1.1.1、1.1.2预约冻结（PC端WEB/手机端H5）]表单
     * @param array $params 传入参数
     * @return array 输出结果
     * @param boolean $returnForm 是否返回formHtml，而不是直接跳转到页面
     */
    public function bookfreezeCreatePage($params, $platform = 'pc', $returnForm = true, $formId = 'bookfreezeCreateForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $service = $platform === 'pc' ? 'webBookfreezeCreate' : 'h5BookfreezeCreate';
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/bookfreezeCreateNotify';

            //异步添加订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DT_BID, $params);

            // 请求接口
            if ($returnForm) {
                $result = $this->api->getForm($service, $params, $formId, $targetNew);
                return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
            }else{
                $result = $this->api->getRequestUrl($service, $params);
                return $this->responseSuccess(['url' => $result]);
            }
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.3预约冻结(API)-接口
     * @param $params 参数列表
     * @return array
     */
    public function bookfreezeCreate($params) {
        try {
            //异步添加订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DT_BID, $params);

            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/bookfreezeCreateNotify';
            // 请求接口
            $result = $this->api->request('bookfreezeCreate', $params);

            if (!isset($result['respCode'])) {
                throw new WXException('ERR_REQUEST_TIMEOUT');
            }else if (isset($result['respSubCode']) && $result['respSubCode'] === '200126') {
                throw new WXException('ERR_INVEST_SUBORDER_EXIST');
            }else if ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== '200103') {
                throw new WXException('ERR_DT_BOOKCREATE_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.3预约冻结-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     userId:P2P用户ID
     *     status:状态(S-成功；F-失败)
     *     orderId:请求流水号
     *     freezeAccountAmount:冻结账户金额，单位（分）
     *     freezeType:冻结类型（01-预约投资）
     *     failReason:失败原因(非必传)
     *     remark:备注(非必传)
     */
    public function bookfreezeCreateNotify($responseData) {
        try {
            if (empty($responseData['userId']) || empty($responseData['status']) || empty($responseData['orderId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $responseData['status']);

            // 处理预约冻结的逻辑@TODO
            $service = new \core\service\duotou\DtP2pDealBidService();
            if($service->getBidLock($responseData['orderId']) === '0'){
                throw new WXException('ERR_DT_BID_LOCK_ERROR');
            }
            Logger::info("bookfreezeCreateNotify 获得投资锁");
            $service->dealBidCallBack($responseData['orderId'],$responseData['status']);
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.4取消预约冻结(API)-接口
     * @param $params 参数列表
     * @return array
     *
     */
    public function bookfreezeCancel($params) {
        try {
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/bookfreezeCancelNotify';
            // 异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DT_REDEEM, $params);
            // 请求接口
            $result = $this->api->request('bookfreezeCancel', $params);
            if (!isset($result['respCode']) || ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionFinanceService::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_DT_BOOKCANCEL_FAILED');
            }

            // 商户流水已存在的处理 Edit At 20180504 15:22
            if ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] == SupervisionFinanceService::CODE_ORDER_EXIST) {
                $supervisionFinanceService = new SupervisionFinanceService();
                $orderResult = $supervisionFinanceService->orderSearch($params['orderId']);
                // 没响应或者处理失败，则返回失败
                if (!isset($orderResult['respCode']) || $orderResult['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                    throw new WXException('ERR_DT_BOOKCANCEL_FAILED');
                }
                if (!isset($orderResult['data']['status']) || $orderResult['data']['status'] != self::RESPONSE_SUCCESS) {
                    throw new WXException('ERR_DT_BOOKCANCEL_FAILED');
                }
            }

            //异步更新存管订单
            $supervisionOrderService->asyncUpdateOrder($params['orderId'], SupervisionOrderService::NOTICE_SUCCESS);
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.4取消预约冻结-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     userId:P2P用户ID
     *     status:状态(S-成功；F-失败)
     *     orderId:请求流水号
     *     amount:解冻金额，单位（分）
     *     unFreezeType:解冻类型（01-预约投资）
     *     feeUserId:手续费账户ID(非必传)
     *     feeAmount:收费金额，单位（分）(非必传)
     *     remark:备注(非必传)
     *     failReason:失败原因(非必传)
     */
    public function bookfreezeCancelNotify($responseData) {
        try {
            if (empty($responseData['userId']) || empty($responseData['status']) || empty($responseData['orderId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 处理取消预约冻结的逻辑@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.5预约批量投资（API）-接口
     * @param $params 参数列表
     * @return array
     */
    public function bookInvestBatchCreate($params) {
        try {
            // 外部业务订单号、还款订单集合
            if (empty($params['orderId']) || empty($params['totalAmount'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/bookInvestBatchCreateNotify';
            //异步添加订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DT_BATCH_BID, $params);

            // 请求接口
            $result = $this->api->request('bookInvestBatchCreate', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== '200103') {
                throw new WXException('ERR_DT_BOOKINVESTBATCHCREATE_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.5预约批量投资-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:请求流水号
     *     resultList:子订单集合
     *         subOrderId:子订单号
     *         status:状态(S-成功；F-失败)
     *         failReason:失败原因(非必传)
     *     remark:备注(非必传)
     */
    public function bookInvestBatchCreateNotify($responseData) {
        try {
            $service = new DtDepositoryService();
            if (empty($responseData['resultList'])) {
                throw new WXException('ERR_PARAM');
            }

            foreach ($responseData['resultList'] as $resultData) {
                // 不处理状态为F、I的订单
                if ($resultData['status'] !== self::RESPONSE_SUCCESS) {
                    PaymentApi::log(sprintf('%s | %s, 智多鑫-预约批量投资回调, 该子订单状态不是成功的不处理, resultData：%s', __CLASS__, __FUNCTION__, json_encode($resultData)));
                    continue;
                }

                //异步更新存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncUpdateOrder($resultData['subOrderId'], $resultData['status']);

                $service->dtBidCallBack($resultData['subOrderId'], $resultData['status']);
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, 智多鑫-预约批量投资回调处理失败, errorMsg：%s(%d)', __CLASS__, __FUNCTION__, $e->getMessage(), $e->getCode()));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.6取消预约冻结(API)-接口
     * @param $params 参数列表
     * @return array
     */
    public function bookInvestCancel($params) {
        try {
            // 请求接口
            $result = $this->api->request('bookInvestCancel', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DT_BOOKINVESTCANCEL_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.7批量债权转让投资(API)-接口
     * @param $params 参数列表
     * @return array
     */
    public function bookCreditBatch($params) {
        try {
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/bookCreditBatchNotify';
            // 请求接口
            $result = $this->api->request('bookCreditBatch', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DT_BOOKCREDITBATCH_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.7批量债权转让投资(API)-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:请求流水号
     *     resultList:子订单集合
     *         subOrderId:子订单号
     *         status:状态(S-成功；F-失败)
     *         failReason:失败原因(非必传)
     *     remark:备注(非必传)
     */
    public function bookCreditBatchNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['resultList'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 处理批量债权转让投资的逻辑@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.8取消债权转让(API)-接口
     * @param $params 参数列表
     * @return array
     */
    public function bookCreditCancel($params) {
        try {
            // 请求接口
            $result = $this->api->request('bookCreditCancel', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DT_BOOKCREDITCANCEL_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.9批量标的债权转让(API)-接口
     * @param $params 参数列表
     * @return array
     */
    public function creditAssignmentBatchGrant($params) {
        try {
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/creditAssignBatchGrantNotify';
            // 异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DT_CREDITASSIGNGRANT, $params);

            // 请求接口
            $result = $this->api->request('creditAssignmentBatchGrant', $params);
            // 200126商户子订单流水已存在，200103商户流水已存在 做幂等处理
            if ( !isset($result['respCode']) || ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && !in_array($result['respSubCode'], ['200103', '200126'])) ) {
                throw new WXException('ERR_DT_CREDITASSIGNGRANT_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.9批量标的债权转让(API)-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:请求流水号
     *     amount:金额 单位分
     *     status:状态(S-成功；F-失败)
     *     failReason:失败原因(非必传)
     *     remark:备注(非必传)
     */
    public function creditAssignBatchGrantNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['amount']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            /**
             * 支付不允许自己接自己债权，所以返回特定的前缀来区分，理财在碰到这个前缀就当成是成功了。哈哈--是不是很坑爹
             */
            if($responseData['status'] == self::RESPONSE_FAILURE && substr(trim($responseData['failReason']),0,6) == '200233'){
                $responseData['status'] = self::RESPONSE_SUCCESS;
            }

            // 处理批量标的债权转让的逻辑
            $service = new DtDepositoryService();
            $service->dtTransBondCallBack($responseData['orderId'],$responseData['amount'],$responseData['status']);

            // 异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $responseData['status']);

            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 1.1.10取消债转投资(API)-接口
     * @param $params 参数列表
     * @return array
     */
    public function creditAssignmentCancel($params) {
        try {
            // 请求接口
            $result = $this->api->request('creditAssignmentCancel', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DT_CREDITASSIGNCANCEL_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }
}
