<?php
/**
 * 存管订单服务类
 *
 * 针对P2P项目标的，单标的放还款涉及的转账笔数超过2000时，理财系统底层需将放还款做批次拆分，
 * 以保证单批次提交至海口行存管系统的转账笔数小于等于2000
 */
namespace core\service\supervision;

use NCFGroup\Common\Library\Idworker;
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use libs\utils\Monitor;
use libs\db\Db;
use libs\common\WXException;
use core\enum\JobsEnum;
use core\enum\SupervisionEnum;
use core\dao\jobs\JobsModel;
use core\dao\supervision\SupervisionOrderSplitModel;
use core\service\supervision\SupervisionBaseService;
use core\service\supervision\SupervisionOrderService;

class SupervisionOrderSplitService extends SupervisionBaseService {
    // 状态映射表
    public static $statusMap = [
        SupervisionEnum::NOTICE_SUCCESS => SupervisionOrderSplitModel::ORDER_STATUS_SUCCESS,
        SupervisionEnum::NOTICE_FAILURE => SupervisionOrderSplitModel::ORDER_STATUS_FAILURE,
        SupervisionEnum::NOTICE_PROCESSING => SupervisionOrderSplitModel::ORDER_STATUS_PROCESSING,
    ];

    // 业务订单拆分配置表
    public static $bizOrderSplitMap = [
        SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPAY => [ // 还款
            'apiName' => 'dealRepay', // 存管系统接口名称
            'requestService' => '\\core\\service\\supervision\\SupervisionDealService::dealRepaySupervision', // 业务请求的Service
            'notifyService' => '\\core\\service\\supervision\\SupervisionDealService::dealRepayNotify', // 业务回调的Service
            'notifyAcceptFail' => false,
        ],
        SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPAY_WINDUP => [ // 混清还款
            'apiName' => 'dealReplaceRepay', // 存管系统接口名称
            'requestService' => '\\core\\service\\supervision\\SupervisionDealService::dealReplaceRepaySupervision', // 业务请求的Service
            'notifyService' => '\\core\\service\\supervision\\SupervisionDealService::windupRepayNotify', // 业务回调的Service
            'notifyAcceptFail' => false,
        ],

        SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPLACE_REPAY => [ // 代偿还款
            'apiName' => 'dealReplaceRepay', // 存管系统接口名称
            'requestService' => '\\core\\service\\supervision\\SupervisionDealService::dealReplaceRepaySupervision', // 业务请求的Service
            'notifyService' => '\\core\\service\\supervision\\SupervisionDealService::dealRepayNotify', // 业务回调的Service
            'notifyAcceptFail' => false,
        ],
        SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPLACE_RECHARGE_REPAY => [ // 代充值还款
            'apiName' => 'dealReplaceRechargeRepay', // 存管系统接口名称
            'requestService' => '\\core\\service\\supervision\\SupervisionDealService::dealReplaceRechargeRepaySupervision', // 业务请求的Service
            'notifyService' => '\\core\\service\\supervision\\SupervisionDealService::dealRepayNotify', // 业务回调的Service
            'notifyAcceptFail' => false,
        ],

    ];

    /**
     * 拆分存管订单
     * @param string $function 业务方法名
     * @param string $apiName 存管接口名
     * @param array $params 请求存管接口参数
     * @param int $bizType 业务类型
     * @return boolean
     */
    public function splitSupervisionOrder($function, $apiName, $params, $bizType = SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPAY) {
        try{
            if (empty($function) || empty($apiName) || empty($params)) {
                throw new WXException('ERR_PARAM');
            }
            PaymentApi::log(sprintf('%s | %s, 存管订单批量拆分|业务原始参数, function:%s, apiName:%s, params:%s', __CLASS__, __FUNCTION__, $function, $apiName, json_encode($params)));
            $this->function = $function;
            $this->params = $params;

            // 尚未配置拆分，走原来逻辑
            $serviceConfig = $this->getApi()->getGatewayApi()->getServices()->get($apiName);
            if (empty($serviceConfig) || empty($serviceConfig['orderSplit']) || empty($serviceConfig['orderSplit']['count'])
                || empty($serviceConfig['orderSplit']['listField']) || empty($serviceConfig['orderSplit']['orderIdField'])) {
                throw new WXException('ERR_ORDER_SPLIT_NOCONFIG');
            }

            // 订单单笔拆分笔数
            $splitCountKey = (int)$serviceConfig['orderSplit']['count'];
            // 主订单的[交易流水号]字段
            $orderIdKey = $serviceConfig['orderSplit']['orderIdField'];
            // 需要拆分的[列表]字段
            $splitListKey = $serviceConfig['orderSplit']['listField'];
            // 需要拆分的[特殊列表]字段
            $splitSpecialListKey = isset($serviceConfig['orderSplit']['specialListField']) ? $serviceConfig['orderSplit']['specialListField'] : '';
            // 主订单的[用户ID]字段
            $userIdKey = isset($serviceConfig['orderSplit']['userIdField']) ? $serviceConfig['orderSplit']['userIdField'] : '';
            // 主订单的[标的ID]字段
            $dealIdKey = isset($serviceConfig['orderSplit']['dealIdField']) ? $serviceConfig['orderSplit']['dealIdField'] : '';
            // 子订单里面的[金额]字段
            $subAmountKey = $serviceConfig['orderSplit']['subAmountField'];
            // 拆分后需要替换的字段-该批次总条数
            $totalNumKey = $serviceConfig['orderSplit']['totalNumField'];
            // 拆分后需要替换的字段-该批次总金额
            $totalAmountKey = $serviceConfig['orderSplit']['totalAmountField'];
            if (empty($params[$splitListKey])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 列表数据是json之后传过来的
            if (!is_array($params[$splitListKey])) {
                $params[$splitListKey] = json_decode($params[$splitListKey], true);
            }
            // 特殊列表数据是json之后传过来的
            if (isset($params[$splitSpecialListKey]) && !is_array($params[$splitSpecialListKey])) {
                $params[$splitSpecialListKey] = json_decode($params[$splitSpecialListKey], true);
            }

            $supervisionOrderSplitModel = SupervisionOrderSplitModel::instance();
            // 业务传过来的列表数量
            $paramsListCount = count($params[$splitListKey]);
            // 业务传过来的特殊列表
            $paramsSpecialList = isset($params[$splitSpecialListKey]) ? $params[$splitSpecialListKey] : [];
            // 业务传过来的特殊列表数量
            $paramsSpecialListCount = isset($params[$splitSpecialListKey]) ? count($params[$splitSpecialListKey]) : 0;
            // 业务的交易流水号
            $paramOrderId = isset($params[$orderIdKey]) ? addslashes($params[$orderIdKey]) : 0;
            // 用户ID
            $paramUserId = isset($params[$userIdKey]) ? (int)$params[$userIdKey] : 0;
            // 标的ID
            $paramDealId = isset($params[$dealIdKey]) ? (int)$params[$dealIdKey] : 0;
            // 检查该交易流水号是否已存在
            if ($supervisionOrderSplitModel->getInfoByOrderId($paramOrderId)) {
                PaymentApi::log(sprintf('%s | %s, 该业务的交易流水号已存在, orderId: %s', __CLASS__, __FUNCTION__, $paramOrderId));
                return $this->responseSuccess();
            }

            // 开启事务
            $db = Db::getInstance('firstp2p_payment');
            $db->startTrans();

            // 当传了特殊列表字段时，按单独的拆分笔数进行处理
            if (isset($params[$splitSpecialListKey]) && !empty($serviceConfig['orderSplit']['specialCount'])) {
                $splitCountKey = (int)$serviceConfig['orderSplit']['specialCount'];
            }

            $loopCount = ceil($paramsListCount / $splitCountKey);
            for ($page=1; $page<=$loopCount; $page++) {
                // 新的参数列表
                $newParams = $params;
                $newParams[$orderIdKey] = $outOrderId = Idworker::instance()->getId();
                // 业务参数小于配置的拆分数量时
                if ($paramsListCount <= $splitCountKey) {
                    $tmpList = $params[$splitListKey];
                }else{
                    // 拆分列表
                    $tmpList = array_slice($params[$splitListKey], ($page-1)*$splitCountKey, $splitCountKey);
                    // 计算该批次总条数、该批次总金额
                    if (!empty($tmpList)) {
                        $newParams[$totalNumKey] = $newParams[$totalAmountKey] = 0;
                        foreach ($tmpList as $tmpItem) {
                            $newParams[$totalAmountKey] += isset($tmpItem[$subAmountKey]) ? (int)$tmpItem[$subAmountKey] : 0;
                            ++$newParams[$totalNumKey];
                        }
                    }
                }
                $newParams[$splitListKey] = json_encode($tmpList);

                // 拆分特殊列表字段
                if (isset($params[$splitSpecialListKey])) {
                    $tmpList = array_splice($paramsSpecialList, 0, $splitCountKey);
                    $newParams[$splitSpecialListKey] = json_encode($tmpList);
                    // 记录日志
                    PaymentApi::log(sprintf('%s | %s, 存管订单批量拆分-特殊列表拆分|orderId:%s, outOrderId:%s, userId:%d, 拆分条数:%d, 拆分参数:%s', __CLASS__, __FUNCTION__, $paramOrderId, $outOrderId, $paramUserId, $splitCountKey, $newParams[$splitSpecialListKey]));
                }

                // 新增存管订单拆分数据
                $splitRet = $supervisionOrderSplitModel->addOrderSplit($outOrderId, $params[$orderIdKey], $bizType, $paramUserId, $paramDealId, SupervisionOrderSplitModel::ORDER_STATUS_WAITING, $newParams[$totalNumKey], $newParams[$totalAmountKey]);
                if (false === $splitRet) {
                    throw new WXException('ERR_ADD_ORDER_SPLIT');
                }

                // 异步请求业务/存管接口
                $jobsId = $this->asyncOrderSplitRequest('\core\service\supervision\SupervisionOrderSplitService::requestSupervisionApi', $function, $apiName, $newParams);
                // 更新该订单的jobsId
                $supervisionOrderSplitModel->updateOrderSplitData($outOrderId, ['jobsId'=>$jobsId]);
                // 记录日志
                PaymentApi::log(sprintf('%s | %s, 存管订单批量拆分|插入Jobs异步请求存管接口, orderId:%s, outOrderId:%s, userId:%d, jobsId:%d, 请求参数:%s', __CLASS__, __FUNCTION__, $paramOrderId, $outOrderId, $paramUserId, $jobsId, json_encode($newParams)));

                // 记录拆分后的订单号
                $supervisionOrderSplitModel->addOrderIdCache($params[$orderIdKey], $outOrderId);
            }
            // 提交事务
            $db->commit();

            return $this->responseSuccess();
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            PaymentApi::log(sprintf('%s | %s, 存管订单批量拆分异常|业务方法名:%s，存管接口名:%s，参数:%s，异常内容:%s', __CLASS__, __FUNCTION__, $function, $apiName, json_encode($params), $e->getMessage()));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管订单批量拆分|业务方法名:%s，存管接口名:%s，参数:%s，异常内容:%s', $function, $apiName, json_encode($params), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ORDERSPLIT');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 拆分特殊列表逻辑
     * @param array $splitSpecialList 特殊列表
     * @param int $paramsSpecialListCount 特殊列表数量
     * @param int $splitCountKey 拆分数量
     * @param int $paramOrderId 业务订单号
     * @return string
     */
    private static function _splitSpecialList(&$newParams, &$splitSpecialList, $paramsSpecialListCount, $splitCountKey, $paramOrderId = 0) {
        if (empty($splitSpecialList)) {
            return ;
        }
        $tmpList = array_splice($splitSpecialList, 0, $splitCountKey);
        $newParams[$splitSpecialListKey] = json_encode($tmpList);
        // 记录日志
        PaymentApi::log(sprintf('%s | %s, 存管订单批量拆分-特殊列表拆分|orderId:%s, 拆分条数:%d, 拆分参数:%s', __CLASS__, __FUNCTION__, $paramOrderId, $paramsSpecialListCount, json_encode($tmpList)));
        return ;
    }

    /**
     * 请求存管接口
     * @param string $function
     * @param string $apiName
     * @param array $params
     * @throws WXException
     */
    public function requestSupervisionApi($function, $apiName, $params) {
        try{
            if (empty($function) || empty($apiName) || empty($params)) {
                throw new WXException('ERR_PARAM');
            }

            // 尚未配置拆分，走原来逻辑
            $serviceConfig = $this->getApi()->getGatewayApi()->getServices()->get($apiName);
            if (empty($serviceConfig) || empty($serviceConfig['orderSplit']) || empty($serviceConfig['orderSplit']['orderIdField'])) {
                throw new WXException('ERR_ORDER_SPLIT_NOCONFIG');
            }

            // 主订单的[交易流水号]字段
            $orderIdKey = $serviceConfig['orderSplit']['orderIdField'];
            // 跟存管系统交互的交易流水号
            $outOrderId = isset($params[$orderIdKey]) ? addslashes($params[$orderIdKey]) : 0;

            // 检查该交易流水号是否已存在
            $supervisionOrderSplitModel = SupervisionOrderSplitModel::instance();
            $orderSplitInfo = $supervisionOrderSplitModel->getInfoByOutOrderId($outOrderId);
            if (!empty($orderSplitInfo) && in_array($orderSplitInfo['order_status'], [SupervisionOrderSplitModel::ORDER_STATUS_SUCCESS, SupervisionOrderSplitModel::ORDER_STATUS_FAILURE])) {
                PaymentApi::log(sprintf('%s | %s, 异步请求存管拆分成功|该存管的交易流水号已处理完毕, 业务方法名:%s，存管接口名:%s，outOrderId:%s，参数:%s', __CLASS__, __FUNCTION__, $function, $apiName, $outOrderId, json_encode($params)));
                return true;
            }

            // 请求存管接口
            $newResult = $this->supervisionApiRequest($function, $params);
            // 更新该订单的状态及存管接口返回数据
            $supervisionOrderSplitModel->updateOrderSplitData($outOrderId, ['orderStatus'=>SupervisionOrderSplitModel::ORDER_STATUS_PROCESSING, 'memo'=>$newResult['respCode'].'|'.$newResult['respMsg']]);
            // 存管接口返回失败后，jobs任务需要继续请求
            if (!isset($newResult['status']) || $newResult['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                throw new WXException('ERR_ORDER_SPLIT_SUPERVISION');
            }

            PaymentApi::log(sprintf('%s | %s, 异步请求存管拆分成功|业务方法名:%s，存管接口名:%s，outOrderId:%s，存管接口返回结果:%s', __CLASS__, __FUNCTION__, $function, $apiName, $outOrderId, json_encode($newResult)));
            return true;
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, 异步请求存管拆分异常|业务方法名:%s，存管接口名:%s，参数:%s，异常内容:%s', __CLASS__, __FUNCTION__, $function, $apiName, json_encode($params), $e->getMessage()));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('异步请求存管拆分|业务方法名:%s，存管接口名:%s，outOrderId:%s，异常内容:%s', $function, $apiName, $outOrderId, $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ORDERSPLIT_REQUEST');
            return false;
        }
    }

    /**
     * 存管订单拆分-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     merchantId:商户号
     *     orderId:外部订单号
     *     status:订单处理状态(S-成功；F-失败)
     *     remark:备注
     */
    public function supervisionOrderSplitNotify($responseData) {
        try{
            $isRemoveCache = false;
            if (empty($responseData['orderId']) || empty($responseData['status']) || empty(self::$statusMap[$responseData['status']])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $outOrderId = $responseData['orderId'];
            $responseOrderStatus = $responseData['status'];
            $responseRemark = $responseData['remark'];
            // 根据存管回调的外部订单号幂等处理
            $supervisionOrderSplitModel = SupervisionOrderSplitModel::instance();
            $orderSplitInfo = $supervisionOrderSplitModel->getInfoByOutOrderId($outOrderId);
            if (empty($orderSplitInfo)) {
                throw new WXException('ERR_ORDER_NOT_EXIST');
            }
            // 订单拆分业务尚未配置
            if (empty(self::$bizOrderSplitMap[$orderSplitInfo['biz_type']])) {
                throw new WXException('ERR_ORDER_SPLIT_NOCONFIG');
            }
            // 回调是否接受失败状态
            $bizOrderMap = self::$bizOrderSplitMap[$orderSplitInfo['biz_type']];
            if (false === $bizOrderMap['notifyAcceptFail'] && $responseOrderStatus !== SupervisionEnum::RESPONSE_SUCCESS) {
                throw new WXException('ERR_ORDER_SPLIT_NOTIFY');
            }

            // 订单已处理完毕
            if (in_array($orderSplitInfo['order_status'], [SupervisionOrderSplitModel::ORDER_STATUS_SUCCESS, SupervisionOrderSplitModel::ORDER_STATUS_FAILURE])) {
                PaymentApi::log(sprintf('%s | %s, 存管订单拆分异步回调|该存管的交易流水号已处理完毕, outOrderId:%s', __CLASS__, __FUNCTION__, $outOrderId));
                return $this->responseSuccess();
            }

            // 检查该外部订单号，是否合法
            $isExist = $supervisionOrderSplitModel->isExistOrderIdCache($orderSplitInfo['order_id'], $outOrderId);
            if (false === $isExist) {
                $orderSplitCache = $supervisionOrderSplitModel->sMembersOrderIdCache($orderSplitInfo['order_id']);
                PaymentApi::log(sprintf('%s | %s, 存管订单拆分异步回调|该存管的交易流水号不存在, outOrderId:%s, 业务订单号:%s, 当前的外部订单号列表:%s', __CLASS__, __FUNCTION__, $outOrderId, $orderSplitInfo['order_id'], json_encode($orderSplitCache)));
                throw new WXException('ERR_ORDER_SPLIT_NOEXIST');
            }

            // 从redis移除该外部订单号
            $sRemRet = $supervisionOrderSplitModel->sRemOrderIdCache($orderSplitInfo['order_id'], $outOrderId);
            if (!$sRemRet) {
                throw new WXException('ERR_ORDER_SPLIT_SREM');
            }

            $isRemoveCache = true;
            $db = Db::getInstance('firstp2p_payment');
            $db->startTrans();

            // 更新该订单的状态
            $updateRet = $supervisionOrderSplitModel->updateOrderSplitStatus($outOrderId, self::$statusMap[$responseOrderStatus], $responseRemark);
            if (false === $updateRet) {
                throw new WXException('ERR_ORDER_SPLIT_UPDATE');
            }

            // 获取缓存中的外部订单号列表，只有列表为空时才表示已经回调完毕，通知业务
            $orderSplitCache = $supervisionOrderSplitModel->sMembersOrderIdCache($orderSplitInfo['order_id']);
            if (empty($orderSplitCache)) {
                // 请求具体业务的回调接口
                $requestResult = $this->supervisionApiRequest($bizOrderMap['notifyService'], ['orderId'=>$orderSplitInfo['order_id'], 'status'=>$responseOrderStatus]);
                // 存管接口返回失败后，jobs任务需要继续请求
                if (!isset($requestResult['status']) || $requestResult['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                    throw new WXException('ERR_ORDER_SPLIT_SUPERVISION');
                }
            }

            // 异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($outOrderId, $responseOrderStatus);

            $db->commit();
            PaymentApi::log(sprintf('%s | %s, 存管订单拆分异步回调成功|outOrderId:%s，orderId:%s，拆分订单更新状态:%d，存管回调参数:%s，redis缓存剩余数据:%s', __CLASS__, __FUNCTION__, $responseData['orderId'], $orderSplitInfo['order_id'], (int)$updateRet, json_encode($responseData), json_encode($orderSplitCache)));
            return $this->responseSuccess();
        } catch(\Exception $e) {
            isset($db) && $db->rollback();
            // 操作失败后，需要把外部订单号重新写入redis
            if ($isRemoveCache && !empty($orderSplitInfo['order_id']) && !empty($responseData['orderId'])) {
                $supervisionOrderSplitModel->addOrderIdCache($orderSplitInfo['order_id'], $responseData['orderId']);
            }
            PaymentApi::log(sprintf('%s | %s, 存管订单拆分异步回调异常|outOrderId:%s，存管回调参数:%s，异常内容:%s', __CLASS__, __FUNCTION__, $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管订单拆分异步回调异常|outOrderId:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ORDERSPLITCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 同步请求存管接口
     * @throws \Exception
     * @throws Exception
     * @return boolean
     */
    public function supervisionApiRequest($function = '', $params = []) {
        try {
            empty($function) && $function = $this->function;
            if (empty($function)) {
                throw new WXException('ERR_PARAM');
            }
            empty($params) && $params = $this->params;

            $functionTmp = explode('::', $function);
            if (count($functionTmp) == 1) {
                return call_user_func_array($function, $params);
            } elseif (count($functionTmp) == 2) {
                $classFunc = array(new $functionTmp[0], $functionTmp[1]);
                return call_user_func_array($classFunc, [$params]);
            } else {
                throw new WXException('ERR_SERVICE');
            }
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 异步请求业务/存管接口
     * @throws \Exception
     * @param string $service 订单拆分服务名
     * @param string $function 业务方法名
     * @param string $apiName 存管接口名
     * @param array $params 请求存管接口参数
     * @param int $retryCnt Jobs重试次数
     * @return boolean
     */
    public function asyncOrderSplitRequest($service, $function, $apiName, $params, $retryCnt = 5) {
        $jobsModel = new JobsModel();
        $jobsModel->priority = JobsEnum::PRIORITY_ORDERSPLIT_REQUEST;
        $r = $jobsModel->addJob($service, array($function, $apiName, $params), false, $retryCnt);
        if ($r === false) {
            throw new WXException('ERR_ASYNC_ORDERSPLIT_JOB');
        }
        return $jobsModel->db->insert_id();
    }
}
