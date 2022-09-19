<?php
namespace core\service;

use core\dao\PaymentModel;
use libs\utils\PaymentApi;
use NCFGroup\Common\Library\Zhuge;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;
use core\dao\UserCarryModel; // 提现日志
use core\dao\PaymentNoticeModel; // 充值日志
use core\dao\FinanceDetailLogModel; // 转账明细日志
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\UserBankcardModel;
use core\service\BanklistService;
use core\service\BankService;
use core\service\UserService;
use core\service\UserCarryService;
use core\service\UserBankcardService;
use core\dao\DeliveryRegionModel;
use core\service\DeliveryRegionService;
use core\service\PaymentUserAccountService;
use core\dao\BankModel;
use libs\utils\Curl;
use libs\db\Db;
use libs\utils\Aes;
use libs\utils\Alarm;
use core\service\ChargeService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;
use core\service\BonusService;
use core\service\MsgBoxService;
use core\service\DealService;
use core\service\O2OService;
use core\service\SupervisionAccountService;
use core\dao\ThirdpartyInvestModel;
use core\dao\TradeLogModel;
use core\dao\FundMoneyLogModel;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\sms\SmsServer;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;
use core\service\ncfph\AccountService as PhAccountService;
use core\service\UserAccessLogService;
use core\service\risk\RiskService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\BwlistService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class PaymentService extends BaseService
{
    private $_error = ''; // 错误信息

    const REGISTER_HASREGISTER = 0; // 支付平台已经开户
    const REGISTER_SUCCESS = 1; //支付平台开户成功
    const REGISTER_FAILURE = 2; //支付平台开户失败

    //用户已存在状态
    const REGISTER_USER_EXISTS = '31';
    // 提交相同的银行卡
    const UPDATE_SAME_CARD = '27';

    // 支付成功
    const PAYMENT_SUCCESS = '00';
    // 支付失败
    const PAYMENT_FAIL = '01';

    const CHARGE_PENDING = '02';//待处理
    const CHARGE_SUCCESS = '00';//成功
    const CHARGE_FAILURE = '01';//失败

    const API_RESPONSE_SUCCESS = 'S';
    const API_RESPONSE_FAIL = 'F';

    const ERROR_PAYMENT_ORDER_NOTEXITS = "10";
    const ERROR_PAYMENT_API = "11";

    //认证类型映射表
    private static $cert_status_map = array(
        'EXTERNAL_CERT' => 1, //IVR语音认证
        'FASTPAY_CERT' => 2, //快捷认证(四要素认证)
        'TRANSFER_CERT' => 3, //转账认证
    );

    public function getPaymentByClassname($id)
    {
        $payment = PaymentModel::instance()->getPaymentByClassname($id);
        if ($payment) {
            return $payment->getRow();
        } else {
            return FALSE;
        }
    }

    /**
     * 先锋注册用户，复写web端注册用户接口
     * @param userId 用户ID
     * @return string
     **/
    public function mobileRegister($userId, $userData = array())
    {
        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $userId = trim($userId);
        if (empty($userId)) {
            return self::REGISTER_FAILURE;
        }
        $userService = new UserService();
        $user = $userService->getUserViaSlave($userId);
        if (!empty($user['payment_user_id'])) {
            return self::REGISTER_HASREGISTER;
        }
        if (($user['real_name'] == '' || $user['idno'] == '') && (empty($userData['cardNo']) || empty($userData['realName']))) {
            return self::REGISTER_FAILURE;
        }
        $registerParam['merchantId'] = $merchant;
        $registerParam['userId'] = $user['id'];
        //真实姓名
        $registerParam['realName'] = $user['real_name'];
        if (empty($registerParam['realName']) && !empty($userData['realName'])) {
            $registerParam['realName'] = $userData['realName'];
        }
        $idinfo = $userService->getIdnoAndType($userId);
        $user_type = '01';
        if (is_array($idinfo)) {
            if ($idinfo['id_type'] == 1) {
                $user_type = '01';
            } else if ($idinfo['id_type'] == 2) {
                $user_type = '04';
            } else if ($idinfo['id_type'] == 3) {
                $user_type = '03';
            } else if ($idinfo['id_type'] >= 4 && $idinfo['id_type'] <= 6) {
                $user_type = '02';
            } else if ($idinfo['id_type'] == 99) {
                $user_type = '99';
            }
        }
        // 证件类型
        $registerParam['cardType'] = $user_type; //01-身份证,//02-港澳台
        //证件号
        $registerParam['cardNo'] = $idinfo['idno'];
        if (empty($registerParam['cardNo']) && !empty($userData['cardNo'])) {
            $registerParam['cardNo'] = $userData['cardNo'];
        }
        $registerParam['mobileNo'] = $user['mobile'];
        $query_string = \libs\utils\Aes::buildString($registerParam);
        $signature = md5($query_string . "&key=" . $GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $registerParam['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_USER_REGISTER'];
        $aesData = \libs\utils\Aes::encode($query_string . "&sign=" . $signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retSrc = Curl::post($api, array('data' => $aesData));
        $ret = json_decode($retSrc, true);
        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'PaymentService',
            'function' => 'mobileRegister',
            'msg' => '手机注册用户',
            'api' => $api,
            'request' => $registerParam,
            'response' => $retSrc,
            'response_decode' => $datas,
        );
        logger::wLog($log);

        if (\libs\utils\Aes::validate($datas)) {
            PaymentApi::log("PaymentService.mobileRegister:" . json_encode($log), Logger::INFO);
            // 验证成功
            if (!empty($datas['userId']) && $datas['respCode'] == '00' && ($datas['status'] == '00' || $datas['status'] == '05')) {
                $user->payment_user_id = $datas['userId'];
                $user->save();
                $GLOBALS['user_info']['payment_user_id'] = $datas['userId'];
                return self::REGISTER_SUCCESS;
            } else {
                return self::REGISTER_FAILURE;
            }
        } else {
            PaymentApi::log("PaymentService.mobileRegister:" . json_encode($log), Logger::WARN);
            return self::REGISTER_FAILURE;
        }

    }

    public function getUserType($id_type)
    {
        $user_type = '01';
        if (!empty($id_type)) {
            if ($id_type == 1) {
                $user_type = '01';
            } else if ($id_type == 2) {
                $user_type = '04';
            } else if ($id_type == 3) {
                $user_type = '03';
            } else if ($id_type >= 4 && $id_type <= 6) {
                $user_type = '02';
            } else if ($id_type == 99) {
                $user_type = '04';
            }
        }
        return $user_type;
    }

    /**
     * 即付开户绑卡
     */
    public function combineRegisterForJF($userId, $realName, $cardNo, $bankId, $bankCardNo)
    {
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];

        $userService = new UserService();
        $userInfo = $userService->getUser($userId);
        if (empty($userInfo)) {
            throw new \Exception('用户未注册');
        }

        if ($userInfo['site_id'] != $siteId) {
            throw new \Exception('用户非即付宝用户');
        }

        //幂等处理
        if ($userInfo['payment_user_id'] > 0) {
            return true;
        }

        $data = array(
            'bankName' => $bankId,
            'cardNo' => $cardNo,
            'realName' => $realName,
            'cardName' => $realName,
            'bankCardNo' => $bankCardNo,
            'source' => 1,
        );

        $result = $this->combineRegister($userInfo['id'], $data);
        if ($result['status'] != self::REGISTER_SUCCESS) {
            throw new \Exception($result['msg']);
        }

        return true;
    }

    /**
     * 注册绑卡合并接口
     */
    public function combineRegister($userId, $data)
    {
        $userId = trim($userId);
        $ret = array('status' => self::REGISTER_SUCCESS, 'msg' => '');
        //判断身份实名认证服务是否可用，如果服务不可用则直接返回相应的说明信息
        if (intval(app_conf("ID5_VALID")) === 3) {
            $ret['status'] = self::REGISTER_FAILURE;
            $ret['msg'] = app_conf("ID5_MAINTEN_MSG");
            return $ret;
        }
        $GLOBALS['db']->startTrans();
        try {
            if (empty($userId)) {
                $ret['status'] = self::REGISTER_FAILURE;
                throw new \Exception('参数错误');
            }
            $userService = new UserService();
            $user = $userService->getUser($userId);

            if (!empty($user['payment_user_id'])) {
                $ret['status'] = self::REGISTER_FAILURE;
                throw new \Exception('用户已经开户');
            }
            if ($user['idcardpassed'] != 1) {
                $validResult = $this->doIdValidate($data, $userId);
            }
            $registerParam['userId'] = $user['id'];
            //真实姓名
            $registerParam['realName'] = $data['cardName'];
            // 证件类型
            $registerParam['cardType'] = '01';
            //证件号
            $registerParam['cardNo'] = strtoupper($data['cardNo']);
            // 手机号
            $registerParam['phoneNo'] = $user['mobile'];
            $registerParam['regionCode'] = $user['mobile_code'];
            $registerParam['userType'] = '0'; //所有新实名注册的用户均为新用户
            // 银行数据，必填
            $registerParam['bankCardNo'] = $data['bankCardNo'];
            $registerParam['source'] = $data['source'];
            // 银行数据，可选或支付有默认值
            $newBankcardInfo = array();
            $newBankcardInfo['bank_id'] = intval($data['bankName']);
            $newBankcardInfo['bankcard'] = $registerParam['bankCardNo'];
            $newBankcardInfo['bankzone'] = '';
            $newBankcardInfo['user_id'] = $user['id'];
            $newBankcardInfo['status'] = '1';
            $newBankcardInfo['card_name'] = $registerParam['realName'];
            $newBankcardInfo['card_type'] = 0;
            $newBankcardInfo['region_lv1'] = '0';
            $newBankcardInfo['region_lv2'] = '0';
            $newBankcardInfo['region_lv3'] = '0';
            $newBankcardInfo['region_lv4'] = '0';
            $newBankcardInfo['create_time'] = time();
            $newBankcardInfo['update_time'] = time();
            // 实名认证后绑卡之前
            $userService->checkUserIdno($user['id'], $data['cardNo']);
            // 注册绑卡
            $bankService = new BankService();
            $canBind = $bankService->canBankcardBind($data['bankCardNo'], $user['id']);
            if (!$canBind) {
                $ret['status'] = self::REGISTER_FAILURE;
                throw new \Exception('银行卡已被占用，请重新输入');
            }
            $bankInfo = BankModel::instance()->find(intval($data['bankName']));
            if (empty($bankInfo)) {
                $ret['status'] = self::REGISTER_FAILURE;
                throw new \Exception('无效的银行信息');
            }
            $registerParam['bankName'] = $bankInfo['name'];
            $registerParam['bankCode'] = $bankInfo['short_name'];
            // 注册用户信息
            $user->payment_user_id = $user['id'];
            $user->save();
            $affected_rows = $GLOBALS['db']->affected_rows();
            if ($affected_rows < 1) {
                $ret['status'] = self::REGISTER_FAILURE;
                throw new \Exception('开户失败，更新会员账户失败');
            }
            $GLOBALS['user_info']['payment_user_id'] = $user['id'];
            // 写银行卡信息
            $GLOBALS['db']->autoExecute('firstp2p_user_bankcard', $newBankcardInfo, 'INSERT');
            $affected_rows = $GLOBALS['db']->affected_rows();
            if ($affected_rows < 1) {
                $ret['status'] = self::REGISTER_FAILURE;
                throw new \Exception('开户失败，注册银行卡失败');
            }
            // 55汇源注册绑卡打tag
            $userService = new \core\service\UserService();
            $tagService = new \core\service\UserTagService();
            $bonusService = new \core\service\BonusService();
            $inviteUser = $userService->getUserByInviteCode($user['invite_code']);
            // 请求支付，确定支付开户也能成功

            $result = PaymentApi::instance()->request("register_bindbank", $registerParam);
            if ($result['respCode'] == '00' && ($result['status'] == '00' || $result['status'] == self::REGISTER_USER_EXISTS)) {
                if ($result['status'] == self::REGISTER_USER_EXISTS && app_conf('PAYMENT_QUERY_PASSWORD')) {
                    // 检查用户资料
                    $params = array('source' => 1, 'userId' => $userId);
                    $userInfo = \libs\utils\PaymentApi::instance()->request('searchuserinfo', $params);
                    if ($userInfo['cardNo'] != $registerParam['cardNo']) {
                        $ret['status'] = self::REGISTER_FAILURE;
                        throw new \Exception('请使用第一次注册使用的证件号');
                    }
                    // 获取支付系统所有银行卡列表-安全卡数据
                    $obj = new UserBankcardService();
                    $banksInfo = $obj->queryBankCardsList($userId, true);
                    if (!empty($banksInfo['list'])) {
                        $bankItem = $banksInfo['list'];
                        if ($bankItem['cardNo'] != $data['bankCardNo']) {
                            $ret['status'] = self::REGISTER_FAILURE;
                            throw new \Exception('请使用第一次注册使用的银行卡注册');
                        }
                    }
                }
                $rs_commit = $GLOBALS['db']->commit();
                $ret['status'] = self::REGISTER_SUCCESS;
            } else {
                $alarmContent['request'] = $registerParam;
                $alarmContent['response'] = $result;
                $res = "注册失败";
                $ret['status'] = self::REGISTER_FAILURE;
                self::log($res, Logger::WARN);

                $ignoreResult = array(
                    '银行编码与卡信息不一致',
                    '输入卡信息有误或卡信息不存在',
                    '银行卡只能是借记卡',
                );
                if (!in_array($result['respMsg'], $ignoreResult)) {
                    \libs\utils\Alarm::push('payment', 'register', $res);
                }

                throw new \Exception($result['respMsg']);
            }

            // O2O落单
            O2OService::triggerO2OOrder($registerParam['userId'], CouponGroupEnum::TRIGGER_FIRST_BINDCARD);

            new \core\service\DigService('bindBankCard', array(
                'bankCardNo' => $registerParam['bankCardNo'],
                'bankCardSn' => $registerParam['bankCode'],
                'cardName' => $registerParam['realName'],
                'id' => $registerParam['userId'],
                'mobile' => $registerParam['phoneNo'],
                'cn' => $user['invite_code'],
            ));

            // 广告联盟
            //$adService = new \core\service\AdunionDealService();
            //$adService->triggerAdRecord($userId, 3);
            $event = new \core\event\BonusEvent('bindCard', $userId, $user['invite_code']);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if (!$task_id) {
                Logger::wLog('绑卡添加返利失败|' . $userId . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH . "send_bonus_event" . date('Ymd') . '.log');
            }
            $bonusService = new \core\service\BonusService();
            $bonusService->transCashBonus($userId);
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $ret['status'] = self::REGISTER_FAILURE;
            $ret['msg'] = $e->getMessage();
        }

        return $ret;
    }


    /**
     * 实名认证
     * @param array $data 实名认证数据
     * @param integer $userId 用户id
     * @param boolean $isUpdateIdcard 是否更新用户实名信息
     * throws \Exception
     */
    public function doIdValidate($data, $userId, $isUpdateIdcard = true)
    {
        // 执行实名认证
        if (empty($data['cardNo'])) {
            throw new \Exception('身份信息验证失败,如需帮助请联系客服');
        }
        $userService = new UserService();
        $userExist = $userService->getUserByIdno($data['cardNo'], $userId);
        if ($userExist) {
            throw new \Exception('身份验证失败,如需帮助请联系客服');
        }

        $id5 = new \libs\idno\CommonIdnoVerify();
        // 取得生日（由身份证号）
        $birth = $id5->getBirthDay(trim($data['cardNo']));
        $age = $userService->getAge($birth['year'], $birth['month']);
        // 获取邀请人UID
        if (empty($GLOBALS['user_info']['refer_user_id'])) {
            $userInfo = $userService->getUserViaSlave($userId);
            $refer_user_id = !empty($userInfo['refer_user_id']) ? (int)$userInfo['refer_user_id'] : 0;
        } else {
            $refer_user_id = !empty($GLOBALS['user_info']['refer_user_id']) ? (int)$GLOBALS['user_info']['refer_user_id'] : 0;
        }
        if (($age < 18) || (($age > 70) && $userService->checkReferee($refer_user_id))) {
            throw new \Exception('身份认证失败，平台仅支持年龄为18-70周岁的用户进行认证');
        }

        $flag = app_conf("ID5_VALID");

        //身份认证接口
        if (!empty($flag)) {
            $ret = $id5->checkIdno($data['realName'], $data['cardNo']);
            if ($ret['code'] == '0') {
                $reinfo = 1;
            } else {
                $reinfo = $ret['code'];
            }
            // 只有关闭状态下直接审核通过
        } elseif ($flag === '0') {
            $reinfo = 1;
        }
        if ($reinfo == 1) {
            $userData = new \core\data\UserData();
            $userData->pushCreditReg(array('user_id' => $GLOBALS['user_info']['id'], 'ip' => get_client_ip(), 'time' => time()));

            if ($isUpdateIdcard) {
                $datas['real_name'] = $data['realName'];
                $datas['idno'] = strtoupper(trim($data['cardNo']));
                $datas['idcardpassed'] = 1;
                $datas['idcardpassed_time'] = time();
                $datas['sex'] = $id5->getSex($datas['idno']);
                // 设置出生日期
                $datas['byear'] = $birth['year'];
                $datas['bmonth'] = $birth['month'];
                $datas['bday'] = $birth['day'];
                // 更新用户信息
                $GLOBALS['db']->autoExecute('firstp2p_user', $datas, 'UPDATE', " id = '{$userId}' ");
                $affected_rows = $GLOBALS['db']->affected_rows();
                if ($affected_rows < 1) {
                    throw new \Exception('用户实名认证失败');
                }
            }
            // 记录日志文件
            $log = array(
                'type' => 'idno',
                'user_name' => $data['realName'],
                'user_login_name' => $GLOBALS ['user_info'] ['user_name'],
                'indo' => strtoupper(trim($data['cardNo'])),
                'path' => __FILE__,
                'function' => __FUNCTION__,
                'msg' => '身份证认证成功.',
                'code' => $reinfo,
                'flag' => $flag,
                'time' => time()
            );
            setLog(array('errno' => 0));
            Logger::wLog($log);
        } else {
            // 记录日志文件
            $log = array(
                'type' => 'idno',
                'user_name' => $form_data ['name'],
                'user_login_name' => $GLOBALS ['user_info'] ['user_name'],
                'indo' => $form_data ['idno'],
                'path' => __FILE__,
                'function' => __FUNCTION__,
                'msg' => '身份证认证失败.',
                'code' => $reinfo,
                'flag' => $flag,
                'time' => time()
            );
            Logger::wLog($log);
            throw new \Exception('身份认证失败');
        }
    }

    /**
     * 是否在支付开户
     */
    public function hasRegister($userId)
    {
        $userInfo = UserModel::instance()->findViaSlave($userId, 'payment_user_id');
        if (empty($userInfo['payment_user_id'])) {
            return false;
        }

        return true;
    }

    /**
     * 先锋支付接口平台注册方法
     * @param userId 用户ID
     * @param data 实名认证数据
     * @param boolean $isUpdateIdcard 是否更新用户实名信息
     * @return string
     **/
    public function register($userId, $data = array(), $isUpdateIdcard = false)
    {
        $userId = trim($userId);
        if (empty($userId)) {
            throw new \Exception("userId不能为空");
        }
        $userService = new UserService();
        $user = $userService->getUserViaSlave($userId);
        if (empty($data['realName']) && !empty($user['real_name'])) {
            $data['realName'] = $user['real_name'];
        }
        if (empty($data['cardNo']) && !empty($user['idno'])) {
            $data['cardNo'] = $user['idno'];
        }

        if (!empty($user['payment_user_id'])) {
            return self::REGISTER_HASREGISTER;
        }

        if (isset($user['user_type']) && $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
            throw new \Exception('企业用户{id:' . $userId . '}尚未成功开户');
        }

        try {
            if ($user['idcardpassed'] != 1) {
                // 实名认证并且不更新用户实名信息
                $this->doIdValidate($data, $userId, $isUpdateIdcard);
                //真实姓名
                $registerParam['realName'] = $data['realName'];
                //证件号
                $registerParam['cardNo'] = $data['cardNo'];
            } else {
                //真实姓名
                $registerParam['realName'] = $user['real_name'];
                //证件号
                $registerParam['cardNo'] = $user['idno'];
            }

            // 大写后缀X
            $registerParam['cardNo'] = strtoupper($registerParam['cardNo']);

            $registerParam['userId'] = $user['id'];
            // 证件类型
            $user_type = $this->getUserType($user['id_type']);
            $registerParam['cardType'] = $user_type; //01-身份证,//02-港澳台
            $registerParam['phoneNo'] = $user['mobile'];
            $registerParam['regionCode'] = $user['mobile_code'];
            $registerParam['userType'] = '0'; //所有新实名注册的用户均为新用户

            $gtm = new GlobalTransactionManager();
            $gtm->setName('IdnoVerifyPaymentRegister');
            // 调用超级账户开户接口
            $gtm->addEvent(new \core\tmevent\supervision\UcfpayUserRegisterEvent($registerParam));
            // 更新用户实名信息
            $gtm->addEvent(new EventMaker([
                'commit' => [(new \core\service\UserService), 'updateUserIdnoInfo', [$userId, $registerParam]],
            ]));
            $gtmResult = $gtm->execute();
            if (!$gtmResult) {
                throw new \Exception($gtm->getError());
            }

            // 更新超级账户的开户状态
            $user->payment_user_id = $GLOBALS['user_info']['payment_user_id'] = $registerParam['userId'];
            $user->save();
            return self::REGISTER_SUCCESS;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return self::REGISTER_FAILURE;
        }
    }

    /**
     * 充值结果信息查询
     * @param payment_notice_sn 充值单编号
     * return array
     */
    public function chargeResultInfoQuery($payment_notice_sn, $businessType = '')
    {
        if (empty($payment_notice_sn)) {
            throw new Exception("payment_notice_sn is empty");
        }
        $bType = '';
        switch ($businessType) {
            case PaymentNoticeModel::PLATFORM_H5_NEW_CHARGE:
                // 如果是h5newcharge 则改变值为newcharge
                $bType = 'new_recharge';
                break;
            default:
                //支付默认 businessType,10为充值
                $bType = '10';
        }
        return PaymentApi::instance()->request('searchonetrade', array("businessType" => $bType, "outOrderId" => $payment_notice_sn));
    }

    /**
     * 充值结果信息查询
     * @payment_notice_sn 充值单号
     * return status
     */
    public function chargeStatusByPaymentNoticeNo($payment_notice_sn)
    {
        $chargeResult = $this->chargeResultInfoQuery($payment_notice_sn);
        return $this->chargeStatus($chargeResult);
    }

    private function checkPaymentApiError($result)
    {
        $flag = true;
        if (empty($result)) {
            $flag = false;
        }
        if (!isset($result['status']) || $result['status'] == '02') {
            $flag = false;
        }
        return $flag;
    }

    /**
     * 充值结果状态查询
     * @param $payment_notice_sn 充值结果状态
     * return status
     */
    public function chargeStatus($charge_result_info)
    {
        if (!$this->checkPaymentApiError($charge_result_info)) {
            $result = "充值结果状态查询返回参数错误：" . print_r($charge_result_info, 1);
            \libs\utils\Alarm::push('payment', 'chargeStatus', $result);
            return self::ERROR_PAYMENT_API;
        }

        if ($charge_result_info['status'] == '30004') {
            return self::ERROR_PAYMENT_ORDER_NOTEXITS;
        }

        if ($charge_result_info['respCode'] == '00') {
            if ($charge_result_info['orderStatus'] == '00') {
                return self::CHARGE_SUCCESS;
            } else if ($charge_result_info['orderStatus'] == '02') {
                return self::CHARGE_PENDING;
            }

            return self::CHARGE_FAILURE;
        } else {
            $result = '充值结果状态查询返回错误：' . print_r($charge_result_info, 1);
            \libs\utils\Alarm::push('payment', 'chargeStatus', $result);
            return self::ERROR_PAYMENT_API;
        }
    }

    /**
     * 充值失败处理
     * @param array $paymentNotice 充值数组
     * @return string[]
     */
    private function chargeFailueDeal($paymentNotice)
    {
        if ($paymentNotice['is_paid'] == ChargeService::STATUS_FAILED) {
            return array('respCode' => '00', 'respMsg' => '充值单支付失败已受理');
        }

        if ($paymentNotice['is_paid'] == ChargeService::STATUS_SUCCESS) {
            \libs\utils\Alarm::push('payment', 'ChargeNotifyException', "充值回调失败拒绝受理. {$paymentNotice['id']}");
            return array('respCode' => '01', 'respMsg' => '拒绝受理, 充值单已经为成功状态');
        }

        $status = ChargeService::STATUS_FAILED;
        $sql = "UPDATE firstp2p_payment_notice SET is_paid='{$status}' WHERE id='{$paymentNotice['id']}' AND is_paid IN (0, 2)";
        if (!$GLOBALS['db']->query($sql) || $GLOBALS['db']->affected_rows() != 1) {
            \libs\utils\Alarm::push('payment', 'ChargeNotifyException', "充值回调失败处理失败. {$paymentNotice['id']}");
            return array('respCode' => '02', 'respMsg' => '充值单支付失败受理失败');
        }

        //生产用户访问日志
        $device = UserAccessLogService::getPaymentDevice($paymentNotice['platform']);
        $extraInfo = [
            'orderId'       => $paymentNotice['notice_sn'],
            'chargeAmount'  => (int) bcmul($paymentNotice['money'], 100),
            'chargeChannel' => $paymentNotice['payment_id'] == PaymentNoticeModel::PAYMENT_UCFPAY ? UserAccessLogEnum::CHARGE_CHANNEL_UCFPAY : UserAccessLogEnum::CHARGE_CHANNEL_YEEPAY,
        ];
        UserAccessLogService::produceLog($paymentNotice['user_id'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网信充值%s元失败', (float)$paymentNotice['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_FAIL);

        return array('respCode' => '00', 'respMsg' => '充值单支付失败受理成功');
    }

    /**
     * 充值回调结果为充值状态处理
     */
    private function chargePaddingDeal($paymentNotice)
    {
        if ($paymentNotice['is_paid'] == ChargeService::STATUS_INHAND) {
            return array('respCode' => '00', 'respMsg' => '充值单已经为处理中状态');
        }

        if ($paymentNotice['is_paid'] == ChargeService::STATUS_FAILED) {
            return array('respCode' => '00', 'respMsg' => '充值单已经为失败状态');
        }

        if ($paymentNotice['is_paid'] == ChargeService::STATUS_SUCCESS) {
            return array('respCode' => '00', 'respMsg' => '充值单已经为成功状态');
        }

        $status = ChargeService::STATUS_INHAND;
        $sql = "UPDATE firstp2p_payment_notice SET is_paid='{$status}' WHERE id='{$paymentNotice['id']}' AND is_paid=0";
        if (!$GLOBALS['db']->query($sql) || $GLOBALS['db']->affected_rows() != 1) {
            return array('respCode' => '02', 'respMsg' => '充值单回调处理中处理失败');
        }

        return array('respCode' => '00', 'respMsg' => '充值单处理中受理成功');
    }

    /**
     * 整理充值参数并发送请求到O2O领券
     *
     * @param array $paymentNotice 充值记录
     * @param int $chargeAccount 充值账户(1:超级账户2:存管账户)
     */
    public function chargeTriggerO2O($paymentNotice, $chargeAccount = 1)
    {
        $paymentNotice['user_id'] = intval($paymentNotice['user_id']);
        if ($chargeAccount == 1) {
            // 超级账户充值
        } else if ($chargeAccount == 2) {
            // 存管账户充值
            // 存管回调的金额是amount字段，单位是分
            $paymentNotice['money'] = bcmul($paymentNotice['amount'], 0.01, 2);
        }
        // 快捷充值（线上）19，大额充值（线下）20
        if (in_array($paymentNotice['platform'], PaymentNoticeModel::$offlinePlatform)) {
            $action = PaymentNoticeModel::TRIGGER_CHARGE_OFFLINE;
        }else{
            $action = PaymentNoticeModel::TRIGGER_CHARGE_ONLINE;
        }
        // 获取用户最后一次提现成功的时间
        $withdrawTime = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT withdraw_time FROM firstp2p_user_carry WHERE withdraw_status = 1 AND  user_id = '{$paymentNotice['user_id']}' ORDER BY ID DESC LIMIT 1");
        // 实际时间需+8小时
        $withdrawTime = $withdrawTime ? ($withdrawTime + 28800) : 0;
        $params = array($paymentNotice['user_id'], $action, $paymentNotice['id'],
            $paymentNotice['money'], $paymentNotice['site_id'], $withdrawTime);

        // 添加一个异步任务
        $taskObj = new GTaskService();
        $event = new \core\event\O2ORetryEvent('chargeTriggerO2O', $params);
        $taskId = $taskObj->doBackground($event, 10);
        if (!$taskId) {
            PaymentApi::log('O2OService::chargeTriggerO2O event add failed, data:' . json_encode($params), \libs\utils\Logger::ERR);
        }
        PaymentApi::log('O2OService::chargeTriggerO2O event add success, userId:' . $paymentNotice['user_id'] . ', outOrderId:' . $paymentNotice['notice_sn'] . ', taskId:' . $taskId . ', gearmanTaskParams:' . json_encode($params), Logger::INFO);
    }

    /**
     * 充值成功处理
     * @param array $paymentNotice 充值数组
     * @param string $outerNoticeSn
     * @param string $amountLimit
     * @throws \Exception
     * @return string[]
     */
    private function chargeSuccessDeal($paymentNotice, $outerNoticeSn = '', $amountLimit = '')
    {
        if ($paymentNotice['is_paid'] == ChargeService::STATUS_SUCCESS) {
            return array('respCode' => '00', 'respMsg' => '充值单支付成功已受理');
        }

        if ($paymentNotice['is_paid'] == ChargeService::STATUS_FAILED) {
            \libs\utils\Alarm::push('payment', 'ChargeNotifyException', "充值回调成功拒绝受理. {$paymentNotice['id']}");
            return array('respCode' => '01', 'respMsg' => '拒绝受理, 充值单已经为失败状态');
        }

        $chargeService = new ChargeService();

        try {
            $GLOBALS['db']->startTrans();

            $ret = $chargeService->paidSuccess($paymentNotice, $outerNoticeSn, $amountLimit);
            if ($ret === false) {
                throw new \Exception('充值单支付成功受理失败');
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            \libs\utils\Alarm::push('payment', 'ChargeNotify', 'paidSuccess()受理异常:' . $e->getMessage());
            return array('respCode' => '02', 'respMsg' => '充值单支付成功受理异常:' . $e->getMessage());
        }

        $this->setChargeStatusCache($paymentNotice['user_id'], $paymentNotice['notice_sn']);

        //生产用户访问日志
        $device = UserAccessLogService::getPaymentDevice($paymentNotice['platform']);
        $extraInfo = [
            'orderId'       => $paymentNotice['notice_sn'],
            'chargeAmount'  => (int) bcmul($paymentNotice['money'], 100),
            'chargeChannel' => $paymentNotice['payment_id'] == PaymentNoticeModel::PAYMENT_UCFPAY ? UserAccessLogEnum::CHARGE_CHANNEL_UCFPAY : UserAccessLogEnum::CHARGE_CHANNEL_YEEPAY,
        ];
        UserAccessLogService::produceLog($paymentNotice['user_id'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网信充值%s元成功', (float)$paymentNotice['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_SUCCESS);

        return array('respCode' => '00', 'respMsg' => '充值单支付成功受理成功');
    }

    /**
     * 设置充值状态缓存
     */
    public function setChargeStatusCache($userId, $orderSn)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            return $redis->setex("CHARGE_{$userId}_{$orderSn}", 86400, 1);
        }
    }

    /**
     * 获取充值状态
     */
    public function getChargeStatusCache($userId, $orderSn)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            return $redis->get("CHARGE_{$userId}_{$orderSn}");
        }
    }

    /**
     * 充值成功通知回调服务
     * @param int $paymentNoticeSn 充值订单号
     * @param unknown $orderStatus 订单状态
     * @param unknown $amount 订单金额，单位分
     * @throws \Exception
     * @return unknown|string[]|NULL[]
     */
    public function chargeResultCallback($paymentNoticeSn, $orderStatus, $amount)
    {
        try {
            if (empty($paymentNoticeSn) || empty($orderStatus) || empty($amount)) {
                throw new \Exception('参数错误');
            }

            $paymentNotice = PaymentNoticeModel::instance()->getInfoByNoticeSn($paymentNoticeSn);
            if (empty($paymentNotice)) {
                throw new \Exception('充值单不存在');
            }

            if (bccomp(bcmul($paymentNotice['money'], 100, 2), $amount, 2) !== 0) {
                throw new \Exception('充值回调金额有误');
            }

            // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
            $ucfpayOrderInfo = $this->chargeResultInfoQuery($paymentNoticeSn, $paymentNotice['platform']);
            if (empty($ucfpayOrderInfo) || !isset($ucfpayOrderInfo['respCode'])
                || $ucfpayOrderInfo['respCode'] !== '00' || $ucfpayOrderInfo['status'] !== '00') {
                $exceptionMsg = isset($ucfpayOrderInfo['respMsg']) ? '订单查询接口返回:' . $ucfpayOrderInfo['respMsg'] : '支付订单查询接口返回错误';
                \libs\utils\Alarm::push('payment', 'chargeResultCallback', sprintf('先锋支付订单查询接口报错|报错消息:%s', $exceptionMsg));
                throw new \Exception($exceptionMsg);
            }
            // 充值回调的状态、订单查询接口的状态不一致时，添加告警
            if (isset($ucfpayOrderInfo['orderStatus']) && $ucfpayOrderInfo['orderStatus'] !== $orderStatus) {
                \libs\utils\Alarm::push('payment', 'chargeResultCallback', sprintf('先锋支付充值回调的订单状态跟订单查询接口的状态不一致|充值回调的状态:%s|订单查询接口的状态:%s|订单查询接口的报错消息:%s', $orderStatus, $ucfpayOrderInfo['orderStatus'], $ucfpayOrderInfo['respMsg']));
                throw new \Exception('先锋支付充值回调的订单状态跟订单查询接口的状态不一致');
            }
            // 充值回调的金额、订单查询接口的金额不一致时，添加告警
            if (isset($ucfpayOrderInfo['amount']) && bccomp($ucfpayOrderInfo['amount'], $amount) !== 0) {
                \libs\utils\Alarm::push('payment', 'chargeResultCallback', sprintf('先锋支付充值回调的金额跟订单查询接口的金额不一致|充值回调的金额:%s|订单查询接口的金额:%s|订单查询接口的报错消息:%s', $amount, $ucfpayOrderInfo['amount'], $ucfpayOrderInfo['respMsg']));
                throw new \Exception('先锋支付充值回调的金额跟订单查询接口的金额不一致');
            }

            //失败回调处理
            if ($orderStatus == self::CHARGE_FAILURE) {
                return $this->chargeFailueDeal($paymentNotice);
            }

            //成功回调处理
            if ($orderStatus == self::CHARGE_SUCCESS) {
                $handlResult = $this->chargeSuccessDeal($paymentNotice);
                // 充值成功处理成功
                if ($handlResult['respCode'] == '00') {
                    // 触发O2O请求
                    $this->chargeTriggerO2O($paymentNotice, 1);
                }
                $this->zhugeCharge($paymentNotice['platform'], $paymentNotice['user_id'],$amount);
                return $handlResult;
            }

            //处理中回调处理
            if ($orderStatus == self::CHARGE_PENDING) {
                return $this->chargePaddingDeal($paymentNotice);
            }

            throw new \Exception('回调状态有误');
        } catch (\Exception $e) {
            return array('respCode' => '01', 'respMsg' => $e->getMessage());
        }

    }

    public function zhugeCharge($platform, $userId,$amount)
    {
        if(in_array($platform,PaymentNoticeModel::$wapPlatform)){
            $eventName = '网信账户_充值成功_wap';
            $zhugeSource = Zhuge::APP_MOBILE;
            (new Zhuge($zhugeSource))->event($eventName,$userId, ['money'=>bcdiv($amount, 100, 2)]);
        }
        if (in_array($platform,PaymentNoticeModel::$pcPlatform)){
            $eventName = '网信账户_充值成功_pc';
            $zhugeSource = Zhuge::APP_WEB;
            (new Zhuge($zhugeSource))->event($eventName,$userId, ['money'=>bcdiv($amount, 100, 2)]);
        }
        if (in_array($platform,PaymentNoticeModel::$appPlatform)){
            $eventName = '网信账户_充值成功_app';
            $zhugeSource = Zhuge::APP_MOBILE;
            (new Zhuge($zhugeSource))->event($eventName,$userId, ['money'=>bcdiv($amount, 100, 2)]);
        }
    }

    /**
     * 提现成功回调
     */
    public function withdrawCallback($params)
    {
        $orderId = isset($params['outOrderId']) ? intval($params['outOrderId']) : 0;
        $userId = isset($params['userId']) ? intval($params['userId']) : 0;
        $orderStatus = isset($params['orderStatus']) ? trim($params['orderStatus']) : '';

        // 响应数据
        $responseData = array(
            'result' => true,
            'reason' => '',
        );
        if (empty($orderId) || empty($userId) || empty($orderStatus))
        {
            throw new \Exception('outOrderId/userId/orderStatus 一项为空' . var_export(array($orderId, $userId, $orderStatus), true));
            return array('result' => false , 'reason' => '参数无效');
        }
        try {
            $userCarry = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "user_carry WHERE id = '{$orderId}' AND user_id='{$userId}'");
            if (empty($userCarry)) {
                throw new \Exception('提现记录' . $orderId .'不存在.', -1);
            }
            $_withdrawStatus = array(
                UserCarryModel::WITHDRAW_STATUS_SUCCESS => '00',
                UserCarryModel::WITHDRAW_STATUS_FAILED => '01',
                UserCarryModel::WITHDRAW_STATUS_PAY_PROCESS => '09',
            );
            $userService = new UserService();
            $user = $userService->getUser($userId);
            if (empty($user)) {
                throw new \Exception('用户查询失败.', -1);
            }
            // 如果订单状态已经跟返回的状态一致
            if ($_withdrawStatus[$userCarry['withdraw_status']] == $orderStatus) {
                throw new \Exception('提现已经处理', 9999);
            }

            $GLOBALS['db']->startTrans();
            $realChangeMoney = bcadd($userCarry['money'], $userCarry['fee'], 2);
            $pay_process_time = 0;
            $withdrawStatus = 0;
            $withdrawMsg = '';
            $content = '';
            $toUpdate = array();
            // 处理成功
            $money_format = format_price($userCarry['money']);
            $date_format = to_date($userCarry['create_time'],"Y年m月d日 H:i:s");
            $bizToken = ['orderId' => $orderId];
            if ($orderStatus == '00') {
                $tpl = 'TPL_SMS_WITHDRAW_SUCCESS_NEW';
                $mark = '提现成功';
                $user->changeMoney($realChangeMoney,'提现成功', '网信账户提现成功' . $orderId, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY, 0, $bizToken);
                $withdrawStatus = UserCarryModel::WITHDRAW_STATUS_SUCCESS;
                $toUpdate['status'] = 3;
                $toUpdate['update_time'] = get_gmtime();

                $content = sprintf('您于%s申请的%s提现已汇款，具体到账时间根据各银行规定，请注意查收。 ', $date_format, $money_format);

                // 记录api日志，供数据端使用
                $apiLog = array(
                    'time' => date('Y-m-d H:i:s'),
                    'userId' => $userId,
                    'userCarryId' => $orderId,
                    'money' => $userCarry['money'],
                );
                setLog(array('errno' => 0));
                logger::wLog("API_CASHOUT_RESP:".json_encode($apiLog));
                PaymentApi::log("API_CASHOUT_RESP:".json_encode($apiLog), Logger::INFO);
                \libs\utils\Monitor::add('WITHDRAW_SUCCESS');
            } else if ($orderStatus == '01') {
                $tpl = 'TPL_SMS_ACCOUNT_CASHOUT_FAIL_NEW';
                $mark = '提现失败';
                // 处理失败
                $withdrawStatus = UserCarryModel::WITHDRAW_STATUS_FAILED;
                $withdrawMsg = $params['respMsg'];
                $toUpdate['status'] = 3;
                $toUpdate['update_time'] = get_gmtime();

                // 放款提现发送告警
                if ($userCarry['deal_id'] != 0)
                {
                    $failAt = date('Y-m-d H:i:s');
                    $dealInfo = $GLOBALS['db']->getRow("SELECT project_id FROM firstp2p_deal WHERE id='{$userCarry['deal_id']}'");
                    $oldDealName = getOldDealNameWithPrefix($userCarry['deal_id'], $dealInfo['project_id']);
                    $failMessage = "提现编号：{$userCarry['id']} 放款标题：{$oldDealName} 放款金额：{$userCarry['money']} 失败时间：{$failAt}";
                    Alarm::push('deal_withdraw_fail', '放款提现失败', $failMessage);
                }

                // 借款人的提现失败不解冻结
                if (empty($userCarry['deal_id'])) {
                    $user->changeMoney(-$realChangeMoney,'提现失败', '银行受理失败，如有疑问请拨打平台客服热线 95782。', 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                } else {
                    $is_skip_notice = true;
                }

                $content = sprintf('您于%s提交的%s提现银行受理失败。如有疑问请拨打平台客服热线 95782。', $date_format, $money_format);
                \libs\utils\Monitor::add('WITHDRAW_FAILED');
            }
            else if ($orderStatus == '09')
            {
                $withdrawStatus = UserCarryModel::WITHDRAW_STATUS_PAY_PROCESS;
                $withdrawMsg = $params['respMsg'];
                $pay_process_time = $toUpdate['update_time'] = get_gmtime();
            }
            else {
                // 无效数据
                $tpl = 'TPL_SMS_ACCOUNT_CASHOUT_FAIL_NEW';
                $mark = '提现失败';
                $withdrawMsg = $params['respMsg'];
                $withdrawStatus = UserCarryModel::WITHDRAW_STATUS_FAILED;
                $toUpdate['status'] = 3;
                $toUpdate['update_time'] = get_gmtime();
                $content = sprintf('您于%s提交的%s提现银行受理失败。如有疑问请拨打平台客服热线 95782。', $date_format, $money_format);
            }
            // 更新 提现表的批准状态
            $upResult = $GLOBALS['db']->autoExecute('firstp2p_user_carry', $toUpdate, 'UPDATE', ' id  = ' . $orderId);

            $withdrawMsg = addslashes($withdrawMsg);
            $sql = "UPDATE ".DB_PREFIX."user_carry SET withdraw_status='{$withdrawStatus}', withdraw_msg = '{$withdrawMsg}', withdraw_time=".get_gmtime() . ", update_time=" .get_gmtime(). ($pay_process_time? ", pay_process_time = '{$pay_process_time}' " : " ");
            $sql .= " WHERE id='$orderId' AND user_id = '{$userId}' AND withdraw_status NOT IN (" . UserCarryModel::WITHDRAW_STATUS_SUCCESS . ',' . UserCarryModel::WITHDRAW_STATUS_FAILED . ')';
            $GLOBALS['db']->query($sql);
            if ($GLOBALS['db']->affected_rows() < 1)
            {
                throw new \Exception("提现记录已经为最终状态，不能更改为:".UserCarryModel::$withdrawDesc[$withdraw_status], 200);
            }

            // 提现失败，发起自动提现
            if ($orderStatus == '01') {
                $userCarryService = new UserCarryService();
                $ret = $userCarryService->autoWithdrawRetry($userId);
                if (!$ret) {
                    throw new \Exception('自动提现重试失败');
                }
            }

            $GLOBALS['db']->commit();

            // 支付处理中不发送站内信和短信，直接返回成功
            if ($orderStatus == UserCarryModel::WITHDRAW_STATUS_PAY_PROCESS || !empty($is_skip_notice))
            {
                return $responseData;
            }

            //生产用户访问日志
            if ($withdrawStatus == UserCarryModel::WITHDRAW_STATUS_SUCCESS || $withdrawStatus == UserCarryModel::WITHDRAW_STATUS_FAILED) {
                $device = UserAccessLogService::getPaymentDevice($userCarry['platform']);
                $extraInfo = [
                    'orderId' => $orderId,
                    'withdrawAmount' => (int) bcmul($userCarry['money'], 100),
                ];
                $logStatus = UserAccessLogService::getLogStatus($withdrawStatus);
                $statusName = $withdrawStatus == UserCarryModel::WITHDRAW_STATUS_SUCCESS ? '成功' : '失败';
                UserAccessLogService::produceLog($userId, UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网信提现%s元%s', (float)$userCarry['money'], $statusName), $extraInfo, '', $device, $logStatus);
            }

            //TODO 首次提现现金红包返利
            $userCarryCount = UserCarryModel::instance()->count('user_id = ' .$userId. ' AND withdraw_status = ' .UserCarryModel::WITHDRAW_STATUS_SUCCESS);
            if ($userCarryCount == 1) {
                $bonusService = new BonusService();
                $res = $bonusService->rebateCashBonus($userId);
                if (!$res) {
                    \libs\utils\PaymentApi::log("FirstCarryCashRebateError." . $userId);
                }
            }

            //发送站内信
            $typeMap = array('提现成功' => 6, '提现失败' => 7);
            $msgbox = new MsgBoxService();
            $structured_content = array(
                'main_content' => $content,
                'money' => (6 == $typeMap[$mark]) ? sprintf("-%s", number_format($userCarry['money'], 2)) : '',
                'turn_type' => MsgBoxEnum::TURN_TYPE_MONEY_LOG,
            );
            $msgbox->create($userId, $typeMap[$mark], $mark, $content, $structured_content);

            // 增加短信提示
            if (app_conf("SMS_ON")==1){
                // SMSSend 提现回调
                if ($user['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                {
                    $accountTitle = get_company_shortname($user['id']); // by fanjingwen
                } else {
                    $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                }

                $msg_content = array(
                    'account_title' => $accountTitle,
                    'sms_amount' =>  format_price($realChangeMoney),
                );
                $dealService = new DealService();
                // 掌众不发提现成功短信
                if(!$dealService->isZhangzhongDeal($userCarry['deal_id'])) {
                    SmsServer::instance()->send($user['mobile'], $tpl, $msg_content, $user['id']);
                }
            }

        }
        catch(\Exception $e) {
            $code = $e->getCode();
            $reason = $e->getMessage();
            if ($code != 9999) {
                $GLOBALS['db']->rollback();
                \libs\utils\Alarm::push('payment', '提现回调失败', $e->getMessage());
                $responseData = array('result' => false, 'reason' => $reason);
            }
            else {
                //重复处理
                $responseData = array('result' => true, 'reason' => $reason);
            }
        }
        return $responseData;
    }

    public function searchTrades($conditions, $page = 1) {
        $strConditions = array();
        // 时间范围搜索
        $now = time();
        $begin_time = $end_time  = 0;
        if(!empty($conditions['beginTime'])) {
            $begin_time = intval($conditions['beginTime']);
            //最大查看一月前数据
            // if ($begin_time < $now - 30*86400) {
                // $begin_time = $now - 30*86400;
            // }
        }
        if (!empty($conditions['endTime'])) {
            $end_time = intval($conditions['endTime']);
        }

        if ($end_time <= $begin_time) {
            return array('respCode' => '01', 'respMsg' => 'BEGINTIME_LARGER_THAN_ENDTIME');
        }
        $condition_time = '';
        if ($end_time > $begin_time) {
            $condition_time = sprintf('  log_time >= %d AND log_time  <= %d ', $begin_time, $end_time);
            $strConditions[] = $condition_time;
        }

        // 其他过滤条件
        $condition_other = '';
        if (!empty($conditions['userId'])) {
            $condition_other = sprintf(' user_id = %d ', intval($conditions['userId']));
            $strConditions[] = $condition_other;
        }
        $tranType = intval($conditions['tranType']);

        //设置 页码信息
        $page_size = 200;
        $page_no = 1;
        if(!empty($conditions['pageSize'])) {
            $page_size = intval($conditions['pageSize'])> 1000 ? 1000 : intval($conditions['pageSize']);
            if ($page_size <= 1) {
                $page_size = 1;
            }
        }

        if (!empty($conditions['pageNo'])) {
            $page_no = intval($conditions['pageNo']) <=  1 ? 1 : intval($conditions['pageNo']);
        }

        $_limit_s = $page_size * ($page_no - 1);
        $_limit_e = $page_size;
        $condition_limit  = sprintf(' LIMIT %d, %d ',  $_limit_s, $_limit_e);
        $order_by = ' ORDER BY id DESC ';

        $condition_sql = '';
        if (!empty($strConditions)) {
            $condition_sql = implode(' AND ', $strConditions);
        }

        $trades = array('total' => 0, 'list' => array());
        if($tranType === 100) {
            $condition_sql = str_replace('log_time', 'pay_time', $condition_sql);
            $condition_sql .= ' AND is_paid = 1  '; // 已支付
            $trades['list'] = PaymentNoticeModel::instance()->findAll($condition_sql . $order_by . $condition_limit, true, 'user_id AS userId, user_id AS receiverId,  0 AS payerId, money AS amount,"CNY" AS curType,  100 AS businessType, notice_sn AS orderId, "00"  AS orderStatus, pay_time AS gmt_finished');
            $trades['total'] = PaymentNoticeModel::instance()->count($condition_sql);
            $trades['total'] = ceil($trades['total'] / $page_size);
        }
        else if ($tranType === 200)  {
             $condition_sql = str_replace('log_time', 'update_time', $condition_sql);
             $condition_sql .= ' AND status = 3 AND type = 1 '; // 提现并且被最终审核通过
            $trades['list'] = UserCarryModel::instance()->findAll($condition_sql . $order_by . $condition_limit, true, 'user_id as userId , user_id AS payerId, 0 AS receiverId, money + fee AS amount,"CNY" AS curType, 200 AS businessType, id AS orderId, "00"  AS orderStatus, update_time AS gmt_finished');
            $trades['total'] = UserCarryModel::instance()->count($condition_sql);
            $trades['total'] = ceil($trades['total'] / $page_size);
        }
        else if ($tranType === 300) {
            $user_mapping = !empty($conditions['ptype']) ? intval($conditions['ptype']) : 1;
            $condition_sql = str_replace('log_time', 'create_time', $condition_sql);
            if ($user_mapping === 1) {
                $condition_sql = str_replace('user_id', 'payerId', $condition_sql);
            }
            else if ($user_mapping === 2)  {
                $condition_sql = str_replace('user_id', 'recieverId', $condition_sql);
            }
            $trades['list'] = FinanceDetailLogModel::instance()->findAll($condition_sql . $order_by . $condition_limit, true, '0 AS userId, payerId, receiverId, repaymentAmount AS amount, curType, 300 AS businessType, outOrderId AS orderId, status  AS orderStatus, create_time AS gmt_finished');
            $trades['total'] = FinanceDetailLogModel::instance()->count($condition_sql);
            $trades['total'] = ceil($trades['total'] / $page_size);
        }

        $result = array(
            'respCode' => '00',
            'respMsg' => '',
            'pageSize' => $page_size,
            'pageTotal' => $trades['total'],
            'orders' => $trades['list'],
        );
        return $result;
    }


    /**
     * 日志记录
     */
    public static function log($body, $level = Logger::INFO)
    {
        $destination = APP_ROOT_PATH.'log/logger/PaymentService.'.date('y_m').'.log';
        Logger::wLog($body, $level, Logger::FILE, $destination);
    }

    /**
     * response
     * 被通知支付成功回调处理
     *
     * @param mixed $request
     * @access public
     * @return true 表示支付成功，false表示已经支付或者操作失败，没有数据变动
     */
    public function response($request)
    {
        $tranStat = $request['tranStat'];
        $paymentNoticeSn = $request['orderNo'];
        $money = $request['orderAmt'];
        $outerNoticeSn = $request['tranSerialNo'];
        $amountLimit = !isset($request['amountLimit']) ? PaymentNoticeModel::AMOUNT_LIMIT_NULL : $request['amountLimit'];

        $paymentNotice = PaymentNoticeModel::instance()->getInfoByNoticeSn($paymentNoticeSn);
        if (empty($paymentNotice)) {
            return array('status' => 1, 'msg' => '支付单已经处理过或者不存在');
        }

        //充值成功处理
        if ($tranStat == 1) {
            (new Zhuge(Zhuge::APP_WEB))->event('网信账户_充值成功_app', $paymentNotice['user_id'], ['money'=>$paymentNotice['money']]);
            (new Zhuge(Zhuge::APP_MOBILE))->event('网信账户_充值成功_app', $paymentNotice['user_id'], ['money'=>$paymentNotice['money']]);
            // 手机端支付成功
            \libs\utils\Monitor::add('MOBILE_PAY_SUCCESS');
            //成功订单处理
            $result = $this->chargeSuccessDeal($paymentNotice, $outerNoticeSn, $amountLimit);
            if ($result['respCode'] !== '00') {
                return array('status' => 1, 'msg' => $result['respMsg']);
            }

            //调支付端查询用户充值银行卡信息，给未绑定银行卡用户绑定银行卡，失败没关系
            try {
                $GLOBALS['db']->startTrans();
                $this->bindBankcard($paymentNotice['user_id'], 1, $request);
                $GLOBALS['db']->commit();
            } catch(\Exception $e) {
                $GLOBALS['db']->rollback();
                \libs\utils\Alarm::push('payment', '移动端充值成功绑卡失败', $paymentNotice['user_id']."-".$paymentNotice['id']);
            }

            // 触发O2O
            $this->chargeTriggerO2O($paymentNotice);

            return array('status' => 0, 'msg' => '充值回调成功');
        }

        //充值失败处理
        if ($tranStat == 0) { // 充值失败
            $paymentNotice->amount_limit = $amountLimit; // 大小额
            if ($amountLimit == PaymentNoticeModel::AMOUNT_LIMIT_BIG) { // 只有大额的才会记录该状态
                $paymentNotice->is_paid = 3; // 充值失败
            }
            if (!$paymentNotice->save()) {
                $msg = "回调支付失败时更新订单大小额状态失败。";
                \libs\utils\Alarm::push('payment', '移动端回调失败处理失败', $msg.json_encode($request));
            }
            return array('status' => 0, 'msg' => '支付端回调充值失败,可以再重试');
        }

        //充值处理中处理
        if ($tranStat == 2) {
            $paymentNotice->amount_limit = $amountLimit; // 大小额
            if (!$paymentNotice->save()) {
                \libs\utils\Alarm::push('payment', '移动端充值处理中处理失败', json_encode($request));
            }
            // 移动端充值失败
            \libs\utils\Monitor::add('MOBILE_PAY_FAIL');
            return array('status' => 1, 'msg' => '支付端回调充值处理中,可以再重试');
        }

        \libs\utils\Alarm::push('payment', '移动端回调处理失败', json_encode($request));
        return array('status' => 1, 'msg' => '支付端回调返回异常');
    }

    /**
     * apply
     * 调用支付端创建订单
     *
     * @param mixed $notice_id
     * @param mixed $merchant
     * @param mixed $uid
     * @param mixed $amount
     * @param mixed $bankCardId 银行卡唯一标识
     * @param mixed $curType
     * @access public
     * @return void
     */
    public function apply($notice_id, $merchant, $uid, $amount, $bankCardId = '', $curType = 'CNY') {
        $payment_notice = PaymentNoticeModel::instance()->find($notice_id);
        if (empty($payment_notice)) {
            $msg = '没有对应订单号！';
            PaymentApi::log("PaymentService.apply:".$notice_id.$msg, Logger::WARN);
            \libs\utils\Alarm::push('payment', "PaymentService.apply", $notice_id.$msg);
            return false;
        }
        $params = array(
            'outOrderId' => $payment_notice['notice_sn'],
            'merchantId' => $merchant,
            'userId' => $uid,
            'amount' => $amount * 100,
            'curType' => $curType,
        );
        if (!empty($bankCardId)) {
            $params['bankCardId'] = $bankCardId;
        }
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_PAY_CREATE'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retSrc = Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retSrc, true);
        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'PaymentService',
            'money' => $amount,
            'function' => 'apply',
            'msg' => '调用支付端创建订单接口',
            'api' => $api,
            'request' => $params,
            'response' => $retSrc,
            'response_decode' => $datas,
        );
        logger::wLog($log);

        if (\libs\utils\Aes::validate($datas)) {
            PaymentApi::log("PaymentService.apply:".json_encode($log), Logger::INFO);
            // 验证成功
            if ($datas['status'] == '00') {
                // 支付创建订单成功
                \libs\utils\Monitor::add('MOBILE_PAY_CREATE_SUCCESS');
                // 需要将p2p后台的订单状态设置为处理中
                $payment_notice->is_paid = 2;
                $payment_notice->save();
                // app端需要的数据是给移动端进行签名使用的

                //生产访问日志
                $device = UserAccessLogService::getPaymentDevice($payment_notice['platform']);
                $extraInfo = [
                    'orderId'       => $payment_notice['notice_sn'],
                    'chargeAmount'  => (int) bcmul($payment_notice['money'], 100),
                    'chargeChannel' => $payment_notice['payment_id'] == PaymentNoticeModel::PAYMENT_UCFPAY ? UserAccessLogEnum::CHARGE_CHANNEL_UCFPAY : UserAccessLogEnum::CHARGE_CHANNEL_YEEPAY,
                ];
                UserAccessLogService::produceLog($payment_notice['user_id'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网信充值申请%s元', (float)$payment_notice['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_INIT);



                return $params;
            } else {
                // 支付创建订单未返回成功
                \libs\utils\Monitor::add('MOBILE_PAY_CREATE_FAIL');
                return false;
            }
        } else {
            PaymentApi::log("PaymentService.apply:".json_encode($log), Logger::WARN);
            $msg = "返回结果数据验证失败。";
            \libs\utils\Alarm::push('payment', "PaymentService.apply", $msg);
            // 创建订单失败
            \libs\utils\Monitor::add('MOBILE_PAY_CREATE_FAIL');
            return false;
        }
    }

    /**
     * check
     * 检查订单支付结果
     *
     * @param mixed $order_id
     * @param mixed $merchant
     * @param mixed $businessType
     * @access public
     * @return void
     */
    public function check($order_id, $merchant, $businessType) {
        $params = array(
            'outOrderId' => $order_id,
            'merchantId' => $merchant,
            'businessType' => $businessType,
        );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_PAY_CHECK'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retStr = Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retStr, true);

        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'PaymentService',
            'order_id' => $order_id,
            'function' => 'check',
            'msg' => '调用支付端查询订单接口',
            'api' => $api,
            'request' => $aesData,
            'response' => $retStr,
            'response_decode' => $datas,
        );
        logger::wLog($log);
        if (\libs\utils\Aes::validate($datas)) {
            PaymentApi::log("PaymentService.check:".json_encode($log), Logger::INFO);
            // 验证成功
            return $datas;
        } else {
            PaymentApi::log("PaymentService.check:".json_decode($log), Logger::WARN);
            return false;
        }
    }

    /**
     * cashOut
     * 申请提现
     *
     * @param mixed $uid
     * @param mixed $money
     * @access public
     * @return void
     */
    public function cashOut($uid, $money, $platform = PaymentNoticeModel::PLATFORM_WEB, $changeMoneyDealType = -1) {
        $bankcard_info = UserBankcardModel::instance()->getByUserId($uid);
        if(!$bankcard_info || $bankcard_info['status'] != 1) {
            return false;
        }

        // 提现时，检查用户是否符合风控延迟提现规则-JIRA4937
        $isWithdrawDelayUser = $this->isWithdrawLimitedByUserId($uid, $money);

        $fee = 0; // 不收手续费
        $create_time = get_gmtime();

        $user_carry_dao = new UserCarryModel();
        $user_carry_dao->user_id = $uid;
        $user_carry_dao->money = $money;
        $user_carry_dao->fee = 0;
        $user_carry_dao->bank_id = $bankcard_info['bank_id'];
        $user_carry_dao->real_name = $bankcard_info['card_name'];
        $user_carry_dao->region_lv1 = $bankcard_info['region_lv1'];
        $user_carry_dao->region_lv2 = $bankcard_info['region_lv2'];
        $user_carry_dao->region_lv3  = $bankcard_info['region_lv3'];
        $user_carry_dao->region_lv4 = $bankcard_info['region_lv4'];
        $user_carry_dao->bankcard = $bankcard_info['bankcard'];
        $user_carry_dao->bankzone = $bankcard_info['bankzone'];
        $user_carry_dao->create_time = $create_time;
        $user_carry_dao->warning_stat = $isWithdrawDelayUser ? UserCarryModel::WITHDRAW_IS_DELAY : UserCarryModel::WITHDRAW_IS_NORMAL; // 是否被风控延迟提现;
        $user_carry_dao->money_limit = floatval(app_conf('PAYMENT_AUTO_AUDIT'));
        $user_carry_dao->platform = $platform;

        // 起事务
        $user_dao = new UserModel();
        $user = $user_dao->find($uid);
        try {
            $GLOBALS['db']->startTrans();
            if (!$user_carry_dao->save()) {
                throw new \Exception("添加提现申请记录失败", 999);
            }
            //更新会员账户信息
            $message = "提现申请";
            $note = "网信账户提现申请";
            (int)$changeMoneyDealType > 0 && $user->changeMoneyDealType = (int)$changeMoneyDealType;
            $bizToken = ['orderId' => $user_carry_dao->id];
            $flag = $user->changeMoney($money+$fee, $message, $note, 0, 0, 1, 0, $bizToken);
            if (!$flag) {
                throw new \Exception("修改用户账户金额失败", 999);
            }

            $GLOBALS['db']->commit();
            $msg = $uid."(".$money.")提现申请发起成功";
            PaymentApi::log("PaymentService.cashOut:".$msg, Logger::INFO);
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $msg = $uid."(".$money.")提现申请发起失败";
            logger::wLog($msg);
            PaymentApi::log("PaymentService.cashOut:".$msg, Logger::ERR);
            \libs\utils\Alarm::push('payment', "PaymentService.cashOut.FAIL:", $msg);
            \libs\utils\Monitor::add('WITHDRAW_APPLY_FAILED');
            return false;
        }

        try {
            if (!$user_carry_dao->id) {
                throw new \Exception('错误的提现id');
            }
            $data = array();
            $userCarryData = UserCarryModel::instance()->find($user_carry_dao->id);
            $data['update_time_step1'] = $data['update_time_step2'] = $data['update_time'] = get_gmtime();

            $GLOBALS['db']->startTrans();
            try {
                $data['status'] = 3;
                $data['desc'] = $userCarryData['desc'] . '延时处理提现<p>运营：自动审批</p><p>财务：自动审批</p>';
                // 更新数据，考虑并发，增加乐观锁
                $upResult = $GLOBALS['db']->autoExecute('firstp2p_user_carry', $data, 'UPDATE', ' id  = ' . $user_carry_dao->id . ' AND status = 0');
                if ($upResult === false) {
                    throw new \Exception('自动提现更新失败');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $data['status'] = 1;
                $data['desc'] = '自动提现异常，转财务复核';
                unset($data['update_time_step2']);
                $upResult = $GLOBALS['db']->autoExecute('firstp2p_user_carry', $data, 'UPDATE', ' id  = ' . $user_carry_dao->id . ' AND status = 0');
                if ($upResult === false) {
                    throw new \Exception('<p>' . $e->getMessage() . '</p><p>自动审批错误后，提现更新失败</p>');
                }
                throw $e;
            }
        } catch (\Exception $e) {
            // send email warning
            \libs\utils\Alarm::push('payment', "paymentAutoAudit", $e->getMessage() . "\t" . $user_carry_dao->id);
        }

        // 站内信和短信发送, 发送失败记录日志
        try
        {
            // 获取提现时效配置
            $apiConfObj = new \core\service\ApiConfService();
            $withdrawTimeConf = $apiConfObj->getWithdrawTime();

            $content = "您于".to_date($create_time,"Y年m月d日 H:i:s")."提交的".format_price($money)."提现申请我们正在处理，如您填写的账户信息正确无误，您的资金将会于{$withdrawTimeConf}个工作日内到达您的银行账户.";
            $contMoney = format_price($money);
            // 站内信通知
            send_user_msg("",$content,0,$uid, get_gmtime(),0,true,5);
            //短信通知
            if(app_conf("SMS_WITHDRAW_ON") == 1){
                $params = array(
                    'account_title' => '',
                    'time' => to_date($create_time, 'm-d H:i'),
                    'money' => $contMoney,
                );
                SmsServer::instance()->send($user['mobile'], 'TPL_SMS_ACCOUNT_CASHOUT_NEW', $params, $user['id']);
            }
        }
        catch (\Exception $e)
        {
            // keep silent
            \libs\utils\PaymentApi::log('cashOut#'.$user_carry_dao->id.' send sms failed, msg:'.json_encode(func_get_args()).' exception msg:'.$e->getMessage());
        }
        return $user_carry_dao->id;
    }

    /**
     * bindBankcard
     * 使用用户充值银行卡绑定为用户的默认提现银行卡，只有在用户从未绑定过的情况下做该处理
     *
     * @param mixed $uid
     * @param mixed $dealId
     * @param mixed $request
     * @access public
     * @return void
     */
    public function bindBankcard($uid, $dealId, $request, $recover = false)
    {
        //安全卡才绑定银行卡
        if (isset($request['bankCardType']) && $request['bankCardType'] != UserBankcardService::CARD_TYPE_SAFE) {
            return true;
        }

        $userService = new UserService();
        $GLOBALS['user_info'] = $userService->getUser($uid);

        $bankcardBind = UserBankcardModel::instance()->getCardByUser($uid);
        // 对于已经绑定过的不处理，依然存在用户修改与系统调用并发的情况
        if (!empty($bankcardBind) && !empty($bankcardBind['bankcard']) && !$recover) {
            if (isset($bankcardBind['verify_status']) && $bankcardBind['verify_status'] == 0) {
                // 获取支付系统所有银行卡列表-安全卡数据
                $obj = new UserBankcardService();
                $authCardInfo = $obj->queryBankCardsList($uid, true);
                if (isset($authCardInfo['respCode']) && $authCardInfo['respCode'] == '00') {
                    $ucfpayBankcard = $authCardInfo['list'];
                    $_verifyUpdate = [];
                    $_verifyUpdate['verify_status'] = 1;
                    $_verifyUpdate['status'] = 1;
                    $_verifyUpdate['update_time'] = get_gmtime();
                    $_verifyUpdate['cert_status'] = UserBankcardModel::$cert_status_map[$ucfpayBankcard['certStatus']];
                    $updateResult = UserBankcardModel::instance()->updateCard($bankcardBind['id'], $_verifyUpdate);
                    return $updateResult;
                }
                $msg = '该用户已经绑卡';
                logger::wLog($msg);
                PaymentApi::log("PaymentService.bindBankcard.IGNORE:".$msg, Logger::INFO);
                return false;
            }
        }
        if (!empty($request['accountNo'])) {
            $bankcard = $request['accountNo'];
            $bank = $request['bankName'];
            $city = $request['city'];
            $msg = "充值回调银行卡信息。".json_encode($request);
            logger::wLog($msg);
            PaymentApi::log($msg, Logger::INFO);
        } elseif ($dealId > 0) {
            $tryTimes = 3;
            $queryFlag = false;
            do {
                $retData = $this->queryPayBankInfo($uid, $dealId);
                if (isset($retData['status']) && $retData['status'] == '00') {
                    $queryFlag = true;
                    break;
                } else {
                    $msg = "PaymentService.bindBankcard:重试调用API.";
                    logger::wLog($msg);
                    PaymentApi::log($msg, Logger::WARN);
                }
            } while ( --$tryTimes >= 0);
            if ($queryFlag) {
                $bankcard = $retData['cardNo'];
                $bank = $retData['bankName'];
                $city = $retData['city'];
            } else {
                $msg = "查询支付端用户银行卡接口无信息。";
                \libs\utils\Alarm::push('payment', "PaymentService.bindbankcard.query.FAIL:", $msg.json_encode($retData));
            }
        }
        if (empty($bankcard)) {
            return;
        }
        $bankService = new BankService();
        $canBind = $bankService->canBankcardBind($bankcard, $uid);
        if ($canBind === false) {
            $msg = $uid."(".$bankcard.")不可重新绑定！";
            logger::wLog($msg);
            PaymentApi::log("PaymentService.bindBankcard.FAIL:".$msg, Logger::INFO);
            \libs\utils\Alarm::push('payment', "PaymentService.bindbankcard.FAIL:", $msg);
            return false;
        }
        // fix  银行名称无法匹配到银行记录表数据
        if (!empty($request['bankNo'])) {
            $bankInfo = BankModel::instance()->getBankByCode(addslashes(trim($request['bankNo'])));
            // 拼装信息
            $userBankCard['bank_id'] = $bankInfo['id'];
        }
        $userBankCard['bankcard'] = $bankcard;
        $userBankCard['user_id'] = $uid;
        //$userBankCard['card_type'] = $retData['cardType'];

        if (!empty($city)) {
            $regionInfo = DeliveryRegionModel::instance()->getRegionsByCity($city);
            if ($regionInfo) {
                $userBankCard['region_lv1'] = 1;
                $userBankCard['region_lv2'] = $regionInfo['id'];
                $userBankCard['region_lv3'] = $regionInfo['sub_id'];
            }
        }

        $userBankCard['status'] = 1;
        $userBankCard['card_name'] = $GLOBALS['user_info']['real_name'];
        $userBankCard['verify_status'] = 1;

        logger::wLog($userBankCard);
        PaymentApi::log("PaymentService.bindBankcard.SUCCESS:".json_encode($userBankCard), Logger::INFO);
        // 无需返回
        if ($bankcardBind && $recover) {
            $userBankCard['update_time'] = get_gmtime();
            return UserBankcardModel::instance()->updateCard($bankcardBind['id'], $userBankCard);
        } else {
            $userBankCard['create_time'] = get_gmtime();
            return $bankService->saveBank($userBankCard, 1);
        }
    }

    /**
     * queryPayBankInfo
     * 充值成功回调后，查询支付端用户充值银行卡信息
     *
     * @param mixed $uid
     * @param mixed $dealId
     * @access public
     * @return void
     */
    public function queryPayBankInfo($uid, $dealId = 0)
    {
        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $params = array(
            'merchantId' => $merchant,
            'userId' => $uid,
            //'outOrderId' => $dealId, // 已经跟支付童鞋确认，无需传此参数
        );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_PAY_BANKINFO'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retStr = Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retStr, true);

        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'UserService',
            'uid' => $uid,
            'function' => 'queryPayBankInfo',
            'msg' => '调用支付端查询用户充值银行卡信息接口',
            'api' => $api,
            'request' => $params,
            'response' => $retStr,
            'response_decode' => $datas,
        );
        logger::wLog($log);
        if (\libs\utils\Aes::validate($datas)) {
            PaymentApi::log("PaymentService.queryPayBankInfo:".json_encode($log), Logger::INFO);
            // 验证成功
            return $datas;
        } else {
            PaymentApi::log("PaymentService.queryPayBankInfo:".json_encode($log), Logger::WARN);
            return false;
        }
    }

    /*#######2014-08-20 新增功能#########*/
    /**
     * bankcardSync
     * 用户在绑定银行卡的时候同步发送给支付进行银行卡绑定，仅限web用户操作
     * @param integer $userId 用户id
     * @param array $bankcardInfo 绑定银行卡信息
     * @throws \Exception
     * @return bool
     */
    public function bankcardSync($userId, $bankcardInfo = array(), $userBaseInfo = array()) {
        static $counter = 1;
        if (empty($userId) || empty($bankcardInfo)) {
            return false;
        }
        // 增加是否开户检测，并增加自动尝试2次开户
        $haveAccount = false;
        $tryTimes = 3;
        do {
            try {
                $haveAccount = $this->register($userId);
            }
            catch (\Exception $e) {
                $haveAccount == self::REGISTER_FAILURE;
                // 企业用户抛出异常
                if (isset($userBaseInfo['user_type']) && $userBaseInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                    throw new \Exception($e->getMessage(), 999);
                }
            }
            if ($haveAccount == self::REGISTER_HASREGISTER || $haveAccount == self::REGISTER_SUCCESS) {
                break;
            }
        } while ( -- $tryTimes >= 0);

        if ($haveAccount == self::REGISTER_FAILURE) {
            $alarmContent['request'] = $bankcardInfo;
            $alarmContent['response'] = $result;
            $result = "绑定银行卡，自动开户失败:".print_r($alarmContent,1);
            \libs\utils\Alarm::push('payment', "bindbankcard",$result);
            throw new \Exception('绑定银行卡失败,自动尝试为用户开户失败', 999);
        }

        $bankcardInfo['userId'] = $userId;
        $result = PaymentApi::instance()->request("bindbankcard",$bankcardInfo);
        if($result['respCode'] == '00'&&($result['status'] == '00')){
            return true;
        }
        else if ($result['respCode'] == '00' && $result['status'] == '25') {
            $bankcardInfo['businessType'] = 2;
            if ($counter ++ >= 3) {
                return false;
            }
            return $this->bankcardSync($userId, $bankcardInfo);
        }
        else{
           $alarmContent['request'] = $bankcardInfo;
           $alarmContent['response'] = $result;
           $result = "绑定银行卡失败:".print_r($alarmContent,1);
           \libs\utils\Alarm::push('payment', '同步绑卡失败', $result);
           throw new \Exception('绑定银行卡失败,请确认银行卡信息是否正确', 999);
        }
        return false;
    }

    /**
     * getBankcardInfo
     * 读取银行卡信息，构造银行信息完整信息，仅限web用户操作
     * @param array $data 银行卡信息
     * @param boolean $isNew 是否新是开卡
     * @param integer $group_id 分组
     * @throws \Exception
     * @return array
     */
    public function getBankcardInfo($data, $isNew = false, $group_id = 0, $fromUserId = 0) {
        $bankService = new BankService();
        //$regionService = new RegionService();
        $banklistService = new BanklistService();
        $userBankcardService = new UserBankcardService();
        $deliverRegionService = new DeliveryRegionService();
        // 收集接口数据
        // 如果没有修改银行卡号，则读出银行卡号
        $userId = $GLOBALS['user_info']['id'] ? $GLOBALS['user_info']['id'] : $fromUserId;
        $_userInfo = $GLOBALS['db']->get_slave()->getRow("SELECT mobile,user_type FROM firstp2p_user WHERE id = '{$userId}'");
        if (!isset($data['bankcard'])) {
            $userBankcard = $userBankcardService->getBankcard($userId);
            $bankcard = $userBankcard->bankcard;
            if (!$bankcard) {
                throw new \Exception('查询用户绑定银行卡信息失败');
            }
            $data['bankcard'] = $bankcard;
        }
        // 查询银行信息
        $bankInfo = $bankService->getBank($data['bank_id']);
        if (empty($bankInfo['id'])) {
            throw new \Exception('查询银行信息失败');
        }
        // 查询联行号
        $bankCode = '00000000';
        if (!empty($data['bankzone'])) {
            $bankIssue = $banklistService->getBankIssueByName($data['bankzone']);
            if (!empty($bankIssue)) {
                $bankCode = $bankIssue;
            }
        }
        $bankcardInfo = array();
        $bankcardInfo['cardNo'] = $data['bankcard'];
        $bankcardInfo['bankCode'] = $bankInfo->short_name;
        $bankcardInfo['bankName'] = $bankInfo->name;
        $bankcardInfo['bankCardName'] = !empty($data['card_name']) ? $data['card_name'] : ''; // 银行开户名
        $bankcardInfo['cardType'] = '1';
        // 手机号6开头的为企业用户，走企业用户绑卡接口
        $userMobile = $_userInfo['mobile'];
        if ($userMobile{0} == '6' || (isset($_userInfo['user_type']) && $_userInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE)){
            $bankcardInfo['userType'] = '2';
        }
        else {
            $bankcardInfo['userType'] = '1';
        }
        $provinceName = '';
        if (!empty($data['region_lv2'])) {
            $provinceRegion = $deliverRegionService->getRegion($data['region_lv2']);
            if (!empty($provinceRegion['name'])) {
                $provinceName = $provinceRegion->name;
            }
        }
        $bankcardInfo['province'] = $provinceName;
        $cityName = '';
        if (!empty($data['region_lv3'])) {
            $cityRegion = $deliverRegionService->getRegion($data['region_lv3']);
            if (!empty($cityRegion['name'])) {
                $cityName = $cityRegion->name;
            }
        }
        $bankcardInfo['city'] = $cityName;
        $bankcardInfo['branchBankId'] = $bankCode;
        $bankcardInfo['branchBankName'] = $data['bankzone'] ? $data['bankzone'] : '';
        if ($isNew) {
            $bankcardInfo['businessType'] = 1;
        }
        else {
            $bankcardInfo['businessType'] = 2;
        }
        return $bankcardInfo;
    }

    /**
     * 查询小额转账记录
     */
    private function _queryTransferRecordInfo($orderId)
    {
        $params = array(
            'merchantId' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'],
            'merchantNo' => $orderId,
        );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string.'&key='.$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_BANKCARD_QUERY_BY_ORDERID'];
        $aesData = \libs\utils\Aes::encode($query_string.'&sign='.$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retSrc = Curl::post($api, array('data' => $aesData));
        $ret = json_decode($retSrc, true);
        $result = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));

        PaymentApi::log("QueryTransferRecordInfo. url:{$api}, params:{$query_string}, result:{$result}");

        parse_str($result, $datas);
        return $datas;
    }

    /**
     * bankcardBindCallback
     *
     * @param array $args = array('merchantId' => , 'userId' => , 'merchantNo' => , money => , bankId =>, bankName=>, accountNo => ,)
     * @param boolean $bindCard 是否需要更新user bankcard
     * @return array
     */
    public function bankcardBindCallback($args)
    {
        //验证用户
        $userInfo = UserModel::instance()->find($args['userId']);
        $bankcardBind = UserBankcardModel::instance()->getCardByUser($args['userId']);
        if (empty($userInfo) || $userInfo['is_delete'] != 0 || $userInfo['is_effect'] != 1) {
            return array('status' => -301, 'msg' => '无效的用户');
        }

        $merchantNo = isset($args['merchantNo']) ? trim($args['merchantNo']) : '';
        if (empty($merchantNo)) {
            return array('status' => -1, 'msg' => 'merchantNo参数无效');
        }

        //查询小额转账记录
        $datas = $this->_queryTransferRecordInfo($merchantNo);
        if (!\libs\utils\Aes::validate($datas)) {
            return array('status' => -1, 'msg' => '小额转账查询签名验证失败');
        }

        if ($datas['respCode'] != '0000' || $datas['status'] != '0000' || $datas['transferStatus'] != '00') {
            return array('status' => -1, 'msg' => '小额打款查询失败');
        }

        $orderSn = isset($datas['merchantNo']) ? trim($datas['merchantNo']) : '';
        $userId = isset($datas['userId']) ? intval($datas['userId']) : 0;
        $money = isset($datas['amount']) ? bcdiv($datas['amount'], 100, 2) : 0;
        if (empty($orderSn) || empty($userId) || empty($money)) {
            return array('status' => -1, 'msg' => '小额打款查询结果有误');
        }
        //绑卡
        try {
            $GLOBALS['db']->startTrans();

            if (!$this->bindBankcard($userId, 0, $datas, true)) {
                throw new \Exception('绑卡失败');
            }
            //是否已有充值记录
            $paymentNotice = PaymentNoticeModel::instance()->getInfoByNoticeSn($orderSn);
            if (empty($paymentNotice)) {
                //创建充值单 并更新用户余额
                $chargeService = new ChargeService();
                $noticeId = $chargeService->createOrder($userId, $money, PaymentNoticeModel::PLATFORM_WEB, $orderSn);
                $paymentNotice = PaymentNoticeModel::instance()->getInfoByNoticeSn($orderSn);
                $res = $chargeService->paidSuccess($paymentNotice);
                if (!$res) {
                    throw new \Exception('修改支付订单状态失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log('bankcardBindCallbackFailed. msg:'.$e->getMessage());
            \libs\utils\Alarm::push('payment', '小额打款回调失败', $e->getMessage());
            return array('status' => -200, 'msg' => $e->getMessage());
        }

        //新绑卡用户触发红包
        if (empty($bankcardBind)) {
            $event = new \core\event\BonusEvent('bindCard', $userId, $userInfo['invite_code']);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if (!$task_id) {
                Logger::wLog('绑卡添加返利失败|' .$userId. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            }
            $bonusService = new BonusService();
            $bonusService->transCashBonus($args['userId']);
        }

        return array('status' => 0, 'msg' => '小额打款回调成功');
    }

    /**
     * BindBankcardOnlyCallback
     * 小额转账绑卡回调
     *
     * @param array $args
     * @param mixed $platform
     * @access public
     * @return void
     */
    public function BindBankcardOnlyCallback($args = array())
    {
        // 记录日志文件
        $logCallBack = array(
            'type' => 'payment',
            'function' => 'BindBankcardOnlyCallback',
            'params' => $args,
            'msg' => '支付小额转账绑卡回调',
            'time' => time(),
        );
        logger::wLog($logCallBack);

        $userInfo = UserModel::instance()->find($args['userId']);
        $bankcardBind = UserBankcardModel::instance()->getCardByUser($args['userId']);
        try {
            $GLOBALS['db']->startTrans();
            // 绑卡
            $result = $this->bindBankcard($args['userId'], 0, $args, true);
            if (!$result) {
                throw new \Exception('绑卡失败');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log('PaymentService.BindBankcardOnlyCallback. EXCEP:'.json_encode($logCallBack, JSON_UNESCAPED_UNICODE));
            \libs\utils\Alarm::push('payment', '小额转账绑卡回调失败', $e->getMessage().json_encode($args, JSON_UNESCAPED_UNICODE));
            return array('respCode' => '01', 'status' => -200, 'msg' => $e->getMessage());
        }
        if (empty($bankcardBind)) {
            $event = new \core\event\BonusEvent('bindCard', $args['userId'], $userInfo['invite_code']);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if (!$task_id) {
                Logger::wLog('绑卡添加返利失败|' .$args['userId']. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            }

            $bonusService = new BonusService();
            $bonusService->transCashBonus($args['userId']);
        }
        return array('respCode' => '00', 'status' => 0, 'msg' => '小额转账绑卡回调成功');
    }

    /**
     * 线下充值业务回调
     *
     * @param integer $userId 用户id
     * @param string $paymentNoticeSn 充值订单号
     * @param string $orderStatus 充值订单状态
     * @param integer $amount 订单金额
     * @param string $bizType 业务类型
     * @param string $memo 订单备注
     * @return array
     */
    public function offlineChargeResultCallback($userId, $paymentNoticeSn, $orderStatus, $amount, $bizType, $memo = '') {
        if (empty($paymentNoticeSn) || empty($amount)) {
            return array('respCode' => '01', 'respMsg' => '参数错误');
        }
        // 只处理成功的订单 orderStatus = 00的
        if (empty($orderStatus) || $orderStatus != '00') {
            return array('respCode' => '00', 'respMsg' => '订单状态不支持');
        }
        // 等幂支持
        $paymentNotice = PaymentNoticeModel::instance()->getInfoByNoticeSn($paymentNoticeSn);
        if (!empty($paymentNotice) && $paymentNotice['is_paid'] == 1)
        {
            return array('respCode' => '00', 'respMsg' => '订单已经处理');
        }

        // 转换单位为元
        $amount = bcdiv($amount, 100, 2);
        try {
            $GLOBALS['db']->startTrans();
            $service = new ChargeService();
            $platform = PaymentNoticeModel::PLATFORM_OFFLINE;
            // 支付线下充值
            if ($bizType == 'offline') {
                $platform = PaymentNoticeModel::PLATFORM_OFFLINE;
            }
            // 线下POS机刷卡
            else if ($bizType == 'pos') {
                $platform = PaymentNoticeModel::PLATFORM_POS;
            }
            // 工资宝充值
            else if ($bizType == 'salary')
            {
                $platform = PaymentNoticeModel::PLATFORM_SALARY;
            }
            // 第三方投资接口退款接口
            else if ($bizType == 'refund')
            {
                $platform = PaymentNoticeModel::PLATFORM_REFUND;
            }
            // 基金赎回
            else if ($bizType == 'fund_redeem')
            {
                $platform = PaymentNoticeModel::PLATFORM_FUND_REDEEM;
                $service->setExtraData(['memo' => $memo]);
                $service->setUserLogType('基金到账');
                // 是否发短信
                $service->setSmsSwitch(true);
                $_data = explode(',', $memo);
                $fundMoneyLog = [];
                $fundMoneyLog['out_order_id'] = $_data[0];
                $fundMoneyLog['user_id'] = $userId;
                $fundMoneyLog['fund_name'] = $_data[1];
                $fundMoneyLog['money'] = $amount;
                $fundMoneyLog['event'] = FundMoneyLogModel::EVENT_REFUND;
                $fundMoneyLog['event_info'] = '0';
                $fundMoneyLog['status'] = FundMoneyLogModel::STATUS_SUCCESS;
                $fundMoneyLog['update_time'] = get_gmtime();
                $fundMoneyLog['create_time'] = get_gmtime();
                $fundService = new \core\service\FundService();
                $fundService->createMoneyLog($fundMoneyLog);
            }
            // 大额充值通道
            else if ($bizType == 'offline_v2') {
                $service->setSmsSwitch(true);
                $platform = PaymentNoticeModel::PLATFORM_OFFLINE_V2;
            }
            // 网信大额充值-下单模式
            else if ($bizType == 'offline_v3') {
                $platform = PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE;
            }
            $noticeId = $service->createOrder($userId, $amount, $platform, $paymentNoticeSn);
            // 重新查询订单数据
            $paymentNotice = PaymentNoticeModel::instance()->getInfoByNoticeSn($paymentNoticeSn);
            $res = $service->paidSuccess($paymentNotice);
            if (!$res) {
                throw new \Exception('操作失败');
            }

            // 网信账户线下大额充值处理成功，触发O2O请求
            if (in_array($platform, [PaymentNoticeModel::PLATFORM_OFFLINE, PaymentNoticeModel::PLATFORM_OFFLINE_V2, PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE])) {
                $this->chargeTriggerO2O($paymentNotice, 1);
            }
            $this->zhugeCharge($platform, $userId,$amount);
            $GLOBALS['db']->commit();

            //生产用户访问日志
            $device = UserAccessLogService::getPaymentDevice($paymentNotice['platform']);
            $extraInfo = [
                'orderId'       => $paymentNotice['notice_sn'],
                'chargeAmount'  => (int) bcmul($paymentNotice['money'], 100),
                'chargeChannel' => UserAccessLogEnum::CHARGE_CHANNEL_OFFLINE,
            ];
            UserAccessLogService::produceLog($paymentNotice['user_id'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网信大额充值%s元成功', (float)$paymentNotice['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_SUCCESS);

            return array('respCode' => '00', 'respMsg' => '充值成功');
        }
        catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return array('respCode' => '01', 'respMsg' => '操作失败');
        }
        return array('respCode' => '01', 'respMsg' => '回调状态有误');
    }

    public function filterXss($data) {
        if (empty($data['realName'])) {
           throw new \Exception('姓名长度不合法');
        }
        if (empty($data['cardNo'])) {
           throw new \Exception('请填写身份证号');
        }
        $data['realName'] = htmlspecialchars(trim($data['realName']));
        $data['cardNo'] = htmlspecialchars(trim($data['cardNo']));
        if(strlen($data['cardNo']) == 15) {
            throw new \Exception('仅支持二代身份证');
        }
        if (strlen($data['cardNo']) != 18) {
            throw new \Exception('身份证长度不正确');
        }
        return $data;
    }


    /**
     * 投资回调处理
     */
    public function investNotifyCallback($responseData, $merchantId = 1)
    {
        $investModel = new ThirdpartyInvestModel();
        $investModel->db->startTrans();
        try
        {
            $tradeLogModel = new TradeLogModel();

            // 外部订单号
            $outOrderId = addslashes($responseData['outOrderId']);

            // 读取第三方投资记录信息
            $tradeLog = $tradeLogModel->findBy(" outOrderId = '{$outOrderId}' ");
            if (empty($tradeLog))
            {
                throw new \Exception('交易信息不存在');
            }
            // 合作伙伴id(商户ID,需要传给openapi)
            $merchantId = $responseData['merchantId'] = (int)$tradeLog['merchantId'];

            // 修改第三方业务结果
            $thirdpartyInvestData = array();
            $thirdpartyInvestData['orderStatus'] = addslashes($responseData['orderStatus']);
            $thirdpartyInvestData['paymentId'] = addslashes($responseData['paymentId']);
            $thirdpartyInvestData['updateTime'] = strtotime($responseData['finishTime']);
            $thirdpartyInvestData['merchantId'] = $merchantId;

            $conditionString = "outOrderId = '{$outOrderId}' AND merchantId = '{$merchantId}' AND orderStatus IN ('I', 'N')";
            if (!$investModel->updateRecord($thirdpartyInvestData, $conditionString)) {
                //判断是否已经更新成功
                $investData = $investModel->getRecordByMerchantIdOutOrderId($merchantId, $outOrderId, $thirdpartyInvestData['orderStatus']);
                if (!empty($investData))
                {
                    $investModel->db->commit();
                    return true;
                }
                else
                {
                    throw new \Exception('业务订单更新失败');
                }
            }

            // 投资结果，用户资金处理
            $investData = $investModel->getRecordByMerchantIdOutOrderId($merchantId, $outOrderId);
            $user = \core\dao\UserModel::instance()->find($investData['userId']);
            $money = bcdiv($investData['amount'], 100, 2);
            if ($responseData['orderStatus'] === 'F')
            {
                // 投资失败，给用户扣减冻结金额，增加可用余额
                $user->changeMoneyAsyn = true;
                $user->changeMoney(-$money, '投资失败解冻', '投资扣款失败, 单号:'.$investData['outOrderId'], 0, 0, \core\dao\UserModel::TYPE_LOCK_MONEY);
                $user->changeMoneyAsyn = false;
            }
            else if ($responseData['orderStatus'] === 'S')
            {
                // 投资成功回调， 扣减用户冻结
                $user->changeMoneyAsyn = true;
                $user->changeMoney($money, '投资成功', '投资成功 单号:'.$investData['outOrderId'], 0, 0, \core\dao\UserModel::TYPE_DEDUCT_LOCK_MONEY);
                $user->changeMoneyAsyn = false;
            }

            // 修改tradelog结果
            $tradeLogData = array();
            $tradeLogData['orderStatus'] = $thirdpartyInvestData['orderStatus'];
            $tradeLogData['updateTime'] = $thirdpartyInvestData['updateTime'];
            $tradeLogData['merchantId'] = $merchantId;

            if (!$tradeLogModel->updateRecord($tradeLogData, $conditionString))
            {
                throw new \Exception('交易信息更新失败');
            }
            // 加入通知队列通知业务方
            $event = new \core\event\InvestNotifyEvent($responseData);
            $taskService = new PtpTaskClient();
            $taskId = $taskService->register($event, 20);

            if (empty($taskId)) {
                throw new \Exception('交易信息落单失败');
            }

            // 返回支付处理结果
            $investModel->db->commit();
        }
        catch (\Exception $e)
        {
            $investModel->db->rollback();
            PaymentApi::log($e->getMessage(), Logger::ERR);
            throw new \Exception('交易失败.');
        }
        // 立刻通知
        $taskService->notify($taskId, 'domq_invest_notify');
    }

    /**
     * 转账回调处理
     * @param string $jsonOrders json订单列表
     * @return array $list 每个订单的处理结果
     */
    public function transferNotifyCallback($orderCount, $jsonOrders) {
        $order = json_decode($jsonOrders, true);
        if (!is_array($order)) {
            PaymentApi::log('transOrders is not a validate orderList, transOrders is :'.$jsonOrders, Logger::ERR);
            \libs\utils\Alarm::push('payment', 'TransferNotify', 'transOrders is not a validate orderList. params:'.$jsonOrders);
            throw new \Exception('transOrders is not a validate orderList');
        }
        // 统计记录次数
        $processedCounter = 0;
        // 统计处理结果
        $respItems = array();
        foreach ($order as $k => $item) {
            if ($item['orderStatus'] === self::API_RESPONSE_SUCCESS || $item['orderStatus'] === self::API_RESPONSE_FAIL) {
                $processedCounter ++;
                $respItems[] = $this->processTransfer($item);
            }
        }

        if ($orderCount !== $processedCounter) {
            PaymentApi::log('transOrders is not an effective orderList with orderStatus not in [S|F], but '.$item['orderStatus'].' indeed.', Logger::ERR);
            throw new \Exception('transOrders is not an effective orderList with orderStatus not in [S|F], but '.$item['orderStatus'].' indeed');
        }
        return $respItems;
    }

    /**
     * 处理单条转账记录
     */
    public function processTransfer($transOrder) {
        $orderId = isset($transOrder['outOrderId']) ? intval($transOrder['outOrderId']) : 0;
        $_toUpdate = array();
        // 更新转账记录
        $retData = array();
        $retData['outOrderId'] = $transOrder['outOrderId'];
        $retData['orderStatus'] = self::API_RESPONSE_SUCCESS;
        $retData['respMsg'] = '';
        switch($transOrder['orderStatus']) {
            case self::API_RESPONSE_SUCCESS:
                $_toUpdate['status'] = 1;
                $_toUpdate['reason'] = $transOrder['orderMessage'];
                // 失败通知强刷finance_detail_log 状态为失败状态
                $GLOBALS['db']->autoExecute('firstp2p_finance_detail_log', $_toUpdate, 'UPDATE', " id = '{$orderId}'");
                return $retData;
                break;
            case self::API_RESPONSE_FAIL:
                $_toUpdate['status'] = 2;
                $_toUpdate['reason'] = $transOrder['orderMessage'];
                // 失败通知强刷finance_detail_log 状态为失败状态
                $GLOBALS['db']->autoExecute('firstp2p_finance_detail_log', $_toUpdate, 'UPDATE', " id = '{$orderId}'");
                $affRows = $GLOBALS['db']->affected_rows();

                // 更新成功
                if ($affRows == 1) {
                    return $retData;
                }
                else {
                    //更新失败，等幂支持
                    $_testSql = "SELECT status FROM firstp2p_finance_detail_log WHERE id = '{$orderId}'";
                    $_order = $GLOBALS['db']->get_slave()->getOne($_testSql);
                    if ($_order == 2) {
                        return $retData;
                    }
                    else {
                        $retData['orderStatus'] = self::API_RESPONSE_FAIL;
                        $retData['respMsg'] = 'update order#'.$orderId.' status failed.';
                        PaymentApi::log('update order status failed, outOrderId:'.$orderId.', param:'.json_encode($_toUpdate), Logger::ERR);
                        return $retData;
                    }
                }
                break;
        }
    }

    /**
     * 更换银行卡接口- 带支付侧余额校验和验卡状态校验
     * @param array $data 改卡数据变更
     */
    public function changeCard($data) {
        $qdata = $data;
        unset($qdata['bankcardNo']);
        $result = PaymentApi::instance()->requestMobile('mquerycardstatus', $qdata);
        if (!isset($result['changeFlag']) || $result['changeFlag'] != '1') {
            $msg = $result['respMsg'];
            if ($result['authStatus'] == 'I') {
                $msg = '正在认证中';
            }
            throw new \Exception($msg);
        }
        $result = PaymentApi::instance()->requestMobile('mchangecard', $data);
        if (empty($result) || $result['status'] != '00') {
            throw new \Exception('更换银行卡失败,请核对信息后重试');
        }
    }

    /**
     * 支付四要素验卡接口 PC版本
     */
    public function authCard($userId, $formId = 'authCardForm')
    {
        $orderId = md5('AUTHCARD'.microtime(true));

        $params = [
            'userId' => $userId,
            'orderId' => $orderId,
        ];
        $form = PaymentApi::instance()->getGateway()->getForm('authCard', $params);
        return [
            'formString' => $form,
            'formId' => $formId,
        ];
    }

    /**
     * 同步第三方投资数据
     */
    public function investSync($params)
    {
        $response = PaymentApi::instance()->request('invest', $params);
        if (isset($response['status']) && $response['status'] === 'S')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /** 企业会员开户-注册接口
     * @param array $data  企业用户开户参数
     *
     * @return boolean
     */
    public function companyRegister($data)
    {
        $result = PaymentApi::instance()->request('newcompregister', $data);
        if (isset($result['respCode']) && $result['respCode'] == '00')
        {
            if (isset($result['status']) && $result['status'] == '00')
            {
                return true;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return $result;
        }
    }

    /**
     * 企业会员开户-更新接口
     * @param array $data  企业用户开户参数
     *
     * @return boolean
     */
    public function companyUpdate($data)
    {
        $result = PaymentApi::instance()->request('newcompupdate', $data);
        if (isset($result['respCode']) && $result['respCode'] == '00')
        {
            if (isset($result['status']) && $result['status'] == '00')
            {
                return true;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return $result;
        }
    }


    /**
     * 验卡成功回调
     */
    public function bindcardNotifyCallback($data)
    {
        $result = array();
        $result['respCode'] = '00';
        $result['respMsg'] = '';
        $userId = $data['userId'];
        $user = UserModel::instance()->findViaSlave($userId);
        // 更新方式，默认为用户未绑卡，使用INSERT 插入用户绑卡记录
        $updateMode = 'INSERT';
        // 是否已经处理过
        $processed = false;
        // 是否首次绑卡
        $isFirstBind = false;
        $db = \libs\db\Db::getInstance('firstp2p');
        $db->startTrans();
        try
        {
            // 用户充值操作
            $noticeSn = isset($data['outOrderId']) ? addslashes($data['outOrderId']) : false;
            $authAmount = isset($data['authAmount']) ? $data['authAmount'] : 0;
            // todo 调用支付订单查询接口查询订单金额
            if (!empty($noticeSn) && bccomp($authAmount, '0', 2) > 0)
            {
                do
                {
                    // 读取订单，看是否已经支付成功
                    $paymentNoticeRecord = $db->getRow(sprintf("SELECT is_paid FROM firstp2p_payment_notice WHERE notice_sn = '%s'", $noticeSn));
                    if (!empty($paymentNoticeRecord) && $paymentNoticeRecord['is_paid'] == ChargeService::STATUS_SUCCESS)
                    {
                        $processed = true;
                        break;
                    }
                    $chargeService = new ChargeService();
                    $noticeId = $chargeService->createOrder($userId, bcdiv($data['authAmount'], 100, 2), PaymentNoticeModel::PLATFORM_AUTHCARD, $noticeSn);
                    $i = 0;
                    do
                    {
                        $paymentNoticeRecord = $db->getRow("SELECT * FROM firstp2p_payment_notice WHERE id = '{$noticeId}'");
                    } while ($i ++ < 3 && empty($paymentNoticeRecord));
                    if (!empty($paymentNoticeRecord))
                    {
                        if ($paymentNoticeRecord['is_paid'] !== ChargeService::STATUS_SUCCESS)
                        {
                            $upResult = $chargeService->paidSuccess($paymentNoticeRecord);
                            if (!$upResult)
                            {
                                throw new \Exception('验证绑卡充值订单处理失败');
                            }
                        }
                    }
                } while (false);
            }
            //安全卡才更新银行卡
            if (isset($data['bankCardType']) && intval($data['bankCardType']) != UserBankcardService::CARD_TYPE_SAFE) {
                $processed = true;
            }
            // 如果充值订单已经被处理，这里不处理银行卡更新数据
            if ($processed !== true)
            {
                // 检查用户银行卡是否存在
                $qstr = sprintf(" user_id = '%d'", $userId);
                $i = 0;
                do
                {
                    if ($i > 0)
                    {
                        usleep('500');
                    }
                    $bankcardModel = UserBankcardModel::instance()->findByViaSlave($qstr);
                } while ($i++ <3 && empty($bankcardModel));
                if (!empty($bankcardModel))
                {
                    $updateMode = 'UPDATE';
                }
                // 更新用户银行卡数据
                $dataUpdate = array();
                $dataUpdate['bankcard'] = $data['cardNo'];
                // 更新的时候，同时更新用户的真实开户名
                $dataUpdate['card_name'] = $user['real_name'];
                $dataUpdate['verify_status'] = UserBankcardModel::VERIFY_STATUS_VALIDATED;  //已验卡
                $dataUpdate['status'] = UserBankcardModel::STATUS_BINDED; // 已绑卡
                $dataUpdate['update_time'] = time();
                if ($updateMode == 'INSERT')
                {
                    $dataUpdate['user_id'] = $userId;
                    $dataUpdate['create_time'] = $dataUpdate['update_time'];
                    // 首次绑卡
                    $isFirstBind = true;
                }
                //认证类型
                if (isset($data['certStatus'])) {
                    $dataUpdate['cert_status'] = isset(self::$cert_status_map[$data['certStatus']])
                        ? self::$cert_status_map[$data['certStatus']] : 0;
                }
                // 银行卡相关信息收集
                $qstr = sprintf(" short_name = '%s'", addslashes($data['bankCode']));
                $bankInfo = BankModel::instance()->findByViaSlave($qstr);
                if (!empty($bankInfo))
                {
                    $dataUpdate['bank_id'] = $bankInfo['id'];
                    $dataUpdate['bank_id'] = $bankInfo['id'];
                }
                // 支行信息查询
                if (isset($data['brankId']))
                {
                    $dataUpdate['branch_no'] = $data['brankId'];
                    $qstr = sprintf(" bank_id = '%s' ", addslashes($data['brankId']));
                    $bankBranchInfo = $db->getRow("SELECT * FROM firstp2p_banklist WHERE {$qstr}");
                    if (!empty($bankBranchInfo))
                    {
                        $dataUpdate['bankzone'] = $bankBranchInfo['name'];
                        // 地区信息
                        $dataUpdate['region_lv1'] = 1; //中国
                        $qstr = sprintf(" name = '%s' ", addslashes($bankBranchInfo['province']));
                        $province = DeliveryRegionModel::instance()->findByViaSlave($qstr);
                        if (!empty($province))
                        {
                            $dataUpdate['region_lv2'] = $province['id'];//省
                        }
                        $qstr = sprintf(" name = '%s' ", addslashes($bankBranchInfo['city']));
                        $city = DeliveryRegionModel::instance()->findByViaSlave($qstr);
                        if (!empty($city))
                        {
                            $dataUpdate['region_lv3'] = $city['id']; //市
                        }
                    }
                }
                // 更新数据库
                $updateCondition = $updateMode === 'INSERT' ? '' : sprintf(" id = '%d' ", $bankcardModel['id']);
                $db->autoExecute('firstp2p_user_bankcard', $dataUpdate, $updateMode, $updateCondition);
                $affectRows = $db->affected_rows();
                if ($affectRows <= 0)
                {
                    throw new \Exception('更新银行卡失败');
                }

                //调用存管绑卡
                $supervisionAccountService = new SupervisionAccountService();
                $isSvUser = $supervisionAccountService->isSupervisionUser($userId);
                $svService = new \core\service\SupervisionService();
                if ($isSvUser || $svService->isUpgradeAccount($userId)) {
                    $params = array(
                        'userId' => $userId,
                        'bankCardNo' => $data['cardNo'],
                        'bankName' => $data['bankName'],
                        'cardType' => SupervisionAccountService::CARD_TYPE_DEBIT,//借记卡
                        'bankCode' => $data['bankCode'],
                        'cardFlag' => SupervisionAccountService::CARD_FLAG_PRI,//对私
                        'cardCertType' => $data['certStatus'],
                    );
                    $bindcardResult = $supervisionAccountService->memberCardBind($params);
                    if (empty($bindcardResult) || $bindcardResult['status'] != SupervisionAccountService::RESPONSE_SUCCESS || $bindcardResult['respCode'] != SupervisionAccountService::RESPONSE_CODE_SUCCESS) {
                        throw new \Exception($bindcardResult['respMsg']);
                    }
                }
            }

            $db->commit();
            $result['outOrderId'] = $data['outOrderId'];
            \libs\utils\Monitor::add('USER_BINDCARD_SUCCCESS');

            //生产用户访问日志
            if ($updateMode === 'INSERT') {
                $extraInfo = [
                    'userId' => (int) $userId,
                    'cardNo' => $data['cardNo'],
                    'bankName' => $data['bankName'],
                ];
                UserAccessLogService::produceLog($userId, UserAccessLogEnum::TYPE_BIND_BANK_CARD, '绑定银行卡成功', $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN);
            }
        }
        catch(\Exception $e)
        {
            $db->rollback();
            $result['respCode'] = '01';
            $result['respMsg'] = $e->getMessage();
            \libs\utils\Monitor::add('USER_BINDCARD_FAILED');
            PaymentApi::log('BindCardNotify failed: '.$e->getMessage(), Logger::ERR);
        }

        if ($result['respCode'] === '00' && $isFirstBind === true)
        {
            try
            {
                // O2O落单
                O2OService::triggerO2OOrder($userId, CouponGroupEnum::TRIGGER_FIRST_BINDCARD);
                // 绑卡回调
                new \core\service\DigService('bindBankCard', array(
                    'bankCardNo' => $data['cardNo'],
                    'bankCardSn' => $data['bankCode'],
                    'cardName' => $user['real_name'],
                    'id' => $userId,
                    'mobile' => $user['mobile'],
                    'cn' => $user['invite_code'],
                ));
                (new Zhuge(Zhuge::APP_WEB))->event('绑卡成功_APP&wap&PC', $userId, []);
                (new Zhuge(Zhuge::APP_MOBILE))->event('绑卡成功_APP&wap&PC', $userId, []);
                // 广告联盟
                //$adService = new \core\service\AdunionDealService();
                //$adService->triggerAdRecord($userId, 3);
                // 绑卡返利
                $event = new \core\event\BonusEvent('bindCard', $userId, $user['invite_code']);
                $task_obj = new GTaskService();
                $task_id = $task_obj->doBackground($event, 20);
                if (!$task_id) {
                    Logger::wLog('绑卡添加返利失败|' .$userId. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
                }
                // 红包返利
                $bonusService = new \core\service\BonusService();
                $bonusService->transCashBonus($userId);
            }
            catch (\Exception $e)
            {
                // log
            }
        }
        return $result;
    }

    /**
     * 跳转到本地页面绑再跳转到支付
     */
    public function getBankcardValidateForm($params, $methodPost = false, $targetNew = false, $formId = 'bankcardValidateForm')
    {
        $method = $methodPost === true ? 'post' : 'get';
        $url = '/payment/goValidateCard';
        $target = $targetNew ? "target='blank'" : '';
        $html = "<form action='$url' id='$formId' $target style='display:none;' method='{$method}'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        return $html;

    }


    /**
     * 跳转到绑卡界面再跳转到支付
     */
    public function getBindCardForm($params, $methodPost = false, $targetNew = false, $formId = 'getBindCardForm')
    {
        $method = $methodPost === true ? 'post' : 'get';
        $url = '/payment/goBindCard';
        $target = $targetNew ? "target='blank'" : '';
        $html = "<form action='$url' id='$formId' $target style='display:none;' method='{$method}'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        return $html;

    }

    /**
     * H5 获取四要素验证表单
     * @param array $params 业务参数
     *      integer $userId  网信理财用户ID
     *      string  $returnUrl 网信理财回跳地址
     * @param boolean $methodPost 是否POST请求 默认为true
     * @param boolean $targetNew 是否新窗口打开 默认为true
     * @param string $formId 表单ID 默认为 h5BindCardForm
     *
     * @return array $result 结果数组
     *      string $form 表单html代码
     *      string $formId 表单名称
     */
    public function getH5BindCardForm($params, $methodPost = true, $targetNew = true, $formId = 'unitebankForm')
    {
        $form = \libs\utils\PaymentGatewayApi::instance()->getForm('cardValidate', $params,$formId, false);
        $formData = '';
        $result = ['respCode' => '00', 'respMsg' => ''];
        if (!empty($form)) {
            $formData = [
                'form' => $form,
                'formId' => $formId,
                ];
        } else {
            $result['respCode'] = '01';
        }
        $result['data'] = $formData;
        $result['status'] = 'S';
        return $result;
    }

    /**
     * 根据银行卡号，去先锋支付获取卡Bin信息
     * @param int $cardNo
     */
    public function getCardBinInfoByCardNo($cardNo)
    {
        if (empty($cardNo) || !is_numeric($cardNo)) {
            return array('respCode'=>'01', 'respMsg'=>'cardNo is empty');
        }
        // 去先锋支付获取卡Bin信息
        $params = array('source' => 'getCardBinInfoByCardNo', 'accountNo' => trim($cardNo));
        $result = PaymentApi::instance()->request('searchcardbin', $params);
        if (!empty($result) && $result['respCode'] == '00' && $result['status'] == '00' && !empty($result['bankCode'])) {
            // 匹配P2P银行id
            $bankInfo = $GLOBALS['db']->get_slave()->getRow("SELECT `id`,`name` FROM firstp2p_bank WHERE `short_name` = '{$result['bankCode']}'");
            if (!empty($bankInfo)) {
                return array('respCode'=>'00', 'bankId'=>$bankInfo['id'], 'bankName'=>$bankInfo['name'], 'shortName'=>$result['bankCode'], 'cardNo'=>$result['accountNo']);
            }
            $result['respMsg'] = 'bankCode is not exist';
        }
        return array('respCode'=>'02', 'respMsg'=>(!empty($result['respMsg']) ? $result['respMsg'] : '卡Bin接口请求失败'));
    }

    /**
     * 静默绑卡-把用户ID、真实姓名、银行卡号录入绑卡表
     * @param int $userId
     * @param int $cardNo
     * @return boolean
     */
    public function insertQuietBindCard($userId, $cardNo, $cardType = UserBankcardModel::CARD_TYPE_PERSONAL)
    {
        if (empty($userId) || empty($cardNo) || !is_numeric($cardNo)) {
            return array('respCode'=>'01', 'respMsg'=>'userId|cardNo is empty');
        }
        // 根据银行卡号，去先锋支付获取卡Bin信息
        $cardBinInfo = $this->getCardBinInfoByCardNo($cardNo);
        if ($cardBinInfo['respCode'] !== '00') {
            return array('respCode'=>'02', 'respMsg'=>$cardBinInfo['respMsg']);
        }
        // 获取用户基本信息
        $userInfo = UserModel::instance()->findBy('id=:id','real_name,payment_user_id',array(':id'=>$userId), true);
        if (empty($userInfo) || empty($userInfo['real_name'])) {
            return array('respCode'=>'03', 'respMsg'=>'用户不存在或真实姓名为空');
        }
        $userName = $userInfo['real_name'];
        if(!empty($userInfo['payment_user_id'])) {
            return array('respCode'=>'04', 'respMsg'=>'用户已开户，不能重复绑卡');
        }
        // 检查银行卡是否已存在
        $bankService = new BankService();
        $canBind = $bankService->canBankcardBind($cardNo, $userId);
        if (!$canBind) {
            return array('respCode'=>'05', 'respMsg'=>'银行卡已被占用，请重新输入');
        }
        $userBankCardInfo = array();
        $userBankCardInfo['bank_id'] = intval($cardBinInfo['bankId']);
        $userBankCardInfo['bankcard'] = trim($cardNo); // 银行卡号
        $userBankCardInfo['bankzone'] = ''; // 开户网点
        $userBankCardInfo['user_id'] = $userId;
        $userBankCardInfo['status'] = 0; // 绑卡状态
        $userBankCardInfo['verify_status'] = 0; // 四要素验证状态
        $userBankCardInfo['card_name'] = trim($userName); // 用户真实姓名
        $userBankCardInfo['card_type'] = $cardType; // 银行卡类型
        $userBankCardInfo['region_lv1'] = $userBankCardInfo['region_lv2'] = $userBankCardInfo['region_lv3'] = $userBankCardInfo['region_lv4'] = 0;
        $userBankCardInfo['create_time'] = $userBankCardInfo['update_time'] = get_gmtime();
        $insertCardRet = UserBankcardModel::instance()->insertCard($userBankCardInfo);
        if ($insertCardRet) {
            return array('respCode'=>'00', 'respMsg'=>'SUCCESS');
        }
        return array('respCode'=>'06', 'respMsg'=>'用户绑卡失败，请重试');
    }

    /**
     * 银行卡解绑
     * @param string $userId 用户Id
     * @param string $bankcardNo 银行卡号
     * @return boolean
     */
    public function unbindCard($userId, $bankcardNo, $isBankCardId = false) {
        if(empty($userId) || empty($bankcardNo)) {
            return false;
        }
        $params = [];
        $params['userId'] = $userId;
        if (!$isBankCardId) {
            $params['cardNo'] = $bankcardNo;
        } else {
            $params['bankCardId'] = $bankcardNo;
        }
        $result = PaymentApi::instance()->request('unbindCard', $params);
        // 兼容支付不友好的等幂
        if (isset($result['status']) && ($result['status'] == '00' || $result['respCode'] == '30010')) {
            return true;
        }
        return false;
    }

    /**
     * 更新手机号
     * @param string $userId 用户Id
     * @param string $mobile 手机号
     * @param string $mobileCode 国家编码
     * @return boolean
     */
    public function updateMobile($userId, $mobile, $mobileCode = '') {
        if(empty($userId) || empty($mobile)) {
            return false;
        }
        $params = ['userId' => $userId, 'newPhone' => $mobile];
        !empty($mobileCode) && $params['regionCode'] = $mobileCode;
        $result = PaymentApi::instance()->request('phoneupdate', $params);
        if (empty($result) || (isset($result['respCode']) && $result['respCode'] != '00')
            || (isset($result['status']) && $result['status'] != '00')) {
            $statArr = array('00' => '成功', '01' => '失败', '02' => '参数错误', '04' => '查询用户失败', '14' => '手机号码已被占用', '15' => '修改手机号失败');
            $respArr = array('00' => '服务调用成功', '01' => '服务调用失败');
            $errMsg = isset($statArr[$result['status']]) ? $statArr[$result['status']] : '修改手机号失败';
            \libs\utils\Alarm::push('payment', '修改手机号失败', 'userId:' . $userId . ',respCode:' . $respArr[$result['respCode']] . ',status:' . $errMsg);
            PaymentApi::log(sprintf('%s::%s, 更新用户手机号失败, userId:%d, newMobile:%s, ucfpayResult:%s', __CLASS__, __FUNCTION__, $userId, $mobile, json_encode($result), Logger::INFO));
            return ['ret'=>false, 'msg'=>$errMsg];
        }
        return ['ret'=>true, 'msg'=>'success'];
    }

    /**
     * 一键四要素认证绑卡
     * @param array $requestData 请求参数
     * @param integer $cardType 0 对私 1 对公
     */
    public function onekeyBindcard($requestData ,$cardType = 0) {
        $response = [];
        $userId = intval($requestData['userId']);
        try {
            $response = PaymentApi::instance()->request('quickAuthBindCard', $requestData);
            if (!empty($response) && (isset($response['status']) && $response['status'] == '00')) {
                $db = Db::getInstance('firstp2p', 'slave');
                // $银行id
                $bankcard = [];
                $bankName = '未知';
                if (!empty($response['bankCode'])) {
                    $bankCode = addslashes(trim($response['bankCode']));

                    $bank = $db->getRow("SELECT id,name FROM firstp2p_bank WHERE short_name = '{$bankCode}'");
                    if (!empty($bank)) {
                        $bankcard['bank_id'] = $bank['id'];
                        $bankName = $bank['name'];
                    }
                }

                $bankcard['user_id'] = $userId;
                $bankcard['bankcard'] = $requestData['bankCardNo'];
                $bankcard['card_name'] = $requestData['userName'];
                $bankcard['cert_status'] = UserBankcardModel::$cert_status_map[$response['certType']];
                $bankcard['verify_status'] = 1;
                $bankcard['status'] = 1;
                $bankcard['card_type'] = $cardType;
                $bankcard['create_time'] = get_gmtime();
                $bankcard['update_time'] = get_gmtime();
                $dbMode = 'INSERT';
                $userHasBindcard = $db->getOne("SELECT COUNT(*) FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
                if ($userHasBindcard) {
                    $dbMode = 'UPDATE';
                    unset($bankcard['create_time']);
                }
                $masterDb = Db::getInstance('firstp2p', 'master');
                $masterDb->autoExecute('firstp2p_user_bankcard', $bankcard, $dbMode, " user_id = '{$userId}' ");
                $affectedRows = $masterDb->affected_rows();
                if ($affectedRows <= 0) {
                    throw new \Exception('绑卡更新数据库失败');
                }

                //调用存管绑卡
                $PhService = new PhAccountService();
                $userService = new UserService();
                $userInfo = $userService->getUserByUserId($userId);
                $phRes = $PhService->getUserAccountId($userId, $userInfo['user_purpose']);
                $supervisionAccountService = new SupervisionAccountService();
                $svService = new \core\service\SupervisionService();
                if (!empty($phRes['accountId']) || $svService->isUpgradeAccount($userId)) {
                    $params = array(
                        'userId' => $phRes['accountId'],
                        'bankCardNo' => $requestData['bankCardNo'],
                        'bankName' => $bankName,
                        'cardType' => SupervisionAccountService::CARD_TYPE_DEBIT,//借记卡
                        'bankCode' => $response['bankCode'],
                        'cardFlag' => $cardType == 0 ? SupervisionAccountService::CARD_FLAG_PRI : SupervisionAccountService::CARD_FLAG_PUB,//银行卡类型
                        'cardCertType' => $response['certType'],
                    );
                    $bindcardResult = $supervisionAccountService->memberCardBind($params);
                    if (empty($bindcardResult) || $bindcardResult['status'] != SupervisionAccountService::RESPONSE_SUCCESS || $bindcardResult['respCode'] != SupervisionAccountService::RESPONSE_CODE_SUCCESS) {
                        throw new \Exception($bindcardResult['respMsg']);
                    }
                }
            }
        } catch(\Exception $e) {
            $response['status'] = '01';
            $response['respMsg']= '数据更新失败';
            PaymentApi::log('user_bankcard update failed, uid:'.$userId.' err:'.$e->getMessage());
        }
        return $response;

    }

    /**
     * 设置错误信息
     */
    public function setError($error)
    {
        $this->_error = $error;
    }

    /**
     * 读取错误信息
     */
    public function getLastError()
    {
        return $this->_error ?: '';
    }

    /**
     * 提现时，检查用户是否符合风控延迟提现规则
     * @param int $userId 用户ID
     * @param float $money 提现金额，单位元
     */
    public function isWithdrawLimitedByUserId($userId, $money) {
        //火眼检查
        $user = \core\dao\UserModel::instance()->find($userId, '*', true);
        $extraData = [
            'user_id' => $userId,
            'user_name' => !empty($user['user_name']) ? $user['user_name'] : '',
            'group_id' => !empty($user['group_id']) ? $user['group_id'] : '',
            'amount' => bcmul($money, 100),
            'platform' => UserAccountEnum::PLATFORM_WANGXIN,
            'account_type' => !empty($user['user_purpose']) ? $user['user_purpose'] : '',
        ];
        $checkRet = RiskService::check('WITHDRAW', $extraData);
        if (false === $checkRet) {
            PaymentApi::log(sprintf('%s | %s, userId: %s, money: %s, %s', __CLASS__, __FUNCTION__, $userId, $money, 'risk check false'));
            return true;
        }

        // 检查用户是否有投资记录(如果没有记录，则符合提现延迟规则)
        $hasExist = \core\dao\DealLoadModel::instance()->hasExistByUserId($userId);
        if (true === $hasExist) {
            return false;
        }

        // 检查用户是否有借款记录(如果没有记录，则符合提现延迟规则)
        $hasExist = DealModel::instance()->hasExistByUserId($userId);
        if (true === $hasExist) {
            return false;
        }

        // 检查用户是否有黄金的投资记录(如果没有记录，则符合提现延迟规则)
        $hasExist = (new \core\service\GoldService())->isUserDealLoadByUserId($userId);
        if (true === $hasExist) {
            return false;
        }

        // 检查提现金额（如果单笔提现金额大于等于500元，则符合提现延迟规则）
        if (bccomp($money, UserCarryModel::$withdrawDelayConfig['withdrawMoney'], 2) < 0) {
            // 增加风控拦截
            $userService = new UserService();
            $userInfo = $userService->getUser($userId);
            $userInfo = $userInfo->getRow();
            $ret = RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH)->checkSync($userInfo, ['money' => $money]);
            return $ret;
        }
        // 检查用户是否普通用户(如果是普通用户，则符合提现延迟规则)
        $isEnterpriseUser = (new UserService())->checkEnterpriseUser($userId);
        if (true === $isEnterpriseUser) {
            return false;
        }

        // 检查用户24小时内，是否有现金充值记录(如果有记录，则符合提现延迟规则)
        $payTime = get_gmtime() - (int)UserCarryModel::$withdrawDelayConfig['payTime'];
        $hasExist = PaymentNoticeModel::instance()->hasExistByUserId($userId, $payTime);
        if (false === $hasExist) {
            return false;
        }

        // 检查用户是否有黄金的借款记录(如果没有记录，则符合提现延迟规则)
        $hasExist = (new \core\service\GoldService())->isHavedLoanByuserId($userId);
        if (true === $hasExist) {
            return false;
        }
        return true;
    }

    /**
     * 跳转到四要素认证页面
     * @param integer userId
     */
    public function gotoFactorAuthPage($params) {
        $target = $targetNew ? "target='blank'" : '';
        $formId = 'factorAuthForm';
        $config = PaymentApi::instance()->getGateway()->getConfig('factorAuth');

        $html = "<form action='{$config['url']}' id='$formId' $target style='display:none;' method='post'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        PaymentApi::log('Ucfpay getForm factorAuth, params:'.json_encode($params));
        echo $html;
        echo '正在跳向支付页面....<br/><script>document.forms[0].submit();</script>';
        return true;
    }

    /**
     * H5 获取四要素验证表单
     */
    public function getAuthCardForm($params){
        $formId = 'h5authCardForm';
        $form = PaymentApi::instance()->getGateway()->getForm('h5authCard', $params,$formId, false);
        $formData = '';
        $result = ['respCode' => '00', 'respMsg' => ''];
        if (!empty($form)) {
            $formData = [
                    'form' => $form,
                    'formId' => $formId,
            ];
        } else {
            $result['respCode'] = '01';
        }
        $result['data'] = $formData;
        $result['status'] = 'S';
        return $result;
    }

    /**
     * 线下充值接口
     */
    public function getOfflineChargeForm($params)
    {
        $formId = 'offlineChargeForm';
        $form = PaymentApi::instance()->getGateway()->getForm('offlineCharge', $params, $formId, false);
        $formData = '';
        $result = ['respCode' => '00', 'respMsg' => ''];
        if (!empty($form)) {
            $formData = [
                    'form' => $form,
                    'formId' => $formId,
            ];
        } else {
            $result['respCode'] = '01';
        }
        $result['data'] = $formData;
        $result['status'] = 'S';
        return $result;
    }

    /**
     * 网信大额充值接口-下单模式
     */
    public function getOfflineChargeV3Form($params)
    {
        $formId = 'offlineChargeV3Form';
        $form = PaymentApi::instance()->getGateway()->getForm('offlineChargeV3', $params, $formId, false);
        $formData = '';
        $result = ['respCode' => '00', 'respMsg' => ''];
        if (!empty($form)) {
            $formData = [
                'form' => $form,
                'formId' => $formId,
            ];
        } else {
            $result['respCode'] = '01';
            $result['respMsg'] = '生成Form表单失败';
        }
        $result['data'] = $formData;
        $result['status'] = 'S';
        return $result;
    }

    /**
     * 用户是否在大额充值银行白名单内
     * @param int $userId
     * @param int $chargeSource 充值来源(0:App充值1:PC/Wap充值)
     * @param array $bankcardInfo
     */
    public static function isOfflineBankList($userId, $chargeSource = 0, $bankcardInfo = []) {
        // 获取用户绑卡数据
        $bankcardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
        if (empty($bankcardInfo) || empty($bankcardInfo['bank_id'])) {
            return 0;
        }

        // 获取网信大额充值下单模式-银行白名单列表的配置
        $bankListKey = $chargeSource == 0 ? 'WX_OFFLINECHARGE_BANKLIST' : 'WXPC_OFFLINECHARGE_BANKLIST';
        $bankWhiteListConf = app_conf($bankListKey);
        if (empty($bankWhiteListConf)) {
            return 0;
        }

        $bankWhiteListConf = strtoupper(preg_replace('/\s+/', '', $bankWhiteListConf));
        $wxBankWhiteList = explode('|', $bankWhiteListConf);
        if (empty($wxBankWhiteList)) {
            return 0;
        }

        // 获取该银行的基本信息
        $obj = new BankService();
        $bankInfo = $obj->getBank($bankcardInfo['bank_id']);
        if (empty($bankInfo) || empty($bankInfo['short_name'])) {
            return 0;
        }

        // 配置了该银行简码，则可以大额充值
        return in_array($bankInfo['short_name'], $wxBankWhiteList) ? 1 : 0;
    }

    /**
     * 用户是否可以使用大额充值
     * @param int $userId 用户ID
     * @param string $bankCode 银行简码
     * @param int $chargeSource 充值来源(0:App充值1:PC/Wap充值)
     */
    public static function isBigCharge($userId, $bankCode, $chargeSource = 0) {
        if (empty($userId) || empty($bankCode)) {
            return false;
        }

        // 获取网信大额充值下单模式-银行白名单列表的配置
        $bankListKey = $chargeSource == 0 ? 'WX_OFFLINECHARGE_BANKLIST' : 'WXPC_OFFLINECHARGE_BANKLIST';
        $bankWhiteListConf = app_conf($bankListKey);
        if (empty($bankWhiteListConf)) {
            return false;
        }

        $bankWhiteListConf = strtoupper(preg_replace('/\s+/', '', $bankWhiteListConf));
        $wxBankWhiteList = explode('|', $bankWhiteListConf);
        if (empty($wxBankWhiteList)) {
            return false;
        }

        if ( ! in_array($bankCode, $wxBankWhiteList)) {
            return false;
        }

        // 如果用户在用户黑名单
        $isBlack = self::inBlackList($userId);
        if ($isBlack) {
            return false;
        }

        // 配置了银行简码+不在用户黑名单，则可以大额充值
        return true;
    }

    /**
     * 获取网信下单模式大额充值的提示文案
     * @param int $userId
     */
    public static function getOfflineChargeV3Tips($userId) {
        // 用户是否在大额充值银行白名单内
        $inBankList = self::isOfflineBankList($userId);
        if (!$inBankList) {
            return '已超今日限额，可在电脑上进行网银充值';
        }
        // 如果用户在黑名单
        $inBlackList = self::inBlackList($userId);
        if ($inBlackList) {
            return '已超今日限额，可在电脑上进行网银充值';
        }
        return '已超今日限额，可使用大额充值';
    }

    /**
     * 获取黑名单
     * @param int $userId 用户ID
     * @return boolean
     */
    public static function inBlackList($userId, $key = 'WX_OFFLINECHARGE_USERLIST') {
        return BwlistService::inList($key, $userId);
    }

    /**
     * 获取指定用户的未完成的大额充值订单数量
     * @param integer $userId 用户id
     * @param string $status 要查询的用户大额充值订单状态 : I：处理中，F：失败，S：成功 不传：所有
     * @return integer
     */
    public function getMyOfflineOrderNum($userId, $status = 'I')
    {
        $params = [
            'userId' => intval($userId),
            'orderStatus' => trim($status),
        ];
        $result = PaymentApi::instance()->request('offlineChargeOrderQuery', $params);
        if (empty($result) || $result['respCode'] != '00')
        {
            return 0;
        }
        return intval($result['orderNum']);
    }

    /**
     * 跳转至大额充值历史页面
     * @param array $params 参数清单
     *  integer $userId 用户id
     * @param string $platform  h5,pc
     *
     * @return array
     */
    public function offlineChargeOrderPage($params, $platform = 'PC', $hasTitle = 'Y')
    {
        $platform = $platform == 'h5' ? 'APP' : 'PC';
        $params['reqSource'] = strtoupper($platform);
        $params['hasTitle'] = $hasTitle;
        $params['orderStatus'] = 'I';
        if (isset($params['queryStatus'])) {
            $params['orderStatus'] = $params['queryStatus'];
        }
        $formId = 'historyForm';
        $formString = PaymentApi::instance()->getGateway()->getForm('offlineChargeOrderPage', $params, $formId, false);
        return [
            'respCode'  => '00',
            'status'    => 'S',
            'data' => [
                'formId' => $formId,
                'form' => $formString,
            ],
        ];
    }

    /**
     * 点击充值按钮检查限额
     * @param UserModel $userObject 用户对象
     * @param float $chargeMoney 充值金额 单位元
     * @param int $platform
     */
    public function preCharge($userObject , $accountServ, $chargeMoney, $platform = UserAccountEnum::PLATFORM_WANGXIN) {
        $result = [
            'errno' => 0,
            'error' => '',
            'data' => [
                'isDayLimit' => 0, //是否超出当日限额
                'offChargeTips' => '', //限额弹窗文案
                'channelList' => [], //渠道列表
            ]
        ];

        try {
            if (empty($userObject) || empty($accountServ) || empty($chargeMoney))
            {
                throw new \Exception('参数错误', 1);
            }
            $userBankCardInfo = $userObject->getUserBankCard();
            if (empty($userBankCardInfo)) {
                throw new \Exception('用户暂未绑卡', 1);
            }

            //获取限额数据
            $limitData = $accountServ->getLimitDescByPlatform($userObject->id, $platform, $userBankCardInfo);

            //单日限额判断
            $totalDayChargeAmount = $limitData['totalDayChargeAmount'];
            $totalAmount = bcadd($totalDayChargeAmount, $chargeMoney, 2);
            if ((int)$limitData['dayTotalLimit'] >= 0 && bccomp($totalAmount, $limitData['dayTotalLimit'], 2) === 1) {
                $result['data']['isDayLimit'] = 1;
                $result['data']['offChargeTips'] = $platform == UserAccountEnum::PLATFORM_WANGXIN ? $this->getOfflineChargeV3Tips($userObject->id)
                    : '已超今日限额，可使用大额充值';
                throw new \Exception('已超今日限额', 0);
            }
            //单个充值渠道，单笔限额判断 网贷平台 , 网信单笔充值在 payment/apply接口中判断
            if ($platform == UserAccountEnum::PLATFORM_SUPERVISION
                && (int)$limitData['singleMaxLimit'] >= 0 && bccomp($chargeMoney, $limitData['singleMaxLimit'], 2) === 1) {
                     throw new \Exception('已超单笔充值限额，请重新输入充值金额或选择其他充值方式', 2);
            }

            //只有账户类型是网信 才返回channelList
            if ($result['data']['isDayLimit'] != 1 && $platform == UserAccountEnum::PLATFORM_WANGXIN) {
                $channelParams = [
                    'money' => $chargeMoney,
                    'ucfLimitTips' => '',
                    'ucfLimitToast' => '',
                    'yeeLimitTips' => '',
                    'yeeLimitToast' => '',
                ];
                $limitList = !empty($limitData['list']) ? $limitData['list'] : [];

                foreach ($limitList as $val) {
                    if ($val['pay_channel'] == PaymentNoticeModel::CHARGE_QUICK_CHANNEL) {
                        // 就干掉先锋支付的日累积充值金额
                        $val['dayChargeAmount'] = 0;
                        $channelParams['ucfLimitTips'] = '充值限额：' . $this->formatMoney($val['max_quota']) . '元/笔 '. $this->formatMoney($val['day_quota']) . '元/日';
                        $totalChannelAmount = bcadd($val['dayChargeAmount'], $chargeMoney, 2);
                        if (bccomp($val['day_quota'], 0, 2) >= 0 && bccomp($totalChannelAmount, $val['day_quota'], 2) > 0) {
                            $channelParams['ucfLimitToast'] = $this->getOfflineChargeV3Tips($userObject->id);
                        }
                        // 单笔和单日都无限额
                        if (bccomp($val['max_quota'], 0, 2) < 0 && bccomp($val['day_quota'], 0, 2) < 0)
                        {
                            $channelParams['ucfLimitTips'] = '';
                        } else if (bccomp($val['day_quota'], 0, 2) < 0 && bccomp($val['max_quota'], 0, 2) >= 0) {
                            //日限额无限制, 单笔大于等于0
                            $channelParams['ucfLimitTips'] = '充值限额：' . $this->formatMoney($val['max_quota']) . '元/笔';
                        } else if (bccomp($val['day_quota'], 0, 2) >= 0 && bccomp($val['max_quota'], 0, 2) < 0)
                        {
                            // 日限额有限制, 单笔无限制
                            $channelParams['ucfLimitTips'] = '充值限额：' . $this->formatMoney($val['day_quota']) . '元/笔 '. $this->formatMoney($val['day_quota']) . '元/日';
                        }
                    }
                    if ($val['pay_channel'] == PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL) {
                        $channelParams['yeeLimitTips'] = '充值限额：' . $this->formatMoney($val['day_quota']) . '元/日';
                        $totalChannelAmount = bcadd($val['dayChargeAmount'], $chargeMoney, 2);
                        if (bccomp($val['day_quota'], 0, 2) >= 0 && bccomp($totalChannelAmount, $val['day_quota'], 2) > 0) {
                            $channelParams['yeeLimitToast'] = $this->getOfflineChargeV3Tips($userObject->id);
                        }
                        //无限制
                        if (bccomp($val['day_quota'], 0, 2) < 0) {
                            $channelParams['yeeLimitTips'] = '';
                        }
                    }
                }

                $paymentChannelList = $accountServ->getAvailableChargeChannel($userObject->id, $platform, $channelParams, $userBankCardInfo);
                if (empty($paymentChannelList['ret'])) {
                    throw new \Exception('服务异常，请稍后重试', 3);
                }
                $result['data']['channelList'] = $paymentChannelList['list'];
            }
        } catch (\Exception $e) {
            $result['errno'] = $e->getCode();
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * 银行卡预留手机号修改-生成Url
     * @param array $params
     * @param string $hasTitle 是否显示页面header(Y/N)
     * @param string $platform 平台
     * @return string $url
     */
    public function h5UpdateBankPhone($params, $hasTitle = 'N', $platform = 'h5') {
        if (empty($params['userId'])  || empty($params['bankCardId'])) {
            throw new \Exception('参数不正确');
        }
        $source = $platform == 'h5' ? '1' : '2';
        $params['orderId'] = strval(Idworker::instance()->getId()); //生成订单号
        $params['hasTitle'] = $hasTitle;
        $params['mobileType'] = $source . $this->getOs();
        $params['returnUrl'] = !empty($params['returnUrl']) ? $params['returnUrl'] : '';
        $requestUrl = PaymentApi::instance()->getGateway()->getRequestUrl('h5UpdateBankPhone', $params);
        return $requestUrl;
    }

    /**
     * 大额充值订单处理中详情页面
     */
    public function getLargeOrderInfoUrl($params, $hasTitle = 'N', $platform = 'h5') {
        if (empty($params['userId']) || empty($params['outOrderId'])) {
            throw new \Exception('参数不正确');
        }
        $source = $platform == 'h5' ? '1' : '2';
        $params['hasTitle'] = $hasTitle;
        $params['mobileType'] = $source . $this->getOs();
        $params['returnUrl'] = !empty($params['returnUrl']) ? $params['returnUrl'] : '';
        $params['wxVersion'] = isset($_SERVER['HTTP_VERSION']) ? intval($_SERVER['HTTP_VERSION']) : 0;
        $requestUrl = PaymentApi::instance()->getGateway()->getRequestUrl('getOrderInfo', $params);
        return $requestUrl;
    }

    public function formatMoney($money) {
        if ($money%10000 !== 0) {
            return number_format(bcdiv($money, 1, 0));
        }
        return bcdiv($money, 10000) . '万';
    }

    /**
     * 新的限额显示描述
     * @param float $money 单位元
     * @param string $font
     */
    public function formatMoneyNew($money, $font = '万') {
        if (fmod($money, 10000) == 0) {
            return number_format(floatval(bcdiv($money, 10000, 2))) . $font;
        }
        return number_format(floatval($money), 2);
    }

    /**
     * 获取用户限额信息
     */
    public function getNewChargeLimit($params)
    {
        $result = PaymentApi::instance()->request('bankLimit', $params);
        if (empty($result['respCode']) || (isset($result['respCode']) && $result['respCode'] != '00'))
        {
            return false;
        }
        return $result;
    }

    /**
     * 获取客户端系统
     */
    public function getOs()
    {
        $platform = 0;
        $str = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : $_SERVER['HTTP_USER_AGENT'];
        if (stripos($str, 'ios') !== false) {
            $platform = 1;
        } else if (stripos($str, 'android') !== false) {
            $platform = 2;
        }
        return $platform;
    }
}
