<?php
/**
 *  提现
 * User: lys
 * Date: 2015/6/15
 * Time: 15:34
 */
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Protos\Ptp\RequestPaymentCashOut;
use NCFGroup\Protos\Ptp\RequestPaymentCashOutV2;
use core\service\PaymentService;
use core\service\ChargeService;
use core\service\UserService;
use core\service\UserBankcardService;
use core\service\SupervisionOrderService; // 存管订单服务
use core\dao\FinanceQueueModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\UserCarryModel;
use core\dao\PaymentNoticeModel;
use libs\db\Db;
use libs\utils\PaymentApi;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;
use core\service\WithdrawProxyService;
use core\service\ApiConfService;
use core\dao\WithdrawProxyModel;

use NCFGroup\Protos\Ptp\RequestDeposit;
use NCFGroup\Protos\Ptp\ResponseDeposit;
use NCFGroup\Protos\Ptp\RequestWithdraw;
use NCFGroup\Protos\Ptp\ResponseWithdraw;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\StandardApi;

use NCFGroup\Protos\Creditloan\RequestCommon;

class PtpPaymentService extends ServiceBase {
    /**
     * 业务类型-充值
     * @var int
     */
    const BUSINESS_TYPE_1 = 1;

    /**
     * 业务类型-提现
     * @var int
     */
    const BUSINESS_TYPE_2 = 2;

    /**
     * 业务类型-转账
     * @var int
     */
    const BUSINESS_TYPE_3 = 3;

    /**
     * 转账业务类型-可用->可用
     * @var int
     */
    const TRANSFER_TYPE_1 = 1;

    /**
     * 转账业务类型-可用->冻结
     * @var int
     */
    const TRANSFER_TYPE_2 = 2;

    /**
     * 转账业务类型-冻结->可用
     * @var int
     */
    const TRANSFER_TYPE_3 = 3;

    /**
     * 转账业务类型-冻结->冻结
     * @var int
     */
    const TRANSFER_TYPE_4 = 4;

    /**
     * 业务类型
     * @var array
     */
    private static $businessTypeMap = array(
        self::BUSINESS_TYPE_1 => '充值',
        self::BUSINESS_TYPE_2 => '提现',
        self::BUSINESS_TYPE_3 => '转账',
    );

    /**
     * 转账业务类型
     * @var array
     */
    private static $transferTypeMap = array(
        self::TRANSFER_TYPE_1 => '可用->可用',
        self::TRANSFER_TYPE_2 => '可用->冻结',
        self::TRANSFER_TYPE_3 => '冻结->可用',
        self::TRANSFER_TYPE_4 => '冻结->冻结',
    );

    /**
     * 资金冻结/解冻类型
     * @var array
     */
    private static $freezeMap = array(
        'freeze' => '冻结',
        'unfreeze' => '解冻',
        'consume' => '扣减冻结',
    );

    /**
     * 提现申请
     */
    public function cashOut(RequestPaymentCashOut $request){
        $userId = $request->getUserId();
        $money = $request->getMoney();
        $os = $request->getOs();

        // 获取提现时效配置
        $apiConfObj = new ApiConfService();
        $withdrawTimeConf = $apiConfObj->getWithdrawTime();

        $paymentService = new PaymentService();
        $result = $paymentService->cashOut($userId, $money, $os);
        $ret = array('success' => 0, 'msg' => '申请提现成功，预计' . $withdrawTimeConf . '个工作日内到账, 实际到账时间依据账户托管方及提现银行而有所差异');
        if ($result === false){
            $ret = array('success' => 1, 'msg' => '申请提现失败，请稍后重试');
        }
        return $ret;
    }


    /**
     * 提现申请V2.0
     */
    public function cashOutV2(RequestPaymentCashOutV2 $request){
        $response = new ResponseBase();
        try{
            if (!$this->checkSign($request)) {
                throw new \Exception("验签失败", 21000);
            }
        }catch(\Exception $e){
            $response->msg = $e->getMessage();
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }

        $userId = $request->getUserId();
        $money = $request->getMoney();
        $os = $request->getOs();
        $bankCardId = $request->getBankCardId();


        if(empty($os)){
            // 到时候改一下体现来源理财师
            $os = PaymentNoticeModel::PLATFORM_LCS;
        }

        if (!empty($os) && !in_array($os,array(PaymentNoticeModel::PLATFORM_LCS))){
            $response->msg = 'os类型错误';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        if(bccomp($money,0.00,2) <= 0){
            $response->msg = '提现金额错误';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);

        //风控
        RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH,Risk::PF_OPEN_API,$this->device)->check($userInfo, Risk::ASYNC,array('os'=>$os,'money'=>$money));

        $bankService = new UserBankcardService();
        $bankCardInfo = $bankService->getBankcard($userId);

        if (empty($bankCardInfo) || empty($bankCardInfo['bankcard']) || formatBankcard($bankCardInfo['bankcard'])!=$bankCardId ) {
            $response->msg = '银行信息不符，请联系管理员';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        // 检查用户身份认证
        if ($userInfo['idcardpassed'] == 0){
            $response->msg = '身份认证异常，请联系管理员';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        if ($userInfo['idcardpassed'] == 3){
            $response->msg = '用户与银行信息不符，请联系管理员';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        // Protos 返回的用户金额增加了千分位， 不能直接计算
        $userMoney = str_replace(',', '', $userInfo['money']);
        // 检查用户提现金额是否小于可用余额
        if (bccomp($userMoney, $money, 2) < 0)
        {
            $response->msg = '用户提现金额不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }

        $carryService = new \core\service\UserCarryService();
        // 超级账户提现不计算红包金额
        $canWithdraw = $carryService->canWithdrawAmount($userId, $money, false, false);
        if (!$canWithdraw) {
            $response->msg = '提现不予受理，请联系管理员';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        $canWithdraw = $carryService->canWithdraw($userInfo['userId'], $money);
        if (!$canWithdraw['result']) {
            $response->msg = $canWithdraw['reason'];
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }

        // 获取提现时效配置
        $apiConfObj = new ApiConfService();
        $withdrawTimeConf = $apiConfObj->getWithdrawTime();

        $paymentService = new PaymentService();
        $result = $paymentService->cashOut($userId, $money, $os);
        $ret = array('success' => 0, 'msg' => '申请提现成功，预计' . $withdrawTimeConf . '个工作日内到账, 实际到账时间依据账户托管方及提现银行而有所差异');
        if ($result === false){
            $response->msg = '申请提现失败，请稍后重试';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH,Risk::PF_OPEN_API)->notify();
        $response->rescode = RPCErrorCode::SUCCESS;
        $response->msg = '申请提现成功，预计' . $withdrawTimeConf . '个工作日内到账, 实际到账时间依据账户托管方及提现银行而有所差异';
        return $response;
    }

    /**
     * 是否可以提现
     */
    public function canWithdrawAmount(RequestCanWithdrawAmount $request) {
        $userId = $request->getUserId();
        $money = $request->getAmount();
        $userCarryService = new \core\service\UserCarryService();
        // 超级账户提现不计算红包金额
        $result = $userCarryService->canWithdrawAmount($userId, $money, false, false);
        $ret = array('result' => $result);
        return $ret;
    }

    public function depositList(RequestDeposit $request){
        $userId = $request->getUserId();
        // 默认从第一页开始
        $pn = $request->getPageNum();
        $pn = empty($pn)?1:$pn;
        // 默认一页10条纪录
        $ps = $request->getPageSize();
        $ps = empty($ps)?10:$ps;
        $offset = ($pn-1)*$ps;

        $uls = new  \core\service\UserLogService();
        $list = $uls->get_charge_list($userId,$offset,$ps);
        $response = new ResponseDeposit();
        if (!empty($list)) {
            $ret = array();
            foreach($list as $one){
                $tmp = array(
                    'notice_sn'=>$one['notice_sn'],
                    'status_cn'=>$one['status_cn'],
                    'money' => $one['money'],
                    'fee'=>$one['fee'],
                    'create_time'=>$one['create_time']+28800,
                );
                $ret[] = $tmp;
            }
            $response->setList($ret);
            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    public function withdrawList(RequestWithdraw $request){
        $userId = $request->getUserId();
        // 默认从第一页开始
        $pn = $request->getPageNum();
        $pn = empty($pn)?1:$pn;
        // 默认一页10条纪录
        $ps = $request->getPageSize();
        $ps = empty($ps)?10:$ps;
        $offset = ($pn-1)*$ps;
        $ucs = new  \core\service\UserCarryService();
        $list = $ucs->getWithdrawListByUserId($userId,$offset,$ps);
        $response = new ResponseWithdraw();
        if (!empty($list)) {
            $response->setList($list);
            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    public function regUcfpay(SimpleRequestBase $request) {
        $userId = $request->UserId;
        $cardNo = $request->CardNo;
        $realName = $request->RealName;
        $paymentService = new PaymentService();
        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::SUCCESS;
        $idInfo = array();
        if (!empty($cardNo) && !empty($realName)) {
            $idInfo = array('cardNo' => $cardNo, 'realName' => $realName);
        }
        if (false === $paymentService->hasRegister($userId)) {
            $res = $paymentService->register($userId, $idInfo);
            if ($res == PaymentService::REGISTER_FAILURE) {
                $response->resCode = RPCErrorCode::FAILD;
                $response->resMsg = $paymentService->getLastError();
                Logger::info('ThirdRegUcfPayFail:'.$paymentService->getLastError());
            }
        }
        return $response;
    }

    /**
     * 2.2创建充值订单服务
     */
    public function createOrder(SimpleRequestBase $request) {
        try {
            //获取request参数
            $merchantNo = $request->merchantNo; // 商户编号
            $userId = $request->userId; // 用户ID
            $businessType = intval($request->businessType); // 业务类型(1:充值2:提现3:转账)
            $outOrderId = !empty($request->outOrderId) ? addslashes($request->outOrderId) : ''; // 第三方业务订单号
            $amount = intval($request->amount); // 充值金额,单位分
            $createTime = addslashes($request->createTime); // 创建时间,单位:YYYY-mm-dd H:i:s
            $pType = isset($request->pType) ? addslashes($request->pType) : PaymentApi::PAYMENT_SERVICE_UCFPAY; // 充值渠道(ucfpay:先锋支付|yeepay:易宝支付)
            $returnUrl = isset($request->returnUrl) ? $request->returnUrl : ''; // 充值回调地址
            $clientId = isset($request->clientId) ? $request->clientId : ''; // 开放平台的clientId
            //校验数据类型、是否为空等
            if (empty($merchantNo))
            {
                throw new \Exception('商户编号不能为空或参数错误');
            }
            if (empty($userId) || !is_int($userId))
            {
                throw new \Exception('用户ID不能为空或参数错误');
            }
            if (empty($amount) || !is_int($amount))
            {
                throw new \Exception('转账金额不能为空或参数错误');
            }
            if (empty($createTime))
            {
                throw new \Exception('创建时间不能为空');
            }
            if (empty($businessType) || empty(self::$businessTypeMap[$businessType]) || $businessType != self::BUSINESS_TYPE_1)
            {
                throw new \Exception('业务类型不能为空或不合法');
            }
            // 先锋支付降级时，不能提供充值服务
            if (PaymentApi::isServiceDown())
            {
                throw new \Exception(PaymentApi::maintainMessage());
            }
            // 获取当前可用的支付方式
            $paymentChannelList = PaymentApi::getPaymentChannel();
            // 没有可用的支付方式
            if (empty($paymentChannelList))
            {
                throw new \Exception('暂无可用的支付渠道');
            }

            // 幂等校验
            if (!empty($outOrderId))
            {
                $isIdmData = $this->isIdmpotence($request);
                // 该数据已经处理
                if (!empty($isIdmData) && true === $isIdmData['idmpotenced'])
                {
                    throw new \Exception('订单重复提交，请重新发起充值');
                }
            }

            $formData = $result = array();
            $result['formid'] = 'h5ChargeForm';
            // 充值金额，单位元
            $amountYuan = bcdiv($amount, 100, 2);
            switch ($pType)
            {
                case PaymentApi::PAYMENT_SERVICE_UCFPAY: // 先锋支付
                    // 后台已配置为开启状态
                    if (isset($paymentChannelList[$pType]) && !empty($paymentChannelList[$pType]))
                    {
                        // 获取支付方式ID
                        $paymentId = PaymentApi::instance()->getGateway()->getConfig('common', 'PAYMENT_ID');

                        $chargeService = new \core\service\ChargeService();
                        $orderSn = $chargeService->createOrder($userId, $amountYuan, PaymentNoticeModel::PLATFORM_H5, $outOrderId, $paymentId);
                        // 订单ID
                        if (intval($orderSn) <= 0)
                        {
                            throw new \Exception('创建订单失败');
                        }
                        $paymentNoticeModel = new PaymentNoticeModel();
                        $paymentNotice = $paymentNoticeModel->find($orderSn);
                        if (!isset($paymentNotice['notice_sn']) || empty($paymentNotice['notice_sn']))
                        {
                            throw new \Exception('创建订单失败');
                        }

                        $result['orderId'] = $orderSn;
                        // 生成[先锋支付]的form表单
                        $formData['outOrderId'] = $paymentNotice['notice_sn'];
                        $formData['userId'] = $userId;
                        $formData['amount'] = $amount;
                        $formData['returnUrl'] = $returnUrl;
                        $formData['changeBankCardUrl'] = $this->getChangeBankCardUrl($clientId);
                        $form = \libs\utils\PaymentGatewayApi::instance()->getForm('h5charge', $formData, $result['formid'], false);
                    }else{
                        $createOrderTips = PaymentApi::instance()->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS');
                        $createOrderExceptionTips = !empty($createOrderTips) ? $createOrderTips : PaymentApi::maintainMessage();
                        throw new \Exception($createOrderExceptionTips);
                    }
                    break;
                case PaymentApi::PAYMENT_SERVICE_YEEPAY: // 易宝支付
                        throw new \Exception('易宝支付暂不可用');
                    break;
                default:
                    throw new \Exception('不支持的支付方式');
                    break;
            }
            $result['form'] = $form;
            // 返回响应数据
            return $this->_response($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s.%s_is_exception,merchantNo:%d,userId:%d,outOrderId:%s,amount:%d,params:%s,ExceptionMsg:%s', __CLASS__, __FUNCTION__, $merchantNo, $userId, $outOrderId, $amount, json_encode($request->toArray()), $e->getMessage()));
            // 返回响应数据
            return $this->_response(array(), '01', $e->getMessage());
        }
    }

    /**
     * 2.3提现申请服务
     */
    public function withdrawApply(SimpleRequestBase $request) {
        try {
            //获取request参数
            $merchantNo = $request->merchantNo; // 商户编号
            $userId = $request->userId; // 用户ID
            $outOrderId = addslashes($request->outOrderId); // 商户提现订单号
            $businessType = intval($request->businessType); // 业务类型(1:充值2:提现3:转账)
            $amount = intval($request->amount); // 充值金额,单位分
            $os = intval($request->os); // 提现来源平台
            //校验数据类型、是否为空等
            if (empty($merchantNo))
            {
                throw new \Exception('商户编号不能为空或参数错误');
            }
            if (empty($userId) || !is_int($userId))
            {
                throw new \Exception('用户ID不能为空或参数错误');
            }
            if (empty($outOrderId))
            {
                throw new \Exception('商户提现订单号不能为空');
            }
            if (empty($businessType) || empty(self::$businessTypeMap[$businessType]) || $businessType != self::BUSINESS_TYPE_2)
            {
                throw new \Exception('业务类型不能为空或不合法');
            }
            if (empty($amount) || !is_int($amount))
            {
                throw new \Exception('转账金额不能为空或参数错误');
            }

            // 获取提现时效配置
            $apiConfObj = new ApiConfService();
            $withdrawTimeConf = $apiConfObj->getWithdrawTime();

            //开启事务
            $db = Db::getInstance('firstp2p');
            $db->startTrans();

            // 幂等校验
            $isIdmData = $this->isIdmpotence($request);
            // 该数据已经处理
            if (!empty($isIdmData) && true === $isIdmData['idmpotenced'])
            {
                // 提交事务
                $db->commit();
                return $this->_response(array('outOrderId'=>(isset($isIdmData['data']['outOrderId']) ? $isIdmData['data']['outOrderId'] : ''), 'message'=>'申请提现成功，预计' . $withdrawTimeConf . '个工作日内到账, 实际到账时间依据账户托管方及提现银行而有所差异'));
            }

            // 充值金额，单位元
            $amountYuan = bcdiv($amount, 100, 2);
            // 申请提现
            $paymentService = new PaymentService();
            $userCarryId = $paymentService->cashOut($userId, $amountYuan, $os);
            if ($userCarryId === false)
            {
                throw new \Exception('申请提现失败，请稍后重试');
            }
            // 更新幂等表的业务ID记录
            $this->_updateBaseOrderId($userCarryId, $isIdmData);
            // 提交事务
            $db->commit();

            // 返回响应数据
            return $this->_response(array('outOrderId'=>$outOrderId, 'orderId'=>$userCarryId, 'message'=>'申请提现成功，预计' . $withdrawTimeConf . '个工作日内到账, 实际到账时间依据账户托管方及提现银行而有所差异'));
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s.%s_is_exception,merchantNo:%d,userId:%d,outOrderId:%s,params:%s,ExceptionMsg:%s', __CLASS__, __FUNCTION__, $merchantNo, $userId, $outOrderId, json_encode($request->toArray()), $e->getMessage()));
            // 回滚事务
            isset($db) && $db->rollback();
            // 返回响应数据
            return $this->_response(array(), '01', $e->getMessage());
        }
    }

    /**
     * 2.4转账服务
     */
    public function transferMoney(SimpleRequestBase $request) {
        try {
            //获取request参数
            $merchantNo = $request->merchantNo; // 商户编号
            $outOrderId = addslashes($request->outOrderId); // 商户转账订单号
            $businessType = intval($request->businessType); // 业务类型(1:充值2:提现3:转账)
            $payerId = $request->payerId; // 转出用户ID
            $receiverId = $request->receiverId; // 转入用户ID
            $amount = $request->amount; // 转账金额，单位分
            $transferType = isset($request->transferType) ? intval($request->transferType) : 1;
            $message = addslashes($request->message); // 转账标题
            $payerMemo = addslashes($request->payerMemo); // 转账描述-付款方
            $receiverMemo = addslashes($request->receiverMemo); // 转账描述-收款方
            $negative = isset($request->negative) ? intval($request->negative) : 1; // 是否允许扣负
            //校验数据类型、是否为空等
            if (empty($merchantNo))
            {
                throw new \Exception('商户编号不能为空或参数错误');
            }
            if (empty($outOrderId))
            {
                throw new \Exception('商户转账订单号不能为空');
            }
            if (empty($businessType) || empty(self::$businessTypeMap[$businessType]) || $businessType != self::BUSINESS_TYPE_3)
            {
                throw new \Exception('业务类型不能为空或不合法');
            }
            if (empty($payerId) || !is_int($payerId))
            {
                throw new \Exception('付款方用户ID不能为空或参数错误');
            }
            if (empty($receiverId) || !is_int($receiverId))
            {
                throw new \Exception('收款方用户ID不能为空或参数错误');
            }
            if (empty($amount) || !is_int($amount))
            {
                throw new \Exception('转账金额不能为空或参数错误');
            }
            if (empty($transferType) || empty(self::$transferTypeMap[$transferType]))
            {
                throw new \Exception('转账业务类型不能为空或不合法');
            }
            if (empty($message))
            {
                throw new \Exception('转账标题不能为空');
            }
            if (empty($payerMemo))
            {
                throw new \Exception('付款方的转账描述不能为空');
            }
            if (empty($receiverMemo))
            {
                throw new \Exception('收款方的转账描述不能为空');
            }

            // 获取转出用户信息
            $payerUserModel = UserModel::instance()->find($payerId);
            if (!$payerUserModel)
            {
                throw new \Exception('转出方用户不存在');
            }
            // 获取转入用户信息
            $receiverUserModel = UserModel::instance()->find($receiverId);
            if (!$receiverUserModel)
            {
                throw new \Exception('转入方用户不存在');
            }

            // 充值金额，单位元
            $amountYuan = bcdiv($amount, 100, 2);
            //开启事务
            $db = Db::getInstance('firstp2p');
            $db->startTrans();

            // 幂等校验
            $isIdmData = $this->isIdmpotence($request);
            // 该数据已经处理
            if (!empty($isIdmData) && true === $isIdmData['idmpotenced'])
            {
                $db->commit();
                return $this->_response(array('orderId'=>(isset($isIdmData['data']['orderId']) ? $isIdmData['data']['orderId'] : ''), 'orderStatus'=>(isset($isIdmData['data']['orderStatus']) ? $isIdmData['data']['orderStatus'] : 'N')));
            }

            // 转账业务类型(1:可用->可用2:可用->冻结3:冻结->可用4:冻结->冻结)
            switch ($transferType)
            {
                case self::TRANSFER_TYPE_1: // 可用->可用
                    // 扣除可用
                    $payerUserModel->changeMoney(-$amountYuan, $message, $payerMemo, 0, 0, UserModel::TYPE_MONEY, $negative);
                    // 增加可用
                    $receiverUserModel->changeMoney($amountYuan, $message, $receiverMemo, 0, 0, UserModel::TYPE_MONEY, $negative);
                    break;
                case self::TRANSFER_TYPE_2: // 可用->冻结
                    // 扣除可用
                    $payerUserModel->changeMoney(-$amountYuan, $message, $payerMemo, 0, 0, UserModel::TYPE_MONEY, $negative);
                    // 不允许扣负的时候，检查用户资金是否足够+增加冻结
                    $this->_checkMoneyForJC($receiverId, -$amountYuan, '+lock_money', $negative);
                    $receiverUserModel->changeMoney(-$amountYuan, $message, $receiverMemo, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
                    break;
                case self::TRANSFER_TYPE_3: // 冻结->可用
                    // 不允许扣负的时候，检查用户资金是否足够+扣除冻结
                    $this->_checkMoneyForJC($payerId, $amountYuan, '-lock_money', $negative);
                    $payerUserModel->changeMoney($amountYuan, $message, $payerMemo, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
                    // 增加可用
                    $receiverUserModel->changeMoney($amountYuan, $message, $receiverMemo, 0, 0, UserModel::TYPE_MONEY, $negative);
                    break;
                case self::TRANSFER_TYPE_4: // 冻结->冻结
                    // 不允许扣负的时候，检查用户资金是否足够+扣除冻结
                    $this->_checkMoneyForJC($payerId, $amountYuan, '-lock_money', $negative);
                    $payerUserModel->changeMoney($amountYuan, $message, $payerMemo, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
                    // 不允许扣负的时候，检查用户资金是否足够+增加冻结
                    $this->_checkMoneyForJC($receiverId, -$amountYuan, '+lock_money', $negative);
                    $receiverUserModel->changeMoney(-$amountYuan, $message, $receiverMemo, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
                    break;
                default:break;
            }
            // 转账处理
            $syncRemoteData = array();
            $syncRemoteData[] = array(
                'outOrderId' => $outOrderId,
                'payerId' => $payerId,
                'receiverId' => $receiverId,
                'repaymentAmount' => $amount, // 转账金额，以分为单位
                'curType' => 'CNY',
                'bizType' => FinanceQueueModel::PAYQUEUE_BIZTYPE_16,
            );
            $financeQueueResult = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
            if (empty($financeQueueResult))
            {
                throw new \Exception('同步资金平台插入转账队列失败');
            }
            // firstp2p_finance_detail_log表的自增ID
            $orderId = isset($financeQueueResult[0]) ? $financeQueueResult[0] : 0;
            if (!is_numeric($orderId) || $orderId <= 0)
            {
                throw new \Exception('插入转账队列失败');
            }
            // 更新幂等表的业务ID记录
            $this->_updateBaseOrderId($orderId, $isIdmData);

            // 提交事务
            $db->commit();
            // 返回响应数据
            return $this->_response(array('orderId'=>$orderId, 'orderStatus'=>'N'));
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s.%s_is_exception,merchantNo:%d,payerId:%d,receiverId:%d,params:%s,ExceptionMsg:%s', __CLASS__, __FUNCTION__, $merchantNo, $payerId, $receiverId, json_encode($request->toArray()), $e->getMessage()));
            // 回滚事务
            isset($db) && $db->rollback();
            // 返回响应数据
            return $this->_response(array(), '01', $e->getMessage());
        }
    }

    /**
     * 2.5支付密码服务
     */
    public function getPaymentPwdSign(SimpleRequestBase $request) {
        try {
            //获取request参数
            $merchantNo = $request->merchantNo; // 商户编号
            $userId = $request->userId; // 用户ID
            $post = $request->post; // POST数据
            if (empty($merchantNo)) {
                throw new \Exception('商户编号不能为空或参数错误');
            }
            if (empty($userId) || !is_int($userId)) {
                throw new \Exception('用户ID不能为空或参数错误');
            }
            if (empty($post))
            {
                throw new \Exception('参数post不能为空');
            }

            $params = array('merchantId' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'], 'userId' => $userId);
            $paramsSign = $params + (array)$post;
            $params['sign'] = \libs\utils\Aes::signature($paramsSign, $GLOBALS['sys_config']['XFZF_SEC_KEY']);
            // 返回响应数据
            return $this->_response($params);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s.%s_is_exception,merchantNo:%d,userId:%d,params:%s,ExceptionMsg:%s', __CLASS__, __FUNCTION__, $merchantNo, $userId, json_encode($request->toArray()), $e->getMessage()));
            // 返回响应数据
            return $this->_response(array(), '01', $e->getMessage());
        }
    }

    /**
     * 2.6 订单查询
     */
    public function searchTrade(SimpleRequestBase $request)
    {
        try {
            //获取request参数
            $merchantNo = $request->merchantNo;
            $businessType = $request->businessType;
            $outOrderId = $request->outOrderId;
            $orderId = $request->orderId;

            if ((empty($outOrderId) && empty($orderId)) || empty($businessType))
            {
                throw new \Exception('参数错误');
            }
            // 如果orderId为空， 则通过outOrderId查出orderId
            $baseOrder = null;
            if (empty($orderId))
            {
                $baseOrder = $this->_getBaseOrder($request);
                if (empty($baseOrder))
                {
                    throw new \Exception('订单不存在');
                }
                $orderId = $baseOrder['order_id'];
            }

            // 根据不同的业务类型，获取不同业务的具体数据
            $businessOrder = $this->_getBusinessOrder($businessType, $orderId);
            if ($businessOrder === false)
            {
                throw new \Exception('业务订单不存在');
            }
            $responseData = [];
            if (isset($businessOrder['order_state']) && !empty($businessOrder['order_state']))
            {
                $responseData['orderId'] = $orderId;
                $responseData['outOrderId'] = $outOrderId;
                $responseData['orderStatus'] = $businessOrder['order_state'];
                $responseData['amount'] = $businessOrder['_amount'];
                $responseData['create_time'] = $businessOrder['create_time'];
            }
            // 返回响应数据
            return $this->_response($responseData);
        } catch (\Exception $e) {
            // 返回响应数据
            return $this->_response(array(), '01', $e->getMessage());
        }
    }

    /**
     * 2.7资金冻结解冻服务
     */
    public function moneyOperate(SimpleRequestBase $request) {
        try {
            //获取request参数
            $merchantNo = $request->merchantNo; // 商户编号
            $userId = $request->userId; // 用户ID
            $outOrderId = addslashes($request->outOrderId); // 商户订单号
            $opType = addslashes($request->opType); // 操作类型
            $amount = intval($request->amount); // 充值金额,单位分
            $message = addslashes($request->message); // 操作标题
            $memo = addslashes($request->memo); // 操作理由
            $negative = isset($request->negative) ? intval($request->negative) : 1; // 是否允许扣负
            //校验数据类型、是否为空等
            if (empty($merchantNo))
            {
                throw new \Exception('商户编号不能为空或参数错误');
            }
            if (empty($userId) || !is_int($userId))
            {
                throw new \Exception('用户ID不能为空或参数错误');
            }
            if (empty($outOrderId))
            {
                throw new \Exception('商户订单号不能为空');
            }
            if (empty($opType) || empty(self::$freezeMap[$opType]))
            {
                throw new \Exception('操作类型不能为空或不合法');
            }
            if ($amount <= 0)
            {
                throw new \Exception('金额必须大于0');
            }
            if (empty($message))
            {
                throw new \Exception('操作标题不能为空');
            }
            if (empty($memo))
            {
                throw new \Exception('操作描述不能为空');
            }

            // 获取用户信息
            $userModel = UserModel::instance()->find($userId);
            if (!$userModel)
            {
                throw new \Exception('用户不存在');
            }

            // 充值金额，单位元
            $amountYuan = bcdiv($amount, 100, 2);
            //开启事务
            $db = Db::getInstance('firstp2p');
            $db->startTrans();

            // 幂等校验
            $isIdmData = $this->isIdmpotence($request);
            // 该数据已经处理
            if (!empty($isIdmData) && true === $isIdmData['idmpotenced'])
            {
                $db->commit();
                return $this->_response(array('outOrderId'=>(isset($isIdmData['data']['outOrderId']) ? $isIdmData['data']['outOrderId'] : '')));
            }

            // 操作类型(freeze:冻结unfreeze:解冻consume:扣减冻结)
            switch ($opType)
            {
                case 'freeze': // 冻结
                    // 不允许扣负的时候，检查用户资金是否足够+冻结
                    $this->_checkMoneyForJC($userId, $amountYuan, '-money', $negative);
                    $result = $userModel->changeMoney($amountYuan, $message, $memo, 0, 0, UserModel::TYPE_LOCK_MONEY, $negative);
                    break;
                case 'unfreeze': // 解冻
                    // 不允许扣负的时候，检查用户资金是否足够+解冻
                    $this->_checkMoneyForJC($userId, -$amountYuan, '+money', $negative);
                    $result = $userModel->changeMoney(-$amountYuan, $message, $memo, 0, 0, UserModel::TYPE_LOCK_MONEY);
                    break;
                case 'consume': // 扣减冻结
                    // 不允许扣负的时候，检查用户资金是否足够+扣减冻结
                    $this->_checkMoneyForJC($userId, $amountYuan, '-lock_money', $negative);
                    $result = $userModel->changeMoney($amountYuan, $message, $memo, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
                    break;
                default:break;
            }
            if (false === $result)
            {
                throw new \Exception('资金操作失败');
            }
            // 提交事务
            $db->commit();
            // 返回响应数据
            return $this->_response(array('outOrderId'=>$outOrderId));
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s.%s_is_exception,merchantNo:%d,userId:%d,outOrderId:%s,params:%s,ExceptionMsg:%s', __CLASS__, __FUNCTION__, $merchantNo, $userId, $outOrderId, json_encode($request->toArray()), $e->getMessage()));
            // 回滚事务
            isset($db) && $db->rollback();
            // 返回响应数据
            return $this->_response(array(), '01', $e->getMessage());
        }
    }

    /**
     * 换卡页面回跳设置
     * @para data  $this->form->data
     */
    private function getChangeBankCardUrl($clientId) {
        $urlConf = $GLOBALS['sys_config']['CHANGE_BANKCARD_URL_MAP'];
        if(isset($urlConf[$clientId]) && !empty($urlConf[$clientId]))
        {
            return urlencode($urlConf[$clientId]);
        }
        return '';
    }

    private function _response($data = array(), $respCode = '00', $respMsg = '') {
        $response = new ResponseBase();
        $response->respCode = $respCode;
        $response->respMsg = $respMsg;
        $response->data = $data;
        return $response;
    }

    /**
     * 检查幂等性
     * @param array 业务参数集合 merchantNo,businessType,outOrderId
     * @return ['isIdmpotence' => true| false, 'data' => []] 如果isIdmpotence == true, data为等幂的数据状态， 如果isIdmpotence  == false 则可以做业务
     */
    public function isIdmpotence(SimpleRequestBase $idmpotenceInfo)
    {
        $db = Db::getInstance('firstp2p');
        $businessData = [];
        if (empty($idmpotenceInfo->merchantNo) || empty($idmpotenceInfo->outOrderId))
        {
            throw new \Exception('参数错误，商户或者订单号为空');
        }

        // 检查幂等性
        $baseOrder = $this->_getBaseOrder($idmpotenceInfo);
        if ($baseOrder)
        {
            $businessDataStatus = $this->_getBusinessStatus($idmpotenceInfo, $baseOrder);
            $businessData = [];
            $businessData['id'] = $baseOrder['id'];
            $businessData['orderStatus'] = $businessDataStatus;
            $businessData['orderId'] = $baseOrder['order_id'];
            $businessData['outOrderId'] = $baseOrder['out_order_id'];
            $businessData['create_time'] = date('Y-m-d H:i:s', $baseOrder['create_time']);
            $businessData['update_time'] = date('Y-m-d H:i:s', $baseOrder['update_time']);
            return ['idmpotenced' => true, 'data' => $businessData];
        }

        // 构造数据
        $idmpotenceData = [];
        $idmpotenceData['merchant_no'] = $idmpotenceInfo->merchantNo;
        $idmpotenceData['business_type'] = $idmpotenceInfo->businessType;
        $idmpotenceData['out_order_id'] = $idmpotenceInfo->outOrderId;
        $idmpotenceData['create_time'] = time();
        $idmpotenceData['update_time'] = $idmpotenceData['create_time'];
        // 录入幂等性校验表
        $db->autoExecute('firstp2p_base_order', $idmpotenceData, 'INSERT');
        $affectedRows = $db->affected_rows();
        if ($affectedRows >= 1)
        {
            $idmpotenceData['id'] = $db->insert_id();
            return ['idmpotenced' => false, 'data' => $idmpotenceData];
        }
        throw new \Exception('等幂校验失败');
    }

    /**
     * 读取订单的实际状态
     */
    private function _getBusinessStatus(SimpleRequestBase $request, $baseOrder = [])
    {
        $db = Db::getInstance('firstp2p');
        $businessType = $request->businessType;
        if (empty($request->outOrderId))
        {
            return [];
        }
        if (!empty($baseOrder))
        {
            // 如果有等幂的终态，返回等幂的状态
            if (in_array($baseOrder['order_state'], ['S', 'F']))
            {
                return $baseOrder['order_state'];
            }
        }
        $businessOrder = $this->_getBusinessOrder($businessType, $baseOrder['order_id']);
        // 回填订单状态
        if ($businessOrder !== false && in_array($businessOrder['order_state'], ['S', 'F', 'I']))
        {
            $updateState = ['order_state' => $businessOrder['order_state']];
            $db->autoExecute('firstp2p_base_order', $updateState, 'UPDATE' , " id = '{$baseOrder['id']}'");
        }
        return $businessOrder['order_state'];
    }

    /**
     * 根据不同的业务类型，获取不同业务的具体数据
     * @param int $businessType
     * @param int $orderId
     */
    private function _getBusinessOrder($businessType, $orderId = '')
    {
        if (empty($orderId))
        {
            return false;
        }
        $db = Db::getInstance('firstp2p');
        $businessOrder = [];
        switch ($businessType)
        {
            case self::BUSINESS_TYPE_1:
                $businessOrder = $db->getRow("SELECT * FROM firstp2p_payment_notice WHERE id = '{$orderId}'");
                $businessOrder['_amount'] = isset($businessOrder['money']) ? bcmul($businessOrder['money'], 100) : 0;
                if (isset($businessOrder['is_paid']))
                {
                    switch($businessOrder['is_paid'])
                    {
                        case PaymentNoticeModel::IS_PAID_NO: $businessOrder['order_state'] = 'N';break;
                        case PaymentNoticeModel::IS_PAID_SUCCESS: $businessOrder['order_state'] = 'S';break;
                        case PaymentNoticeModel::IS_PAID_ING: $businessOrder['order_state'] = 'I';break;
                        case PaymentNoticeModel::IS_PAID_FAIL: $businessOrder['order_state'] = 'F';break;
                    }
                }
                break;
            case self::BUSINESS_TYPE_2:
                $businessOrder = $db->getRow("SELECT * FROM firstp2p_user_carry WHERE id = '{$orderId}'");
                $businessOrder['_amount'] = isset($businessOrder['money']) ? bcmul($businessOrder['money'], 100) : 0;
                if (isset($businessOrder['withdraw_status']))
                {
                    switch($businessOrder['withdraw_status'])
                    {
                        case UserCarryModel::WITHDRAW_STATUS_CREATE: $businessOrder['order_state'] = 'N';break;
                        case UserCarryModel::WITHDRAW_STATUS_SUCCESS: $businessOrder['order_state'] = 'S';break;
                        case UserCarryModel::WITHDRAW_STATUS_FAILED: $businessOrder['order_state'] = 'F';break;
                        case UserCarryModel::WITHDRAW_STATUS_PROCESS: $businessOrder['order_state'] = 'I';break;
                    }
                }
                break;
            case self::BUSINESS_TYPE_3:
                $businessOrder = $db->getRow("SELECT * FROM firstp2p_finance_detail_log WHERE id = '{$orderId}'");
                $businessOrder['_amount'] = isset($businessOrder['repaymentAmount']) ? intval($businessOrder['repaymentAmount']) : 0;
                if (isset($businessOrder['status']))
                {
                    switch($businessOrder['status'])
                    {
                        case FinanceQueueModel::STATUS_NORMAL: $businessOrder['order_state'] = 'N';break;
                        case FinanceQueueModel::STATUS_SUCCESS: $businessOrder['order_state'] = 'S';break;
                        case FinanceQueueModel::STATUS_ERROR: $businessOrder['order_state'] = 'F';break;
                        case FinanceQueueModel::STATUS_SENDING: $businessOrder['order_state'] = 'I';break;
                        default:break;
                    }
                }
                break;
        }
        return $businessOrder;
    }

    private function _getBaseOrder(SimpleRequestBase $idmpotenceInfo)
    {
        $db = Db::getInstance('firstp2p');
        $checkIdmpotenceCondition = ' 1 ';
        if (!empty($idmpotenceInfo->merchantNo))
        {
            $merchantNo = addslashes($idmpotenceInfo->merchantNo);
            $checkIdmpotenceCondition .= " AND merchant_no = '{$merchantNo}' ";
        }
        if (!empty($idmpotenceInfo->businessType))
        {
            $businessType = intval($idmpotenceInfo->businessType);
            $checkIdmpotenceCondition .= " AND business_type = '{$businessType}' ";
        }
        if (!empty($idmpotenceInfo->outOrderId))
        {
            $outOrderId = addslashes($idmpotenceInfo->outOrderId);
            $checkIdmpotenceCondition .= " AND out_order_id = '{$outOrderId}' ";
        }
        if (!empty($idmpotenceInfo->orderId))
        {
            $orderId = intval($idmpotenceInfo->orderId);
            $checkIdmpotenceCondition .= " AND order_id = '{$orderId}' ";
        }
        $sql = "SELECT id,out_order_id,order_id,order_state,create_time,update_time FROM firstp2p_base_order WHERE {$checkIdmpotenceCondition}";
        $baseOrder = $db->getRow($sql, true);
        return $baseOrder;

    }

    /**
     * 更新base_order表的业务方ID
     * @param int $orderId 外部业务订单号
     * @param array $isIdmData 等幂函数返回数组
     * @param int $baseOrderId 自增ID
     */
    private function _updateBaseOrderId($orderId, $isIdmData = array(), $baseOrderId = 0)
    {
        $baseOrderId = !empty($isIdmData['data']['id']) && false === $isIdmData['idmpotenced'] ? $isIdmData['data']['id'] : 0;
        if ($orderId > 0 && $baseOrderId > 0)
        {
            $db = Db::getInstance('firstp2p');
            $db->autoExecute('firstp2p_base_order', array('order_id'=>$orderId), 'UPDATE' , " id = '{$baseOrderId}'");
            return $db->affected_rows() == 1 ? true : false;
        }
        return false;
    }

    /**
     * 查询用户的余额、冻结金额等信息
     * @param int $userId 用户ID
     * @param array $condition 查询条件
     */
    private function _getUserMoneyInfo($userId, $condition)
    {
        $userModel = UserModel::instance();
        return $userModel->findBy(sprintf('id = %d %s', $userModel->escape($userId), $condition), 'id,money,lock_money');
    }

    /**
     * 检查用户资金是否足够
     * @param int $userId 用户ID
     * @param floal $amount 金额，单位元
     * @param string $type 检查类型,默认做冻结余额的检查 
     * @param int $negative 是否允许扣负(0:不允许1:允许)
     */
    private function _checkMoneyForJC($userId, $amount, $type = '-money', $negative = 1)
    {
        // 允许扣负的不处理
        if ($negative != 0)
        {
            return true;
        }

        switch ($type)
        {
            case '-money': // 冻结余额
            case '+money': // 解除冻结
                // 传入的金额为正值时的处理
                if (bccomp($amount, '0.00', 2) >= 0)
                {
                    $condition = sprintf(' AND money >= \'%s\'', floatval($amount));
                } else {
                    $condition = sprintf(' AND lock_money + \'%s\' >= 0', floatval($amount));
                }
                // 查询用户的余额、冻结金额等信息
                $userMoneyInfo = $this->_getUserMoneyInfo($userId, $condition);
                if (empty($userMoneyInfo))
                {
                    throw new \Exception('用户资金不足，操作失败');
                }
                break;
            case '-lock_money': // 扣除冻结
            case '+lock_money': // 增加冻结
                if (bccomp($amount, '0.00', 2) >= 0)
                {
                    $condition = sprintf(' AND lock_money >= \'%s\'', floatval($amount));
                    // 查询用户的余额、冻结金额等信息
                    $userMoneyInfo = $this->_getUserMoneyInfo($userId, $condition);
                    if (empty($userMoneyInfo))
                    {
                        throw new \Exception('用户资金不足，操作失败');
                    }
                }
                break;
            default:break;
        }
        return true;
    }


    /**
     * 验签
     * @param  AbstractRequestBase $request [description]
     * @return [type]                       [description]
     */
    private function checkSign(AbstractRequestBase $request)
    {
        $params = $request->toArray();

        if (!($sign = $params['sign'])) {
            throw new \Exception("缺少签名", 21001);
        }
        unset($params['sign']);
        unset($params['requestDatetime']); // 去掉request默认参数

        $timeout = 10*60;
        if (empty($params['timestamp']) || abs($params['timestamp'] - time()) > $timeout) {
            throw new \Exception("timestamp time out", 21004);
        }

        if (!($clientID = $params['clientID'])) {
            throw new \Exception("缺少clientID", 21002);
        }

        $conf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        if (!isset($conf[$clientID])) {
            throw new \Exception("clientID错误", 21003);
        }
        $secret = $conf[$clientID]['client_secret'];

        $sortedReq = $secret;
        ksort($params);
        reset($params);
        while (list ($key, $val) = each($params)) {
            if (!is_null($val)) {
                if (is_bool($val)) $val = $val === true ? "true" : "false";
                $sortedReq .= $key . $val;
            }
        }
        $sortedReq .= $secret;
        $sign_md5 = strtoupper(md5($sortedReq));

        if ($sign != $sign_md5) {
            return false;
        }
        return true;
    }


    /**
     * 提现到某个商户账户
     * @param RequestCommon $request
     *      integer $type 交易资产端类型  1 超级账户提现 2存管账户提现
     *      integer $amount 提现金额 单位分
     *      integer $userId 提现网信用户Id
     *      string $orderId 提现单号
     *      [option] integer $bidId 标的id
     *      [option] integer $serviceFee 服务费金额
     *      [option] array  $serviceFeelist 服务费收取账户
     *      [option] integer $bidId 标的id
     *      [option] integer $repayAmount 标的实际提现金额
     *      [option] integer $totalAmount 标的回款本金  totalAmount - $repaymentAmount 为用户收到的解冻的金额
     *      [option] integer $merchantBatchNo 代发批次号
     *      [option] string  $merchantId 代发商户号
     *
     */
    public function withdrawToMerchant(RequestCommon $request)
    {
        $data = $request->getVars();
        $type = isset($data['type']) ? intval($data['type']) : '';
        if (empty($type)) {
            return ['status'=>'01', 'respCode' => '01', 'respMsg'=>'交易资产端类型错误'];
        }
        $userId = isset($data['userId']) ? intval($data['userId']) : '';
        if (empty($userId)) {
            return ['status'=>'01', 'respCode' => '01', 'respMsg'=>'用户ID错误'];
        }
        $amount= isset($data['amount']) ? intval($data['amount']) : '';
        if (empty($amount)) {
            return ['status'=>'01', 'respCode' => '01', 'respMsg'=>'交易金额错误'];
        }
        $orderId = isset($data['orderId']) ? intval($data['orderId']) : '';
        if (empty($orderId)) {
            return ['status'=>'01', 'respCode' => '01', 'respMsg'=>'提现单号错误'];
        }
        $merchantBatchNo = isset($data['merchantBatchNo']) ? intval($data['merchantBatchNo']) : false;
        $merchantId = isset($data['merchantId']) ? trim($data['merchantId']) : '';
        // 提现基础业务数据
        $orderInfo = [
            'userId' => $userId,
            'orderId' => $orderId,
        ];

        try {
            if(!empty($merchantBatchNo))
            {
                // 盈嘉线下还款, 走代发模式
                $dealId = $data['bidId'];
                $dealInfo = DealModel::instance()->find($dealId);
                $projectInfo = DealProjectModel::instance()->find($dealInfo->project_id);

                $withdrawInfo = [];

                // 本息
                if ($data['repayAmount'] > 0)
                {
                    $withdrawInfo['principalInterest'] = [
                        'userId'            => $userId,
                        'amount'            => $data['repayAmount'],
                        'merchantNo'        => $orderId,
                        'merchantNoSeq'     => 0,
                        'merchantBatchNo'   => $merchantBatchNo,
                        'merchantId'        => $merchantId,
                        'projectId'         => $dealInfo->project_id,
                        'projectName'       => $projectInfo->name,
                        'bizType'           => WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN,
                    ];
                }

                // 服务费金额
                if ($data['serviceFee'] > 0 )
                {
                    $withdrawInfo['fee'] = [
                        'userId'            => 'speedloan',
                        'amount'            => $data['serviceFee'],
                        'merchantNo'        => $orderId,
                        'merchantNoSeq'     => 1,
                        'merchantBatchNo'   => $merchantBatchNo,
                        'merchantId'        => $merchantId,
                        'projectId'         => $dealInfo->project_id,
                        'projectName'       => $projectInfo->name,
                        'bizType'           => WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_FEE,
                    ];
                }

                $remain = bcsub(bcsub($data['totalAmount'], $data['repayAmount']), $data['serviceFee']);
                if ($remain >  0)
                {
                    // 退还本息
                    $withdrawInfo['return'] = [
                        'userId'            => $userId,
                        'amount'            => $remain,
                        'merchantNo'        => $orderId,
                        'merchantNoSeq'     => 2,
                        'merchantBatchNo'   => $merchantBatchNo,
                        'merchantId'        => $merchantId,
                        'projectId'         => $dealInfo->project_id,
                        'projectName'       => $projectInfo->name,
                        'bizType'           => WithdrawProxyModel::BIZ_TYPE_SPEEDLOAN_RETURN,
                    ];
                }

                foreach ($withdrawInfo as $payInfo)
                {
                    WithdrawProxyService::addWithdrawRecord($payInfo);
                }
                $result = ['status' => '00', 'respCode' => '00', 'respMsg' => '成功'];
            } else {
                // 非盈嘉线下还款走正常线上还款逻辑
                switch ($type)
                {
                    // 超级账户提现至海口银行
                    case 1:
                        $orderInfo += [
                            'outOrderId' => $orderInfo['orderId'],
                            'curType' => 'CNY',
                            'userName' => app_conf('SPEED_LOAN_MERCHANT_NAME'),
                            'amount' => intval($data['repayAmount']),
                            'bankCardNo' => app_conf('SPEED_LOAN_MERCHANT_ACCOUNT'),
                            'priPub' => '2',
                        ];
                        unset($orderInfo['orderId']);
                        // 发起提现
                        $requestResult = PaymentApi::instance()->request('creditLoanWithdraw', $orderInfo);
                        if (!isset($requestResult['status']) || $requestResult['status'] != '00') {
                            throw new \Exception('提现发起失败,失败原因:'.$requestResult['respMsg']);
                        }

                        $result = ['status' => '00', 'respCode' => '00', 'respMsg' => '成功'];
                        break;
                    // 存管账户提现至海口银行
                    case 2:
                        // 存管行接口参数
                        $orderInfo += [
                            'bankCardName' => app_conf('SPEED_LOAN_MERCHANT_NAME'),
                            'bankCardNo' => app_conf('SPEED_LOAN_MERCHANT_ACCOUNT'),
                            'bidId' => intval($data['bidId']),
                            'totalAmount' => intval($data['totalAmount']),
                            'repayAmount' => intval($data['repayAmount']),
                            'cardFlag' => '1',
                        ];
                        $financeService = new \core\service\SupervisionFinanceService();
                        // 收取服务费
                        if (isset($data['serviceFee']) && !empty($data['serviceFee'])) {
                            $orderInfo['chargeAmount'] = intval($data['serviceFee']);
                            $orderInfo['chargeOrderList'] = json_encode([[
                                'amount' => $orderInfo['chargeAmount'],
                                'receiveUserId' => app_conf('SPEED_LOAN_SERVICE_FEE_UID'),
                                'subOrderId' => Idworker::instance()->getId(),
                            ]]);
                        }

                        //异步添加存管订单
                        $supervisionOrderService = new SupervisionOrderService();
                        $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_CREDIT_LOAN_WITHDRAW, $orderInfo);

                        $supervisionApi = StandardApi::instance(StandardApi::SUPERVISION_GATEWAY);
                        $orderInfo['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/creditloan/withdrawNotify';
                        // 请求存管接口
                        $result= $supervisionApi->request('creditLoanWithdraw', $orderInfo);
                        if (!isset($result['respCode']) || $result['respCode'] != '00') {
                            throw new \Exception('提现失败');
                        }
                        $result = ['status' => '00', 'respCode' => '00', 'respMsg' => '成功'];

                        break;
                }
            }
        } catch (\Exception $e) {
            $result = ['status' => '01', 'respCode' => '01', 'respmsg' => $e->getMessage()];
        }
        return $result;
    }


    /**
     * TODO 查询提现至电子账户订单
     * @param RequestCommon $request
     *      integer $type 业务类型 1 超级账户 2存管账户
     *      string $orderId 需要查询的提现单号
     */
    public function searchWithdrawOrder(RequestCommon $request)
    {
        $result = [];
        try {
            $data = $request->getVars();
            $type = isset($data['type']) ? intval($data['type']) : 0;
            if (empty($type) || !in_array($type, [1, 2])) {
                throw new \Exception('无效的业务类型, type=1 查询超级账户提现至电子账户订单 type=2 查询存管账户提现至电子账户订单');
            }
            $orderId = isset($data['orderId']) ? trim($data['orderId']) : '';
            if (empty($orderId)) {
                throw new \Exception('单号错误');
            }
            switch($type) {
                case 1:
                    $requestResult = PaymentApi::instance()->request('towithdrawaltrustbank', $orderInfo);
                case 2:
            }

        } catch (\Exception $e) {
            $result = ['status' => '01', 'respCode' => '01', 'respMsg' => $e->getMessage()];
        }
        return $result;
    }
}
