<?php
namespace core\service\supervision;

use NCFGroup\Common\Library\Idworker;
use libs\db\Db;
use libs\utils\Logger;
use libs\utils\Curl;
use libs\common\ErrCode;
use libs\common\WXException;
use libs\utils\Alarm;
use libs\utils\Monitor;
use core\enum\SupervisionEnum;
use core\enum\AccountEnum;
use core\dao\supervision\SupervisionOrderSplitModel;
use core\dao\repay\DealLoanRepayModel;
use core\dao\deal\DealModel;
use core\service\supervision\SupervisionService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionOrderService;
use core\service\supervision\SupervisionFinanceService;
use core\service\supervision\SupervisionOrderSplitService;
use core\service\deal\P2pDealBidService;
use core\service\deal\P2pDealGrantService;
use core\service\deal\P2pDealCancelService;
use core\service\repay\P2pDealRepayService;

/**
 * P2P存管-标的相关服务
 *
 */
class SupervisionDealService extends SupervisionBaseService {

    /**
     * 投资成功页面弹出快速投资服务的次数
     * @var int
     */
    private static $alertQuickBidCount = 0;

    /**
     * 记录弹出快速投资服务次数的缓存key
     * @var string
     */
    const KEY_DEAL_QUICKBID = 'supervision_deal_quickbid_%s';

    /**
     * 标的报备
     * @param array $params 参数列表
     * @return array
     */
    public function dealCreate($params) {
        try {
            // 受托支付
            if (!empty($params['isEntrustedPay']) && $params['isEntrustedPay'] == 1) {
                if (empty($params['bankCardNO']) || empty($params['bankCode']) || empty($params['cardName'])
                || empty($params['cardFlag'])) {
                    throw new WXException('ERR_PARAM_LOSE');
                }
                // cardFlag:卡标识(1为对公,2为对私)、issuer:联行号
                if (!empty($params['cardFlag']) && $params['cardFlag'] == SupervisionEnum::CARD_FLAG_PUB && empty($params['issuer'])) {
                    throw new WXException('ERR_PARAM_LOSE');
                }
            }

            // 请求接口
            $result = $this->api->request('dealCreate', $params);
            // 存管系统返回标的已存在
            if (isset($result['respSubCode']) && $result['respSubCode'] === '200107') {
                return $this->responseSuccess();
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的报备|标的ID:%d，标的名称:%s，异常内容:%s', $params['bidId'], $params['name'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALCREATE');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 标的报备/更新-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     merchantId:商户号
     *     bidId:标的ID
     *     bidStatus:标的状态(S-成功；F-失败)
     *     bankAuditStatus:银行审核状态(S-成功；F-失败)
     */
    public function dealReportNotify($responseData) {
        try {
            if (empty($responseData['bidId']) || empty($responseData['bidStatus']) || empty($responseData['bankAuditStatus'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 报备失败，不处理
            if ($responseData['bidStatus'] !== SupervisionEnum::RESPONSE_SUCCESS || $responseData['bankAuditStatus'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 更新标的报备/更新的状态
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 标的混清还款-批量
     * @param array $params
     */
    public function windupRepay($params) {
        $orderSplitService = new SupervisionOrderSplitService();
        // 业务类型-标的还款
        $bizType = SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPAY_WINDUP;
        return $orderSplitService->splitSupervisionOrder(
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['requestService'],
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['apiName'],
            $params,
            $bizType
        );

    }

    /**
     * 标的还款-批量
     * @param array $params
     */
    public function dealRepay($params) {
        $orderSplitService = new SupervisionOrderSplitService();
        // 业务类型-标的还款
        $bizType = SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPAY;
        return $orderSplitService->splitSupervisionOrder(
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['requestService'],
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['apiName'],
            $params,
            $bizType
        );
    }

    /**
     * 标的还款
     * @param array $params 参数列表
     * @return array
     */
    public function dealRepaySupervision($params) {
        try {
            // 外部业务订单号、还款订单集合
            if (empty($params['repayOrderList'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DEAL_REPAY, $params);

            // 请求接口，标的请求重发并处理流水单号幂等
            $result = $this->dealRequest($this->api, 'dealRepay', $params, $params['orderId'], SupervisionEnum::BATCHORDER_TYPE_REPAY, 'ERR_DEAL_REPAY', '200103');
            if (!empty($result['batchOrderStatus'])) {
                if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    return $this->responseSuccess();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_PROCESSING) {
                    return $this->responseProsessing();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_FAILURE) {
                    throw new WXException('ERR_DEAL_REPAY');
                }
            }

            // 通过Jobs记录[外部订单号]到order表@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的还款|标的ID:%d，订单号:%s，异常内容:%s', $params['bidId'], $params['orderId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALREPAY');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }


    public function windupRepayNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 更新标的还款状态@TODO
            // 检查是否已经处理
            $orderId = $responseData['orderId'] ?: "";
            $db = Db::getInstance('firstp2p', 'master');
            $sql = "SELECT * FROM firstp2p_windup_detail WHERE repay_batch_id = '{$orderId}' AND pay_status NOT IN (1,2)";
            $data = $db->getAll($sql);
            // 如果没有数据或者已经终态
            if (empty($data)) {
                return $this->responseSuccess();
            }

            $db->startTrans();

            $timestamp = get_gmtime();
            $datetime = time();
            // 如果还款失败
            if ($responseData['status'] == 'F') {
                $batchState = 7;

                foreach ($data as $repayItem) {
                    // 解冻借款人冻结还款总金额
                    $freezeSql = "UPDATE firstp2p_account SET lock_money = lock_money - {$repayItem['amount']}, money = money + {$repayItem['amount']} WHERE id = '{$repayItem['pay_user_id']}'";
                    //var_dump($freezeSql);
                    $db->query($freezeSql);

                    $this->checkAffectRow($db, 'ERR_DEAL_REPAY');
                    // 标记混清明细数据状态失败
                    $windupSql = "UPDATE firstp2p_windup_detail SET pay_status = 2, update_time = '{$timestamp}' WHERE id = {$repayItem['id']}";
                    $db->query($windupSql);
                    $this->checkAffectRow($db, 'ERR_DEAL_REPAY');
                }

            } else if ($responseData['status'] == 'S') {
                $batchState = 6;
                // 如果还款成功
                foreach ($data as $repayItem) {
                    $amount = bcdiv($repayItem['amount'], 100, 2);
                    // 因为是彻底还清，因此涉及的此次批次号的数据的标的和用户，所有的未还本金应该为已还状态
                    // 投资用户回款表状态修改
                    $list = DealLoanRepayModel::instance()->getDealUnpaiedPrincipalByDealIdAndUserId($repayItem['deal_id'], $repayItem['user_id']);
                    if (is_array($list)) {
                        foreach ($list as $item) {
                            // 收集写入sql
                            $sql = "UPDATE firstp2p_deal_loan_repay SET `status`= 1, update_time = '{$timestamp}' WHERE id = '{$item['id']}'";
                            $db->query($sql);
                            $this->checkAffectRow($db, 'ERR_DEAL_REPAY');
                        }
                    }

                    // 投资人回款计划修改
                    $list = DealLoanRepayModel::instance()->getDealUnpaiedPrincipalByDealIdAndUserId($repayItem['deal_id'], $repayItem['user_id']);
                    $dealRepayLog = [];
                    if (is_array($list)) {
                        foreach ($list as $item) {
                            if (empty($dealRepayLog[$item['deal_repay_id']])) {
                                $dealRepayLog[$item['deal_repay_id']] = 0;
                            }
                            // 收集写入sql
                            $sql = "UPDATE firstp2p_deal_loan_repay SET `status`= 1, update_time = '{$timestamp}' WHERE id = '{$item['id']}'";
                            $db->query($sql);
                            $this->checkAffectRow($db, 'ERR_DEAL_REPAY');
                            // 以还款计划维度统计每期还款计划金额，用于更新投资人的还款计划表，如果待还本金清零，也不执行还清操作。
                            $dealRepayLog[$item['deal_repay_id']] = bcadd($dealRepayLog[$item['deal_repay_id']], $item['money'], 2);
                        }
                    }
                    // 借款人还款计划修改
                    if (!empty($dealRepayLog)) {
                        foreach ($dealRepayLog as $dealRepayId => $money) {
                            $sql = "UPDATE firstp2p_deal_repay SET principal = principal - '{$money}', repay_money = repay_money - '{$money}' WHERE id = '{$dealRepayId}'";
                            $db->query($sql);
                            $this->checkAffectRow($db, 'ERR_DEAL_REPAY');
                        }
                    }


                    // 代偿还款用户资金记录生成和冻结资金扣减
                    AccountService::changeMoney($repayItem['pay_user_id'], $amount, '还本', $repayItem['deal_id'], AccountEnum::MONEY_TYPE_LOCK_REDUCE);

                    //投资人用户资金记录
                    AccountService::changeMoney($repayItem['user_id'], $amount, '本金', $repayItem['deal_id'], AccountEnum::MONEY_TYPE_INCR);
                    // 投资人资产信息
                    $db->query("UPDATE firstp2p_user_loan_repay_statistics SET load_repay_money = load_repay_money +  '{$amount}', norepay_principal = norepay_principal - '{$amount}' WHERE user_id = '{$repayItem['user_id']}'");
                    $this->checkAffectRow($db, 'ERR_DEAL_REPAY');

                    // 回款日历

                    // 混清明细状态
                    $db->query("UPDATE firstp2p_windup_detail SET pay_status = '1', update_time = '{$datetime}' WHERE id = '{$repayItem['id']}'");
                    $this->checkAffectRow($db, 'ERR_DEAL_REPAY');


                    // 标的状态为已还清状态作为悲观锁
                    //$dealInfo = DealModel::instance()->find($repayItem['deal_id']);
                    //$dealUpdateSQL = "UPDATE firstp2p_deal SET deal_status = '5' WHERE id = '{$repayItem['deal_id']}' AND deal_status = '4'";
                    //$db->query($dealUpdateSQL);
                    //$this->checkAffectRow($db, 'ERR_DEAL_REPAY');
                }
                // 混清批次状态
                $db->query("UPDATE firstp2p_windup SET state= '{$batchState}', update_time = '{$datetime}' WHERE out_order_id = '{$repayItem['batch_id']}'");
                $this->checkAffectRow($db, 'ERR_DEAL_REPAY');

            }
            $db->commit();
            return $this->responseSuccess();
        } catch(\Exception $e) {
            $db->rollback();
            // 记录告警
            //Alarm::push('supervision', __METHOD__, sprintf('存管标的还款回调|订单号:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            var_dump($e->getMessage());
            //Monitor::add('SUPERVISION_DEALREPAYCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 标的还款-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     merchantId:商户号
     *       orderId:外部订单号
     *     status:订单处理状态(S-成功；F-失败)
     *     remark:备注
     */
    public function dealRepayNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 更新标的还款状态@TODO
            $s = new P2pDealRepayService();
            $s->dealRepayCallBack($responseData['orderId'],$responseData['status']);

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的还款回调|订单号:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALREPAYCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 标的更新
     * @param array $params 参数列表
     * @return array
     */
    public function dealUpdate($params) {
        try {
            // 请求接口
            $result = $this->api->request('dealUpdate', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DEAL_UPDATE');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 标的查询
     * @param array $dealId 标的id
     * @return array
     */
    public function dealSearch($dealId) {
        try {
            $params = array('bidId' => $dealId);
            // 请求接口
            $result = $this->api->request('dealSearch', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DEAL_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 投资验密
     * @param array $params 参数列表
     * @return array
     */
    public function investCreateSecret($params, $platform = 'pc', $formId = 'registerForm', $targetNew = true) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM_PLATFORM');
            }

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_INVEST_CREATE, $params);

            // 请求接口
            $service = $platform === 'pc' ? 'investCreateSecret' : 'h5InvestCreateSecret';
            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 投资-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:外部订单号
     *     amount:金额
     *     status:订单处理状态 S-成功 F-失败
     */
    public function investCreateNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['amount']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            $biddealService = new P2pDealBidService();
            if ('0' === $biddealService->getBidLock($responseData['orderId'])) {
                Logger::error(__FILE__.' '.__LINE__.' '.$responseData['orderId'].' bid callback Synchronous lock fail');
                throw new \Exception("get lock fail",-99999);
            }

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $responseData['status']);


            //投资逻辑
            $bidService = new P2pDealBidService();
            $bidRes = $bidService->bankBidCallBack($responseData['orderId'], $responseData['status']);
            if(!$bidRes){
                throw new \Exception("投资失败");
            }
            //TODO 资产中心

            return $this->responseSuccess();
        } catch(\Exception $e) {

            $errCode = $e->getCode();
            if ($errCode == -99999){
                $errCode = 0;
            }else{
                // 记录告警
                Alarm::push('supervision', __METHOD__, sprintf('存管标的投资回调|订单号:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            }

            // 添加监控
            Monitor::add('SUPERVISION_DEALINVESTCREATECALLBACK');
            return $this->responseFailure($errCode, $e->getMessage());
        }
    }

    /**
     * 免密投资
     * @param array $params 参数列表
     * @return array
     */
    public function investCreate($params) {
        try {
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_INVEST_CREATE, $params);

            // 请求接口
            $result = $this->investRequest($this->api, 'investCreate', $params, $params['orderId'], 'ERR_INVEST_CREATE', 'ERR_INVEST_SUBORDER_EXIST');

            //异步更新存管订单
            $supervisionOrderService->asyncUpdateOrder($params['orderId'], SupervisionEnum::NOTICE_SUCCESS);

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管免密投资|订单号:%s，用户ID:%s，异常内容:%s', $params['orderId'], $params['userId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALINVESTCREATE');
            // 如果是网络超时或红包子单已存在 或 查单失败，则继续抛出异常
            if (in_array($e->getCode(), [ErrCode::getCode('ERR_REQUEST_TIMEOUT'), ErrCode::getCode('ERR_INVEST_SUBORDER_EXIST'), ErrCode::getCode('ERR_INVEST_ORDER_SEARCH')])) {
                throw $e;
            }
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 投资取消
     * @param array $params 参数列表
     * @return array
     */
    public function investCancel($params) {
        try {
            // 请求接口
            $result = $this->api->request('investCancel', $params);
            // 存管系统返回单号已存在(原单非投资单)
            if (isset($result['respSubCode'])) {
                if ($result['respSubCode'] === '200005') {
                    //throw new WXException('ERR_INVEST_NO_EXIST');
                    return $this->responseSuccess(); // 投资取消如果存管没有这个订单认为取消成功
                }else if ($result['respSubCode'] === '200213') {
                    // 200213:子订单已存在|200214:原订单不是成功状态，不能取消
                    return $this->responseSuccess();
                }
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_INVEST_CANCEL');
            }

            //异步更新存管订单
            if ($result['respCode'] === SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncUpdateOrder($params['origOrderId'], SupervisionEnum::NOTICE_CANCEL);
            }

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管投资取消|原投资订单号:%s，异常内容:%s', $params['origOrderId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALINVESTCANCEL');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 放款
     * @param array $params 参数列表
     * @return array
     */
    public function dealGrant($params) {
        try {
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DEAL_GRANT, $params);

            // 请求接口，标的请求重发并处理流水单号幂等
            $result = $this->dealRequest($this->api, 'dealGrant', $params, $params['orderId'], SupervisionEnum::BATCHORDER_TYPE_GRANT, 'ERR_DEALGRANT', '200103');
            if (!empty($result['batchOrderStatus'])) {
                if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    return $this->responseSuccess();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_PROCESSING) {
                    return $this->responseProsessing();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_FAILURE) {
                    throw new WXException('ERR_DEALGRANT');
                }
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的放款|标的ID:%d，订单号:%s，借款人用户ID:%d，异常内容:%s', $params['bidId'], $params['orderId'], $params['userId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALGRANT');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 放款-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:外部订单号
     *     status:订单处理状态 S-成功
     *     remark:备注
     */
    public function dealGrantNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //放款逻辑
            $grantService = new P2pDealGrantService();
            $grantService->dealGrantCallBack($responseData['orderId'],$responseData['status']);

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $responseData['status']);

            return $this->responseSuccess(['ret'=>true]);
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的放款回调|订单号:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALGRANTCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * H5确认投资-页面
     * @param array $params 参数列表
     * @return array
     */
    public function h5InvestConfirm($params, $formId = 'h5InvestForm', $targetNew = true) {
        try {
            // 请求存管页面
            $result = $this->api->getForm('h5InvestConfirm', $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 请求流标接口
     * @param array $params
     *      bidId string 标的Id
     *      rpDirect string 红包流向
     *
     * @return array
     */
    public function dealCancel($params) {
        try {
            // 请求接口
            $result = $this->api->request('dealCancel', $params);
            // 存管系统返回商户流水已存在
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200103') {
                // 存管系统-查询该笔订单状态
                $supervisionFinanceObj = new SupervisionFinanceService();
                $supervisionOrderInfo = $supervisionFinanceObj->batchOrderSearch($params['bidId'], SupervisionEnum::BATCHORDER_TYPE_DEALCANCEL);
                if ($supervisionOrderInfo['status'] == SupervisionEnum::RESPONSE_SUCCESS && !empty($supervisionOrderInfo['data'])) {
                    if ($supervisionOrderInfo['data']['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                        return $this->responseSuccess();
                    }else if ($supervisionOrderInfo['data']['status'] == SupervisionEnum::RESPONSE_PROCESSING) {
                        return $this->responseProsessing();
                    }
                }
                // 存管系统没有查到订单状态或者订单状态为失败，则返回失败
                throw new WXException('ERR_DEALCANCEL');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_DEALCANCEL');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的流标|标的ID:%d，异常内容:%s', $params['bidId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALCANCEL');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 流标回调接口
     */
    public function dealCancelNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //流标逻辑@TODO
            $s = new P2pDealCancelService();
            $s->dealCancelCallBack($responseData['orderId'],$responseData['status']);

            //异步更新存管订单
            if ($responseData['status'] == SupervisionEnum::NOTICE_SUCCESS) {
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncUpdateOrderByDealId($responseData['orderId'], SupervisionEnum::NOTICE_CANCEL);
            }

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的流标回调|订单号:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALCANCELCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 标的代偿还款-批量
     * @param array $params
     */
    public function dealReplaceRepay($params) {
        $orderSplitService = new SupervisionOrderSplitService();
        // 业务类型-标的还款
        $bizType = SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPLACE_REPAY;
        return $orderSplitService->splitSupervisionOrder(
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['requestService'],
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['apiName'],
            $params,
            $bizType
        );
    }

    /**
     * 代偿
     * @param array $params 参数列表
     * @return array
     */
    public function dealReplaceRepaySupervision($params) {
        try {
            // 外部业务订单号、还款订单集合
            if (empty($params['repayOrderList'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
//             foreach ($params['repayOrderList'] as $item) {
//                 if (empty($item['receiveUserId']) || empty($item['amount'])) {
//                     throw new WXException('ERR_PARAM_LOSE');
//                 }
//             }

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DEAL_REPLACE_REPAY, $params);

            // 请求接口，标的请求重发并处理流水单号幂等
            $result = $this->dealRequest($this->api, 'dealReplaceRepay', $params, $params['orderId'], SupervisionEnum::BATCHORDER_TYPE_REPAY, 'ERR_DEAL_REPLACE_REPAY', '200103');
            if (!empty($result['batchOrderStatus'])) {
                if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    return $this->responseSuccess();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_PROCESSING) {
                    return $this->responseProsessing();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_FAILURE) {
                    throw new WXException('ERR_DEAL_REPLACE_REPAY');
                }
            }

            // 通过Jobs记录[外部订单号]到order表@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的代偿|标的ID:%d，订单号:%s，付款用户ID:%d，原始借款用户ID:%d，异常内容:%s', $params['bidId'], $params['orderId'], $params['payUserId'], $params['originalPayUserId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALREPLACEREPAY');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 还代偿款
     * @param array $params 参数列表
     * @return array
     */
    public function dealReturnRepay($params) {
        try {
            // 外部业务订单号、还款订单集合
            if (empty($params['repayOrderList'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            /*
            foreach ($params['repayOrderList'] as $item) {
                if (empty($item['receiveUserId']) || empty($item['amount'])) {
                    throw new WXException('ERR_PARAM_LOSE');
                }
            }
             */

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DEAL_RETURN_REPAY, $params);

            // 请求接口
            $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN').'/supervision/returnRepayNotify';
            $result = $this->api->request('dealReturnRepay', $params);
            if (!isset($result['respCode']) || ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_DEAL_RETURN_REPAY');
            }

            // 通过Jobs记录[外部订单号]到order表@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的还代偿款|标的ID:%d，订单号:%s，还代偿人用户ID:%d，异常内容:%s', $params['bidId'], $params['orderId'], $params['payUserId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALRETURNREPAY');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 投资成功页面弹出快速投资服务的次数
     * @param int $accountId 账户ID
     * @throws \Exception
     * @return boolean
     */
    public function setQuickBidAuthCount($accountId) {
        //存管降级状态下，不进行快捷投资服务开通提示
        if (SupervisionService::isServiceDown() || empty($accountId)) {
            return false;
        }
        $supervisionAccountObj = new SupervisionAccountService();
        // 存管开关关闭或用户尚未开通存管户时，不弹窗
        $isSupervision = $supervisionAccountObj->isSupervisionUser($accountId);
        if (!$isSupervision) {
            return false;
        }
        // 检查用户是否已开通免密授权
        $isQuickBidAuth = (int)$supervisionAccountObj->isQuickBidAuthorization($accountId);
        if ($isQuickBidAuth === 1) {
            return false;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::KEY_DEAL_QUICKBID, $accountId);
        $incrCount = $redis->get($cacheKey);
        if ($incrCount >= self::$alertQuickBidCount) {
            return false;
        }
        return $redis->incr($cacheKey) ? true : false;
    }

    /**
     * 标的请求重发并处理流水单号幂等
     * @param obj $supervisionApi
     * @param string $apiName
     * @param array $params
     * @param string $orderId
     * @param string $batchOrderType
     * @param string $respSubKey
     * @param string $respSubCode
     * @throws WXException
     * @return array
     */
    public function dealRequest($supervisionApi, $apiName, $params, $orderId, $batchOrderType, $respSubKey = 'ERR_DEAL_REPAY', $respSubCode = '200103') {
        // 请求接口
        $result = $supervisionApi->request($apiName, $params);
        // 存管接口异常时，重新发起请求
        if (empty($result) && (!empty(Curl::$error) || Curl::$httpCode != 200)) {
            $result = $supervisionApi->request($apiName, $params);
        }
        // 存管系统返回商户流水已存在
        if (isset($result['respSubCode']) && $result['respSubCode'] == $respSubCode) {
            // 存管系统-查询该笔订单状态
            $supervisionFinanceObj = new SupervisionFinanceService();
            $supervisionOrderInfo = $supervisionFinanceObj->batchOrderSearch($orderId, $batchOrderType);
            if ($supervisionOrderInfo['status'] != SupervisionEnum::RESPONSE_SUCCESS || empty($supervisionOrderInfo['data'])) {
                // 存管系统没有查到订单状态或者订单状态为失败，则返回失败
                throw new WXException($respSubKey);
            }
            $result['batchOrderStatus'] = $supervisionOrderInfo['data']['status'];
            return $result;
        }else if (isset($result['respSubCode']) && $result['respSubCode'] == '200126') {
            // 存管系统返回商户子订单已存在，则表示受理成功
            return $result;
        }else if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
            throw new WXException($respSubKey);
        }
        return $result;
    }

    /**
     * 投资请求重发并处理流水单号幂等
     * @param obj $supervisionApi
     * @param string $apiName
     * @param array $params
     * @param int $orderId
     * @param string $respErrorKey
     * @param string $respSubErrorKey
     * @throws WXException
     * @return array
     */
    public function investRequest($supervisionApi, $apiName, $params, $orderId, $respErrorKey = 'ERR_INVEST_CREATE', $respSubErrorKey = 'ERR_INVEST_SUBORDER_EXIST') {
        // 请求接口
        $result = $supervisionApi->request($apiName, $params);
        if (!isset($result['respCode'])) {
            throw new WXException('ERR_REQUEST_TIMEOUT');
        }else if (isset($result['respSubCode']) && $result['respSubCode'] == '200103') {
            // 存管系统返回商户流水已存在
            // 存管系统-查询该笔订单状态
            $supervisionFinanceObj = new SupervisionFinanceService();
            $supervisionOrderInfo = $supervisionFinanceObj->orderSearch($orderId);
            if ($supervisionOrderInfo['status'] != SupervisionEnum::RESPONSE_SUCCESS || empty($supervisionOrderInfo['data'])
                || $supervisionOrderInfo['data']['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                // 存管系统没有查到订单状态或者订单状态为失败、进行中，则返回失败 抛出异常
                throw new WXException('ERR_INVEST_ORDER_SEARCH');
            }
            $result = $supervisionOrderInfo['data'];
            return $result;
        }else if (isset($result['respSubCode']) && $result['respSubCode'] == '200126') {
            // 存管系统返回商户子订单已存在，也抛异常
            throw new WXException($respSubErrorKey);
        }else if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
            throw new WXException($respErrorKey);
        }
        return $result;
    }

    /**
     * 导入历史标的数据
     * @param array $params
     *      bidId string 标的id
     *      name string 标的名称
     *      amount string 标的金额 单位分
     *      userId string 借款人id
     *      bidRate string 标的年利率 格式 0.0000 四位有效精度
     *      bidType string 标的类型 01信用 02抵押 03 债转转让 04 - 99 其他
     *      cycle string 借款周期
     *      repaymentType string 还款方式 01 一次性还本付息 02 等额本金 03 等额本息 04 按期付息到期还本 99其他
     *      borrPurpose string 借款用途
     *      productType string 标的产品类型 01房贷类 02车贷类 03收益权转让 04 信用贷款类 05股票配资类 06 银行承兑汇票 07 商业承兑汇票 08消费贷款类 09 供应链类 99 其他
     *      borrName string 借款方名称
     *      borrUserType string 借款人用户类型 1 个人 2企业
     *      borrCertType string 借款方证件类型IDC 身份证 GAT 港澳台居民来往内地通行证 MILIARY 军官证 PASS_PORT 护照 BLC 营业执照 USCC 统一社会信用代码
     *      borrCertNo string 借款方证件号码 借款企业营业执照号码（借款方为企业时）
     * @return boolean
     */
    public function dealImport($params) {
        $params['orderId'] = Idworker::instance()->getId();
        $result = $this->api->request('dealImport', $params);
        if (empty($result) || !isset($result['respCode']) || $result['respCode'] == '02') {
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200107') {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 导入投资单信息
     * @param array $params
     *      userId string 投资用户ID
     *      orderId string 原始投资单单号ID
     *      bidId string 标的ID
     *      amount long 原始投资金额 单位分
     *      orgAmount long 带还款本金金额 单位分
     *
     * @return boolean
     */
    public function dealOrderImport($params) {
        $result = $this->api->request('dealOrderImport', $params);
        if (empty($result) || !isset($result['respCode']) || $result['respCode'] == '02') {
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200107') {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 导入债转信息
     * @param array $params
     *      userId string 用户id
     *      bidId string 标的id
     *      sumAmount long 投资债权总本金 单位分
     *      leftAmount long 待还本金 单位分
     *
     * @return boolean
     */
    public function dealCreditImport($params) {
        $result = $this->api->request('dealCreditImport', $params);
        if (empty($result) || !isset($result['respCode']) || $result['respCode'] == '02') {
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200424') {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 标的代充值还款-批量
     * @param array $params
     */
    public function dealReplaceRechargeRepay($params) {
        $orderSplitService = new SupervisionOrderSplitService();
        // 业务类型-标的还款
        $bizType = SupervisionOrderSplitModel::BIZ_TYPE_DEAL_REPLACE_RECHARGE_REPAY;
        return $orderSplitService->splitSupervisionOrder(
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['requestService'],
            SupervisionOrderSplitService::$bizOrderSplitMap[$bizType]['apiName'],
            $params,
            $bizType
        );
    }

    /**
     * 代偿
     * @param array $params 参数列表
     * @return array
     */
    public function dealReplaceRechargeRepaySupervision($params) {
        try {
            // 外部业务订单号、还款订单集合
            if (empty($params['repayOrderList'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
//             foreach ($params['repayOrderList'] as $item) {
//                 if (empty($item['receiveUserId']) || empty($item['amount'])) {
//                     throw new WXException('ERR_PARAM_LOSE');
//                 }
//             }

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_DEAL_REPLACE_RECHARGE_REPAY, $params);

            // 请求接口，标的请求重发并处理流水单号幂等
            $result = $this->dealRequest($this->api, 'dealReplaceRechargeRepay', $params, $params['orderId'], SupervisionEnum::BATCHORDER_TYPE_REPAY, 'ERR_DEAL_REPLACE_RECHARGE_REPAY', '200103');
            if (!empty($result['batchOrderStatus'])) {
                if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    return $this->responseSuccess();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_PROCESSING) {
                    return $this->responseProsessing();
                }else if ($result['batchOrderStatus'] == SupervisionEnum::RESPONSE_FAILURE) {
                    throw new WXException('ERR_DEAL_REPLACE_RECHARGE_REPAY');
                }
            }

            // 通过Jobs记录[外部订单号]到order表@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管标的代充值还款|标的ID:%d，订单号:%s，付款用户ID:%d，原始借款用户ID:%d，异常内容:%s', $params['bidId'], $params['orderId'], $params['payUserId'], $params['originalPayUserId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_DEALREPLACERECHARGEREPAY');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 放款后收费接口
     * @param array $params 请求接口参数
     *      string orderId 外部订单号
     *      integer userId 借款人用户id
     *      integer amount 收费总金额
     *      integer totalNum 收费总笔数
     *      integer bidId 收费标的id
     *      integer expireTime 收费过期时间
     *      string callbackUrl 收费成功通知地址
     *      array chargeOrderList 收费清单
     *          integer amount 收费金额 单位分
     *          integer receiveUserId 收款人用户id
     *          string subOrderId 收款子单号
     *          string remark 备注
     *
     * @return array | false
     */
    public function chargeFeeAfterGrant($params)
    {
        try {
            return $this->api->request('chargeFeeAfterGrant', $params);
        } catch (\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, "放款后收费失败, 标的编号:{$params['bidId']}");
            // 添加监控
            Monitor::add('SUPERVISION_ChargeFeeAfterGrant');
            return false;
        }
    }

    private function checkAffectRow($db, $WXExceptionName = 'ERR_PARAM_LOSE') {
        $affectedRows = $db->affected_rows();
        if ($affectedRows== 0) {
            throw new WXException('ERR_DEAL_REPAY');
        }
        return true;
    }
}
