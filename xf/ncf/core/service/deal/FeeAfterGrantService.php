<?php
namespace core\service\deal;
use core\dao\jobs\JobsModel;
use core\enum\DealGrantFeeEnum;
use core\enum\JobsEnum;
use core\service\BaseService;
use core\service\account\AccountService;
use core\dao\deal\FeeAfterGrantModel;
use core\dao\account\AccountModel;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use core\service\supervision\SupervisionDealService;
use core\service\supervision\SupervisionFinanceService;
use core\enum\SupervisionEnum;
use core\enum\AccountEnum;
use libs\utils\Alarm;
use libs\utils\Monitor;


class FeeAfterGrantService extends BaseService
{
    const STATUS_ALL = -1; // 全部
    const STATUS_INIT = 0; // 未处理
    const STATUS_PROCESSING = 1; // 处理中
    const STATUS_SUCCESS = 2; // 成功
    const STATUS_FAILURE = 3; // 失败
    const STATUS_FAILURE_OVERTIME = 4; // 超时关单


    const SUB_CODE_REPEATED = '200103'; // 子码返回订单已经存在


    static  $resultMap = [
        self::STATUS_ALL => '全部',
        self::STATUS_INIT => '未处理',
        self::STATUS_PROCESSING => '处理中',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAILURE => '失败',
        self::STATUS_FAILURE_OVERTIME => '超时关单',
    ];


    /**
     * 创建代扣收费单据,如果deal_id已经存在则返回deal_id对应的代扣缴费记录id
     *
     * @param array $params
     *      integer deal_id 标的id 必填
     *      string  deal_name 标的名称 必填
     *      integer deal_user_id 标的借款人id 必填
     *      string  deal_user_name 标的借款人姓名 必填
     *      integer grant_time 标的放款时间 必填
     *      integer fee_amount 标的代扣费用总金额 必填
     *      array   fee_detail_list 标的代扣费用明细 必填
     *          integer amount 收费费用, 单位分
     *          integer receiveUserId 收费用户账户id
     *          string remark 收费名称,  咨询费
     *
     * @return mixed<bool|integer>
     */
    public function createFeeOrder($params)
    {
        try {
            $feeAfterGrantModel = new FeeAfterGrantModel();
            $this->checkIfMissingCreationParams($params);
            $record = $this->isDealIdExists($feeAfterGrantModel, $params['deal_id']);
            if (!empty($record['id']))
            {
                return $record['id'];
            }
            // 转换array 为json序列化数据
            $feeDetailList = $params['fee_detail_list'];
            unset($params['fee_detail_list']);
            $params['out_order_id'] = Idworker::instance()->getId();
            $params['create_time'] = time();
            foreach ($feeDetailList as $k => $feeDetail)
            {
                $feeDetailList[$k]['subOrderId'] = Idworker::instance()->getId();
            }
            $params['fee_detail_list'] = json_encode($feeDetailList, JSON_UNESCAPED_UNICODE);

            $feeAfterGrantModel->setRow($params);
            $result = $feeAfterGrantModel->insert();
            if (!$result)
            {
                throw new \Exception('执行model::insert 返回false');
            }
            $feeOrderId = $feeAfterGrantModel->id;
            Logger::info('创建代扣收费单据成功, 单据id:'.$feeOrderId.' 标的id:'.$params['deal_id'].' 用户id:'.$params['deal_user_id']);
            return $feeOrderId;
        } catch (\Exception $e) {
            Logger::error('创建代扣收费单据失败,原因:'.$e->getMessage());
            return false;
        }
    }


    /**
     * 检查dealId是否存在,如果存在则返回记录id
     * @param integer $dealId 标的id
     * @return array|bool
     */
    public function isDealIdExists($feeAfterGrantMdl, $dealId)
    {
        return $feeAfterGrantMdl->isDealIdExists($dealId);
    }


    /**
     * 检查创建代扣缴费订单必要的元素是否佩奇
     * @param array $params 创建代扣收费单据数据
     * @throws Exception
     * @return true
     */
    public function checkIfMissingCreationParams($params)
    {
        if (empty($params['deal_id']))
        {
            throw new \Exception('deal_id:标的编号不能为空');
        }
        if (empty($params['deal_name']))
        {
            throw new \Exception('deal_name:标的名称不能为空');
        }
        if (empty($params['deal_user_id']))
        {
            throw new \Exception('deal_user_id:标的借款人id不能为空');
        }
        if (empty($params['deal_user_name']))
        {
            throw new \Exception('deal_user_name:标的借款人姓名不能为空');
        }
        if (empty($params['grant_time']))
        {
            throw new \Exception('grant_time:标的放款时间不能为空');
        }
        if (empty($params['fee_amount']))
        {
            throw new \Exception('fee_amount:标的代扣费用总金额不能为空');
        }
        if (empty($params['fee_detail_list']))
        {
            throw new \Exception('fee_detail_list:标的代扣费用明细不能为空');
        }
        $totalAmount = 0;
        foreach ($params['fee_detail_list'] as $feeDetail)
        {
            $totalAmount += $feeDetail['amount'];
        }
        if (bccomp($params['fee_amount'], $totalAmount) !== 0)
        {
            throw new \Exception('fee_amount:标的代扣费用明细总额不等于扣费总金额');
        }

        return true;
    }

    /**
     * 根据id, 使用toUpdateProperties的数据更新此条记录 记录不存在则返回false
     *
     * @param integer $id 代扣缴费记录编号
     * @param array $toUpdateProperties 要更新的数据
     * @param array $lockStatus 使用charge_result状态值作为乐观锁
     *
     *
     * @return bool
     */
    public function updateRecordById($id, array $toUpdateProperties, $lockStatus = [])
    {
        try {
            $feeAfterGrantMdl = FeeAfterGrantModel::instance()->find($id);
            if (empty($feeAfterGrantMdl))
            {
                throw new \Exception('代扣缴费记录:'.$id.'不存在');
            }
            if (in_array($feeAfterGrantMdl->charge_result, $lockStatus))
            {
                return true;
            }
            $result = $feeAfterGrantMdl->update($toUpdateProperties);
            if(!$result || $feeAfterGrantMdl->db->affected_rows() < 1)
            {
                throw new \Exception("执行数据库更新失败");
            }
            Logger::info('更新代扣收费记录#'.$id.'成功');
            return true;
        } catch (\Exception $e) {
            Logger::error('更新代扣收费记录失败, 原因:'.$e->getMessage());
            return false;
        }
    }


    /**
     * 请求扣费接口, 只有存在并且状态为未处理,处理中的订单可以去请求存管系统,其他的不予处理
     * @param integer $dealId 要代扣费用的标的
     * @return
     */
    public function requestChargeFeeAfterGrant($dealId)
    {
        try {
            $record = FeeAfterGrantModel::instance()->isDealIdExists($dealId, [self::STATUS_INIT, self::STATUS_PROCESSING]);
            if (empty($record))
            {
                // 记录告警
                Alarm::push('supervision', __METHOD__, '代扣缴费订单不存在或者已经被处理过了, 标的id:'.$dealId);
                // 请求代扣缴费失败打点
                Monitor::add('SUPERVISION_ChargeFeeAfterGrantFail');
                return true;
            }
            $params = [];
            $params['orderId'] = $record['out_order_id'];
            $params['userId'] = $record['deal_user_id'];
            $params['bidId'] = $record['deal_id'];
            $params['amount'] = $record['fee_amount'];
            $details = json_decode($record['fee_detail_list'], true);
            $params['totalNum'] = count($details);
            $chargeOrderList = [];
            foreach ($details as $feeDetail)
            {
                $chargeOrderList[] = [
                    'receiveUserId' => $feeDetail['receiveUserId'],
                    'amount' => $feeDetail['amount'],
                    'subOrderId' => $feeDetail['subOrderId'],
                    'remark' => $feeDetail['remark'],
                ];
            }
            $params['chargeOrderList'] = json_encode($chargeOrderList, JSON_UNESCAPED_UNICODE);
            $params['callbackUrl'] = app_conf('NOTIFY_DOMAIN').'/supervision/feeAfterGrantNotify';
            // 订单创建的3600s 之后会被强制关闭
            $params['expireTime'] = date('YmdHis', time() + 3600);
            $svDealService = new SupervisionDealService();
            $result = $svDealService->chargeFeeAfterGrant($params);
            /// 请求存管失败,超时无返回, 可以重试
            if (empty($result) )
            {
                $msg = isset($result['respMsg']) ? ',返回值:'.$result['respMsg'] : '';
                throw new \Exception('请求存管代扣收费接口失败'.$msg);
            } elseif (isset($result['respCode']) && $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {

                // 同步返回失败之后,没有通知的订单更新
                $callbackData = [
                    'status' => 'F',
                    'remark' => !empty($result['respMsg']) ? addslashes($result['respMsg']) : '请求存管发生异常',
                    'orderId' => $record['out_order_id'],
                    'respSubCode' => $result['respSubCode'],
                ];

                // 兼容扣费接口返回失败,但是状态是受理中的状态
                // 兼容返回失败, 订单已经存在的状态
                if ($result['respSubCode'] == '000001' || $result['respSubCode'] == self::SUB_CODE_REPEATED) {
                    $callbackData['status'] = 'I';
                    $callbackData['remark'] = '';
                }

                $result = $this->chargeFeeAfterGrantNotify($callbackData);
                if ($result == false)
                {
                    throw new \Exception("存管代扣收费记录更新失败,数据:".json_encode($callbackData, JSON_UNESCAPED_UNICODE));
                }
            }
            $this->updateRecordById($record['id'], ['request_time' => time()]);
            // 请求代扣缴费成功打点
            Monitor::add('SUPERVISION_ChargeFeeAfterGrantSuccess');
            return true;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            // 记录告警
            Alarm::push('supervision', __METHOD__, $e->getMessage());
            // 请求代扣缴费失败打点
            Monitor::add('SUPERVISION_ChargeFeeAfterGrantFail');
            return false;
        }
    }


    /**
     * 代扣缴费通知处理逻辑
     */
    public function chargeFeeAfterGrantNotify($callbackData)
    {
        try {
            $hasTransStarted = false;
            $db = FeeAfterGrantModel::instance()->db;
            $orderId = isset($callbackData['orderId']) ? intval($callbackData['orderId']) : '';
            if (empty($orderId))
            {
                throw new \Exception('代扣缴费通知结果格式不正确,没有orderId');
            }
            // 使用悲观锁更新订单
            $queryForUpdate = true;
            $record = FeeAfterGrantModel::instance()->getRecordByOrderId($orderId, $queryForUpdate);
            if (empty($record))
            {
                throw new \Exception('代扣缴费通知订单'.$orderId.'不存在');
            }
            // 转换通知订单状态为业务系统订单状态
            $businessOrderStatus = $this->convertNoticeStatusToBusinessStatus($callbackData['status'], $callbackData['respSubCode']);
            // 已经处理过
            if ($businessOrderStatus == $record['charge_result'])
            {
                return true;
            }


            // 需要更新代扣缴费记录状态和生成资金记录
            $db->startTrans();
            $hasTransStarted = true;

            $note = "编号{$record['deal_id']} {$record['deal_name']} 借款人ID{$record['deal_user_id']} 借款人姓名{$record['deal_user_name']}";


            // 收费成功, 写入资金记录
            if ($businessOrderStatus == self::STATUS_SUCCESS)
            {
                // 借款人增加缴费入账资金记录
                $payUserMdl = AccountModel::instance()->find($record['deal_user_id']);
                if (empty($payUserMdl))
                {
                    throw new \Exception('代扣缴费通知处理失败, 标的编号:'.$record['deal_id'].'借款人增加缴费金额失败,原因是:借款人账户账户id'.$record['deal_user_id'].'不存在');
                }
                AccountService::changeMoney($record['deal_user_id'], bcdiv($record['fee_amount'], 100, 2), '收费', $note, AccountEnum::MONEY_TYPE_INCR);


                // 循环收费列表
                $feeDetailList = json_decode($record['fee_detail_list'], true);
                foreach ($feeDetailList as $feeDetail)
                {
                    // 给相应收费账户增加 收费资金记录
                    $accountMdl = AccountModel::instance()->find($feeDetail['receiveUserId']);
                    if (empty($accountMdl))
                    {
                        throw new \Exception('代扣缴费通知处理失败, 标的编号:'.$record['deal_id'].'费用-'.$feeDetail['remark'].'收取失败,原因是:收款账户id'.$feeDetail['receiveUserId'].'不存在');
                    }
                    AccountService::changeMoney($feeDetail['receiveUserId'], bcdiv($feeDetail['amount'], 100, 2), $feeDetail['remark'], $note, AccountEnum::MONEY_TYPE_INCR);
                    // 借款人缴费记录
                    AccountService::changeMoney($record['deal_user_id'], bcdiv($feeDetail['amount'], 100, 2), $feeDetail['remark'], $note, AccountEnum::MONEY_TYPE_REDUCE);
                }
            }
            // 增加db 乐观锁
            $updateTime = time();
            $failReason = isset($callbackData['remark']) ? addslashes(trim($callbackData['remark'])) : '';
            // 处理支付失败原因
            if ($businessOrderStatus == self::STATUS_FAILURE) {
                // 查询支付订单接口,读取详细失败原因
                $sfs = new SupervisionFinanceService();
                $searchResult = $sfs->orderSearch($record['out_order_id']);
                if (isset($searchResult['data']['failReason'])) {
                    $failReason = $searchResult['data']['failReason'];
                }
            }
            $updateSql = "UPDATE firstp2p_fee_after_grant SET update_time = {$updateTime}, callback_time = {$updateTime}, charge_result = '{$businessOrderStatus}', fail_reason = '{$failReason}' WHERE id = {$record['id']} AND charge_result IN (".implode(',', [self::STATUS_PROCESSING, self::STATUS_INIT]).')';
            $db->query($updateSql);
            $result = $db->affected_rows() > 0 ? true : false;
            if ($result == false)
            {
                throw new \Exception("代扣缴费通知处理失败,原因:更新代扣收费记录失败或者该记录已经收过了");
            }

            // 业务状态是成功或者失败的时候才会去通知功夫贷
            if (in_array($businessOrderStatus, [self::STATUS_SUCCESS, self::STATUS_FAILURE])) {
                // 添加jobs，推送结果给功夫贷
                $jobsModel = new JobsModel();
                $param = array(
                    'orderId' => $orderId,
                );
                $jobsModel->priority = JobsEnum::PRIORITY_NOTIFY_GFD;
                $r = $jobsModel->addJob('\core\service\notify\NotifyGfdService::notifyFeeAfterGrant', $param,false,1440);
                if ($r === false) {
                    throw new \Exception("添加推送结果给功夫贷jobs失败");
                }
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {

            $hasTransStarted && $db->rollback();
            Logger::error($e->getMessage());
            // 记录告警
            Alarm::push('supervision', __METHOD__, $e->getMessage());
            // 添加监控
            Monitor::add('SUPERVISION_ChargeFeeAfterGrantNotify');
            return false;
        }
    }


    /**
     * 根据通知返回的状态来处理
     */
    public function convertNoticeStatusToBusinessStatus($noticeStatus, $noticeSubCode = '')
    {
        switch ($noticeStatus)
        {
            case 'I':
                return self::STATUS_PROCESSING;
            case 'S':
                return self::STATUS_SUCCESS;
            case 'F':
                // 订单已经存在的请求
                if($noticeSubCode == self::SUB_CODE_REPEATED)
                {
                    return self::STATUS_PROCESSING;
                }
                return self::STATUS_FAILURE;
        }
    }

    public function getOverTimeList(){
        $t = DealGrantFeeEnum::OVER_TIME_SECONDS;
       // $statusStr = self::STATUS_SUCCESS . "," . self::STATUS_FAILURE;
        $cond = 'charge_result = '.self::STATUS_PROCESSING.' AND request_time <'.(time() - $t);
        return FeeAfterGrantModel::instance()->findAllViaSlave($cond);
    }

}
