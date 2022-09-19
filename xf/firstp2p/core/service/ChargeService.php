<?php
/**
 * 充值Service
 */
namespace core\service;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\PaymentNoticeModel;
use core\service\ncfph\SupervisionService as PhSupervisionService;
use core\service\QrCodeService;
use core\service\PaymentService;
use core\service\PaymentUserAccountService;
use core\service\SupervisionFinanceService;

require_once APP_ROOT_PATH.'system/libs/user.php';

class ChargeService extends BaseService
{
    /**
     *  是否在支付成功处理逻辑中，给用户处理账户余额, 存管坑爹修改
     */
    const PAID_WITHOUT_PROCESS_ACCOUNT = false;

    /**
     * 存管账户划转至网信账户时需要处理用户余额
     */
    const PAID_WITH_PROCESS_ACCOUNT = true;

    //数据库成功状态
    const STATUS_SUCCESS = 1;

    //数据库处理中状态
    const STATUS_INHAND = 2;

    //数据库失败状态
    const STATUS_FAILED = 3;

    // APP_TO_PC_CHARGE 跳转到未完成支付地址
    const APP_TO_PC_CHARGE_URI = '/payment/PayIng';

    // 跳转到PC端充值聚合页
    const PC_TOGETHER_CHARGE_URI = '/account/charge';

    // APP_TO_PC_CHARGE 缓存key名称
    const HAS_APP_TO_PC_CHARGE_ORDER = 'APPTOPC_USER_CONFIRM_CHARGE_%d';

    // 查询限额配置
    // 代扣
    const LIMIT_TYPE_APP = 1;
    // H5
    const LIMIT_TYPE_H5 = 2;
    // 新协议支付
    const LIMIT_TYPE_NEWH5 = 3;
    // 多渠道协议支付
    const LIMIT_TYPE_MIXED = 4;

    // 不限额就是单笔9999999999 分
    const LIMIT_NONE_LIMIT = 9999999999;

    // 渠道可用状态
    const CHANNEL_AVALIABLE = 1;

    // 渠道不可用状态
    const CHANNEL_NOT_AVALIABLE = 0;

    /**
     * 扩展订单信息，比如备注等
     */
    private $_extraData = [];

    /**
     * 用户资金记录类型
     */
    private $_userLogType = '';
/** * 是否发送短信 */
    private $_smsSwitch = true;

    // 充值方式定义
    const PAYMENT_METHOD_UCFPAY = 'UCFPAY';
    const PAYMENT_METHOD_YEEPAY = 'YEEPAY';
    const PAYMENT_METHOD_BIGPAY = 'BIGPAY';
    const PAYMENT_METHOD_PCPAY = 'PCPAY';
    // 多卡充值-充值方式列表配置
    public static $chargeChannelConfig = [
        ['payment_method'=>self::PAYMENT_METHOD_UCFPAY, 'payment_name'=>'快捷充值-先锋', 'is_valid'=>1, 'link_url'=>'/payment/apply', 'singlelimit'=>0, 'daylimit'=>0, 'limit_desc'=>'', 'lowlimit'=>0, 'icon_type'=>1],
        ['payment_method'=>self::PAYMENT_METHOD_YEEPAY, 'payment_name'=>'快捷充值-易宝', 'is_valid'=>1, 'link_url'=>'/payment/start', 'singlelimit'=>0, 'daylimit'=>0, 'limit_desc'=>'', 'lowlimit'=>0, 'icon_type'=>1],
        ['payment_method'=>self::PAYMENT_METHOD_BIGPAY, 'payment_name'=>'大额充值', 'is_valid'=>1, 'link_url'=>'/payment/offlineChargeOrder?ref=offline', 'singlelimit'=>self::LIMIT_NONE_LIMIT, 'daylimit'=>self::LIMIT_NONE_LIMIT, 'limit_desc'=>'提交充值申请，使用手机银行或网上银行转账', 'lowlimit'=>0, 'icon_type'=>0],
        ['payment_method'=>self::PAYMENT_METHOD_PCPAY, 'payment_name'=>'电脑端网银充值', 'is_valid'=>1, 'link_url'=>'/payment/offlineCharge?ref=quick', 'singlelimit'=>self::LIMIT_NONE_LIMIT, 'daylimit'=>self::LIMIT_NONE_LIMIT, 'limit_desc'=>'扫码登录电脑端进行充值，需使用U盾/Ukey', 'lowlimit'=>0, 'icon_type'=>0],
    ];

    /**
     * 创建充值单
     */
    public function createOrder($userId, $money, $platform = PaymentNoticeModel::PLATFORM_WEB, $orderSn = '', $paymentId = 4, $siteId = 1)
    {
        \libs\utils\Monitor::add('CHARGE_CREATE_ORDER');
        $orderSn = $orderSn === '' ? date('YmdHis').mt_rand(1000000, 9999999) : $orderSn;
        $siteId = isset($siteId) ? intval($siteId) : \libs\utils\Site::getId();
        try {
            $GLOBALS['db']->startTrans();
            $noticeId = $this->_createPaymentNotice($userId, $money, $orderSn, $platform, $paymentId, $siteId);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return 0;
        }

        return $noticeId;
    }

    private function _createPaymentNotice($userId, $money, $orderSn, $platform, $paymentId = 4, $siteId = 1)
    {
        $data = array(
            'create_time' => get_gmtime(),
            'user_id' => $userId,
            'payment_id' => (int)$paymentId,
            'money' => $money,
            'platform' => $platform,
            'notice_sn' => $orderSn,
            'site_id' => $siteId,
        );

        $extraInfo = $this->getExtraData();
        if (!empty($extraInfo))
        {
            $data = array_merge($data, $extraInfo);
        }

        return $GLOBALS['db']->insert('firstp2p_payment_notice', $data);
    }

    /**
     * 支付成功处理 (原payment_paid + order_paid)
     */
    public function paidSuccess($paymentNotice, $outerNoticeSn = '', $amountLimit = 0, $payTime = 0, $payWithProcessAccount = true)
    {
        \libs\utils\Monitor::add('CHARGE_SUCCESS');

        $id = isset($paymentNotice['id']) ? intval($paymentNotice['id']) : 0;
        if ($id === 0) {
            throw new \Exception('参数错误');
        }

        //更新payment_notice表
        if (!$this->updatePaymentNotice($id, $outerNoticeSn, $amountLimit, $payTime)) {
            return false;
        }

        //更新用户余额，产生资金记录
        $user = \core\dao\UserModel::instance()->find($paymentNotice['user_id']);
        $message = sprintf('网信账户充值单%s支付成功', $paymentNotice['notice_sn']);
        $negative = 1;
        // 基金赎回 订单描述
        if ($paymentNotice['platform'] == PaymentNoticeModel::PLATFORM_FUND_REDEEM) {
            $fundInfo = explode(',', $paymentNotice['memo']);
            $suffix = mb_strlen($fundInfo[1], 'UTF-8') > 10 ? '...' : '';
            $fundTitle = mb_substr($fundInfo[1], 0, 10, 'UTF-8').$suffix;
            $message = sprintf('订单：%s，%s', $paymentNotice['notice_sn'], $fundTitle);
        }
        // 大额充值 订单描述
        else if ($paymentNotice['platform'] == PaymentNoticeModel::PLATFORM_OFFLINE_V2) {
            $message = sprintf('账户大额充值单%s支付成功', $paymentNotice['notice_sn']);
        }
        $userLogType = $this->getUserLogType();
        if (empty($userLogType))
        {
            $userLogType = '充值';
        }
        // 是否给用户增加资金
        if (!$payWithProcessAccount) {
            $user->changeMoneyDealType = \core\dao\DealModel::DEAL_TYPE_SUPERVISION;
        }
        $bizToken = ['orderId' => $outerNoticeSn];
        $user->changeMoney($paymentNotice['money'], $userLogType, $message, 0, 0, 0, $negative, $bizToken);
        //生成付款的短信及邮件
        $smsSwitch = $this->getSmsSwitch();
        if ($smsSwitch)
        {
            if ($paymentNotice['platform'] == \core\dao\PaymentNoticeModel::PLATFORM_FUND_REDEEM)
            {
                send_fund_sms($id);
            }
            else
            {
                send_payment_sms($id);
                send_payment_mail($id);
            }
        }

        setLog(array('errno' => 0));
        return true;
    }

    private function updatePaymentNotice($id, $outerNoticeSn, $amountLimit, $payTime = 0)
    {
        $currentTime = get_gmtime();
        $data = array(
            'is_paid' => 1,
            'pay_time' => $payTime <= 0 ? $currentTime : $payTime,
            'outer_notice_sn' => $outerNoticeSn,
            'amount_limit' => $amountLimit,
            'update_time' => $currentTime,
        );

        $GLOBALS['db']->update('firstp2p_payment_notice', $data, "id='{$id}' AND is_paid IN (0, 2)");

        return $GLOBALS['db']->affected_rows() == 1 ? true : false;
    }

    public function setExtraData($data)
    {
        $this->_extraData = $data;
    }

    public function getExtraData()
    {
        return $this->_extraData;
    }

    public function setUserLogType($type)
    {
        $this->_userLogType = $type;
    }

    public function getUserLogType()
    {
        return $this->_userLogType;
    }

    public function setSmsSwitch($boolval = true)
    {
        $this->_smsSwitch = $boolval;
    }

    public function getSmsSwitch()
    {
        return $this->_smsSwitch;
    }

    /**
     * 获取用户最后一次充值
     * @param integer $userId
     * @return mixed
     */
    public function getUserLastCharge($userId) {
        if (empty($userId)) {
            return false;
        }
        $lastChargeLog = PaymentNoticeModel::instance()->db->getRow("SELECT * FROM firstp2p_payment_notice WHERE user_id = '{$userId}' AND is_paid = ".PaymentNoticeModel::IS_PAID_SUCCESS .' ORDER BY id DESC LIMIT 1');
        $lastP2pChargeLog = PhSupervisionService::GetUserLastCharge($userId);
        $chargeLog = [];
        if(!empty($lastChargeLog) && !empty($lastP2pChargeLog) && ($lastChargeLog['pay_time'] + 28800 >= $lastP2pChargeLog['update_time'])) {
            // 订单号
            $chargeLog['order_id'] = $lastChargeLog['payment_notice'];
            // 支付时间
            $chargeLog['pay_time'] = $lastChargeLog['pay_time'] + 28800;
            // 支付时间格式化
            $chargeLog['create_datetime'] = date('Y-m-d H:i:s', $chargeLog['pay_time']);
            // 支付金额 单位分
            $chargeLog['amount'] = bcmul($lastChargeLog['money'], 100);
        }
        else if(!empty($lastChargeLog) && !empty($lastP2pChargeLog) && ($lastChargeLog['pay_time'] + 28800 < $lastP2pChargeLog['update_time'])) {
            // 订单号
            $chargeLog['order_id'] = $lastP2pChargeLog['out_order_id'];
            // 支付时间
            $chargeLog['pay_time'] = $lastP2pChargeLog['update_time'];
            // 支付时间格式化
            $chargeLog['create_datetime'] = date('Y-m-d H:i:s', $chargeLog['pay_time']);
            // 支付金额 单位分
            $chargeLog['amount'] = $lastP2pChargeLog['amount'];
        } else if (!empty($lastChargeLog)) {
            // 订单号
            $chargeLog['order_id'] = $lastChargeLog['payment_notice'];
            // 支付时间
            $chargeLog['pay_time'] = $lastChargeLog['pay_time'] + 28800;
            // 支付时间格式化
            $chargeLog['create_datetime'] = date('Y-m-d H:i:s', $chargeLog['pay_time']);
            // 支付金额 单位分
            $chargeLog['amount'] = bcmul($lastChargeLog['money'], 100);
        } else if (!empty($lastP2pChargeLog)) {
            // 订单号
            $chargeLog['order_id'] = $lastP2pChargeLog['out_order_id'];
            // 支付时间
            $chargeLog['pay_time'] = $lastP2pChargeLog['update_time'];
            // 支付时间格式化
            $chargeLog['create_datetime'] = date('Y-m-d H:i:s', $chargeLog['pay_time']);
            // 支付金额 单位分
            $chargeLog['amount'] = $lastP2pChargeLog['amount'];
        }
        return $chargeLog;
    }

    /**
     * 根据用户id判断用户是否需要跳转至APPTOPC充值确认页面
     * @param integer $userId
     * @param array $chargeInfo
     * @return boolean || string
     */
    public static function SetAppToPcChargeOrder($userId, $chargeInfo)
    {
        if (empty($chargeInfo)) {
            return false;
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis)
        {
            return false;
        }
        $cacheKey = sprintf(self::HAS_APP_TO_PC_CHARGE_ORDER, $userId);
        $ret = $redis->setex($cacheKey, 3600, json_encode($chargeInfo));
        // 记录日志
        PaymentApi::log(sprintf('%s，userId：%d, chargeInfo：%s，生成未完成充值订单成功', __METHOD__, $userId, json_encode($chargeInfo, JSON_UNESCAPED_UNICODE)));
        return $ret;
    }

    /**
     * 获取APPTOPC的充值信息
     * @param integer $userId
     * @return boolean || string
     */
    public static function getAppToPcChargeOrder($userId)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            return false;
        }
        $cacheKey = sprintf(self::HAS_APP_TO_PC_CHARGE_ORDER, $userId);
        $chargeInfo = $redis->get($cacheKey);
        if (empty($chargeInfo)) {
            return false;
        }
        $chargeInfo = json_decode($chargeInfo, true);
        return !empty($chargeInfo) ? $chargeInfo : false;
    }

    /**
     * 清空APPTOPC的充值信息
     * @param integer $userId
     * @return boolean || string
     */
    public static function clearAppToPcChargeOrder($userId)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis)
        {
            return false;
        }
        $cacheKey = sprintf(self::HAS_APP_TO_PC_CHARGE_ORDER, $userId);
        return $redis->del($cacheKey);
    }

    /**
     * 根据用户id判断用户是否需要跳转至APPTOPC充值确认页面
     * @param integer $userId
     * @return boolean || string
     */
    public static function getUserAppToPcChargeUrl($userId)
    {
        $orderInfo = self::getAppToPcChargeOrder($userId);
        if (empty($orderInfo)) {
            return false;
        }
        return self::APP_TO_PC_CHARGE_URI;
    }

    /**
     * 判断用户如果是从大额充值的扫一扫登录的，则跳转到PC充值聚合页面
     * @param integer $userId
     * @return boolean || string
     */
    public static function getUserPcTogetherChargeUrl($userId)
    {
        $qrRefInfo = QrCodeService::getQrRefInfo($userId);
        if (!empty($qrRefInfo) && $qrRefInfo['qrRef'] == QrCodeService::QRREF_OFFLINE) {
            // 销毁扫码来源标识信息
            QrCodeService::clearQrRefInfo($userId);
            return self::PC_TOGETHER_CHARGE_URI;
        }
        return false;
    }


    /**
     * 根据指定的银行卡id获取支付的动态限额数据
     * @param integer $userId 充值用户网信用户id
     * @param string $bankCardId 支付银行卡唯一标示
     * @param integer $limitType 支付业务类型,默认为APP代扣
     * @return array
     */
    public function getLimitRuleByBankCardId($userId, $bankCardId = '', $limitType = self::LIMIT_TYPE_APP) {
        if (empty($bankCardId)) {
            return [];
        }
        $result = $this->queryAllBankLimits($userId, $bankCardId, $limitType);
        if (isset($result) && is_array($result)) {
            foreach ($result as $v) {
                if ($v['bankCardId'] == $bankCardId) {
                    return $v;
                }
            }
        }
        return [];
    }

    /**
     * 读取用户指定的银行卡标识的限额信息
     * @param integer $userId 充值用户网信用户id
     * @param string $bankCardId 支付银行卡唯一标示,逗号分隔
     * @param integer $limitType 支付业务类型,默认为APP代扣
     * @return array 返回结果可能因为
     *      1. 请求超时 时 返回空数组
     *      2. 无限额配置时(支付返回-1时), dailyLimit = 99999999.99, singleLimit 为9999999.99
     *      3. 有限额配置时(singleLimit为非负数并且大于0), dailyLimit = 实际限额配置, singleLimit 为 实际限额配置
     *      4. 有限额配置时(singleLimit = 0), dailyLimit = 0, singleLimit 为0 如果busType = LIMIT_TYPE_MIXED 时, dailyLimit = 99999999.99 singleLimit = 99999999.99
     */
    public function queryAllBankLimits($userId, $bankCardIds = '', $limitType = self::LIMIT_TYPE_APP) {
        $params = ['userId' => $userId, 'bankCardIds' => $bankCardIds, 'busType' => (int)$limitType];
        $result = PaymentApi::instance()->request("queryAllBankLimit", $params);
        // 查询超时或者返回异常,则返回空数据, 外层应该当作不限额处理
        if (empty($result) || $result['respCode'] != '00') {
            return [];
        }

        $ps = new PaymentService();
        if (isset($result['respCode']) && $result['respCode'] == '00' && is_array($result['limitInfos'])) {
            foreach ($result['limitInfos'] as $k => $v) {
                // 设置支付卡是否有效
                $result['limitInfos'][$k]['is_valid'] = self::CHANNEL_AVALIABLE;
                // 单笔限额
                if ($v['singleLimit'] == '-1') {
                    // 单笔无限额处理
                    $result['limitInfos'][$k]['singlelimit'] = self::LIMIT_NONE_LIMIT;
                    $result['limitInfos'][$k]['singlelimit_desc'] = '';
                    $result['limitInfos'][$k]['singlelimit_tips'] = '';
                } else if (bccomp($v['singleLimit'], '0.00', 2) > 0) {
                    // 单笔正常限额处理
                    $limitAmount = bcmul($v['singleLimit'], 100);
                    $result['limitInfos'][$k]['singlelimit'] = (int)$limitAmount;
                    $result['limitInfos'][$k]['singlelimit_desc'] = '该卡本次充值限额：'.$ps->formatMoneyNew($v['singleLimit']).'元';
                    $result['limitInfos'][$k]['singlelimit_tips'] = '本次单笔充值限额为'.$ps->formatMoneyNew($v['singleLimit']).'元';
                } else if (bccomp($v['singleLimit'], '0.00', 2) == 0) {
                    // 单笔限额不可用处理
                    $limitAmount = bcmul($v['singleLimit'], 100);
                    $result['limitInfos'][$k]['singlelimit'] = (int)$limitAmount;
                    $result['limitInfos'][$k]['singlelimit_desc'] = '';
                    $result['limitInfos'][$k]['singlelimit_tips'] = '';
                    $result['limitInfos'][$k]['is_valid'] = self::CHANNEL_NOT_AVALIABLE;
                }

                // 最小限额
                if (!empty($v['lowestLimit'])) {
                    if (bccomp($v['lowestLimit'], '0.00', 2) < 0) {
                        $result['limitInfos'][$k]['lowlimit'] = 0;
                        $result['limitInfos'][$k]['lowlimit_desc'] = '';
                    } else {
                        // 单笔最小限额处理
                        $limitAmount = bcmul($v['lowestLimit'], 100);
                        $result['limitInfos'][$k]['lowlimit'] = (int)$limitAmount;
                        $result['limitInfos'][$k]['lowlimit_desc'] = '单笔最小金额：'.$ps->formatMoneyNew($v['lowestLimit']).'元';
                    }
                }

                // 日限额
                if ($v['dailyLimit'] == '-1') {
                    // 无限额处理
                    $result['limitInfos'][$k]['daylimit'] = self::LIMIT_NONE_LIMIT;
                    $result['limitInfos'][$k]['daylimit_desc'] = '';
                    $result['limitInfos'][$k]['daylimit_tips'] = '';
                } else if (bccomp($v['dailyLimit'], '0.00', 2) > 0) {
                    // 日限额处理
                    $limitAmount = bcmul($v['dailyLimit'], 100);
                    $result['limitInfos'][$k]['daylimit'] = (int)$limitAmount;
                    $result['limitInfos'][$k]['daylimit_desc'] = '今日剩余充值限额：'.$ps->formatMoneyNew($v['dailyLimit']).'元';
                    $result['limitInfos'][$k]['daylimit_tips'] = '今日剩余充值额度为'.$ps->formatMoneyNew($v['dailyLimit']).'元';
                } else if (bccomp($v['dailyLimit'], 0, 2) == 0) {
                    // 日限额用完处理
                    $limitAmount = bcmul($v['dailyLimit'], 100);
                    $result['limitInfos'][$k]['daylimit'] = (int)$limitAmount;
                    $result['limitInfos'][$k]['daylimit_desc'] = '今日充值限额已达上限';
                    $result['limitInfos'][$k]['daylimit_tips'] = '今日充值限额已达上限';
                    $result['limitInfos'][$k]['is_valid'] = self::CHANNEL_NOT_AVALIABLE;
                }
            }
        }
        return $result['limitInfos'];
    }

    /**
     * 获取新协议支付的银行限额列表
     * @param int $userId 用户ID
     * @param array $userCardList 银行卡列表
     */
    public function getNewH5ChargeLimitList($userId, $userCardList) {
        $result = [];
        if (empty($userCardList)) {
            return $result;
        }

        foreach ($userCardList as $item) {
            $tmpInfo = $this->getNewH5ChargeLimitInfo($userId, $item['bankCode']);
            if (empty($tmpInfo)) {
                $tmpInfo = [
                    'is_valid'=>self::CHANNEL_AVALIABLE,'singlelimit'=>self::LIMIT_NONE_LIMIT,
                    'daylimit'=>self::LIMIT_NONE_LIMIT, 'lowlimit'=>0,'limit_desc'=>'', 'singlelimit_desc'=>'',
                    'daylimit_desc'=>'', 'lowlimit_desc'=>'',
                ];
            }

            // 银行卡绑卡ID
            $tmpInfo['bankCardId'] = $item['bankCardId'];
            // 银行简码
            $tmpInfo['bankCode'] = $item['bankCode'];
            $result[] = $tmpInfo;
        }
        return $result;
    }

    /**
     * 获取指定bankCode的易宝充值限额数据
     * @param integer $userId 用户id
     * @param string $bankCode 银行简码
     * @return array
     *      可能的返回值
     *      1. 空数组 该银行卡没有设置易宝限额或者查询限额服务不可用, 此时不限额
     *      2.
     */
    public function getYeepayLimitInfo($userId, $bankCode) {
        $phApiService = new PhSupervisionService();
        $ps = new PaymentService();
        $paymentNoticeMdl = new PaymentNoticeModel();
        $limitInfo = $phApiService->getPhChargeLimitOne('YEEPAY_PAY', $bankCode);
        // 如果为空数组,则返回不限额
        if (empty($limitInfo)) {
            return [];
        }
        // 默认易宝支付渠道对该卡可用
        $limitInfo['is_valid'] = self::CHANNEL_AVALIABLE;

        // 充值单笔最小金额为1分
        if ($limitInfo['min_quota'] >= 0) {
            $limitInfo['lowlimit'] = $limitInfo['min_quota'] ?: 1;
            $lowLimitRen = bcdiv($limitInfo['lowlimit'], 100, 2);
            $limitInfo['lowlimit_desc'] = '单笔最小金额：'.$ps->formatMoneyNew($lowLimitRen).'元';
        }

        // 统计用户今天易宝充值金额(单位元) 以及剩余易宝可用充值日限额额度
        $userTodayChargeAmountRen = $paymentNoticeMdl->sumUserOnlineChargeAmountToday($userId, PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL);
        // 元转成分
        $userTodayChargeAmount = bcmul($userTodayChargeAmountRen, 100);
        // 计算用户易宝剩余日限额
        $availableAmount = bcsub($limitInfo['day_quota'],$userTodayChargeAmount);
        // 溢出充值日限额处理
        $availableAmount = $availableAmount >= 0 ? $availableAmount : 0;
        // 转换成元,格式化
        $availableAmountRen =  bcdiv($availableAmount, 100, 2);

        // 充值日限额
        if ($limitInfo['day_quota'] == -1) {
            //日限额不限额
            $limitInfo['daylimit'] = self::LIMIT_NONE_LIMIT;
            $limitInfo['daylimit_desc'] = '';
            $limitInfo['daylimit_tips'] = '';
            $limitInfo['daylimit_format'] = '';
        } else if (!empty($limitInfo['day_quota']) && $availableAmount > 0) {
            // 动态日限额判断
            $limitInfo['daylimit'] = $availableAmount;
            $limitInfo['daylimit_desc'] = '今日剩余充值限额：'.$ps->formatMoneyNew($availableAmountRen).'元';
            $limitInfo['daylimit_tips'] = '今日剩余充值额度为'.$ps->formatMoneyNew($availableAmountRen).'元';
            $limitInfo['daylimit_format'] = $ps->formatMoneyNew($availableAmountRen).'元';
        } else if (!empty($limitInfo['day_quota']) && $availableAmount == 0) {
            $limitInfo['daylimit'] = 0;
            $limitInfo['daylimit_desc'] = '今日充值限额已达上限';
            $limitInfo['daylimit_tips'] = '今日充值限额已达上限';
            $limitInfo['daylimit_format'] = '';
            // 易宝支付渠道超过日限额则变为不可用
            $limitInfo['is_valid'] = self::CHANNEL_NOT_AVALIABLE;
        }

        // 单笔最大充值金额
        if ($limitInfo['max_quota'] == -1) {
            //单笔最大限额不限额
            $limitInfo['singlelimit'] = self::LIMIT_NONE_LIMIT;
            $limitInfo['singlelimit_desc'] = '';
            $limitInfo['singlelimit_tips'] = '';
            $limitInfo['singlelimit_format'] = '';
        } else if (bccomp($limitInfo['max_quota'], '0.00', 2) > 0) {
            //存在单笔最大金额限制
            $limitInfo['singlelimit'] = $limitInfo['max_quota'] >= $availableAmount ? $availableAmount : $limitInfo['max_quota'];
            $singlelimitRen = bcdiv($limitInfo['singlelimit'], 100, 2);
            $limitInfo['singlelimit_desc'] = '该卡本次充值限额：'.$ps->formatMoneyNew($singlelimitRen).'元';
            $limitInfo['singlelimit_tips'] = '本次单笔充值限额为：'.$ps->formatMoneyNew($singlelimitRen).'元';
            $limitInfo['singlelimit_format'] = $ps->formatMoneyNew($singlelimitRen).'元';
        }

        return $limitInfo;
    }

    /**
     * 获取指定bankCode的新协议充值限额数据
     * @param integer $userId 用户id
     * @param string $bankCode 银行简码
     * @param string $chargeType 充值通道
     * @return array
     *      可能的返回值
     *      1. 空数组 该银行卡没有设置新协议充值限额, 此时不限额
     *      2.
     */
    public function getNewH5ChargeLimitInfo($userId, $bankCode, $chargeType = PaymentNoticeModel::CHARGE_QUICK_CHANNEL) {
        $phApiService = new PhSupervisionService();
        $limitInfo = $phApiService->getPhChargeLimitOne($chargeType, $bankCode);
        // 如果为空数组,则返回不限额
        if (empty($limitInfo)) {
            return [];
        }
        // 默认支付渠道对该卡可用
        $limitInfo['is_valid'] = self::CHANNEL_AVALIABLE;

        $ps = new PaymentService();
        // 单笔最小充值金额为1分
        if ($limitInfo['min_quota'] >= 0) {
            $limitInfo['lowlimit'] = $limitInfo['min_quota'] ? $limitInfo['min_quota'] : 1;
            $lowLimitRen = bcdiv($limitInfo['lowlimit'], 100, 2);
            $limitInfo['lowlimit_desc'] = '单笔最小金额：'.$ps->formatMoneyNew($lowLimitRen).'元';
        }

        // 单笔最大充值金额
        if ($limitInfo['max_quota'] == -1) {
            // 单笔最大限额，不限额
            $limitInfo['singlelimit'] = self::LIMIT_NONE_LIMIT;
            $limitInfo['singlelimit_desc'] = '';
            $limitInfo['singlelimit_tips'] = '';
            $limitInfo['singlelimit_format'] = '';
        } else if ($limitInfo['max_quota'] > 0) {
            // 存在单笔最大金额限制
            $limitInfo['singlelimit'] = (int)$limitInfo['max_quota'];
            $singlelimitRen = bcdiv($limitInfo['singlelimit'], 100, 2);
            $limitInfo['singlelimit_desc'] = '该卡本次充值限额：'.$ps->formatMoneyNew($singlelimitRen).'元';
            $limitInfo['singlelimit_tips'] = '本次单笔充值限额为：'.$ps->formatMoneyNew($singlelimitRen).'元';
            $limitInfo['singlelimit_format'] = $ps->formatMoneyNew($singlelimitRen).'元';
        }

        // 充值日限额
        if ($limitInfo['day_quota'] == -1) {
            // 日限额不限额
            $limitInfo['daylimit'] = self::LIMIT_NONE_LIMIT;
            $limitInfo['daylimit_desc'] = '';
            $limitInfo['daylimit_tips'] = '';
            $limitInfo['daylimit_format'] = '';
        } else if ($limitInfo['day_quota'] > 0) {
            // 日限额，单位分
            $limitInfo['daylimit'] = (int)$limitInfo['day_quota'];
            // 转换成元,格式化
            $availableAmountRen =  bcdiv($limitInfo['daylimit'], 100, 2);
            $limitInfo['daylimit_desc'] = '今日剩余充值限额：'.$ps->formatMoneyNew($availableAmountRen).'元';
            $limitInfo['daylimit_tips'] = '今日剩余充值额度为'.$ps->formatMoneyNew($availableAmountRen).'元';
            $limitInfo['daylimit_format'] = $ps->formatMoneyNew($availableAmountRen).'元';
        }
        return $limitInfo;
    }

    /**
     * 获取充值方式列表
     * @param int $userId 用户ID
     * @param string $bankCardId 银行卡绑卡标识
     * @param string $bankCode 银行简码
     */
    public function queryChannelList($userId, $bankCardId, $bankCode) {
        foreach(self::$chargeChannelConfig as $key => $channelItem) {
            switch ($channelItem['payment_method']) {
                case self::PAYMENT_METHOD_UCFPAY: // 先锋支付
                    // 不存在于名单内的银行， 不展示
                    $isBankList = PaymentUserAccountService::inBankListConfig($bankCode, 'CHANNEL_BANKLIST_XFZF');
                    if ( ! $isBankList) {
                        unset(self::$chargeChannelConfig[$key]);
                        continue;
                    }

                    $limitType = ChargeService::LIMIT_TYPE_APP;
                    // 新协议支付开关是否打开
                    $isH5Charge = SupervisionFinanceService::isNewBankLimitOpen();
                    if ($isH5Charge) {
                        self::$chargeChannelConfig[$key]['link_url'] = '/payment/createOrderUcfpay';
                        $limitType = ChargeService::LIMIT_TYPE_NEWH5;
                    }
                    // 读取用户指定的银行卡标识的限额信息
                    $cardLimitList = $this->queryAllBankLimits($userId, $bankCardId, $limitType);

                    // 没查到限额信息，则不限额
                    if (empty($cardLimitList) || empty($cardLimitList[0])) {
                        self::$chargeChannelConfig[$key]['singlelimit'] = self::LIMIT_NONE_LIMIT;
                        self::$chargeChannelConfig[$key]['daylimit'] = self::LIMIT_NONE_LIMIT;
                        self::$chargeChannelConfig[$key]['lowlimit'] = 0;
                        self::$chargeChannelConfig[$key]['limit_desc'] = '';
                        self::$chargeChannelConfig[$key]['singlelimit_tips'] = '';
                        self::$chargeChannelConfig[$key]['daylimit_tips'] = '';
                        continue;
                    }

                    $cardLimitOne = $cardLimitList[0];
                    self::$chargeChannelConfig[$key]['singlelimit'] = $cardLimitOne['singlelimit'];
                    self::$chargeChannelConfig[$key]['daylimit'] = $cardLimitOne['daylimit'];
                    self::$chargeChannelConfig[$key]['lowlimit'] = isset($cardLimitOne['lowlimit']) ? $cardLimitOne['lowlimit'] : 0;
                    self::$chargeChannelConfig[$key]['singlelimit_tips'] = isset($cardLimitOne['singlelimit_tips']) ? $cardLimitOne['singlelimit_tips'] : '';
                    self::$chargeChannelConfig[$key]['daylimit_tips'] = isset($cardLimitOne['daylimit_tips']) ? $cardLimitOne['daylimit_tips'] : '';
                    if (!empty($cardLimitOne['is_valid'])) {
                        self::$chargeChannelConfig[$key]['limit_desc'] = join("\n", [$cardLimitOne['singlelimit_desc'], $cardLimitOne['daylimit_desc']]);
                    }else{
                        // 当前充值方式不可用
                        self::$chargeChannelConfig[$key]['limit_desc'] = $cardLimitOne['daylimit_desc'];
                    }
                    break;
                case self::PAYMENT_METHOD_YEEPAY: // 易宝充值
                    // 不存在于名单内的银行， 不展示
                    $isBankList = PaymentUserAccountService::inBankListConfig($bankCode, 'CHANNEL_BANKLIST_YEEPAY');
                    if ( ! $isBankList) {
                        unset(self::$chargeChannelConfig[$key]);
                        continue;
                    }

                    // 读取用户指定的银行卡标识的限额信息
                    $cardLimitOne = $this->getYeepayLimitInfo($userId, $bankCode);
                    // 没查到限额信息，则不限额
                    if (empty($cardLimitOne)) {
                        self::$chargeChannelConfig[$key]['singlelimit'] = self::LIMIT_NONE_LIMIT;
                        self::$chargeChannelConfig[$key]['daylimit'] = self::LIMIT_NONE_LIMIT;
                        self::$chargeChannelConfig[$key]['lowlimit'] = 0;
                        self::$chargeChannelConfig[$key]['limit_desc'] = '';
                        self::$chargeChannelConfig[$key]['singlelimit_tips'] = '';
                        self::$chargeChannelConfig[$key]['daylimit_tips'] = '';
                        continue;
                    }

                    self::$chargeChannelConfig[$key]['singlelimit'] = $cardLimitOne['singlelimit'];
                    self::$chargeChannelConfig[$key]['daylimit'] = $cardLimitOne['daylimit'];
                    self::$chargeChannelConfig[$key]['lowlimit'] = isset($cardLimitOne['lowlimit']) ? $cardLimitOne['lowlimit'] : 0;
                    self::$chargeChannelConfig[$key]['singlelimit_tips'] = isset($cardLimitOne['singlelimit_tips']) ? $cardLimitOne['singlelimit_tips'] : '';
                    self::$chargeChannelConfig[$key]['daylimit_tips'] = isset($cardLimitOne['daylimit_tips']) ? $cardLimitOne['daylimit_tips'] : '';
                    if (!empty($cardLimitOne['is_valid'])) {
                        self::$chargeChannelConfig[$key]['limit_desc'] = join("\n", [$cardLimitOne['singlelimit_desc'], $cardLimitOne['daylimit_desc']]);
                    }else{
                        // 当前充值方式不可用
                        self::$chargeChannelConfig[$key]['limit_desc'] = $cardLimitOne['daylimit_desc'];
                    }
                    break;
                case self::PAYMENT_METHOD_BIGPAY: // 大额充值
                    // 用户是否可以使用大额充值
                    $isBig = PaymentService::isBigCharge($userId, $bankCode);
                    if ( ! $isBig) {
                        unset(self::$chargeChannelConfig[$key]);
                        continue;
                    }

                    break;
                case self::PAYMENT_METHOD_PCPAY: // 电脑端网银充值
                    break;
            }
        }
        return [
            'list' => array_values(self::$chargeChannelConfig),
        ];
    }
}
