<?php
/**
 * 海口联合农商银行-支付服务
 *
 * 海口联合商业银行-直销贷款接口封装
 * 
 * @package     core\service
 * @author      guofeng3
 * @copyright   (c) 2016, Wxlc Corporation. All rights reserved.
 * @History:
 *     1.0.0 | guofeng3 | 2016-07-19 16:00:00 | initialization
 ********************************** 80 Columns *********************************
*/

namespace core\service\payment;

use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\db\Db;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\creditloan\CreditLoanService;
use core\service\supervision\SupervisionBaseService;

class UniteBankPaymentService extends SupervisionBaseService {
    /**
     * 支付状态-失败
     * @var int
     */
    const YBPAY_STATUS_FAILURE = 0;

    /**
     * 支付状态-成功
     * @var int
     */
    const YBPAY_STATUS_SUCCESS = 1;

    /**
     * 支付状态-撤销
     * @var int
     */
    const YBPAY_STATUS_REVOKE = 2;

    /**
     * 支付状态-处理中
     * @var int
     */
    const YBPAY_STATUS_ING = 3;

    /**
     * 支付状态-已撤销
     * @var int
     */
    const YBPAY_STATUS_HASREVOKE = 4;

    public function __construct()
    {
        parent::__construct(StandardApi::UNITEBANK_GATEWAY);
    }

    /**
     * 2.1 贷款账户开户-页面
     * 异步POST回调
     * HTTP请求方式：POST
     * 传入参数：
     *     userId:网信申请流水-网信理财用户UID-必传
     *     ReservedInfo:预留信息
     *     Email:邮箱
     *     ReferrNo:推荐人
     */
    public function createLoanAcctPre($params, $userInfo = array())
    {
        if (!isset($params['userId']) OR empty($params['userId']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取用户详细信息
        $userDetailInfo = $this->_getUserDetailInfo($params['userId'], $userInfo, true, true);
        if (!isset($userDetailInfo['respCode']) || $userDetailInfo['respCode'] !== '00')
        {
            return array('respCode' => 1002, 'respMsg' => $userDetailInfo['respMsg']);
        }

        // 组织数据
        $requestParams = array(
            'WJnlNo' => (string)$userDetailInfo['userInfo']['id'], // 网信申请流水-必填
            'UserId' => (string)$userDetailInfo['userInfo']['real_name'], // 姓名-必填
            'IdNo' => (string)$userDetailInfo['userInfo']['idno'], // 身份证号-必填
            'BankName' => (string)$userDetailInfo['bankInfo']['name'], // 开户银行-必填
            'AcNo' => (string)$userDetailInfo['userBankCardInfo']['bankcard'], // 绑定卡号-必填
            'MobilePhone' => (string)$userDetailInfo['userInfo']['mobile'], // 手机号-必填
        );
        !empty($params['ReservedInfo']) && $requestParams['ReservedInfo'] = (string)$params['ReservedInfo']; // 预留信息
        !empty($params['Email']) && $requestParams['Email'] = (string)$params['Email']; // 邮箱
        !empty($params['ReferrNo']) && $requestParams['ReferrNo'] = (string)$params['ReferrNo']; // 推荐人

        // 请求页面
        $result = array();
        $result['formid'] = 'unitebankForm';
        $result['form'] = $this->api->getForm('CreateLoanAcctPre', $requestParams, $result['formid'], false, false);
        return array('respCode' => '00', 'respMsg' => '', 'data' => $result);
    }

    /**
     * 2.2 新增的贷款账户开户+贷款申请-页面
     * 异步POST回调
     * HTTP请求方式：POST
     * 传入参数：
     *     userId:网信申请流水-网信理财用户UID-必传
     *     LWJnlNo:网信贷款申请流水-必传
     *     LAmount:贷款申请金额-必传
     *     LTime:借款期限,单位天-必传
     *     ReservedInfo:预留信息
     *     Email:邮箱
     *     ReferrNo:推荐人工号
     */
    public function createNewLoanAcctPre($params, $userInfo = array())
    {
        if (!isset($params['userId']) OR !isset($params['LWJnlNo']) OR !isset($params['LAmount'])
            OR !isset($params['LTime']) OR empty($params['userId']) OR empty($params['LWJnlNo'])
            OR empty($params['LAmount']) OR empty($params['LTime']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取用户详细信息
        $userDetailInfo = $this->_getUserDetailInfo($params['userId'], $userInfo, true, true);
        if (!isset($userDetailInfo['respCode']) || $userDetailInfo['respCode'] !== '00')
        {
            return array('respCode' => 1002, 'respMsg' => $userDetailInfo['respMsg']);
        }

        // 组织数据
        $requestParams = array(
            'WJnlNo' => (string)$userDetailInfo['userInfo']['id'], // 网信注册申请流水-必填
            'LWJnlNo' => (string)$params['LWJnlNo'], // 网信贷款申请流水-必填
            'LAmount' => (string)$params['LAmount'], // 贷款申请金额-必填
            'LTime' => (string)$params['LTime'], // 借款期限,单位天-必填
            'UserId' => (string)$userDetailInfo['userInfo']['real_name'], // 姓名-必填
            'IdNo' => (string)$userDetailInfo['userInfo']['idno'], // 身份证号-必填
            'BankName' => (string)$userDetailInfo['bankInfo']['name'], // 开户银行-必填
            'AcNo' => (string)$userDetailInfo['userBankCardInfo']['bankcard'], // 绑定卡号-必填
            'MobilePhone' => (string)$userDetailInfo['userInfo']['mobile'], // 手机号-必填
        );
        !empty($params['ReservedInfo']) && $requestParams['ReservedInfo'] = (string)$params['ReservedInfo']; // 预留信息
        !empty($params['Email']) && $requestParams['Email'] = (string)$params['Email']; // 邮箱
        !empty($params['ReferrNo']) && $requestParams['ReferrNo'] = (string)$params['ReferrNo']; // 推荐人

        // 请求页面
        $result = array();
        $result['formid'] = 'unitebankForm';
        $result['form'] = $this->api->getForm('CreateNewLoanAcctPre', $requestParams, $result['formid'], false, false);
        return array('respCode' => '00', 'respMsg' => '', 'data' => $result);
    }

    /**
     * 2.3 贷款申请-页面
     * 异步POST回调
     * HTTP请求方式：POST
     * 传入参数：
     *     userId:网信理财用户UID-必传
     *     WJnlNo:网信申请流水-必传
     *     LAmount:贷款金额,两位小数-必传
     *     LTime:借款期限,单位天-必传
     */
    public function loanApplyPre($params, $userInfo = array())
    {
        if (!isset($params['userId']) OR !isset($params['WJnlNo']) OR !isset($params['LAmount'])
            OR !isset($params['LTime']) OR empty($params['userId']) OR empty($params['WJnlNo'])
            OR empty($params['LAmount']) OR empty($params['LTime']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取用户详细信息
        $userDetailInfo = $this->_getUserDetailInfo($params['userId'], $userInfo, false, false);
        if (!isset($userDetailInfo['respCode']) || $userDetailInfo['respCode'] !== '00')
        {
            return array('respCode' => 1002, 'respMsg' => $userDetailInfo['respMsg']);
        }

        // 组织数据
        $requestParams = array(
            'WJnlNo' => (string)$params['WJnlNo'], // 网信申请流水-必填
            'UserId' => (string)$userDetailInfo['userInfo']['real_name'], // 姓名-必填
            'LAmount' => (string)$params['LAmount'], // 贷款申请金额-必填
            'LTime' => (string)$params['LTime'], // 借款期限,单位天-必填
            'IdNo' => (string)$userDetailInfo['userInfo']['idno'], // 身份证号-必填
            'MobilePhone' => (string)$userDetailInfo['userInfo']['mobile'], // 手机号-必填
        );

        // 请求页面
        $result = array();
        $result['formid'] = 'unitebankForm';
        $result['form'] = $this->api->getForm('LoanApplyPre', $requestParams, $result['formid'], false, false);
        return array('respCode' => '00', 'respMsg' => '', 'data' => $result);
    }

    /**
     * 2.4 还款申请（用户发起）-页面
     * 异步POST回调
     * HTTP请求方式：POST
     * 传入参数：
     *     userId:网信理财用户UID-必传
     *     WJnlNo:网信借款申请流水-必传
     *     Amount:还款金额,小数点2位-必传
     *     PTime:还款日期,yyyyMMdd-必传
     *     PState:还款状态(1:网信提前还款2:正常还款3:逾期还款)-必传
     *     PRate:还款利率(7表示7%)-必传
     */
    public function loanRepayEarlyPre($params, $userInfo = array())
    {
        if (!isset($params['userId']) OR !isset($params['WJnlNo']) OR !isset($params['Amount'])
            OR !isset($params['PTime']) OR !isset($params['PState']) OR !isset($params['PRate'])
            OR empty($params['userId']) OR empty($params['WJnlNo']) OR empty($params['Amount'])
            OR empty($params['PTime']) OR is_null($params['PState']) OR empty($params['PRate']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取用户详细信息
        $userDetailInfo = $this->_getUserDetailInfo($params['userId'], $userInfo, false, false);
        if (!isset($userDetailInfo['respCode']) || $userDetailInfo['respCode'] !== '00')
        {
            return array('respCode' => 1002, 'respMsg' => $userDetailInfo['respMsg']);
        }

        // 组织数据
        $requestParams = array(
            'WJnlNo' => (string)$params['WJnlNo'], // 网信申请流水-必填
            'OWJnlNo' => (string)$params['WJnlNo'], // 网信借款申请流水-必填
            'UserId' => (string)$userDetailInfo['userInfo']['real_name'], // 姓名-必填
            'Amount' => (string)$params['Amount'], // 还款金额-必填
            'PTime' => (string)$params['PTime'], // 还款日期-必填
            'PState' => (string)$params['PState'], // 还款状态-必填
            'PRate' => (string)$params['PRate'], // 还款利率-必填
            'IdNo' => (string)$userDetailInfo['userInfo']['idno'], // 身份证号-必填
            'MobilePhone' => (string)$userDetailInfo['userInfo']['mobile'], // 手机号-必填
        );

        // 请求页面
        $result = array();
        $result['formid'] = 'unitebankForm';
        $result['form'] = $this->api->getForm('LoanRepayEarlyPre', $requestParams, $result['formid'], false, false);
        return array('respCode' => '00', 'respMsg' => '', 'data' => $result);
    }

    /**
     * 2.5 还款申请（网信post申请）-接口
     * 异步POST回调
     * HTTP请求方式：POST
     * 传入参数：
     *     userId:网信理财用户UID-必传
     *     WJnlNo:网信借款申请流水-必传
     *     Amount:还款金额,小数点2位-必传
     *     PTime:还款日期,yyyyMMdd-必传
     *     PState:还款状态(1:网信提前还款2:正常还款3:逾期还款)-必传
     *     PRate:还款利率(7表示7%)-必传
     */
    public function loanRepayEarlyWX($params, $userInfo = array())
    {
        if (!isset($params['userId']) OR !isset($params['WJnlNo']) OR !isset($params['Amount'])
            OR !isset($params['PTime']) OR !isset($params['PState']) OR !isset($params['PRate'])
            OR empty($params['userId']) OR empty($params['WJnlNo']) OR empty($params['Amount'])
            OR empty($params['PTime']) OR is_null($params['PState']) OR empty($params['PRate']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取用户详细信息
        $userDetailInfo = $this->_getUserDetailInfo($params['userId'], $userInfo, false, false);
        if (!isset($userDetailInfo['respCode']) || $userDetailInfo['respCode'] !== '00')
        {
            return array('respCode' => 1002, 'respMsg' => $userDetailInfo['respMsg']);
        }

        // 组织数据
        $requestParams = array(
            'WJnlNo' => (string)$params['WJnlNo'], // 网信申请流水-必填
            'OWJnlNo' => (string)$params['WJnlNo'], // 网信借款申请流水-必填
            'UserId' => (string)$userDetailInfo['userInfo']['real_name'], // 姓名-必填
            'Amount' => (string)$params['Amount'], // 还款金额-必填
            'PTime' => (string)$params['PTime'], // 还款日期-必填
            'PState' => (string)$params['PState'], // 还款状态-必填
            'PRate' => (string)$params['PRate'], // 还款利率-必填
            'IdNo' => (string)$userDetailInfo['userInfo']['idno'], // 身份证号-必填
            'MobilePhone' => (string)$userDetailInfo['userInfo']['mobile'], // 手机号-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_UNITEBANK)->request('LoanRepayEarlyWX', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if ((!empty($response['WJnlNo']) && !empty($response['JnlNo'])) || (!empty($response['jsonError'][0]['_exceptionMessageCode']) && $response['jsonError'][0]['_exceptionMessageCode'] == 'validation_loan_repay_applied'))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => '01', 'respMsg' => isset($response['jsonError'][0]['_exceptionMessage']) ? $response['jsonError'][0]['_exceptionMessage'] : '银行接口数据异常');
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 贷款申请接口(汇总的2.2+2.3接口)
     * @param int $userId 网信用户UID
     */
    public function loanApplyGather($params, $userInfo = array())
    {
        $isAccount = $this->hasAccount($params['userId']);
        if (!$isAccount)
        {
            // 网信贷款申请流水
            !isset($params['LWJnlNo']) && $params['LWJnlNo'] = $params['WJnlNo'];
            // 尚未在银行开户，调用2.2接口
            return $this->createNewLoanAcctPre($params, $userInfo);
        }else{
            // 已经在银行开户，调用2.3接口
            return $this->loanApplyPre($params, $userInfo);
        }
    }

    /**
     * 更新用户海口联合农商银行电子账户信息
     *
     * @param integer userId 用户ID
     * @param [] data 用户电子账号和贷款专用账号 p_account e_account
     * @return bool
     */
    public function updateUserAccount($userId, $data)
    {
        if (!empty($data['p_account']) && !empty($data['e_account']))
        {
            $data['unitebank_state'] = 1;
        }
        return UserBankcardModel::instance()->updateCardByUserId($userId, $data);
    }

    /**
     * 获取该用户的绑卡记录
     * @param int $userId
     */
    public function getUserBankCard($userId)
    {
        $db = Db::getInstance('firstp2p','slave');
        $bankcard = $db->getRow("SELECT e_account,p_account,unitebank_state,cert_status FROM firstp2p_user_bankcard WHERE user_id = '{$userId}' ORDER BY id DESC LIMIT 1");
        return $bankcard ? $bankcard : false;
    }

    /**
     * 检查用户是否在海口联合农商银行开户
     * @param integer userId 用户ID
     * @return mixed 如果已经开户，返回开户账号数据，否则返回false
     */
    public function hasAccount($userId)
    {
        // 获取该用户的绑卡记录
        $bankcard = $this->getUserBankCard($userId);
        if (isset($bankcard['unitebank_state']) && $bankcard['unitebank_state'] == 1)
        {
            return $bankcard;
        }
        return false;
    }

    /**
     * 检查用户是否通过四要素校验
     * @param integer userId 用户ID
     * @return bool
     */
    public function isFastPayVerify($userId)
    {
        // 获取该用户的绑卡记录
        $bankcard = $this->getUserBankCard($userId);
        if (empty($bankcard))
        {
            return false;
        }
        $isFastPayVerify = isset($bankcard['cert_status']) && $bankcard['cert_status'] == 2 ? true : false;
        return $isFastPayVerify;
    }

    /**
     * 开户回调接口(2.1、2.2的注册回调)
     * @param string WJnlNo 网信申请用户ID
     * @param string JnlNo  交易流水
     * @param string TrsResult 交易结果
     * @param string TrsTime 开户时间
     * @param string EAccNo 电子账号
     * @param string PAccNo 贷款专用账号
     *
     * @return ['respCode' => int, 'respMsg' => ''];
     */
    public function CreateLoanAccountNotifyCallback($params)
    {
        $result = ['respCode' => '00', 'respMsg' => ''];
        $db = Db::getInstance('firstp2p', 'master');
        try
        {
            $db->startTrans();
            $updateData = [
                'p_account' => addslashes(trim($params['PAccNo'])),
                'e_account' => addslashes(trim($params['EAccNo'])),
                'unitebank_state' => 1,
            ];
            $ret = UserBankcardModel::instance()->updateCardByUserId(intval($params['WJnlNo']), $updateData);
            if (!$ret)
            {
                throw new \Exception('更新用户海口联合农商银行电子账户信息失败');
            }
            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();
            $result['respCode'] = '01';
            $result['respMsg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * 放款处理回调(3.4的回调)
     * @param string OWJnlNo 借款申请流水号
     * @param string JnlNo 交易流水
     * @param string TrsResult 交易结果
     * @param string Amount 放款金额
     * @param string TrsTime 交易时间
     * @param string PTime 还款日期
     * @param string PAmount 还款金额
     * @param string Prate 还款利率
     * @param string TrsState 交易状态 状态 2 已放款 4 放款失败
     *
     * @return []
     */
    public function LoanLendNotifyCallback($params)
    {
        $result = ['respCode' => '00', 'respMsg' => ''];
        self::log("LoanLendNotifyCallback params:".json_encode($params));
        try{
            $uidDealId = CreditLoanService::getDetailFromWJnlNo($params['OWJnlNo']);
            if(!$uidDealId) {
                throw new \Exception("流水号错误:".$params['OWJnlNo']);
            }
            $uid = $uidDealId[0];
            $dealId = $uidDealId[1];

            if(!$uid || !$dealId) {
                throw new \Exception("流水号解析错误:".$params['OWJnlNo']);
            }

            if(!in_array($params['TrsState'],array(2,4))) {
                throw new \Exception("放款回调状态值错误:".$params['TrsState']);
            }
            $cs = new CreditLoanService();
            $res = $cs->LendCreditLoanSuccess($uid,$dealId,$params['Amount'],strtotime($params['TrsTime']),$params['TrsState']);
            if(!$res) {
                throw new \Exception("放款回调失败");
            }
        }catch (\Exception $ex) {
            $result['respCode'] = '01';
            $result['respMsg'] = $ex->getMessage();
            self::log("LoanLendNotifyCallback error:".$ex->getMessage());
        }
        return $result;
    }

    /**
     * 还款受理回调(2.5的回调)
     * @param string WJnlNo 网信申请流水
     * @param string JnlNo 交易流水
     * @param string TrsResult 交易结果
     * @param string TrsTime 交易时间
     * @param string PAmount 还款金额
     * @param string LAmount 借款金额
     * @param string OWJnlNo 原借款申请流水号
     * @param string TrsState 交易状态 1 申请已受理 8 申请失败
     * @return []
     */
    public function LoanRepayAcceptNotifyCallback($params)
    {
        $result = ['respCode' => '00', 'respMsg' => ''];
        self::log("LoanRepayAcceptNotifyCallback params:".json_encode($params));
        try {
            $uidDealId = CreditLoanService::getDetailFromWJnlNo($params['WJnlNo']);
            if (!$uidDealId) {
                throw new \Exception("流水号错误:" . $params['WJnlNo']);
            }
            $uid = $uidDealId[0];
            $dealId = $uidDealId[1];

            if (!$uid || !$dealId) {
                throw new \Exception("流水号解析错误:" . $params['WJnlNo']);
            }

            // 海口联合农商银行还款受理，不能失败-需要告警
            if (!isset($params['TrsState']) || $params['TrsState'] == CreditLoanService::BANK_REFUSE)
            {
                Alarm::push('unitebank_callback', '海口联合农商银行还款受理回调失败', "海口联合农商银行的还款状态:受理失败|WJnlNo:{$params['WJnlNo']}|JnlNo:{$params['JnlNo']}|TrsState:{$params['TrsState']}");
            }

            if (!in_array($params['TrsState'], array(CreditLoanService::BANK_ACCEPT,CreditLoanService::BANK_REFUSE))) {
                throw new \Exception("还款受理回调状态值错误:" . $params['TrsState']);
            }
            $cs = new CreditLoanService();
            $res = $cs->repayCreditLoanApply($uid,$dealId,$params['PAmount'],$params['TrsState']);
            if(!$res) {
                throw new \Exception("还款回调失败");
            }
        }catch (\Exception $ex) {
            $result['respCode'] = '01';
            $result['respMsg'] = $ex->getMessage();
            self::log("LoanRepayAcceptNotifyCallback error:".$ex->getMessage());
        }
        return $result;
    }

    /**
     * 还款处理回调(3.5的回调)
     * @param string WJnlNo 网信申请用投资记录
     * @param string JnlNo 交易流水
     * @param string TrsResult 交易结果
     * @param string TrsState 交易状态 ( 3已还款 6还款失败)
     * @return []
     */
    public function LoanRepayNotifyCallback($params)
    {
        $result = ['respCode' => '00', 'respMsg' => ''];
        self::log("LoanRepayNotifyCallback params:".json_encode($params));
        try{
            $uidDealId = CreditLoanService::getDetailFromWJnlNo($params['OWJnlNo']);
            if(!$uidDealId) {
                throw new \Exception("流水号错误:".$params['OWJnlNo']);
            }
            $uid = $uidDealId[0];
            $dealId = $uidDealId[1];

            if(!$uid || !$dealId) {
                throw new \Exception("流水号解析错误:".$params['OWJnlNo']);
            }

            // 海口联合农商银行还款，不能失败-需要告警
            if (!isset($params['TrsState']) || $params['TrsState'] == CreditLoanService::BANK_REPAY_FAIL)
            {
                Alarm::push('unitebank_callback', '海口联合农商银行还款回调失败', "海口联合农商银行的还款状态:失败|OWJnlNo:{$params['OWJnlNo']}|JnlNo:{$params['JnlNo']}|TrsState:{$params['TrsState']}");
            }

            if(!in_array($params['TrsState'],array(CreditLoanService::BANK_REPAY_SUCCESS,CreditLoanService::BANK_REPAY_FAIL))) {
                throw new \Exception("还款回调状态值错误:".$params['TrsState']);
            }

            $cs = new CreditLoanService();
            $res = $cs->repayCreditLoanSuccess($uid,$dealId,$params['PAmount'],$params['TrsState'],strtotime($params['TrsTime']));
            if(!$res) {
                throw new \Exception("还款回调更失败");
            }
        }catch (\Exception $ex) {
            $result['respCode'] = '01';
            $result['respMsg'] = $ex->getMessage();
            self::log("LoanRepayNotifyCallback error:".$ex->getMessage());
        }
        return $result;
    }

    /**
     * 获取用户的详细信息（基本信息、绑卡信息）
     * @param int $userId
     * @param array $userInfo
     */
    private function _getUserDetailInfo($userId, $userInfo = array(), $isGetBankCard = true, $isGetBank = false)
    {
        // 获取用户信息
        if (empty($userInfo))
        {
            $userInfo = UserService::getUserById($userId, 'id,real_name,idno,mobile');
            if (!$userInfo)
            {
                return array('respCode' => '01', 'respMsg' => '该用户不存在');
            }
        }

        $bankInfo = $userBankCardInfo = array();
        if ($isGetBankCard)
        {
            // 获取用户绑卡数据
            $userBankCardInfo = BankService::getNewCardByUserId($userId, 'id,bank_id,bankcard,status,card_name,verify_status');
            if (empty($userBankCardInfo) || empty($userBankCardInfo['bankcard']))
            {
                return array('respCode' => '02', 'respMsg' => '该用户尚未绑卡');
            }
        }
        if ($isGetBank)
        {
            // 获取银行名称数据
            $bankInfo = BankService::getBankInfoByBankId($userBankCardInfo['bank_id'], 'id,name');
            if (empty($bankInfo) || empty($bankInfo['name']))
            {
                return array('respCode' => '03', 'respMsg' => '该用户的绑卡银行不存在');
            }
        }
        return array('respCode' => '00', 'userInfo' => $userInfo, 'userBankCardInfo' => $userBankCardInfo, 'bankInfo' => $bankInfo);
    }

    /**
     * 先锋支付发送还款指令处理结果回调
     */
    public function withdrawTrustBankNotifyCallback($params)
    {
        $result = ['respCode' => '00', 'respMsg' => ''];
        self::log("withdrawTrustBankNotifyCallback params:".json_encode($params));
        try{
            $uidDealId = CreditLoanService::getDetailFromWJnlNo($params['outOrderId']);
            if(!$uidDealId) {
                throw new \Exception("流水号错误:".$params['outOrderId']);
            }
            $uid = $uidDealId[0];
            $dealId = $uidDealId[1];

            if(!$uid || !$dealId) {
                throw new \Exception("流水号解析错误:".$params['outOrderId']);
            }

            $cs = new CreditLoanService();
            $res = $cs->LoanCreditLoanForPay($uid,$dealId);
            if(!$res) {
                throw new \Exception("支付回调失败");
            }
        }catch (\Exception $ex) {
            $result['respCode'] = '01';
            $result['respMsg'] = $ex->getMessage();
            self::log("withdrawTrustBankNotifyCallback error:".$ex->getMessage());
        }
        return $result;
    }

    /**
     * 发送还款指令给先锋支付 (throws Exception)
     * @param integer userId 用户ID
     * @param integer amount 金额:单位分
     * @param string outOrderId 外部订单号
     *
     * @return bool
     */
    public function withdrawTrustBank($params, $isCheckUniteState = true)
    {
        $params['userId'] = intval($params['userId']);
        if (empty($params['userId']))
        {
            throw new \Exception('用户ID无效');
        }
        $userBankcard = [];
        if ($isCheckUniteState) {
            $userBankcard = $this->hasAccount($params['userId']);
            if (empty($userBankcard))
            {
                throw new \Exception('尚未开通借款账户');
            }
        }
        $ucfpay = PaymentApi::instance();
        $user = \core\dao\UserModel::instance()->findViaSlave($params['userId']);
        $requestParams = [];
        $requestParams['curType'] = 'CNY';
        $requestParams['userId'] = $params['userId'];
        $requestParams['userName'] = $user['real_name'];
        $requestParams['amount'] = $params['amount'];
        $requestParams['outOrderId'] = $params['outOrderId'];
        $requestParams['bankCardNo'] = !empty($userBankcard['p_account']) ? $userBankcard['p_account'] : $params['pAccount'];
        !empty($params['callbackUrl']) && $requestParams['callbackUrl'] = $params['callbackUrl'];
        $response = $ucfpay->request('towithdrawaltrustbank', $requestParams);
        // 网络请求失败或者支付返回受理失败
        if (empty($response) || (isset($response['status']) && $response['status'] !== '00'))
        {
            throw new \Exception('信用贷还款失败，'.$response['respMsg']);
        }
        return true;
    }

    /**
     * 日志记录
     */
    private static function log($body, $level = Logger::INFO)
    {
        $destination = APP_ROOT_PATH.'log/logger/UniteBankPaymentService.'.date('y_m').'.log';
        Logger::wLog($body, $level, Logger::FILE, $destination);
    }
}
