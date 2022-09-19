<?php
namespace core\service;

use core\dao\WithdrawProxyModel;
use core\dao\WithdrawProxyCheckModel;
use core\dao\WithdrawProxyDebitionModel;
use core\dao\UserModel;
use core\dao\UserBankcardModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\BankModel;
use core\service\speedLoan\LoanService;
use NCFGroup\Common\Library\services\UcfpayGateway;
use NCFGroup\Common\Library\StandardApi as Api;
use NCFGroup\Common\Library\Idworker;
use core\service\ZxDealRepayService;

class WithdrawProxyService extends BaseService
{

    /**
     * 添加代发记录
     * @param array $withdrawData
     *      userId              integer 收款用户id
     *      projectId           integer 项目id
     *      projectName         string  项目名称
     *      merchantBatchNo     integer 项目批次号
     *      bizType             integer 业务类型
     *      merchantNo          integer 业务凭证号
     *      merchantId          string  打款商户号
     *      amount              integer 打款金额分
     *      memo                integer 打款备注
     * @throws Exception
     * @return boolean
     */
    public static function addWithdrawRecord($withdrawData)
    {
        $withdraw = new WithdrawProxyModel();

        if (empty($withdrawData['userId']))
        {
            throw new \Exception('用户id不能为空');
        }
        $withdraw->user_id = $withdrawData['userId'];

        if (empty($withdrawData['projectId']))
        {
            throw new \Exception('项目id不能为空');
        }
        $withdraw->project_id = $withdrawData['projectId'];

        if (empty($withdrawData['projectName']))
        {
            throw new \Exception('项目名称不能为空');
        }
        $withdraw->project_name = $withdrawData['projectName'];

        if (empty($withdrawData['merchantBatchNo']))
        {
            throw new \Exception('项目批次号不能为空');
        }
        $withdraw->merchant_batch_no = $withdrawData['merchantBatchNo'];

        if (empty($withdrawData['bizType']))
        {
            throw new \Exception('业务类型不能为空');
        }
        $withdraw->biz_type = $withdrawData['bizType'];

        if (empty($withdrawData['merchantNo']))
        {
            throw new \Exception('外部订单号不能为空');
        }
        $withdraw->merchant_no = $withdrawData['merchantNo'];

        if (empty($withdrawData['merchantId']))
        {
            throw new \Exception('外部订单号不能为空');
        }
        $withdraw->merchant_id = $withdrawData['merchantId'];

        if (empty($withdrawData['amount']))
        {
            throw new \Exception('金额不能为空');
        }
        $withdraw->amount = $withdrawData['amount'];

        $bankCardInfo = self::checkUserBankInfo($withdrawData['userId'], $withdrawData['bizType']);
        if (empty($bankCardInfo['accountName']))
        {
            throw new \Exception('收款人姓名不能为空');
        }
        $withdraw->account_name = $bankCardInfo['accountName'];

        if (empty($bankCardInfo['accountNo']))
        {
            throw new \Exception('收款账户不能为空');
        }
        $withdraw->account_no = $bankCardInfo['accountNo'];

        if (empty($bankCardInfo['bankNo']))
        {
            throw new \Exception('银行短码不能为空');
        }
        $withdraw->bank_no = $bankCardInfo['bankNo'];

        if ($bankCardInfo['userType'] == '')
        {
            throw new \Exception('银行卡类型不正确');
        }
        $withdraw->user_type = $bankCardInfo['userType'];

        // 选填项
        $withdraw->memo = !empty($withdrawData['memo']) ? $withdrawData['memo'] : '';
        $withdraw->merchant_no_seq = !empty($withdrawData['merchantNoSeq']) ? $withdrawData['merchantNoSeq'] : 0;
        $withdraw->request_no = Idworker::instance()->getId();
        $withdraw->trade_product = UcfpayGateway::TRADE_PRODUCT_NORMAL;
        if (!empty($bankCardInfo['bankIssuer']))
        {
            $withdraw->bank_issuer = $bankCardInfo['bankIssuer'];
        }
        // 默认项目
        $withdraw->create_time = time();

        // 检查第一笔订单是否已经存在, 已经存在则返回true
        $testWithdrawExists = WithdrawProxyModel::isWithdrawExists($withdraw->getRow());
        if ($testWithdrawExists === true)
        {
            return true;
        }

        // 如果单笔金额超过限制,则进行拆单
        if ($withdraw->amount > UcfpayGateway::SINGLE_PAY_AMOUNT_MAX)
        {
            try {
                $db = \libs\db\Db::getInstance('firstp2p', 'master');
                $db->startTrans();
                // fix 拆单计数器从业务传递过来的单号开始计算, 速贷如果每个类型都需要拆单, 则速贷生成代发记录的merchantNoSeq需要使用适合的步进来防止重复
                $seqNo = $withdraw->merchant_no_seq;
                // 计算需要拆分几单
                $maxSubOrders = ceil($withdrawData['amount'] / UcfpayGateway::SINGLE_PAY_AMOUNT_MAX);
                $remainAmount = $withdrawData['amount'] % UcfpayGateway::SINGLE_PAY_AMOUNT_MAX;
                $subOrderAmount = [];
                $subOrderAmount = array_pad($subOrderAmount, $maxSubOrders, UcfpayGateway::SINGLE_PAY_AMOUNT_MAX);
                // 最后一笔金额补充
                if ($remainAmount > 0) {
                    array_shift($subOrderAmount);
                    array_push($subOrderAmount, $remainAmount);
                }
                foreach ($subOrderAmount as $orderAmount)
                {
                    $withdraw->request_no = Idworker::instance()->getId();
                    $withdraw->merchant_no_seq = $seqNo ++;
                    $withdraw->amount = $orderAmount;

                    $subWithdraw = new WithdrawProxyModel();
                    $subOrderInfo = $withdraw->getRow();
                    unset($subOrderInfo['id']);
                    if (!self::createSingleWithdraw($subOrderInfo, $seqNo))
                    {
                        throw new \Exception('拆分提现订单失败');
                    }
                }
                $db->commit();
                return true;
            } catch (\Exception $e) {
                $db->rollback();
                return false;
            }
        }
        return self::createSingleWithdraw($withdraw->getRow());
    }

    /**
     * 创建单笔代发记录
     * @throws \Exception
     * @param array $withdrawData 代发记录数据
     * @param &integer $merchantNoSeq 代发记录拆单序号
     * @return boolean
     */
    public static function createSingleWithdraw($withdrawData, &$merchantNoSeq = 0)
    {
        $withdraw = new WithdrawProxyModel();

        // 如果不是本金和利息, 直接保存
        if (in_array($withdrawData['biz_type'], [WithdrawProxyModel::BIZ_TYPE_REPAY_PRINCIPAL,WithdrawProxyModel::BIZ_TYPE_REPAY_INTEREST]))
        {
            // 检查债权信息
            $debitionInfo = [
                'transferor_user_id' => $withdrawData['user_id'],
                'amount'    => $withdrawData['amount'],
            ];
            $amount = WithdrawProxyDebitionService::caculateDebitionAmount($debitionInfo);
            // 需要再次拆单, 原有的merchant_no_seq 怎么兼容
            $debitionWithdrawData = [];
            if ($amount !== false)
            {
                $bankCardInfo = self::checkUserBankInfo($withdrawData['user_id'], WithdrawProxyModel::BIZ_TYPE_DEBITION);
                // 修改收款账户信息
                $debitionWithdrawData = $withdrawData;
                $remainAmount = $withdrawData['amount'] - $amount;
                if ($remainAmount <= 0) {
                    $withdrawData['account_name']        = $bankCardInfo['accountName'];
                    $withdrawData['account_no']          = $bankCardInfo['accountNo'];
                    $withdrawData['bank_no']             = $bankCardInfo['bankNo'];
                    $withdrawData['user_type']           = $bankCardInfo['userType'];
                    $withdrawData['bank_issuer']         = $bankCardInfo['bankIssuer'];

                    // 用户此次回款全部本金用于偿还债权
                    $withdrawData['biz_type'] = WithdrawProxyModel::BIZ_TYPE_DEBITION;
                    $debitionWithdrawData['amount'] = $amount;
                    if (!WithdrawProxyDebitionService::updateDebition($debitionWithdrawData))
                    {
                        throw new \Exception("更新债权信息失败");
                    }
                } else if ($remainAmount > 0) {
                    // 此次汇款金额大于剩余债权信息,需要拆单
                    $debitionWithdrawData['account_name']        = $bankCardInfo['accountName'];
                    $debitionWithdrawData['account_no']          = $bankCardInfo['accountNo'];
                    $debitionWithdrawData['bank_no']             = $bankCardInfo['bankNo'];
                    $debitionWithdrawData['user_type']           = $bankCardInfo['userType'];
                    $debitionWithdrawData['bank_issuer']         = $bankCardInfo['bankIssuer'];

                    $debitionWithdrawData['request_no'] = Idworker::instance()->getId();
                    $debitionWithdrawData['merchant_no_seq'] = $merchantNoSeq ++;
                    $debitionWithdrawData['amount'] = $amount;
                    $debitionWithdrawData['biz_type'] = WithdrawProxyModel::BIZ_TYPE_DEBITION;
                    $debitionWithdraw = new WithdrawProxyModel();
                    $debitionWithdraw->setRow($debitionWithdrawData);
                    if (!$debitionWithdraw->save())
                    {
                        throw new \Exception("创建偿还债权代发数据失败");
                    }
                    // 剩余金额走正常代发逻辑
                    $withdrawData['amount'] = $remainAmount;
                    $withdrawData['merchant_no_seq'] = $merchantNoSeq ++;
                    if (!WithdrawProxyDebitionService::updateDebition($debitionWithdrawData))
                    {
                        throw new \Exception("更新债权信息失败");
                    }
                }
            }
        }
        $withdraw->setRow($withdrawData);
        if (!$withdraw->save())
        {
            throw new \Exception("创建代发记录失败");
        }
        return true;
    }

    // 银信通服务费收款账户信息
    const CREDIT_LOAN_FEE_ACCOUNT       = '8981210120000019012';
    // 银信通服务费收款账户名称
    const CREDIT_LOAN_FEE_ACCOUNT_NAME  = '北京经讯时代科技有限公司';
    // 银信通服务费收款银行
    const CREDIT_LOAN_BANK_NO           = 'HKBC';
    // 银信通服务费联行号
    const CREDIT_LOAN_BANK_ISSUER       = '314641000014';
    const SPEED_LOAN_BANK_ISSUER        = '314641000014';

    /**
     * 检查用户收款账户信息
     * @param integer $userId   收款用户id
     * @param integer $bizType  打款类型
     * @throws Exception
     * @return array
     */
    private static function checkUserBankInfo($userId, $bizType = WithdrawProxyModel::BIZ_TYPE_REPAY_PRINCIPAL)
    {
        $result = [];
        switch ($bizType)
        {
            case WithdrawProxyModel::BIZ_TYPE_REPAY_PRINCIPAL:
            case WithdrawProxyModel::BIZ_TYPE_REPAY_INTEREST:
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_RETURN:
            case WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_RETURN:
                $user = UserModel::instance()->find($userId);
                if (!$user)
                {
                    throw new \Exception('打款用户信息不存在');
                }
                $userBankcard = UserBankcardModel::instance()->findBy('user_id = '.$userId);
                if (!$userBankcard)
                {
                    throw new \Exception('打款用户尚未绑定银行卡');
                }
                $bank   = BankModel::instance()->find($userBankcard->bank_id);
                if (!$bank)
                {
                    throw new \Exception('尚未支持的银行卡');
                }
                // 银行卡信息
                $result['accountNo']    = $userBankcard->bankcard; // 开户名
                $result['accountName']  = $userBankcard->card_name;
                // 银行简码
                $result['bankNo']       = $bank->short_name;
                // 对公or对私
                $result['userType']     = $userBankcard->card_type == UserBankcardModel::CARD_TYPE_PERSONAL ? WithdrawProxyModel::USER_TYPE_PERSON : WithdrawProxyModel::USER_TYPE_PUBLIC;
                // 联行号
                $result['bankIssuer']   = $userBankcard->branch_no;
                break;
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_PRINCIPAL:
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_INTEREST:
                $user = UserModel::instance()->find($userId);
                if (!$user)
                {
                    throw new \Exception('打款用户信息不存在');
                }
                $userBankcard = UserBankcardModel::instance()->findBy('user_id = '.$userId);
                if (!$userBankcard)
                {
                    throw new \Exception('打款用户尚未绑定银行卡');
                }
                // 银行卡信息
                $result['accountNo']    = $userBankcard->p_account;
                // 开户名
                $result['accountName']  = $userBankcard->card_name;
                // 银行简码
                $result['bankNo']       = 'HKBC';
                // 对公对私
                $result['userType']     = $userBankcard->card_type == UserBankcardModel::CARD_TYPE_PERSONAL ? WithdrawProxyModel::USER_TYPE_PERSON : WithdrawProxyModel::USER_TYPE_PUBLIC;
                // 联行号
                // 联行号
                $result['bankIssuer']   = UcfpayGateway::UNITED_BANK_ISSUER;
                break;
            case WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN:
                // 银行卡信息
                $result['accountNo']    = app_conf('SPEED_LOAN_MERCHANT_ACCOUNT');
                // 开户名
                $result['accountName']  = app_conf('SPEED_LOAN_MERCHANT_NAME');
                // 银行简码
                $result['bankNo']       = 'HKBC';
                // 对公对私
                $result['userType']     = WithdrawProxyModel::USER_TYPE_PUBLIC;
                // 联行号
                $result['bankIssuer']   = self::SPEED_LOAN_BANK_ISSUER;
                break;
            case WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_FEE:
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_FEE:
                // 银行卡信息
                $result['accountNo']    = self::CREDIT_LOAN_FEE_ACCOUNT;
                // 开户名
                $result['accountName']  = self::CREDIT_LOAN_FEE_ACCOUNT_NAME;
                // 银行简码
                $result['bankNo']       = self::CREDIT_LOAN_BANK_NO;
                // 对公对私
                $result['userType']     = WithdrawProxyModel::USER_TYPE_PUBLIC;
                // 联行号
                $result['bankIssuer']   = self::CREDIT_LOAN_BANK_ISSUER;
                break;
            case WithdrawProxyModel::BIZ_TYPE_DEBITION:
                $accountInfo = WithdrawProxyDebitionModel::findDebitionByTransferorId($userId);
                if (empty($accountInfo))
                {
                    throw new \Exception('受让方银行账户信息不存在');
                }
                // 银行卡信息
                $result['accountNo']    = $accountInfo['transferee_account']; // 卡号
                $result['accountName']  = $accountInfo['transferee_name']; // 开户名
                // 银行简码
                $result['bankNo']       = $accountInfo['transferee_bank_code'];
                // 对公or对私
                $result['userType']     = $accountInfo['transferee_user_type'];
                // 联行号
                $result['bankIssuer']   = $accountInfo['transferee_issuer'];

                break;
        }
        return $result;
    }

    /**
     * 处理提现结果
     */
    public static function handleResponse($response, $merchantId = '')
    {
        $outerOrderId = $response['merchantNo'];
        if (empty($outerOrderId))
        {
            throw new \Exception('withdraw order notify carries an empty merchantNo');
        }
        $withdraw = WithdrawProxyModel::instance()->findBy(" request_no = '{$outerOrderId}' ");
        if (!$withdraw)
        {
            throw new \Exception('Withdraw order#'.$outerOrderId.' not exist.');
        }
        $withdraw = $withdraw->getRow();
        // 需要更新的业务数据
        $withdrawUpdateFields = [];
        //  是否开启事务
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $_startTrans = false;
        try {
            // 更新修改时间
            $withdrawUpdateFields['update_time']    = time();
            // 支付交易流水号
            $withdrawUpdateFields['trade_no']       = $response['tradeNo'];
            // 代发成功
            if ($response['status'] == UcfpayGateway::STATUS_SUCCESS)
            {
                if ($withdraw['order_status'] == WithdrawProxyModel::ORDER_STATUS_SUCCESS)
                {
                    return true;
                }
                if ($withdraw['order_status'] == WithdrawProxyModel::ORDER_STATUS_FAILURE)
                {
                    throw new \Exception('Update order failed,  order#'.$withdraw['request_no'] .' is already failed and can not update to success');
                }
                $db->startTrans();
                $_startTrans = true;
                $withdrawUpdateFields['order_status']   = WithdrawProxyModel::ORDER_STATUS_SUCCESS;
                $conditions = ' order_status = '. WithdrawProxyModel::ORDER_STATUS_SENDING." AND request_no = '{$outerOrderId}' ";
                $db->autoExecute('firstp2p_withdraw_proxy', $withdrawUpdateFields, 'UPDATE', $conditions);
                $affRows = $db->affected_rows();
                if ($affRows <= 0)
                {
                    throw new \Exception('Update withdraw#'.$withdraw['merchant_no'].' failed');
                }
                $db->commit();
                return true;
            }

            //代发失败
            if ($response['status'] == UcfpayGateway::STATUS_FAIL)
            {
                // 失败响应消息
                $withdrawUpdateFields['resp_message']   = $response['resMessage'];
                if ($withdraw['order_status'] == WithdrawProxyModel::ORDER_STATUS_FAILURE)
                {
                    return true;
                }
                if ($withdraw['order_status'] == WithdrawProxyModel::ORDER_STATUS_FAILURE)
                {
                    throw new \Exception('Update order failed,  order#'.$withdraw['request_no'] .' is already succeeded and can not update to failure');
                }

                $db->startTrans();
                $_startTrans = true;
                $withdrawUpdateFields['order_status']  = WithdrawProxyModel::ORDER_STATUS_FAILURE;
                $withdrawUpdateFields['fail_reason']      = $response['resMessage'];

                $conditions = ' order_status = '.WithdrawProxyModel::ORDER_STATUS_SENDING. " AND request_no = '{$outerOrderId}' ";
                $db->autoExecute('firstp2p_withdraw_proxy', $withdrawUpdateFields, 'UPDATE', $conditions);
                $affRows = $db->affected_rows();
                if ($affRows <= 0)
                {
                    throw new \Exception('代发处理失败');
                }

                $db->commit();
                return true;
            }
            // 其他特殊错误
            return false;
        } catch (\Exception $e) {
            if ($_startTrans)
            {
                $db->rollback();
            }
            Logger::error($e->getMessage());
            throw $e;
        }
    }

    /**
     * 查询订单状态(业务方使用)
     * @param integer $merchantNo 业务单号
     * @return array
     */
    public static function searchWithdrawProxyRecord($merchantNo)
    {
        $record = WithdrawProxyModel::instance()->findBy(" merchant_no = '".addslashes($merchantNo)."'");
        if (!$record)
        {
            return [];
        }
        return $record->getRow();
    }

    public static function processRequest($withdrawProxyInfo)
    {
        $gateway = Api::instance(Api::UCFPAY_GATEWAY);
        // 构造请求支付代发接口的参数
        $params =[
            'merchantNo'    => $withdrawProxyInfo['request_no'],
            'merchantId'    => $withdrawProxyInfo['merchant_id'],
            'amount'        => $withdrawProxyInfo['amount'],
            'accountNo'     => $withdrawProxyInfo['account_no'],
            'accountName'   => $withdrawProxyInfo['account_name'],
            'userType'      => $withdrawProxyInfo['user_type'],
            'bankNo'        => $withdrawProxyInfo['bank_no'],
            'issuer'        => $withdrawProxyInfo['bank_issuer'],
            //'tradeProuct'   => $withdrawProxyInfo['product_type'],
            'noticeUrl'     => app_conf('NOTIFY_DOMAIN').'/payment/withdrawProxyNotify',
        ];
        try {
            $response = $gateway->request(UcfpayGateway::SERVICE_WITHDRAW, $params);
            // 如果订单已存在
            if (!empty($response['resCode']) && $response['resCode'] == UcfpayGateway::RESPONSE_ORDER_EXIST)
            {
                $queryParams = ['merchantNo' => $withdrawProxyInfo['request_no'], 'merchantId' => $withdrawProxyInfo['merchant_id']];
                $queryResponse = $gateway->request(UcfpayGateway::SERVICE_ORDER_QUERY, $queryParams);
                $resposne = $queryResponse;
            }
            // 如果无响应
            if (empty($resposne))
            {
                return false;
            }
            return self::handleResponse($response);
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * 重新代发
     * @param integer $id
     * @throws \Exception
     */
    public static function redoWithdrawProxy($id)
    {
        if (empty($id))
        {
            throw new \Exception('代发记录ID不能为空');
        }

        $withdrawProxyInfo = WithdrawProxyModel::instance()->find($id);
        if (!$withdrawProxyInfo)
        {
            throw new \Exception('代发记录不存在');
        }

        // 要复制的新数据
        $newWithdrawInfo = $withdrawProxyInfo->getRow();
        unset($newWithdrawInfo['id']);
        unset($newWithdrawInfo['trade_no']);
        unset($newWithdrawInfo['resp_message']);
        $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
        $newWithdrawInfo['retry_admin_name']    = $adminInfo['adm_name'];
        $newWithdrawInfo['update_time']         = 0;
        $newWithdrawInfo['create_time']         = time();
        $newWithdrawInfo['order_status']        = WithdrawProxyModel::ORDER_STATUS_SENDING;
        $newWithdrawInfo['request_no']          = Idworker::instance()->getId();
        $newWithdrawInfo['fallback_counter'] ++;
        // 更新用户银行卡数据
        $bankCardInfo = self::checkUserBankInfo($withdrawProxyInfo->user_id, $withdrawProxyInfo->biz_type);
        $newWithdrawInfo['account_name']        = $bankCardInfo['accountName'];
        $newWithdrawInfo['account_no']          = $bankCardInfo['accountNo'];
        $newWithdrawInfo['bank_no']             = $bankCardInfo['bankNo'];
        $newWithdrawInfo['user_type']           = $bankCardInfo['userType'];
        $newWithdrawInfo['bank_issuer']         = $bankCardInfo['bankIssuer'];

        // 更新原来的代发记录状态为已重新提现
        $updateOldInfo = [
            'order_status'      => WithdrawProxyModel::ORDER_STATUS_FALLBACK,
            'retry_admin_name'  => $adminInfo['adm_name'],
            'update_time'       => time(),
        ];
        $db = $withdrawProxyInfo->db;
        try {
            $db->startTrans();
            $withdrawProxyInfo->update($updateOldInfo);
            $affRows = $db->affected_rows();
            if ($affRows <= 0)
            {
                throw new \Exception('更新原始代发记录状态失败');
            }

            $newWithdrawProxyInfo = new WithdrawProxyModel();
            $newWithdrawProxyInfo->setRow($newWithdrawInfo);
            if (!$newWithdrawProxyInfo->save())
            {
                throw new \Exception('重新代发失败,请重试');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }


    }


    /**
     * 处理业务通知
     */
    public static function processNotify($data)
    {
        if ($data['order_status'] != WithdrawProxyModel::ORDER_STATUS_SUCCESS || $data['notify_service_success'] != WithdrawProxyModel::NOTIFY_SERVICE_WAIT)
        {
            return false;
        }

        //TODO 通知业务直到业务成功
        $allSuccess = self::isMerchantOrderSuccess($data);
        $result = false;
        try {
            switch ($data['biz_type']) {
            case WithdrawProxyModel::BIZ_TYPE_REPAY_PRINCIPAL:
            case WithdrawProxyModel::BIZ_TYPE_REPAY_INTEREST:
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_RETURN:
            case WithdrawProxyModel::BIZ_TYPE_DEBITION:
                if ($allSuccess)
                {
                    $dealRepayService = new ZxDealRepayService();
                    $result = $dealRepayService->transferCallBack($data['merchant_no']);
                }
                break;
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_INTEREST:
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_FEE:
            case WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_PRINCIPAL:
                if ($allSuccess)
                {
                    $dealRepayService = new ZxDealRepayService();
                    $result = $dealRepayService->transferCallBack($data['merchant_no']);
                }
                break;
            case WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_FEE:
            case WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN:
            case WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_RETURN:
            // TODO 通知速贷还款成功
                if ($allSuccess)
                {
                    $loanService = new LoanService();
                    $result = $loanService->withdrawNotify($data['merchant_no'], '00', $data['request_no']);
                }
                break;
            }
            if ($result !== true)
            {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        // 更新执行状态
        return WithdrawProxyModel::instance()->updateNotifySuccess($data['id']);
    }

    /**
     * 根据项目批次号获取速贷的代发金额,如果传递第二个参数,则直接进行金额的比较, 否则返回
     */
    public static function sumByMerchantBatchNo($batchNo, $compareAmount = 0)
    {
        $amountPaid = WithdrawProxyModel::sumByMerchantBatchNo($batchNo, [WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_FEE, WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN, WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_RETURN]);
        if ( $compareAmount > 0 && $amountPaid < $compareAmount)
        {
            return false;
        } else if ($compareAmount > 0 && $amountPaid == $compareAmount) {
            return true;
        }
        return $amountPaid;
    }

    /**
     * 读取指定商户可用余额
     *
     * @param string $merchantId 代发商户编码
     * @return boolean | integer  如果失败返回false, 正确返回用户可用余额 单位分
     */
    public static function queryMerchantBalance($merchantId)
    {
        $queryParams = ['merchantId' => $merchantId];
        try {
            $result = Api::instance('ucfpayGateway')->request(UcfpayGateway::SERVICE_MER_BALANCE, $queryParams);
            if (empty($result) || !isset($result['available']))
            {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        return $result['available'];
    }


    public static function resetNotifyCounter($withdrawId)
    {
        return WithdrawProxyModel::instance()->resetNotifyCounter($withdrawId);
    }

    /**
     * 根据项目代发批次号汇总项目数据
     */
    public static function getSummaryByMerchantNo($merchantBatchNo)
    {
        $merchantBatchNo = addslashes($merchantBatchNo);
        $summary = [
            'merchantBatchNo'   => $merchantBatchNo,
            'cntTotal'          => 0,
            'amtTotal'          => 0,
            'bizTypeCntList'    => array_combine(array_keys(WithdrawProxyModel::$bizTypeDesc),array(0,0,0,0,0,0,0,0,0)),
            'bizTypeAmtList'    => array_combine(array_keys(WithdrawProxyModel::$bizTypeDesc),array(0,0,0,0,0,0,0,0,0)),
            'orderStatusCntList'=> array_combine(array_keys(WithdrawProxyModel::$orderStatusDesc),array(0,0,0,0,0)),
            'orderStatusAmtList'=> array_combine(array_keys(WithdrawProxyModel::$orderStatusDesc),array(0,0,0,0,0)),
        ];
        $records = \libs\db\Db::getInstance('firstp2p','slave')->getAll("SELECT id,amount,order_status,biz_type,user_type,notify_service_success FROM firstp2p_withdraw_proxy WHERE merchant_batch_no = '{$merchantBatchNo}' AND order_status != ".WithdrawProxyModel::ORDER_STATUS_FALLBACK);
        if (!is_array($records))
        {
            return $summary;
        }
        foreach ($records as $record)
        {
            $amount = bcdiv($record['amount'], 100, 2);
            $summary['cntTotal'] ++;
            $summary['amtTotal'] += $amount;
            $summary['bizTypeCntList'][$record['biz_type']] ++;
            $summary['bizTypeAmtList'][$record['biz_type']] += $amount;
            $summary['orderStatusCntList'][$record['order_status']] ++;
            $summary['orderStatusAmtList'][$record['order_status']] += $amount;
        }
        return $summary;
    }

    /**
     * 判断商户订是否全部成功,包含拆单的订单
     */
    public static function isMerchantOrderSuccess($order)
    {
        $baseSql = "SELECT COUNT(*) FROM firstp2p_withdraw_proxy WHERE merchant_no = '{$order['merchant_no']}' AND order_status != ".WithdrawProxyModel::ORDER_STATUS_FALLBACK;
        $totalOrder = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne($baseSql);
        $totalSuccessOrderCnt = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne($baseSql.' AND order_status = '.WithdrawProxyModel::ORDER_STATUS_SUCCESS);
        return $totalOrder == $totalSuccessOrderCnt;
    }

    const CHECK_FTP_ROOT = 'SMrWy2LGBPJsU1q.ucfpay.com';
    const CHECK_FTP_PORT = 21;
    static $merchantIds = array(
        'M200006745' => 'XkvN78Ba',
        'M200006814' => '2W1JHtgs',
    );

    public static function download($merchantId, $date, $overwrite = false)
    {
        if (empty($date))
        {
            $date = date('Y-m-d');
        }
        if (!in_array($merchantId, array_keys(self::$merchantIds)))
        {
            throw new \Exception('商户对账信息配置不正确');
        }
        $config = array(
            'ftp_host'      => self::CHECK_FTP_ROOT,
            'ftp_username'  => $merchantId,
            'ftp_password'  => self::$merchantIds[$merchantId],
        );
        list($year,$month,$day) = explode('-', $date);
        $remoteFile =  "/{$year}/{$month}/{$year}{$month}{$day}-Daifa-{$merchantId}.txt";
        $remoteFileConfirm  =  "/{$year}/{$month}/{$year}{$month}{$day}-Daifa-{$merchantId}.OK";
        $localFile = "/tmp/{$date}{$merchantId}.txt";
        $ftp = new \libs\vfs\fds\FdsFTP($config);
        $result = $ftp->download($remoteFile, $localFile);
        if (!file_exists($localFile))
        {
            throw new \Exception('下载对账文件失败, 请稍后再试');
        }
        return self::checkFile($localFile, $merchantId, $date, $overwrite);
    }

    public static function checkFile($fileFullName, $merchantId, $date, $forceUpdate = false)
    {
        $date = str_replace('-', '', $date);
        // 检查是否已经完成当日对账
        $record = WithdrawProxyCheckModel::instance()->findBy("merchant_id = '{$merchantId}' AND check_date = '{$date}'");
        if ($record && !$forceUpdate)
        {
            return true;
        }
        // 解析文件,一行一行
        $fp = fopen($fileFullName, 'r+');
        try {
            while ($contents = fgets($fp))
            {
                $row = explode('|', $contents);
                $mapper = self::getFileMapper();
                foreach ($mapper['bindColums'] as $columnIdx => $fieldName)
                {
                    $combineRow[$fieldName] = strval($row[$columnIdx]);
                    if ($fieldName == 'remote_success_time')
                    {
                        $combineRow[$fieldName] = strtotime($row[$columnIdx]);
                    }
                }
                $combineRow['type']         = 0;
                $combineRow['check_date'] = $date;
                $combineRow['create_time']  = time();

                $checkInfo = [
                    'orderId' => $combineRow['order_id'],
                    'orderStandStatus'   => $combineRow['remote_order_status'],
                    'amount'   => $combineRow['remote_amount'],
                ];
                $checkResult = self::checkWithdrawProxy($checkInfo, false, $dbRecord);

                $combineRow['check_status']  = intval($checkResult);
                if (!empty($dbRecord))
                {
                    $combineRow['amount'] = $dbRecord['amount'];
                    $combineRow['order_status'] = self::getP2pOrderStatus($dbRecord);
                    $combineRow['success_time'] = $dbRecord['update_time'];
                }

                $record = new WithdrawProxyCheckModel();
                $record->setRow($combineRow);
                $record->save();
            }
        } catch (\Exception $e) {
            \libs\utils\PaymentApi::log('Checker::checkFile failed, '.$e->getMessage());
            return false;
        }
        return true;

    }

    public static function checkDb($merchantId, $date, $forceUpdate = false)
    {
        $termIds = WithdrawProxyModel::getRecordId($merchantId, $date);
        $beginRecordId = $termIds['beginRecordId'];
        $endRecordId = $termIds['endRecordId'];
        if (empty($beginRecordId) or empty($endRecordId))
        {
            throw new \Exception('无对账数据');
        }
        $start = $beginRecordId;
        while ( $start <= $endRecordId)
        {
            // 动态step , 如果 start + step > endRecordId , 则实际step 为 endRecordId - start
            $step = 1000;
            if ($start + $step > $endRecordId)
            {
                $step = $endRecordId - $start + 1;
            }
            $list = WithdrawProxyModel::getRecordList($start, $start + $step);
            $start += $step;
            if(empty($list))
            {
                continue;
            }
            while ($record = array_pop($list))
            {
                // 通用的数据信息
                $checkInfo = [
                    'orderId' => $record['request_no'],
                    'orderStandStatus'   => self::getP2pOrderStatus($record),
                    'amount'   => $record['amount'],
                    'orderId'   => $record['request_no'],
                ];
                $checkResult = [];
                $checkResult['check_status'] = self::checkWithdrawProxy($checkInfo, true);
                $checkResult['amount'] = $record['amount'];
                $checkResult['order_status'] = $record['order_status'];
                $checkResult['success_time'] = $record['update_time'];
                $checkResult['order_id'] = $record['request_no'];
                $checkResult['merchant_id'] = $merchantId;
                $checkResult['check_date'] = $date;
                // 查找文件导入的数据中是否存在此订单
                $withdrawProxyCheckRecord = WithdrawProxyCheckModel::instance()->findBy(" merchant_id = '{$merchantId}' AND check_date = '{$date}' AND order_id = '{$record['request_no']}'");
                if ($withdrawProxyCheckRecord)
                {
                    // 更新
                    $withdrawProxyCheckRecord->update($checkResult);
                } else {
                    // 写入
                    $record = new WithdrawProxyCheckModel();
                    $checkResult['create_time'] = time();
                    $record->setRow($checkResult);
                    $record->save();
                }
            }
        }
        return true;
    }

    /**
     * 单条记录对账逻辑
     * @param $checkInfo  对账核心数据
     * @param sourceP2p 对账方向 true 则为 p2p数据核对存管数据
     * @return integer 对账结果 见self::CHECK_RESULT*
     */
    public static function checkWithdrawProxy($checkInfo, $sourceP2p = false, &$targetRecord = array())
    {
        //$targetRecord = '';
        if ($sourceP2p)
        {
            $targetRecord = WithdrawProxyCheckModel::instance()->findBy(" order_id = '{$checkInfo['orderId']}'");
            if (!$targetRecord || empty($targetRecord['remote_order_status']))
            {
                return WithdrawProxyCheckModel::CHECK_RESULT_GATEWAY_ORDER_NOT_EXIST;
            }
            $targetRecord['orderStandStatus'] = $targetRecord['remote_order_status'];
            $targetRecord['amount'] = $targetRecord['remote_amount'];
        } else {
            $targetRecord = WithdrawProxyModel::instance()->getOrderInfo($checkInfo['orderId']);
            if (!$targetRecord)
            {
                return WithdrawProxyCheckModel::CHECK_RESULT_P2P_ORDER_NOT_EXSIT;
            }
            $targetRecord['orderStandStatus'] = self::getP2pOrderStatus($targetRecord);
        }
        if ($checkInfo['amount'] != $targetRecord['amount'])
        {
            return WithdrawProxyCheckModel::CHECK_RESULT_AMOUNT_NOT_EQUAL;
        }
        if ($checkInfo['orderStandStatus'] != $targetRecord['orderStandStatus'])
        {
            return WithdrawProxyCheckModel::CHECK_RESULT_STATUS_NOT_SAME;
        }
        return WithdrawProxyCheckModel::CHECK_RESULT_OK;
    }

    public static function getP2pOrderStatus($orderInfo)
    {
        switch($orderInfo['order_status'])
        {
            case WithdrawProxyModel::ORDER_STATUS_SUCCESS: return 'S';
            case WithdrawProxyModel::ORDER_STATUS_FAILURE: return 'F';
        }
    }

    public static function getFileMapper($fileType = 'withdrawproxy')
    {
        return [
            'bindColums' => [0 => 'merchant_id', 1 => 'order_id', 4 => 'remote_amount', 6 => 'remote_order_status', 7 => 'remote_success_time']
        ];
    }
}
