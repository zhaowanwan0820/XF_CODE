<?php
namespace core\service\supervision;

use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionFinanceService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionDealService;
use core\service\supervision\SupervisionOrderService;
use core\dao\supervision\NongdanModel;
use core\dao\deal\DealModel;
use core\dao\user\UserModel;
use libs\utils\PaymentApi;
use core\enum\UserAccountEnum;
use core\enum\AccountEnum;
use core\enum\SupervisionEnum;
use core\enum\DealEnum;

class NongdanService
{
    // 账户类型 转出方
    const ACCOUNT_PAYER     = 1;
    // 账户类型 转入方
    const ACCOUNT_RECEIVER  = 2;

    public static function checkAccount($userName, $accountType = self::ACCOUNT_RECEIVER, $money = 0, $type = 1, $realName)
    {
        $supervisionService = new SupervisionService();
        $accountService     = new SupervisionAccountService();
        $accountName = $accountType == self::ACCOUNT_PAYER ? '转出' : '转入';
        //转入用户检查
        $userInfo = UserService::getUserByName(addslashes($userName), 'id,is_effect');
        if (empty($userInfo))
        {
            throw new \Exception($accountName.'用户不存在');
        }
        else {
            if ($realName != UserService::getUserRealName($userInfo['id'])) {
                throw new \Exception($accountName.'会员姓名与帐户姓名不一致');
            }
        }

        //转入用户存管信息检查
        $supervisionInfo = $supervisionService->svInfo($userInfo['id'], true);
        if (empty($supervisionInfo) || $supervisionInfo['isSvUser'] != 1)
        {
            throw new \Exception($accountName.'用户尚未开通存管账户');
        }
        if ($accountType == self::ACCOUNT_PAYER)
        {
            if ($type == NongdanModel::TYPE_RETURNREPAY) {
                //还代偿款 转出必须是融资户
                if ($supervisionInfo['userPurpose'] != UserAccountEnum::ACCOUNT_FINANCE) {
                    throw new \Exception($accountName.'账户非融资户');
                }
            } else {
                // 检查转出用户是否是平台户或者营销户
                if (!in_array($supervisionInfo['userPurpose'], [UserAccountEnum::ACCOUNT_PLATFORM, UserAccountEnum::ACCOUNT_MARKETINGSUBSIDY]))
                {
                    throw new \Exception($accountName.'账户非平台户或者营销户');
                }
            }

            // 检查余额是否足够
            // 检查存管行用户余额是否足够
            $userBalance = $accountService->balanceSearch($userInfo['id']);
            if (!isset($userBalance['respCode']) || $userBalance['status'] != 'S')
            {
                throw new \Exception('读取用户存管余额失败,请重试');
            }
            $userBalance = bcdiv($userBalance['data']['availableBalance'], 100, 2);

            if (bccomp($userBalance, $money, 2) < 0)
            {
                throw new \Exception($accountName.'账户可用余额不足');
            }
        }
        if ($accountType == self::ACCOUNT_RECEIVER)
        {
            if ($userInfo['is_effect'] != 1)
            {
                throw new \Exception($accountName.'账户无效');
            }

            //还代偿款  转入必须是担保户
            if ($type == NongdanModel::TYPE_RETURNREPAY && $supervisionInfo['userPurpose'] != UserAccountEnum::ACCOUNT_GUARANTEE) {
                throw new \Exception($accountName.'账户非担保户');
            }
        }
        return $userInfo['id'];
    }

    public static function checkType($typeName)
    {
        if (!in_array($typeName, array_values(NongdanModel::$typeDesc)))
        {
            throw new \Exception('业务类型不正确');
        }
        return true;
    }

    public static function checkDeal($dealId, $type, $userName)
    {
        //代偿还款才检查标的
        if ($type != NongdanModel::TYPE_RETURNREPAY) {
            return true;
        }
        $dealId = (int) $dealId;
        if (empty($dealId)) {
            throw new \Exception($dealId . '标的编号不能为空');
        }

        //校验标的
        $deal = DealModel::instance()->find($dealId);
        if (empty($deal)) {
            throw new \Exception($dealId . '标的不存在');
        }

        if ($deal['is_effect'] != 1) {
            throw new \Exception($dealId . '标的无效');
        }

        if ($deal['deal_type'] != DealEnum::DEAL_TYPE_GENERAL || $deal['report_status'] != 1) {
            throw new \Exception($dealId . '标的不是网贷标的或未报备');
        }

        if (!in_array($deal['deal_status'], [DealEnum::$DEAL_STATUS['repaying'], DealEnum::$DEAL_STATUS['repaid']])) {
            throw new \Exception($dealId . '标的状态不是还款中或已还清');
        }

        //用户检查
        $userInfo = UserService::getUserByName(addslashes($userName), 'id,is_effect');
        if (empty($userInfo))
        {
            throw new \Exception($userName.'用户不存在');
        }

        //非标的借款人
        if ($deal['user_id'] != $userInfo['id']) {
            throw new \Exception($userName.'非标的借款人'.$dealId);
        }
        return true;
    }

    public static function processRequest($record)
    {
        try {
            switch ($record['type'])
            {
                case NongdanModel::TYPE_PROMOTION:
                case NongdanModel::TYPE_INTEREST:
                    return self::requestBatchTransfer($record);
                case NongdanModel::TYPE_RETURNREPAY:
                    return self::requestReturnRepay($record);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function requestBatchTransfer($record)
    {
        try {
            $request = NongdanModel::instance()->find($record['id']);
            if (empty($request))
            {
                throw new \Exception('数据#'.$record['id'].'不存在');
            }
            $requestData = [
                'orderId'       => 'T'.$record['out_order_id'],
                'remark'        => 'Nongdan',
                'currency'      => 'CNY',
                'subOrderList'  => json_encode([[
                    'bizType'       => SupervisionEnum::BATCHTRANSFER_BENIFIT,
                    'payUserId'     => $record['pay_user_id'],
                    'receiveUserId' => $record['receive_user_id'],
                    'amount'        => $record['money'],
                    'subOrderId'    => 'TO'.$record['out_order_id'], // 增加TO前缀区分业务, 明细对账时,需要区分
                ]]),
            ];

            $financeService = new SupervisionFinanceService();
            $response = $financeService->batchTransfer($requestData);
            // 没有响应
            if (empty($response['data']))
            {
                throw new \Exception('请求失败，请重新发起');
            }
            // 订单已经存在
            $bizData = $response['data'];
            if (isset($bizData['respSubCode']) && $bizData['respSubCode'] == SupervisionEnum::CODE_ORDER_EXIST)
            {
                $orderResult = $financeService->batchOrderSearch('T'.$record['out_order_id'], 8000);
                if (empty($orderResult['data']) || $orderResult['data']['respCode'] != '00')
                {
                    throw new \Exception('请求失败，请重新发起');
                }
                $bizData = $orderResult['data'];
                // 请求 结果处理逻辑
                return self::processBatchTransfer($bizData);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *  还代偿款
     */
    public static function requestReturnRepay($record)
    {
        try {
            $request = NongdanModel::instance()->find($record['id']);
            if (empty($request))
            {
                throw new \Exception('数据#'.$record['id'].'不存在');
            }
            $requestData = [
                'bidId'             => $record['deal_id'],
                'orderId'           => 'T' . $record['out_order_id'],
                'payUserId'         => $record['pay_user_id'],
                'totalNum'          => 1,
                'totalAmount'       => $record['money'],
                'currency'          => 'CNY',
                'remark'            => $record['info'],
                'repayOrderList'    => json_encode([[
                    'receiveUserId'     => $record['receive_user_id'],
                    'amount'            => $record['money'],
                    'subOrderId'        => 'TO'.$record['out_order_id'], // 增加TO前缀区分业务, 明细对账时,需要区分
                    'type'              => 'C',
                ]]),
            ];

            $supervisionDealService = new SupervisionDealService();
            $response = $supervisionDealService->dealReturnRepay($requestData);
            // 没有响应
            if (empty($response['data']))
            {
                throw new \Exception('请求失败，请重新发起');
            }
            // 订单已经存在
            $bizData = $response['data'];
            if (isset($bizData['respSubCode']) && $bizData['respSubCode'] == SupervisionDealService::CODE_ORDER_EXIST)
            {
                $financeService = new SupervisionFinanceService();
                $orderResult = $financeService->batchOrderSearch('T'.$record['out_order_id'], 8000);
                if (empty($orderResult['data']) || $orderResult['data']['respCode'] != '00')
                {
                    throw new \Exception('请求失败，请重新发起');
                }
                $bizData = $orderResult['data'];
                // 请求 结果处理逻辑
                return self::processReturnRepay($bizData);
            }
            return true;

        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * 还代偿款回调处理
     */
    public static function processReturnRepay($bizData)
    {
        // 取批次数据
        $outOrderId = substr($bizData['orderId'], 1, strlen($bizData['orderId']));
        $nongdanMdl = NongdanModel::instance()->findBy(" out_order_id = '{$outOrderId}'");
        $orderStatus = SupervisionEnum::$returnRepayMap[$bizData['status']];
        if (empty($orderStatus) || !$nongdanMdl)
        {
            PaymentApi::log('订单不存在或者状态回传错误, data:'.json_encode($bizData));
            return false;
        }
        if ($nongdanMdl->req_status == $orderStatus)
        {
            return true;
        }
        try {

            $nongdanMdl->db->startTrans();
            $fieldsUpadte  = [];
            $fieldsUpadte['req_status'] = $orderStatus;
            $fieldsUpadte['req_time']   = get_gmtime();
            $nongdanMdl->db->autoExecute('firstp2p_nongdan', $fieldsUpadte, 'UPDATE', " id = '{$nongdanMdl->id}' AND req_status IN (".implode(',', [NongdanModel::REQ_STATUS_INIT, NongdanModel::REQ_STATUS_SENDING]).')');
            $affRows = $nongdanMdl->db->affected_rows();
            if ($affRows <= 0)
            {
                throw new \Exception('订单已经处理');
            }
            // 操作成功
            switch ($orderStatus)
            {
                // 成功
                case NongdanModel::REQ_STATUS_SUCCESS:
                    $result = self::confirmTransfer($nongdanMdl);
                    if (!$result)
                    {
                        throw new \Exception('数据更新失败');
                    }
                break;
                case NongdanModel::REQ_STATUS_FAILURE:
                    // void
                break;
            }

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($bizData['orderId'], $bizData['status']);

            $nongdanMdl->db->commit();
            PaymentApi::log('更新用户还代偿款数据成功.');
        } catch (\Exception $e) {
            PaymentApi::log('更新用户还代偿款数据失败,失败原因:'.$e->getMessage());
            $nongdanMdl->db->rollback();
            return false;
        }
        return true;

    }

    /**
     * 更新数据结果
     */
    public static function processBatchTransfer($bizData)
    {
        // 取批次数据
        $outOrderId = substr($bizData['orderId'], 2, strlen($bizData['orderId']));
        $nongdanMdl = NongdanModel::instance()->findBy(" out_order_id = '{$outOrderId}'");
        $orderStatus = SupervisionEnum::$batchTransferMap[$bizData['status']];
        if (empty($orderStatus) || !$nongdanMdl)
        {
            throw new \Exception('订单不存在或者状态回传错误, data:'.json_encode($bizData));
        }
        if ($nongdanMdl->req_status == $orderStatus)
        {
            return true;
        }
        try {
            // 获取表名
            $tableName = NongdanModel::instance()->tableName();
            $nongdanMdl->db->startTrans();
            $fieldsUpadte  = [];
            $fieldsUpadte['req_status'] = $orderStatus;
            $fieldsUpadte['req_time']   = get_gmtime();
            $nongdanMdl->db->autoExecute($tableName, $fieldsUpadte, 'UPDATE', " id = '{$nongdanMdl->id}' AND req_status IN (".implode(',', [NongdanModel::REQ_STATUS_INIT, NongdanModel::REQ_STATUS_SENDING]).')');
            $affRows = $nongdanMdl->db->affected_rows();
            if ($affRows <= 0)
            {
                throw new \Exception('订单已经处理');
            }
            // 操作成功
            switch ($orderStatus)
            {
                // 成功
                case NongdanModel::REQ_STATUS_SUCCESS:
                    $result = self::confirmTransfer($nongdanMdl);
                    if (!$result)
                    {
                        throw new \Exception('数据更新失败');
                    }
                break;
                case NongdanModel::REQ_STATUS_FAILURE:
                    // void
                break;
            }

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($bizData['orderId'], $bizData['status']);

            $nongdanMdl->db->commit();
            PaymentApi::log('更新用户补息返利数据成功.');
        } catch (\Exception $e) {
            PaymentApi::log('更新用户补息返利数据失败,失败原因:'.$e->getMessage());
            $nongdanMdl->db->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * 返利补息成功处理逻辑, 给出款人扣款,给用户加钱
     * @param NongdanModel $record
     * @return boolean
     */
    public static function confirmTransfer($record)
    {
        // 检查转出用户
        $money = bcdiv($record['money'], 100, 2);
        //self::checkAccount($record['out_name'], self::ACCOUNT_PAYER, $money, $record['type']);
        //self::checkAccount($record['into_name'], self::ACCOUNT_RECEIVER, $money, $record['type']);

        $bizToken = [
            'orderId' => $record['out_order_id'],
        ];
        // 出资方扣款
        AccountService::changeMoney($record['pay_user_id'], $money, NongdanModel::$typeDesc[$record['type']], '交易流水号 '.$record['out_order_id'], AccountEnum::MONEY_TYPE_REDUCE, true, true, 0, $bizToken);

        // 受让方加钱
        AccountService::changeMoney($record['receive_user_id'], $money, NongdanModel::$typeDesc[$record['type']], '', AccountEnum::MONEY_TYPE_INCR, true, true, 0, $bizToken);

        return true;
    }
}
