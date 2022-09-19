<?php
/**
 * UserBankcardService class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace core\service;

use core\dao\UserBankcardModel;
use core\dao\BankModel;
use core\dao\UserModel;
use core\dao\UserBankcardAuditModel;
use core\service\SupervisionAccountService;
use core\service\BankService;
use core\service\ChargeService;
use core\service\PaymentService;
use core\service\AccountService;
use core\service\UserService;
use core\service\SupervisionFinanceService;
use core\tmevent\supervision\UcfpayUpdateUserBankCardEvent;
use core\tmevent\supervision\WxUpdateUserBankCardEvent;
use core\tmevent\supervision\SupervisionUpdateUserBankCardEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use libs\utils\PaymentApi;
use libs\sms\SmsServer;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 * undocumented class
 *
 * @packaged default
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class UserBankcardService extends BaseService {

    const CARD_TYPE_SAFE = 0; // 银行卡业务类型-安全卡
    const CARD_TYPE_CHARGE = 1; // 银行卡业务类型-充值卡
    // 银行卡业务类型配置
    public static $bankCardTypeConfig = [
        self::CARD_TYPE_SAFE,
        self::CARD_TYPE_CHARGE,
    ];
    const CARD_BIND_MAX = 5; // 最大绑卡数
    const BANK_CARDID_DEFAULT = 'd79495f96bb74ff85731eecedc6c3c6e'; // 默认的绑卡ID

    /**
     * 获取用户银行卡信息
     *
     * @return BankcardModel
     **/
    public function getBankcard($user_id, $is_slave = true) {
        $user_id = intval($user_id);
        return UserBankcardModel::instance()->getCardByUser($user_id, '*', $is_slave);
    }

    public function getBankcardByUserIdArr($userIdArr, $is_slave = true) {
        $result = array();
        if (!empty($userIdArr)) {
            $res = UserBankcardModel::instance()->getBankcardByUserIdArr($userIdArr, '*', $is_slave);
            if (!empty($res)) {
                foreach ($res as $item) {
                    $result[$item['user_id']] = $item;
                }
            }
        }

        return $result;
    }


    /**
     * 获取速贷用户银行卡数据
     */
    public function getCreditBankInfo($userId)
    {
        $bankcardInfo = $this->getBankcard($userId);
        $bankcardInfo = $bankcardInfo->getRow();
        $bankInfo = (new \core\service\BankService())->getBank($bankcardInfo['bank_id']);
        $cutCardNo = substr($bankcardInfo['bankcard'], strlen($bankcardInfo['bankcard']) - 4, 4);
        $bankcardInfo['bankAndCardFormat'] = "{$bankInfo['name']}({$cutCardNo})";
        return $bankcardInfo;
    }

    /**
     * getMorInfo
     * 获取用户绑定银行卡的银行编码等扩展信息
     *
     * @param mixed $uid
     * @access public
     * @return void
     */
    public function getMorInfo($uid) {
        return UserBankcardModel::instance()->getMorInfoByUser($uid);
    }

    public function updateBankNameByCode($id, $bankCode) {
        $bankInfo = BankModel::instance()->getBankByCode($bankCode);
        if (empty($bankInfo)) {
            return false;
        }
        $data = array('bank_id' => $bankInfo['id']);
        return UserBankcardModel::instance()->updateCard($id, $data);
    }

    /**
     * 网信理财-更新用户银行卡记录
     * @param array $params
     */
    public function wxUpdateUserBankCard($params) {
        $db = \libs\db\Db::getInstance('firstp2p');
        try {
            $db->startTrans();
            if (empty($params)) {
                throw new \Exception('参数不能为空');
            }

            if (empty($params['bank_id']) && empty($params['bankcode']) ) {
                throw new \Exception('银行id和银行编码不能同时为空');
            }

            // 使用bankcode 反查bank_id
            if (!empty($params['bankcode']) && empty($params['bank_id'])) {
                $bankcode = addslashes(trim($params['bankcode']));
                $params['bank_id'] = $db->getOne("SELECT id FROM firstp2p_bank WHERE short_name = '{$bankcode}'");
            }

            $userId = intval($params['id']);
            $userBankcardInfo = array(
                'card_name' => addslashes($params['bank_card_name']), //开户姓名
                'region_lv1' => intval($params['c_region_lv1']),
                'region_lv2' => intval($params['c_region_lv2']),
                'region_lv3' => intval($params['c_region_lv3']),
                'region_lv4' => intval($params['c_region_lv4']),
                'bankzone' => !empty($params['bankzone_1']) ? addslashes($params['bankzone_1']) : addslashes($params['bank_bankzone']),
                'bankcard' => addslashes($params['bank_bankcard']), //处理卡号 只能是数字
                'bank_id' => intval($params['bank_id']),
                'user_id' => $userId,
                'card_type' => (int)$params['card_type'] == 0 ? UserBankcardModel::CARD_TYPE_PERSONAL : UserBankcardModel::CARD_TYPE_BUSINESS, // 银行卡类型
            );
            isset($params['branch_no']) && $userBankcardInfo['branch_no'] = addslashes($params['branch_no']);

            // 构造提交数据
            if (isset($params['bankcard_id']) && $params['bankcard_id'] > 0) {
                $userBankcardInfo['update_time'] = get_gmtime();
                // 检查用户绑卡方式和验证结果
                if (!empty($params['cert_status'])) {
                    $userBankcardInfo['status'] = 1;
                    $userBankcardInfo['verify_status'] = 1;
                    $userBankcardInfo['cert_status'] = intval($params['cert_status']);
                }
                $mode = 'UPDATE';
                $condition = " user_id = '{$userId}' ";
            } else {
                // 后台新增加修改银行卡置为已绑卡已验卡
                $userBankcardInfo['status'] = 1;
                $userBankcardInfo['verify_status'] = 1;
                $userBankcardInfo['create_time'] = get_gmtime();
                $mode = 'INSERT';
                $condition = '';
            }
            // 更新用户绑卡信息
            $db->autoExecute('firstp2p_user_bankcard', $userBankcardInfo, $mode, $condition);
            if ($db->affected_rows() > 0 && $mode === 'INSERT') {
                // 新增银行卡，支出返利
                $couponService = new \core\service\CouponService();
                $couponService->regRebatePay($userId);
            }

            // 添加自动审核换卡记录
            if (!empty($params['auto_audit']) && $db->affected_rows() > 0 && $mode === 'UPDATE') {
                $userBankcardAuditModel = new UserBankcardAuditModel();
                $res = $userBankcardAuditModel->autoAudit($params);
                if (!$res) {
                    throw new \Exception('添加自动审核换卡记录失败');
                }
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            PaymentApi::log(__FUNCTION__ .' throws Exception:'.$e->getMessage());
            return false;
        }
    }

    /**
     * 网信理财-更新用户银行卡开户名
     * @param int $userId
     * @param string $cardName
     */
    public function wxUpdateUserBankCardName($userId, $cardName) {
        $bankcard = $this->getBankcard($userId);
        if (empty($bankcard) || $bankcard['cardName'] == $cardName) {
            return true;
        }
        $mode = 'UPDATE';
        $userBankcardInfo = [];
        $userBankcardInfo['card_name'] = $cardName;
        $userBankcardInfo['update_time'] = get_gmtime();
        $condition = " user_id = '{$userId}' ";
        $db = \libs\db\Db::getInstance('firstp2p');
        return $db->autoExecute('firstp2p_user_bankcard', $userBankcardInfo, $mode, $condition);
    }

    /**
     * 超级账户-更新用户银行卡记录
     * @param array $userBankcardInfo
     * @param boolean $isNew
     * @param int $groupId
     * @throws \Exception
     */
    public function ucfpayUpdateUserBankCard($userBankcardInfo, $bankcardInfo) {
        try {
            $paymentService = new PaymentService();
            // 发送请求
            $paymentService->bankcardSync($userBankcardInfo['user_id'], $bankcardInfo);
            return ['ret'=>true, 'msg'=>'success'];
        } catch (\Exception $e) {
            return ['ret'=>false, 'msg'=>$e->getMessage()];
        }
    }

    /**
     * 自动换卡
     * @param $postData array post数据
     * @param $isFacePassed bool 是否通过了人脸识别，默认false 
     * array (
     *     'cardNo' => '6214850100000000',
     *     'theTime' => '1500000000000',
     *     'status' => 'S',
     *     'userId' => '100000',
     *     'bankName' => '招商银行',
     *     'merchantId' => '10000',
     *     'orderId' => '2ee1e27ff788d4d8b531fff1c0e04700',
     *     'bankCode' => 'CMB',
     * )
     */
    public function autoUpdateUserBankCard($postData, $isFacePassed = false) {
        if (empty($postData)) {
            return ['respCode' => '01', 'respMsg' => '回调参数错误', 'status' => '01'];
        }

        //用户总资产为0才能自动换卡
        $userId = $postData['userId'];

        // 没过人脸
        if (!$isFacePassed) {
            $supervisionAccountService = new SupervisionAccountService();
            $isZero = $supervisionAccountService->isZeroUserAssets($userId);
            if (!$isZero) {
                return ['respCode' => '02', 'respMsg' => '用户总资产不为0', 'status' => '02'];
            }
        }

        //四要输验证成功才能自动换卡
        if (empty($postData['status']) || $postData['status'] != 'S') {
            return ['respCode' => '07', 'respMsg' => '您的银行卡验证失败，请重试或提供其他银行卡再次申请', 'status' => '07'];
        }

        //检查用户是否已经绑定新卡
        $userCardInfo = UserBankcardModel::instance()->getOneCardByUser($userId, false);
        if ($userCardInfo['bankcard'] == $postData['cardNo']) {
            return ['respCode' => '00', 'respMsg' => '', 'status' => '00'];
        }

        //查询银行卡已绑定的信息
        $bankService = new BankService();
        $canBind = $bankService->checkBankCardCanBind($postData['cardNo'], $userId);
        if (!$canBind) {
            return ['respCode' => '03', 'respMsg' => '该银行卡已被其他用户绑定，请重新设置提现银行卡。', 'status' => '03'];
        }

        //银行id
        $bankId = 0;
        $bankList = $bankService->getBankUserByPaymentMethod();
        foreach ($bankList as $bank) {
            if ($bank['short_name'] == $postData['bankCode']) {
                $bankId = $bank['id'];
            }
        }
        if (empty($bankId)) {
            return ['respCode' => '04', 'respMsg' => '无效的银行编码', 'status' => '04'];
        }

        $gtm = new GlobalTransactionManager();
        $gtm->setName('AutoAuditBankInfo');

        $data = [
            'user_id' => $userId,
            'bank_id' => $bankId,
            'bankcard' => $postData['cardNo'],
            'status' => 1,
            'verify_status' => 1,
            'card_name' => $userCardInfo['card_name'],
            'bankzone' => '',
            'region_lv1' => 0,
            'region_lv2' => 0,
            'region_lv3' => 0,
            'region_lv4' => 0,
            'image_id' => 0,
            'update_time' => get_gmtime(),
            'cert_status' => UserBankcardModel::$cert_status_map['FASTPAY_CERT'],
        ];
        $paymentService = new PaymentService();
        $bankcardInfo = $paymentService->getBankcardInfo($data);
        if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
            // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
            $gtm->addEvent(new UcfpayUpdateUserBankCardEvent($data, $bankcardInfo));
        }

        $userbankcardId = \libs\db\Db::getInstance('firstp2p')->getOne("SELECT id FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
        $userBankcardInfo = array(
            'bank_card_name' => $data['card_name'], //开户姓名
            'c_region_lv1' => $data['region_lv1'],
            'c_region_lv2' => $data['region_lv2'],
            'c_region_lv3' => $data['region_lv3'],
            'c_region_lv4' => $data['region_lv4'],
            'bank_bankzone' => $data['bankzone'],
            'bank_bankcard' => addslashes($data['bankcard']), //处理卡号 只能是数字
            'bank_id' => intval($data['bank_id']),
            'short_name' => $bankcardInfo['bankCode'],
            'bank_name' => $bankcardInfo['bankName'],
            'id' => $userId,
            'bankcard_id' => $userbankcardId,
            'cert_status' => UserBankcardModel::$cert_status_map['FASTPAY_CERT'],
            'card_type' => UserBankcardModel::CARD_TYPE_PERSONAL,
            'auto_audit' => 1,
        );
        $gtm->addEvent(new WxUpdateUserBankCardEvent($userBankcardInfo));

        // 用户已在存管账户开户或者是存管预开户用户
        $supervisionAccountObj = new SupervisionAccountService();
        $svService = new \core\service\SupervisionService();
        if ($supervisionAccountObj->isSupervisionUser($userId) || $svService->isUpgradeAccount($userId)) {
            $gtm->addEvent(new SupervisionUpdateUserBankCardEvent($userId, $userBankcardInfo));
        }

        $gtmRet = $gtm->execute();

        if (!$gtmRet) {
            \libs\utils\Alarm::push('payment', 'autoUpdateUserBankCard', "更新银行卡失败, error:" . $gtm->getError());
            return ['respCode' => '06', 'respMsg' => '更新银行卡失败', 'status' => '06'];
        }

        // 清除验卡状态
        $redisKey = 'authcard_result_' . $userId;
        \SiteApp::init()->dataCache->remove($redisKey);

        //生产用户访问日志
        $bank = BankModel::instance()->find($userCardInfo['bank_id']);
        $extraInfo = [
            'cardNo' => $userCardInfo['bankcard'],
            'bankName' => !empty($bank['name']) ? $bank['name'] : '',
        ];
        $newInfo = [
            'cardNo' => $postData['cardNo'],
            'bankName' => $bankcardInfo['bankName'],
        ];
        UserAccessLogService::produceLog($userId, UserAccessLogEnum::TYPE_UPDATE_BANK_CARD, '自动换卡成功', $extraInfo, $newInfo, DeviceEnum::DEVICE_UNKNOWN);

        //发送消息
        $this->sendUpdateMessage($userId);
        return ['respCode' => '00', 'respMsg' => '', 'status' => '00'];
    }

    //发消息给用户
    private function sendUpdateMessage($userId) {
        //给用户发送消息
        $userInfo = UserModel::instance()->find($userId);
        if (empty($userInfo)) {
            return false;
        }

        $tpl = 'TPL_SMS_USER_BIND_BANK_SUCC';
        $params = array(
            'time' => date('m-d H:i'),
        );
        $title = '修改银行卡成功';
        $des = '审核已通过';
        $tmp_email = 'TPL_EMAIL_AUTH_OK';
        $tmp_sms = 'TPL_SMS_AUTH_OK';
        $content = '您的银行卡修改申请' . $des;

        $group_arr = array(0, $userId);
        sort($group_arr);
        $group_arr[] = 1;
        //站内消息
        $msg_data = array();
        $msg_data['title'] = '银行卡修改提示';
        $msg_data['content'] = $content;
        $msg_data['to_user_id'] = $userId;
        $msg_data['create_time'] = get_gmtime();
        $msg_data['type'] = 0;
        $msg_data['group_key'] = implode("_", $group_arr);
        $msg_data['is_notice'] = 1; //已经通过的 属于系统 消息

        //系统消息
        $msgBoxService = new MsgBoxService();
        $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'], $msg_data['title'], $msg_data['content']);

        try {
            //短信通知
            if (app_conf("SMS_ON") == 1) {
                // SMSSend 银行卡审核结果短信
                SmsServer::instance()->send($userInfo['mobile'], $tpl, $params, $userInfo['id']);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取用户绑卡信息
     * 包含银行LOGO等
     */
    public function getUserBankInfo($userId) {
        $bankcardInfo = $this->getBankcard($userId);
        $bankcardInfo['hideCard'] = formatBankcard($bankcardInfo['bankcard']);
        $bankcardInfo['bankName'] = $bankcardInfo['bankLogo'] = null;
        if ($bankcardInfo['bank_id']) {
            $bankService = new BankService();
            $bankInfo = $bankService->getBankInfo($bankcardInfo['bank_id']);
            $bankcardInfo['bankName'] = $bankInfo['name'];
            $bankcardInfo['bankLogo'] = $bankInfo['logo'];
            $bankcardInfo['shortName'] = $bankInfo['short_name'];
        }
        return $bankcardInfo;
    }

    /**
     * [根据ids批量获取用户银行信息]
     * @param array $userIds 用户ID数组
     * @param string $fields 字段名
     * @return array
     */
    public function getBankListByUserIds($userIds, $fields = '*') {
        $listOfBank = array();
        if (!is_array($userIds) || empty($userIds)) {
            return $listOfBank;
        }

        // 批量获取用户银行卡信息
        $userBankList = UserBankcardModel::instance()->getBankListByUserIds($userIds, $fields);
        foreach ($userBankList as $userBankInfo) {
            $userId = $userBankInfo['user_id'];
            $listOfBank[$userId] = $userBankInfo;
        }
        return $listOfBank;
    }

    /**
     * 获取支付系统所有银行卡列表
     * @param integer $userId 用户ID
     * @param boolean $getSafeCard 是否获取安全卡
     * @param string $bankCardId 银行绑卡ID
     * @param array $userInfo 用户基本信息
     * @return array
     */
    public function queryBankCardsList($userId, $getSafeCard = false, $bankCardId = '', $userInfo = []) {
        $data = ['respCode'=>'00', 'respMsg'=>'', 'list'=>[]];
        if (empty($userId)) {
            $data['respCode'] = PaymentService::CHARGE_FAILURE;
            $data['respMsg'] = 'userId参数 缺失';
            return $data;
        }

        // 调用先锋支付接口获取银行卡列表
        $result = PaymentApi::instance()->request('searchbankcards', ['userId'=>(int)$userId]);
        if (empty($result) || $result['status'] != '00') {
            $data['respCode'] = PaymentService::CHARGE_PENDING;
            $data['respMsg'] = !empty($result['respMsg']) ? $result['respMsg'] : '查询银行卡失败';

            // 获取指定绑卡ID时，不做特殊处理
            if (!empty($bankCardId)) {
                return $data;
            }

            // 当获取支付绑卡列表报错时，从本地数据表获取并组装银行卡列表
            if (empty($userInfo)) {
                // 获取用户基本信息
                $userInfo = UserModel::instance()->find($userId);
            }
            // 获取用户绑卡表记录
            $bankCardInfo = $this->getUserBankInfo($userId);
            $data['list'][] = [
                'cardNo'=>$bankCardInfo['bankcard'], 'cardType'=>1, 'bankCode'=>$bankCardInfo['shortName'],
                'bankName'=>$bankCardInfo['bankName'], 'status'=>PaymentService::API_RESPONSE_SUCCESS, 'certStatus'=>'FASTPAY_CERT',
                'bankCardId'=>self::BANK_CARDID_DEFAULT, 'bankCardType'=>self::CARD_TYPE_SAFE, 'phone'=>$userInfo['mobile'],
            ];
            unset($userInfo, $bankCardInfo);
            return $data;
        }

        // 银行卡列表为空
        if (count($result['bankCards']) <= 0) {
            return $data;
        }

        // 返回所有的银行卡列表
        if ( ! $getSafeCard && empty($bankCardId)) {
            $data['list'] = $result['bankCards'];
            return $data;
        }

        // 找出所有的银行卡列表中的安全卡
        foreach ($result['bankCards'] as $bankData) {
            // 找出银行卡列表中的安全卡数据
            if ($getSafeCard && $bankData['bankCardType'] == self::CARD_TYPE_SAFE) {
                $data['list'] = $bankData;
                break;
            }
            // 找出指定银行卡绑卡ID的银行卡数据
            if (!empty($bankCardId) && $bankData['bankCardId'] == $bankCardId) {
                $data['list'] = $bankData;
                break;
            }
        }
        return $data;
    }

    /**
     * 获取支付系统所有银行卡列表以及限额
     * @param integer $userId 用户ID
     * @return array
     */
    public function queryCardListLimits($userId) {
        $data = ['respCode'=>'00', 'respMsg'=>'', 'url'=>'', 'max'=>self::CARD_BIND_MAX, 'canBindMultiCard' => 0, 'help_url' => '', 'list'=>[]];
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        $data['help_url'] = $http . $_SERVER['HTTP_HOST'] . '/common/advH5?id=WX_CHARGE_HELP&title=' . urlencode('帮助');

        // 获取用户基本信息
        $userInfo = UserModel::instance()->find($userId);

        // 获取支付系统所有银行卡列表
        $userCardList = $this->queryBankCardsList($userId, false, '', $userInfo);
        if ($userCardList['respCode'] == PaymentService::CHARGE_FAILURE || empty($userCardList['list'])) {
            $data['respCode'] = '01';
            $data['respMsg'] = sprintf('用户ID[%d]的银行卡列表为空', $userId);
            return $data;
        }

        // 能否绑定多张银行卡
        $accountService = new AccountService();
        $userService = new UserService();
        $isMainlandRealAuthUser = $accountService->isMainlandRealAuthUser($userInfo);
        $isEnterpriseUser = $userService->checkEnterpriseUser($userId);
        $inMultiCardWhite = $accountService->inMultiCardWhite($userInfo);
        //非企业 大陆实名且在白名单里
        if ( !$isEnterpriseUser && $isMainlandRealAuthUser && $inMultiCardWhite ) {
            $data['canBindMultiCard'] = 1;
        }

        // 新协议支付开关是否打开
        $isH5Charge = SupervisionFinanceService::isNewBankLimitOpen();
        $limitType = $isH5Charge ? ChargeService::LIMIT_TYPE_NEWH5 : ChargeService::LIMIT_TYPE_APP;
        // 获取所有的银行卡限额列表
        $chargeObj = new ChargeService();
        $cardLimitList = $chargeObj->queryAllBankLimits($userId, '', $limitType);
        // 获取先锋支付限额失败，则取默认限额
        if (empty($cardLimitList)) {
            // 获取用户绑卡表记录
            $bankCardInfo = $this->getUserBankInfo($userId);
            $cardLimitList[] = [
                'bankCode'=>$bankCardInfo['shortName'], 'bankCardId'=>self::BANK_CARDID_DEFAULT, 'is_valid'=>ChargeService::CHANNEL_AVALIABLE,
                'singlelimit'=>ChargeService::LIMIT_NONE_LIMIT, 'daylimit'=>ChargeService::LIMIT_NONE_LIMIT, 'lowlimit'=>0,
                'limit_desc'=>'', 'singlelimit_desc'=>'', 'daylimit_desc'=>'', 'lowlimit_desc'=>'',
            ];
        }
        $bankObj = new BankService();
        $paymentObj = new PaymentService();
        if (count($userCardList['list']) < self::CARD_BIND_MAX) {
            // 获取移动端绑卡界面链接
            $bindCardParams = ['userId'=>$userId, 'reqSource'=>1, 'bankCardType'=>self::CARD_TYPE_CHARGE];
            $paymentUserAccountObj = new PaymentUserAccountService();
            $data['url'] = $paymentUserAccountObj->h5AuthBindCard($bindCardParams);
        }

        foreach ($userCardList['list'] as $cardItem) {
            if ( ! in_array($cardItem['bankCardType'], self::$bankCardTypeConfig)) {
                continue;
            }

            foreach ($cardLimitList as $limitItem) {
                if ($cardItem['bankCardId'] == $limitItem['bankCardId']) {
                    // 获取该银行新版的Icon
                    $bankIconInfo = $bankObj->getBankNewIconUrl($cardItem['bankCode'], 'icon_id,mask2x,mask3x');
                    // 整理修改银行预留手机号的参数
                    $phoneParams = ['userId'=>$userId, 'bankCardId'=>$cardItem['bankCardId'], 'returnUrl'=>'firstp2p://api?type=closeall'];
                    $mobileStr = '';
                    if (!empty($cardItem['phone'])) {
                        $mobileStr = '预留手机号：' . format_mobile($cardItem['phone']) . ' 修改 >';
                    }
                    $data['list'][] = [
                        'bankCardId' => isset($cardItem['bankCardId']) ? $cardItem['bankCardId'] : '',
                        'name' => $cardItem['bankName'],
                        'code' => $cardItem['bankCode'],
                        'cardno' => bankNoFormat($cardItem['cardNo'], 4, 4),
                        'cardlast' => substr($cardItem['cardNo'], -4), // 卡号后4位
                        'cardtype' => isset($cardItem['bankCardType']) ? (int)$cardItem['bankCardType'] : self::CARD_TYPE_SAFE,
                        'icon' => !empty($bankIconInfo['icon_id']) ? $bankIconInfo['icon_id'] : '',
                        'mask2x' => !empty($bankIconInfo['mask2x']) ? $bankIconInfo['mask2x'] : '', // 水印图2倍
                        'mask3x' => !empty($bankIconInfo['mask3x']) ? $bankIconInfo['mask3x'] : '', // 水印图3倍
                        'mobile' => isset($cardItem['phone']) ? format_mobile($cardItem['phone']) : '',
                        'mobile_str' => $mobileStr,
                        'singlelimit' => $limitItem['singlelimit'],
                        'daylimit' => $limitItem['daylimit'],
                        'singlelimit_desc' => $limitItem['singlelimit_desc'],
                        'daylimit_desc' => $limitItem['daylimit_desc'],
                        'lowlimit' => isset($limitItem['lowlimit']) ? $limitItem['lowlimit'] : 0,
                        'lowlimit_desc' => isset($limitItem['lowlimit_desc']) ? $limitItem['lowlimit_desc'] : '',
                        'edit_url' => $paymentObj->h5UpdateBankPhone($phoneParams),
                    ];
                    unset($bankIconInfo);
                }
            }
        }
        return $data;
    }
} // END class
