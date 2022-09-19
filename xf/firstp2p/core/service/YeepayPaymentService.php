<?php
/**
 * 易宝-支付服务
 *
 * 易宝-投资通API版接口封装
 * 
 * @package     core\service
 * @author      guofeng3
 * @copyright   (c) 2016, Wxlc Corporation. All rights reserved.
 * @History:
 *     1.0.0 | guofeng3 | 2016-01-16 15:00:00 | initialization
 ********************************** 80 Columns *********************************
*/

namespace core\service;

use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\utils\Block;
use core\service\ChargeService;
use core\dao\PaymentNoticeModel;
use core\dao\FinanceQueueModel;
use core\dao\UserModel;

class YeepayPaymentService extends BaseService {
    /**
     * 用户标识类型配置
     * @var array
     */
    public static $identityTypeConfig = array(
        'IMEI' => 'IMEI', //IMEI
        'MAC' => 'MAC', //MAC地址
        'ID' => 'USER_ID', //用户ID
        'EMAIL' => 'EMAIL', //用户Email
        'MOBILE' => 'MOBILE', //用户手机号
        'IDCARD' => 'ID_CARD', //用户身份证号
        'ORDERPROTOCOL' => 'AGREEMENT_NO', //用户纸质订单协议号
    );

    /**
     * 建议短信发送方式配置-验证码将以短信的方式发送给用户
     * @var string
     */
    const ADVICE_SMS_TYPE_MESSAGE = 'MESSAGE';

    /**
     * 建议短信发送方式配置-验证码将以短信的方式发送给用户
     * @var string
     */
    const ADVICE_SMS_TYPE_VOICE = 'VOICE';

    /**
     * 提现类型-自然日t+1
     * @var string
     */
    const DRAW_TYPE_NORMAL = 'NATRALDAY_NORMAL';

    /**
     * 提现类型-自然日t+0
     * @var string
     */
    const DRAW_TYPE_URGENT = 'NATRALDAY_URGENT';

    /**
     * 证件类型
     * @var string
     */
    const ID_CARD_TYPE_01 = 'ID';

    /**
     * 交易币种-默认156人民币(当前仅支持人民币)
     * @var int
     */
    const CURRENCY_156 = 156;

    /**
     * 订单有效期，单位分钟
     * @var int
     */
    const ORDER_EXPDATE_60 = 60;

    /**
     * 记录用户绑卡请求接口返回的[绑卡请求号、短信验证码]
     * @var string
     */
    const KEY_YEEPAY_BINDCARD = 'yeepay_bindcard_';

    /**
     * 易宝支付状态-失败
     * @var int
     */
    const YBPAY_STATUS_FAILURE = 'PAY_FAIL';

    /**
     * 易宝支付状态-成功
     * @var int
     */
    const YBPAY_STATUS_SUCCESS = 'PAY_SUCCESS';

    /**
     * 易宝支付状态-系统异常
     * @var int
     */
    const YBPAY_STATUS_FAIL = 'FAIL';

    /**
     * 易宝支付状态-处理中
     * @var int
     */
    const YBPAY_STATUS_ING = 'PROCESSING';

    /**
     * 易宝支付状态-已接收
     * @var int
     */
    const YBPAY_STATUS_ACCECPT = 'ACCECPT';

    /**
     * 易宝支付状态-超时失败
     * @var int
     */
    const YBPAY_STATUS_TIME_OUT = 'TIME_OUT';

    /**
     * 易宝支付状态-待短验确认
     * @var int
     */
    const YBPAY_STATUS_TO_VALIDATE = 'TO_VALIDATE';

    /**
     * 新投资通充值状态配置
     * @var string
     */
    public static $ybPayStatusConfig = array(
        self::YBPAY_STATUS_SUCCESS => '支付成功',
        self::YBPAY_STATUS_FAILURE => '支付失败',
        self::YBPAY_STATUS_ING => '支付处理中',
        self::YBPAY_STATUS_ACCECPT => '支付已接收',
        self::YBPAY_STATUS_FAIL => '支付系统异常',
        self::YBPAY_STATUS_TIME_OUT => '支付超时失败',
        self::YBPAY_STATUS_TO_VALIDATE => '待短验确认',
    );

    /**
     * 易宝提现状态-提现失败
     * @var string
     */
    const YBPAY_WITHDRAW_STATUS_FAILURE = 'WITHDRAW_FAIL';

    /**
     * 易宝提现状态-提现退回
     * @var string
     */
    const YBPAY_WITHDRAW_STATUS_REFUND = 'REFUND';

    /**
     * 易宝支付状态-提现成功
     * @var string
     */
    const YBPAY_WITHDRAW_STATUS_SUCCESS = 'WITHDRAW_SUCCESS';

    /**
     * 易宝支付状态-未知
     * @var string
     */
    const YBPAY_WITHDRAW_STATUS_UNKNOW = 'UNKNOW';

    /**
     * 新投资通查询接口-绑卡记录查询
     * @var string
     */
    const SEARCH_TYPE_BINDCARD = 'bindCardRecord';

    /**
     * 新投资通查询接口-充值记录查询
     * @var string
     */
    const SEARCH_TYPE_BINDPAY = 'bindPayRecord';

    /**
     * 新投资通查询接口-提现记录查询
     * @var string
     */
    const SEARCH_TYPE_WITHDRAW = 'withdrawRecord';

    /**
     * 新投资通查询接口-首次充值记录查询
     * @var string
     */
    const SEARCH_TYPE_FIRSTPAY = 'firstPayRecord';

    /**
     * 新投资通查询接口-退款记录查询
     * @var string
     */
    const SEARCH_TYPE_REFUND = 'refundRecord';

    /**
     * 新投资通查询接口配置
     * @var string
     */
    public static $searchTypeConfig = array(
        self::SEARCH_TYPE_BINDCARD => 1,
        self::SEARCH_TYPE_BINDPAY => 1,
        self::SEARCH_TYPE_WITHDRAW => 1,
        self::SEARCH_TYPE_FIRSTPAY => 1,
        self::SEARCH_TYPE_REFUND => 1,
    );

    /**
     * 缓存-易宝-用户绑卡列表
     * @var string
     */
    const CACHEKEY_YEEPAY_BANKCARD_BINDLIST = 'YEEPAY_BANKCARD_BINDLIST_%d';

    /**
     * 缓存-易宝-用户在OPENAPI里的订单信息等-OpenApi
     * @var string
     */
    const CACHEKEY_YEEPAY_ORDER_API = 'YEEPAY_ORDER_API_%s';

    /**
     * 缓存-易宝-用户在API里的订单信息等-Api
     * @var string
     */
    const CACHEKEY_YEEPAY_PAYMENT_API = 'YEEPAY_PAYMENT_API_%s';

    /**
     * 缓存-易宝-充值补单重试列表
     * @var string
     */
    const CACHEKEY_YEEPAY_ORDERREPAIR_RETRYLIST = 'YEEPAY_ORDERREPAIR_RETRYLIST';

    /**
     * 默认-易宝-充值补单最小ID
     * @var int
     */
    const DEFAULT_REPAIR_ORDER_MINID = 18360000;

    /**
     * 默认-易宝-易宝补单进程数量
     * @var int
     */
    const DEFAULT_REPAIR_ORDER_PROCESS = 10;

    /**
     * 易宝接口-错误码、错误信息映射
     * @var array
     */
    private static $errorMap = array(
        'bindBankCard' =>
          array (
            'TZ1001001' => '系统异常，请稍后再试',
            'TZ1001004' => '系统异常，请稍后再试',
            'TZ1001005' => '订单不存在，请重新发起订单',
            'TZ1001006' => '发送验证码的次数过多，请稍后再试',
            'TZ1001007' => '验证码无效，请重新获取新的验证码',
            'TZ1001008' => '您输入的短信验证码有误，请重新输入',
            'TZ1001009' => '该短信验证码已确认成功，无需重复确认',
            'TZ1001011' => '短信发送次数已超限，请24小时后再试',
            'TZ1001012' => '验证码已过期，请重新获取新的验证码',
            'TZ1001013' => '尚未开通补充鉴权权限',
            'TZ1001014' => '交叉鉴权银行卡种不支持借记卡',
            'TZ1001015' => '打款金额验证次数超限',
            'TZ1001016' => '打款金额不匹配',
            'TZ1001017' => '未找到打款记录',
            'TZ1001018' => '打款金额验证异常',
            'TZ1001019' => '打款金额验证已过期',
            'TZ1001020' => '鉴权订单状态暂不支持发短验',
            'TZ1001021' => '不存在成功的鉴权记录，请重新发起鉴权请求',
            'TZ1001022' => '该卡超过打款次数限制',
            'TZ1001024' => '打款金额已验证成功',
            'TZ1001028' => '已绑卡成功',
            'TZ1001029' => '创建手机号豁免白名单记录异常',
            'TZ1001030' => '手机号已被银行验证，无需添加手机号豁免白名单',
            'TZ1001031' => '删除手机号豁免白名单异常',
            'TZ1001032' => '手机号豁免白名单已存在，无需重复添加',
            'TZ1001033' => '您输入的手机号有误，请重新输入',
            'TZ1001034' => '身份证、姓名或银行预留手机号有误',
            'TZ1001035' => '银行卡交易被限制，详情请咨询发卡行',
            'TZ1001036' => '不支持此银行卡，请更换银行卡后再试',
            'TZ1001037' => '交易失败，请开通银行卡的银联在线业务后重试',
            'TZ1001038' => '您输入的姓名有误，请重新输入',
            'TZ1001039' => '您输入的证件信息有误，请重新输入',
            'TZ1001040' => '您输入的银行卡信息有误，请重新输入',
            'TZ1001042' => '银行系统异常，请稍后再试',
            'TZ1001043' => '请求信息有误',
            'TZ1001044' => '绑卡受限',
            'TZ1001045' => '无有效的绑卡关系',
            'TZ1001046' => '无有效的绑卡信息',
            'TZ1001047' => '商户信息异常',
            'TZ1001049' => '入参不能为空',
            'TZ1001050' => '绑卡失败，请联系平台客服处理，客服电话：%s',
            'TZ0400002' => '您输入的卡号有误，请重新输入',
            'default' => '身份证、姓名或银行预留手机号有误',
          ),
          'confirmBindBankCard' => 
          array (
            'TZ1001001' => '系统异常，请稍后再试',
            'TZ1001004' => '系统异常，请稍后再试',
            'TZ1001005' => '订单不存在，请重新发起订单',
            'TZ1001006' => '发送验证码的次数过多，请稍后再试',
            'TZ1001007' => '验证码无效，请重新获取新的验证码',
            'TZ1001008' => '您输入的短信验证码有误，请重新输入',
            'TZ1001009' => '该短信验证码已确认成功，无需重复确认',
            'TZ1001011' => '短信发送次数已超限，请24小时后再试',
            'TZ1001012' => '验证码已过期，请重新获取新的验证码',
            'TZ1001013' => '尚未开通补充鉴权权限',
            'TZ1001014' => '交叉鉴权银行卡种不支持借记卡',
            'TZ1001015' => '打款金额验证次数超限',
            'TZ1001016' => '打款金额不匹配',
            'TZ1001017' => '未找到打款记录',
            'TZ1001018' => '打款金额验证异常',
            'TZ1001019' => '打款金额验证已过期',
            'TZ1001020' => '鉴权订单状态暂不支持发短验',
            'TZ1001021' => '不存在成功的鉴权记录，请重新发起鉴权请求',
            'TZ1001022' => '该卡超过打款次数限制',
            'TZ1001024' => '打款金额已验证成功',
            'TZ1001028' => '已绑卡成功',
            'TZ1001029' => '创建手机号豁免白名单记录异常',
            'TZ1001030' => '手机号已被银行验证，无需添加手机号豁免白名单',
            'TZ1001031' => '删除手机号豁免白名单异常',
            'TZ1001032' => '手机号豁免白名单已存在，无需重复添加',
            'TZ1001033' => '您输入的手机号有误，请重新输入',
            'TZ1001034' => '身份证、姓名或银行预留手机号有误',
            'TZ1001035' => '银行卡交易被限制，详情请咨询发卡行',
            'TZ1001036' => '不支持此银行卡，请更换银行卡后再试',
            'TZ1001037' => '交易失败，请开通银行卡的银联在线业务后重试',
            'TZ1001038' => '您输入的姓名有误，请重新输入',
            'TZ1001039' => '您输入的证件信息有误，请重新输入',
            'TZ1001040' => '您输入的银行卡信息有误，请重新输入',
            'TZ1001042' => '银行系统异常，请稍后再试',
            'TZ1001043' => '请求信息有误',
            'TZ1001044' => '绑卡受限',
            'TZ1001045' => '无有效的绑卡关系',
            'TZ1001046' => '无有效的绑卡信息',
            'TZ1001047' => '商户信息异常',
            'TZ1001049' => '入参不能为空',
            'TZ1001050' => '绑卡失败，请联系平台客服处理，客服电话：%s',
          ),
          'directBindPay' => 
          array (
            'TZ2010001' => 'CVN验证失败',
            'TZ2010002' => 'CVV3或有效期有误',
            'TZ2010003' => '本卡在该商户不允许此交易',
            'TZ2010004' => '参数异常',
            'TZ2010005' => '充值次数超限，请24小时后重试或选择其他充值方式',
            'TZ2010006' => '已超日累计充值限额，请返回重新输入充值金额或选择其他充值方式',
            'TZ2010007' => '当月充值次数超限，请选择其他充值方式',
            'TZ2010008' => '认证失败，请核对信息后再试',
            'TZ2010009' => '冲正成功',
            'TZ2010010' => '冲正异常',
            'TZ2010011' => '充值次数超限，请24小时后重试或选择其他充值方式',
            'TZ2010012' => '您输入的短信验证码有误，请重新输入',
            'TZ2010013' => '短信发送次数已超限，请24小时后再试',
            'TZ2010014' => '短信验证码发送失败，请重新获取验证码',
            'TZ2010015' => '验证码已过期，请重新获取新的验证码',
            'TZ2010016' => '该订单不支持退款',
            'TZ2010017' => '该卡不支持无卡支付',
            'TZ2010018' => '已超单笔充值限额，请返回重新输入充值金额或选择其他充值方式',
            'TZ2010019' => '该客户号已关闭快速支付',
            'TZ2010020' => '已超单笔充值限额，请返回重新输入充值金额或选择其他充值方式',
            'TZ2010021' => '银行卡交易被限制，详情请咨询发卡行',
            'TZ2010022' => '充值金额需大于1元，请返回重新输入充值金额',
            'TZ2010023' => '卡信息或银行预留手机号有误',
            'TZ2010024' => '卡信息输入异常',
            'TZ2010025' => '卡信息验证未通过',
            'TZ2010026' => '不支持此银行卡，请更换银行卡后再试',
            'TZ2010027' => '银行卡账户余额不足，请核实后重试',
            'TZ2010028' => '密码错误次数超限，请联系发卡银行',
            'TZ2010029' => '密码验证失败，请重新输入',
            'TZ2010030' => '您输入的身份证号有误，请重新输入',
            'TZ2010031' => '请求正在处理中，请稍等',
            'TZ2010032' => '已超单笔充值限额，请返回重新输入充值金额或选择其他充值方式',
            'TZ2010033' => '已超月累计充值限额，请返回重新输入充值金额或选择其他充值方式',
            'TZ2010034' => '您输入的身份证号有误，请重新输入',
            'TZ2010035' => '您输入的姓名或身份证号有误，请重新输入',
            'TZ2010036' => '身份证与姓名不匹配',
            'TZ2010037' => '手机号码无记录，请核对后重新输入',
            'TZ2010038' => '请输入手机号',
            'TZ2010039' => '您输入的姓名有误，请重新输入',
            'TZ2010040' => '退款参数异常',
            'TZ2010041' => '退款金额超限',
            'TZ2010042' => '退款可退金额超限',
            'TZ2010043' => '退款已经存在不允许退款',
            'TZ2010044' => '未开通电子支付或交易信息有误',
            'TZ2010045' => '您输入的卡号无效，请重新输入',
            'TZ2010046' => '系统异常，请稍后再试',
            'TZ2010047' => '已超单笔充值限额，请返回重新输入充值金额或选择其他充值方式',
            'TZ2010048' => '请输入姓名',
            'TZ2010049' => '请输入短信验证码',
            'TZ2010050' => '手机号校验过于频繁，请稍后再试',
            'TZ2010051' => '银行处理中，请勿重复操作',
            'TZ2010052' => '您绑定的银行卡状态异常，请联系发卡行',
            'TZ2010053' => '银行退款失败',
            'TZ2010054' => '银行预留手机号有误，请返回修改后重试',
            'TZ2010055' => '银行卡账户余额不足，请核实后重试',
            'TZ2010056' => '预留手机号与开户行归属地不一致',
            'TZ2010057' => '原始金额不正确',
            'TZ2010058' => '账户异常',
            'TZ2010059' => '请输入证件号码',
            'TZ2010060' => '支付记录不存在',
            'TZ2010061' => '充值失败，请稍后重试或选择其他充值方式',
            'TZ2010062' => '重复的请求',
            'TZ2010063' => '发送验证码的次数过多，请稍后再试',
            'TZ2010064' => '订单已成功支付或者冲正请勿重复支付',
            'TZ2010065' => '已超单笔充值限额，请返回重新输入充值金额或选择其他充值方式',
          ),

    );

    /**
     * 4.1 绑定银行卡
     * 4.1.1 绑卡请求接口<br />
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     uid:用户标识<br />
     *     cardno:银行卡号<br />
     *     idcardno:证件号<br />
     *     username:持卡人姓名<br />
     *     phone:银行预留手机号<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     requestno:绑卡请求号<br />
     *     status:状态(TO_VALIDATE：待短验|BIND_FAIL：绑卡失败|BIND_ERROR：绑卡异常(可重试)|TO_ENHANCED：待补充鉴权|TIME_OUT：超时失败|FAIL：系统异常)
     *     codesender:短信验证码发送方(YEEPAY:易宝,BANK:银行,MERCHANT:商户)<br />
     *     smscode:短信验证码<br />
     *     smstype:实际短验发送类型(VOICE：语音|MESSAGE：短信)
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名
     */
    public function bindBankCard($params)
    {
        if (!isset($params['uid']) OR !isset($params['cardno']) OR !isset($params['idcardno'])
            OR !isset($params['username']) OR !isset($params['phone']) OR empty($params['uid'])
            OR empty($params['cardno']) OR empty($params['idcardno']) OR empty($params['username'])
            OR empty($params['phone']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 检查用户请求绑卡接口的频率，60s请求1次
        if (false === Block::check('YEEPAY_BIND_BANKCARD_SECOND', $params['phone'], true))
        {
            return array('respCode' => 1002, 'respMsg' => '请求频次太快，请稍后再试');
        }

        // 生成绑卡请求号
        $requestId = date('YmdHis') . self::random(8, 1);

        // 获取用户请求ip
        $clientIp = ($clientIpInit = get_real_ip()) !== 'unknown' ? $clientIpInit : '127.0.0.1';

        // 组织数据
        $requestParams = array(
            'identityid' => (string)$params['uid'], // 用户标识-必填
            'identitytype' => isset($params['identitytype']) ? $params['identitytype'] : self::$identityTypeConfig['ID'], // 用户标识类型-必填
            'requestno' => (string)$requestId, // 商户生成的唯一绑卡请求号，最长50位-必填
            'cardno' => (string)$params['cardno'], // 银行卡号-必填
            'idcardtype' => (string)self::ID_CARD_TYPE_01, // 证件类型，固定值01-必填
            'idcardno' => (string)$params['idcardno'], // 证件号-必填
            'username' => (string)$params['username'], // 持卡人姓名-必填
            'phone' => (string)$params['phone'], // 银行预留手机号-必填
            'advicesmstype' => isset($params['advicesmstype']) ? (string)$params['advicesmstype'] : (string)self::ADVICE_SMS_TYPE_MESSAGE, // 建议短信发送方式配置-选填
            'requesttime' => date('Y-m-d H:i:s'),
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('bindBankCard', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && empty($response['errormsg']))
            {
                // 记录用户请求绑卡接口的频率，60s请求1次
                Block::check('YEEPAY_BIND_BANKCARD_SECOND', $params['phone']);
                // 记录用户的绑卡请求号，用于校验用户下一步确认绑卡的逻辑
                \es_session::set(sprintf('%s%d', self::KEY_YEEPAY_BINDCARD, $params['uid']), $response['requestno']);
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            $errorMsg = isset(self::$errorMap[__FUNCTION__][$response['errorcode']]) && !empty(self::$errorMap[__FUNCTION__][$response['errorcode']]) ? self::$errorMap[__FUNCTION__][$response['errorcode']] : self::$errorMap[__FUNCTION__]['default'];
            // 绑卡超过次数限制
            if (!empty($response['errorcode']) && in_array($response['errorcode'], ['TZ1001044', 'TZ1001050'])) {
                // 获取客服电话
                $errorMsg = sprintf($errorMsg, self::getCustomerPhone());
            }
            return array('respCode' => $response['errorcode'], 'respMsg' => $errorMsg);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.1 绑定银行卡
     * 4.1.2 确认绑卡接口<br />
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     $userId:用户标识<br />
     *     $validateCode:短信验证码<br />
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     requestno:绑卡请求号<br />
     *     yborderid:易宝流水号<br />
     *     bankcode:银行编码<br />
     *     cardtop:卡号前6位<br />
     *     cardlast:卡号后4位<br />
     *     status:BIND_SUCCESS：绑卡成功|BIND_FAIL：绑卡失败|BIND_ERROR：绑卡异常(可重试)|TO_VALIDATE：待短验|TIME_OUT：超时失败|FAIL：系统异常
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息-请求号不存在<br />
     *     sign:签名
     */
    public function confirmBindBankCard($userId, $validateCode)
    {
        if (empty($userId) OR empty($validateCode))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 获取[4.1.1 绑卡请求接口]返回的绑卡请求号(请求号不存在，请重新绑卡)
        $requestId = \es_session::get(sprintf('%s%d', self::KEY_YEEPAY_BINDCARD, $userId));
        if (empty($requestId))
        {
            return array('respCode' => 1004, 'respMsg' => '绑卡短信验证码校验错误');
        }

        // 组织数据
        $requestParams = array(
            'requestno' => (string)$requestId, // 商户生成的唯一绑卡请求号，最长50位-必填
            'validatecode' => (string)$validateCode, // 短信验证码6位数字-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('confirmBindBankCard', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && $response['status'] === 'BIND_SUCCESS')
            {
                // 清除缓存-易宝-用户绑卡列表
                $redis = self::getRedisSentinels();
                $redis->del(sprintf(self::CACHEKEY_YEEPAY_BANKCARD_BINDLIST, $userId));
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            $errorMsg = isset(self::$errorMap[__FUNCTION__][$response['errorcode']]) && !empty(self::$errorMap[__FUNCTION__][$response['errorcode']]) ? self::$errorMap[__FUNCTION__][$response['errorcode']] : $response['errormsg'];
            // 绑卡超过次数限制
            if (!empty($response['errorcode']) && in_array($response['errorcode'], ['TZ1001044', 'TZ1001050'])) {
                // 获取客服电话
                $errorMsg = sprintf($errorMsg, self::getCustomerPhone());
            }
            return array('respCode' => $response['errorcode'], 'respMsg' => $errorMsg);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.1 绑定银行卡
     * 4.1.3 请求短验重发接口<br />
     * 1.短验码格式：͸ 位随机数字
     * 2.短验码有效期：30 分钟
     * 3.短验码验证错误次数限制：在有效期内允许试错 3 次，3 次之后失效
     * 4.同一订单号的同一手机号限制：在有效期内最多能发送 5 次相同的短验码
     * 5.发送最短间隔限制：大于 50 秒
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     $userId:用户标识<br />
     *     $advicesmstype:建议短验发送类型<br />
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     requestno:绑卡请求号<br />
     *     yborderid:易宝流水号<br />
     *     status:TO_VALIDATE：待短验|TIME_OUT：超时失败|FAIL：系统异常
     *     smscode:短信验证码(商户发短验时返回的易宝生成的短验码（易宝发短验时此字段为空）)
     *     codesender:实际发送方(CUSTOMER：商户发送|YEEPAY：易宝发送|BANK：银行发送)
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名
     */
    public function resendSmsBindBankCard($userId, $advicesmstype = '')
    {
        if (empty($userId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 获取[4.1.1 绑卡请求接口]返回的绑卡请求号(请求号不存在，请重新绑卡)
        $requestId = \es_session::get(sprintf('%s%d', self::KEY_YEEPAY_BINDCARD, $userId));
        if (empty($requestId))
        {
            return array('respCode' => 1004, 'respMsg' => '绑卡短信验证码校验错误');
        }

        // 组织数据
        $requestParams = array(
            'requestno' => (string)$requestId, // 商户生成的唯一绑卡请求号，最长50位-必填
            'advicesmstype' => !empty($advicesmstype) ? (string)$advicesmstype : self::ADVICE_SMS_TYPE_MESSAGE, // 短信发送类型-选填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('resendSmsBindBankCard', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && empty($response['errormsg']))
            {
                // 清除缓存-易宝-用户绑卡列表
                $redis = self::getRedisSentinels();
                $redis->del(sprintf(self::CACHEKEY_YEEPAY_BANKCARD_BINDLIST, $userId));
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            $errorMsg = isset(self::$errorMap[__FUNCTION__][$response['errorcode']]) && !empty(self::$errorMap[__FUNCTION__][$response['errorcode']]) ? self::$errorMap[__FUNCTION__][$response['errorcode']] : $response['errormsg'];
            return array('respCode' => $response['errorcode'], 'respMsg' => $errorMsg);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.2 支付接口-发送短验
     * 4.2.1 支付请求接口<br />
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     uid:用户标识<br />
     *     amount:交易金额，单位分<br />
     *     productname:商品名称，最长50位<br />
     *     productdesc:商品描述，最长200位-选填<br />
     *     card_top:卡号前6位<br />
     *     card_last:卡号后4位<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     orderid:商户订单号<br />
     *     phone:手机号<br />
     *     smsconfirm:短信确认(0:建议不需要进行短信校验1:建议需要进行短信校验)<br />
     *     codesender:短信验证码发送方(YEEPAY:易宝,BANK:银行)<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码-600156<br />
     *     error_msg:错误信息-没有对应绑卡关系<br />
     *     sign:签名
     */
    public function payBindRequest($params)
    {
        if (!isset($params['uid']) OR !isset($params['amount']) OR !isset($params['card_top'])
            OR !isset($params['card_last']) OR empty($params['uid']) OR empty($params['amount'])
            OR empty($params['card_top']) OR empty($params['card_last']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取支付异步回调地址
        $payBindRequestConfig = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('payBindRequest');
        if (!isset($payBindRequestConfig['callbackUrl']) OR empty($payBindRequestConfig['callbackUrl']))
        {
            return array('respCode' => 1005, 'respMsg' => '回调地址尚未配置');
        }

        // 生成充值订单号
        $orderSn = (isset($params['orderId']) && !empty($params['orderId'])) ? $params['orderId'] : date('YmdHis') . mt_rand(1000000, 9999999);
        // 交易金额，单位元
        $amountYuan = isset($params['amount']) ? bcdiv($params['amount'], 100, 2) : 0;
        // 获取用户请求ip
        $clientIp = ($clientIpInit = get_real_ip()) !== 'unknown' ? $clientIpInit : '127.0.0.1';

        // 组织数据
        $requestParams = array(
            'orderid' => (string)$orderSn, // 商户订单号，最长50位-必填
            'transtime' => (int)get_gmtime(), // 时间戳-必填
            'currency' => (int)self::CURRENCY_156, // 交易币种-选填，默认156人民币(当前仅支持人民币)
            'amount' => (int)$params['amount'], // 交易金额，以分为单位-必填
            'productname' => isset($params['productname']) ? (string)$params['productname'] : '充值', // 商品名称，最长50位-必填
            'productdesc' => isset($params['productdesc']) ? (string)$params['productdesc'] : '', // 商品描述，最长200位-选填
            'identityid' => (string)$params['uid'], // 用户标识-必填
            'identitytype' => isset($params['identitytype']) ? $params['identitytype'] : self::$identityTypeConfig['ID'], // 用户标识类型-必填
            'card_top' => (string)$params['card_top'], // 卡号前6位-必填
            'card_last' => (string)$params['card_last'], // 卡号后4位-必填
//             'orderexpdate' => isset($params['orderexpdate']) ? (int)$params['orderexpdate'] : (int)self::ORDER_EXPDATE_60, // 订单有效期，单位分钟-选填，默认24小时，范围5分钟-100天以内
            'callbackurl' => (string)$payBindRequestConfig['callbackUrl'], // 回调地址，用来通知商户支付结果-必填
            'userip' => (string)$clientIp, // 用户请求ip-必填
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 100) : '', // 浏览器信息-选填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('payBindRequest', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error_msg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.2 支付接口-发送短验
     * 4.2.2 发送短信验证码接口<br />
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     $orderId:商户订单号，最长50位<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     orderid:商户订单号<br />
     *     phone:手机号<br />
     *     sendtime:短信发送时间戳<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码-600010<br />
     *     error_msg:错误信息-发送短信验证码失败[000014]<br />
     *     sign:签名<br />
     * 返回参数-其他：<br />
     *     error_code:错误码<br />
     *     error_msg:错误信息
     */
    public function payValidatecodeSend($orderId)
    {
        if (empty($orderId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = array(
            'orderid' => (string)$orderId, // 商户订单号，最长50位-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('payValidatecodeSend', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error_msg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.2 支付接口-发送短验
     * 4.2.3 确认支付接口<br />
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     $orderId:商户订单号，最长50位<br />
     *     $validateCode:短信验证码-选填<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     orderid:商户订单号<br />
     *     yborderid:易宝交易流水号<br />
     *     amount:交易金额，单位分<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码-600010<br />
     *     error_msg:错误信息-支付失败[000014]<br />
     *     sign:签名<br />
     * 注：此接口成功状态的返回，只是调用此接口成功，具体的支付结果将异步发送给支付请求时提供的回调地址
     * 调用确认支付接口成功后，支付结果不管成功还是失败都会回调，若想确认该笔订单是否成功可调用"交易记录查询"接口
     */
    public function payConfirmValidatecode($orderId, $validateCode = 0)
    {
        if (empty($orderId) OR (!empty($validateCode) && !is_numeric($validateCode)))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不合法或不能为空');
        }

        // 组织数据
        $requestParams = array(
            'orderid' => (string)$orderId, // 商户订单号，最长50位-必填
        );
        // 短信验证码
        !empty($validateCode) && $requestParams['validatecode'] = (string)$validateCode;

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('payConfirmValidatecode', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error_msg']))
            {
                // 更新[会员充值单表]，把is_paid更新为2(待支付)
                $GLOBALS['db']->autoExecute('firstp2p_payment_notice', array('is_paid' => PaymentNoticeModel::IS_PAID_ING), 'UPDATE' , " notice_sn = '{$orderId}'");
                $affectRows = $GLOBALS['db']->affected_rows();
                if ($affectRows <= 0) {
                    PaymentApi::log(sprintf('%s|更新[firstp2p_payment_notice]的is_paid为2(待支付)失败|订单号:%s', __METHOD__, $orderId), Logger::ERR);
                }
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.2 支付接口-发送短验
     * 4.2.4 异步通知参数接口<br />
     * HTTP请求方式：POST<br />
     * 
     * 注：易宝异步通知商户支付请求传过来的callbackurl地址，每2秒通知一次，共通知3次
     * 商户收到通知后需要回写，需要返回字符串大写的"SUCCESS"，否则会一直通知多次
     */
    public function payYeepayChargeCallback($params)
    {
        if (empty($params['requestno']) OR bccomp($params['amount'],0.00,2) <= 0  OR empty($params['identityid']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取用户ID
        $userId = (int)$params['identityid'];
        // 理财的充值订单号
        $orderId = addslashes($params['requestno']);
        // 交易金额，单位元
        $amount = floatval($params['amount']);
        // 支付状态(PAY_SUCCESS：支付成功|PAY_FAIL：支付失败|TIME_OUT：超时失败)
        $status = trim($params['status']);

        // 查询充值订单数据
        $paymentNoticeInfo = PaymentNoticeModel::instance()->getInfoByUserIdNoticeSn($userId, $orderId);
        if (empty($paymentNoticeInfo))
        {
            return array('respCode' => 1007, 'respMsg' => '充值订单不存在');
        }

        // 校验充值订单金额，单位元，与易宝回调金额是否一致
        if (bccomp($paymentNoticeInfo['money'], $amount, 2) !== 0)
        {
            PaymentApi::log(sprintf('%s|与易宝的充值回调金额有误|订单ID:%d|回调参数:%s', __METHOD__, $paymentNoticeInfo['id'], json_encode($params)), Logger::ERR);
            return array('respCode' => 1008, 'respMsg' => '充值回调金额有误');
        }

        switch ($status)
        {
            case self::YBPAY_STATUS_SUCCESS: // 支付状态-成功
                return $this->_yeePayChargeSuccess($paymentNoticeInfo, $params);
                break;
            case self::YBPAY_STATUS_FAILURE: // 支付状态-失败
            case self::YBPAY_STATUS_FAIL: // 支付状态-系统异常
            case self::YBPAY_STATUS_ACCECPT: // 支付状态-已接收
            case self::YBPAY_STATUS_TIME_OUT: // 支付状态-超时失败
                return $this->_yeePayChargeFailue($paymentNoticeInfo, $params);
                break;
            default:
                return array('respCode' => 1008, 'respMsg' => 'param[\'status\'] is illegal');
                break;
        }
    }

    /**
     * 4.3 支付接口-不发送短验-支付请求接口（新投资通-4.2无短验充值接口）
     * 
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     uid:用户标识<br />
     *     orderId:订单号<br />
     *     amount:交易金额，单位分<br />
     *     productname:商品名称，最长50位<br />
     *     productdesc:商品描述，最长200位-选填<br />
     *     card_top:卡号前6位<br />
     *     card_last:卡号后4位<br />
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     requestno:商户充值请求号<br />
     *     yborderid:易宝交易流水号<br />
     *     amount:交易金额<br />
     *     status:PROCESSING：处理中|PAY_FAIL：支付失败|TIME_OUT：超时失败|FAIL：系统异常<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码-600156<br />
     *     error_msg:错误信息-没有对应绑卡关系<br />
     *     sign:签名<br />
     * 注：此接口成功状态的返回，只是调用此接口成功，具体的支付结果将异步发送给支付请求时提供的回调地址
     */
    public function directBindPay($params)
    {
        if (!isset($params['uid']) OR !isset($params['orderId']) OR !isset($params['amount'])
            OR !isset($params['productname']) OR !isset($params['card_top']) OR !isset($params['card_last'])
            OR empty($params['uid']) OR empty($params['orderId']) OR empty($params['amount'])
            OR empty($params['productname']) OR empty($params['card_top']) OR empty($params['card_last'])
            OR empty($params['registtime']) OR empty($params['lastloginterminalid']) OR empty($params['terminalid']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 获取支付异步回调地址
        $directBindPayConfig = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('directBindPay');
        if (!isset($directBindPayConfig['callbackUrl']) OR empty($directBindPayConfig['callbackUrl']))
        {
            return array('respCode' => 1005, 'respMsg' => '回调地址尚未配置');
        }

        // 生成充值订单号
        $orderSn = htmlspecialchars($params['orderId']);
        // 交易金额，单位元
        $amountYuan = isset($params['amount']) ? bcdiv($params['amount'], 100, 2) : 0;
        // 获取用户请求ip
        $clientIp = ($clientIpInit = get_real_ip()) !== 'unknown' ? $clientIpInit : '127.0.0.1';

        // 组织数据
        $requestParams = array(
            'requestno' => $orderSn, // 商户充值请求号-必填
            'identityid' => (string)$params['uid'], // 用户标识-必填
            'identitytype' => isset($params['identitytype']) ? $params['identitytype'] : self::$identityTypeConfig['ID'], // 用户标识类型-必填
            'requesttime' => date('Y-m-d H:i:s'),
            'amount' => (string)floatval($params['amount']), // 交易金额(单位元,精确到两位小数,大于等于 0.01)-必填
            'productname' => (string)$params['productname'], // 商品名称，最长50位-必填
            'cardtop' => (string)$params['card_top'], // 卡号前6位-必填
            'cardlast' => (string)$params['card_last'], // 卡号后4位-必填
            'callbackurl' => (string)$directBindPayConfig['callbackUrl'], // 回调地址，用来通知商户支付结果-选填
            'terminalid' => (string)$params['terminalid'], // 注册硬件终端标识码-必填
            'registtime' => (string)$params['registtime'], // 用户注册时间-必填
            'lastloginterminalid' => (string)$params['lastloginterminalid'], // 上一次登录硬件终端标识码-必填
            'issetpaypwd' => (string)0, // 是否设置支付密码(0:否1:是)-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('directBindPay', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && empty($response['errormsg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            $errorMsg = isset(self::$errorMap[__FUNCTION__][$response['errorcode']]) && !empty(self::$errorMap[__FUNCTION__][$response['errorcode']]) ? self::$errorMap[__FUNCTION__][$response['errorcode']] : $response['errormsg'];
            return array('respCode' => $response['errorcode'], 'respMsg' => $errorMsg);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.4 支付接口查询（新投资通-4.6.2充值记录查询）
     * 
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $orderId:商户订单号<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     orderid:商户订单号<br />
     *     yborderid:易宝交易流水号<br />
     *     amount:支付金额，单位分<br />
     *     bindid:绑卡ID<br />
     *     bindvalidthru:绑卡有效期，时间戳<br />
     *     bank:支付卡所属银行的名称<br />
     *     bankcode:银行缩写<br />
     *     closetime:支付时间戳<br />
     *     bankcardtype:银行卡类型(0:储蓄卡1:信用卡)<br />
     *     lastno:卡号后4位<br />
     *     identityid:用户标识ID<br />
     *     identitytype:用户标识类型<br />
     *     status:状态(0:失败1:成功2:未处理3:处理中4:已撤销)<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码-600072<br />
     *     error_msg:错误信息-订单不存在<br />
     *     sign:签名
     */
    public function queryOrderOld($orderId)
    {
        if (empty($orderId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = array(
            'orderid' => (string)$orderId, // 商户订单号-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('queryOrder', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['error_code']) && empty($response['error_msg']))
            {
                // 返回接口数据
                return array('ret'=>true, 'respCode' => '00', 'data' => $response);
            }
            return array('ret'=>true, 'respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('ret'=>false, 'respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 新投资通-4.6.2充值记录查询
     * 商户可根据充值请求号或者易宝流水号查询充值交易记录。
     * HTTP请求方式：GET/POST<br />
     * 传入参数：<br />
     *     $apiName:请求的接口名
     *     $requestNo:充值流水号<br />
     *     $yborderId:易宝流水号<br />
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     requestno:充值流水号<br />
     *     yborderid:易宝交易流水号<br />
     *     status:订单状态(ACCEPT：已接收|TO_ENHANCED：待补充鉴权|TO_VALIDATE：待短验确认|BIND_SUCCESS：绑卡成功|BIND_FAIL：绑卡失败|BIND_ERROR：绑卡异常|WAIT_BIND：待绑卡|PAY_FAIL：支付失败|PROCESSING：处理中|PAY_SUCCESS：支付成功|WITHDRAW_SUCCESS：提现成功|WITHDRAW_FAIL：提现失败|REFUND_SUCCESS：退款成功|REFUND_FAIL：退款失败|TIME_OUT：超时失败|FAIL：系统异常)<br />
     *     amount:金额<br />
     *     cardtop:卡号前六位<br />
     *     cardlast:卡号后四位<br />
     *     bankcode:银行编码<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名
     */
    public function queryOrder($apiName, $requestNo='', $yborderId='')
    {
        if ((empty($requestNo) && empty($yborderId)) || !isset(self::$searchTypeConfig[$apiName]))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = [];
        !empty($requestNo) && $requestParams['requestno'] = (string)$requestNo; // 商户流水号
        !empty($yborderId) && $requestParams['yborderid'] = (string)$yborderId; // 易宝流水号

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request($apiName, $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && empty($response['errormsg']))
            {
                // 返回接口数据
                return array('ret'=>true, 'respCode' => '00', 'data' => $response);
            }
            return array('ret'=>true, 'respCode' => $response['errorcode'], 'respMsg' => $response['errormsg']);
        }
        return array('ret'=>false, 'respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.5 提现接口-暂不接入
     * 
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     uid:用户标识<br />
     *     cardno:银行卡号<br />
     *     amount:提现金额，单位分<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     requestid:商户请求号<br />
     *     ybdrawflowid:易宝流水号
     *     amount:提现金额，单位分
     *     card_top:卡号前6位
     *     card_last:卡号后4位
     *     status:提现请求状态(FAILURE:请求失败SUCCESS:请求成功UNKNOW:未知)<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码<br />
     *     error_msg:错误信息<br />
     *     sign:签名
     */
    public function withDraw($params)
    {
        if (!isset($params['uid']) OR !isset($params['cardno']) OR !isset($params['amount'])
            OR empty($params['uid']) OR empty($params['cardno']) OR empty($params['amount']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 生成绑卡请求号
        $requestId = date('YmdHis') . self::random(8, 1);

        // 获取卡号前6位、后4位
        $cardTop = substr($params['cardno'], 0, 6);
        $cardLast = substr($params['cardno'], -4);

        // 获取支付异步回调地址
        $withDrawConfig = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('withDraw');

        // 获取用户请求ip
        $clientIp = ($clientIpInit = get_real_ip()) !== 'unknown' ? $clientIpInit : '127.0.0.1';

        // 组织数据
        $requestParams = array(
            'requestid' => (string)$requestId, // 商户生成的唯一提现请求号，最长50位-必填
            'identityid' => (string)$params['uid'], // 用户标识-必填
            'identitytype' => isset($params['identitytype']) ? $params['identitytype'] : self::$identityTypeConfig['ID'], // 用户标识类型-必填
            'card_top' => (string)$cardTop, // 卡号前6位-必填
            'card_last' => (string)$cardLast, // 卡号后4位-必填
            'amount' => (int)$params['amount'], // 提现金额，单位分
            'currency' => (int)self::CURRENCY_156, // 币种，默认156人民币-选填
            'drawtype' => isset($params['drawtype']) ? (string)$params['drawtype'] : self::DRAW_TYPE_URGENT, // 提现类型-必填
            'userip' => (string)$clientIp, // 用户请求ip-必填
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 100) : '', // 浏览器信息-选填
            'callbackurl' => isset($withDrawConfig['callbackUrl']) ? $withDrawConfig['callbackUrl'] : '', // 回调地址，用来通知商户提现结果-选填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('withDraw', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error_msg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.5 提现接口-暂不接入
     * 4.5.4 异步提现请求接口<br />
     * HTTP请求方式：POST<br />
     *
     */
    public function withDrawCallback($params)
    {
        if (!isset($params['merchantaccount']) OR !isset($params['requestid']) OR !isset($params['ybdrawflowid'])
            OR !isset($params['amount']) OR !isset($params['card_top']) OR !isset($params['card_last'])
            OR !isset($params['status']) OR empty($params['merchantaccount']) OR empty($params['requestid'])
            OR empty($params['ybdrawflowid']) OR (int)$params['amount'] <= 0 OR empty($params['card_top'])
            OR empty($params['card_last']) OR empty($params['status']))
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 校验签名
        $verifyRet = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->verifySignature($params, $params['sign']);
        if (!$verifyRet)
        {
            return array('respCode' => 1009, 'respMsg' => '签名校验失败');
        }

        // 易宝流水号
        $ybDrawflowId = htmlspecialchars($params['ybdrawflowid']);
        // 查询提现数据
        $withDrawInfo = UserCarryModel::instance()->getInfoByNoticeSn($ybDrawflowId);
        if (empty($withDrawInfo))
        {
            return array('respCode' => 1007, 'respMsg' => '提现单号不存在');
        }

        switch ($params['status'])
        {
            case self::YBPAY_WITHDRAW_STATUS_SUCCESS: // 提现状态-成功
                // TODO 提现成功的处理
                break;
            case self::YBPAY_WITHDRAW_STATUS_FAILURE: // 提现状态-失败
            case self::YBPAY_WITHDRAW_STATUS_REFUND: // 提现状态-退回
            case self::YBPAY_WITHDRAW_STATUS_UNKNOW: // 提现状态-未知
                // TODO 提现失败的处理
                break;
            default:
                return array('respCode' => 1008, 'respMsg' => 'param[\'status\'] is illegal');
                break;
        }
    }

    /**
     * 4.6 提现查询接口-暂不接入
     * 
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $orderId:商户订单号<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     requestid:商户请求号<br />
     *     ybdrawflowid:易宝流水号<br />
     *     amount:提现金额，单位分
     *     card_top:卡号前6位
     *     card_last:卡号后4位
     *     status:提现请求状态(DOING:处理中FAILURE:提现失败REFUND:提现退回SUCCESS:提现成功UNKNOW:未知)<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码<br />
     *     error_msg:错误信息<br />
     *     sign:签名
     */
    public function drawRecord($requestId = '', $ybDrawflowId = '')
    {
        if (empty($requestId) && empty($ybDrawflowId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能同时为空');
        }

        // 组织数据
        $requestParams = array(
            'requestid' => (string)$requestId, // 商户生成的唯一提现请求号，最长50位-必填
            'ybdrawflowid' => (string)$ybDrawflowId, // 易宝提现流水号-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('drawRecord', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error_msg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.7 解绑卡（新投资通-5.3解绑卡）
     * 
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     $userId:用户标识<br />
     *     $cardtop:卡号前6位<br />
     *     $cardlast:卡号后4位<br />
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     identityid:用户标识ID<br />
     *     identitytype:用户标识类型<br />
     *     cardtop:卡号前6位<br />
     *     cardlast:卡号后4位<br />
     *     status:解绑状态(SUCCESS：成功|FAIL：失败)<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码-600073<br />
     *     errormsg:错误信息-无效支付身份标识<br />
     *     sign:签名<br />
     * 返回参数-其他：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     * 注：该接口用于卡信息(如银行预留手机号)变更后的解绑使用，解绑后只能绑定原卡，不可换卡绑定
     */
    public function bankCardUnbind($userId, $cardtop, $cardlast)
    {
        if (empty($userId) OR empty($cardtop) OR empty($cardlast))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = array(
            'identityid' => (string)$userId, // 用户标识-必填
            'identitytype' => self::$identityTypeConfig['ID'], // 用户标识类型-必填
            'cardtop' => (string)$cardtop,
            'cardlast' => (string)$cardlast,
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('bankCardUnbind', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && $response['status'] === 'SUCCESS')
            {
                // 清除缓存-易宝-用户绑卡列表
                $redis = self::getRedisSentinels();
                $redis->del(sprintf(self::CACHEKEY_YEEPAY_BANKCARD_BINDLIST, $userId));
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['errorcode'], 'respMsg' => $response['errormsg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.8 查询绑卡信息列表
     * 查询用户的绑卡信息时使用，只返回未过绑卡有效期且未被解绑的绑卡信息
     * 在绑卡支付前，调用本接口获取用户有效的绑卡关系列表，然后让用户在商户的交互页面选择绑卡关系
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $userId:用户标识<br />
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     identityid:用户标识ID<br />
     *     identitytype:用户标识类型<br />
     *     cardlist:绑卡列表json<br />
     *     bindid:绑卡ID<br />
     *     cardtop:卡号前6位<br />
     *     cardlast:卡号后4位<br />
     *     cardname:卡名称<br />
     *     phone:银行预留手机号，中间4位屏蔽<br />
     *     bankcode:银行编码<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-其他：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息
     */
    public function bankCardAuthBindList($userId, $enableCache = true)
    {
        if (empty($userId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 缓存-易宝-用户绑卡列表
        $redis = self::getRedisSentinels();
        $cacheKey = sprintf(self::CACHEKEY_YEEPAY_BANKCARD_BINDLIST, $userId);
        if ($enableCache)
        {
            $json = $redis->get($cacheKey);
            $response = json_decode($json, true);
            if (!empty($response))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
        }

        // 组织数据
        $requestParams = array(
            'identityid' => (string)$userId, // 用户标识-必填
            'identitytype' => isset($params['identitytype']) ? $params['identitytype'] : self::$identityTypeConfig['ID'], // 用户标识类型-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('bankCardAuthBindList', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && (empty($response['errorcode']) || $response['errorcode'] === 'TZ0200002'))
            {
                // 返回的绑卡列表不为空时，记录缓存数据
                if (isset($response['data']['cardlist']) && !empty($response['data']['cardlist']))
                {
                    $redis->setex($cacheKey, 86400, json_encode($response));
                }
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['errorcode'], 'respMsg' => $response['errormsg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.9 银行卡信息查询
     * 此接口可以用来根据用户输入的银行卡号查询银行卡的借贷类型、银行名称等信息
     * 用户交互页面， 让用户输入卡信息后，先调用本接口检查是否为有效的银行卡
     * (但并不代表是投资通支持的银行卡，投资通支持的银行卡见附录)，然后再进行
     * 支付请求，以提高支付成功率
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     $cardno:银行卡号<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     cardno:卡号<br />
     *     cardtype:借贷类型(1:储蓄卡2:信用卡-1:未知银行卡)<br />
     *     bankname:银行名称<br />
     *     bankcode:银行编码<br />
     *     isvalid:卡号是否有效(0:无效1:有效(但不表示是一键支付支持的银行卡))<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-其他：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息
     */
    public function bankCardCheck($cardno)
    {
        if (empty($cardno))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = array(
            'cardno' => (string)$cardno, // 银行卡号-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('bankCardCheck', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && empty($response['errorcode']) && empty($response['errormsg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['errorcode'], 'respMsg' => $response['errormsg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 4.10 可提现余额接口
     * 
     * HTTP请求方式：GET<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     validamount:可提现余额，单位分<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码<br />
     *     error_msg:错误信息<br />
     *     sign:签名
     */
    public function drawValidAmount()
    {
        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('drawValidAmount', array());
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error_msg']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error_msg']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 5.1 交易记录查询-已废弃
     * 商户可根据订单号查询交易记录-查询支付手续费使用<br />
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $orderId:商户订单号<br />
     *     $ybOrderId:易宝交易流水号<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     orderid:商户订单号<br />
     *     yborderid:易宝交易流水号<br />
     *     amount:订单金额，单位分<br />
     *     currency:交易币种，默认为人民币156(当前仅支持人民币)<br />
     *     sourcefee:付款方手续费，单位分，当前为0<br />
     *     targetfee:收款方手续费，单位分<br />
     *     sourceamount:付款方实付金额，单位分<br />
     *     targetamount:收款方实付金额，单位分<br />
     *     ordertime:下单时间戳<br />
     *     closetime:交易时间戳<br />
     *     productcatalog:商品类别码<br />
     *     productname:商品名称<br />
     *     productdesc:商品描述<br />
     *     type:支付类型(1:银行卡2:非银行卡)<br />
     *     bank:银行名称<br />
     *     bankcode:银行编码<br />
     *     bankcardtype:银行卡类型(1:储蓄卡2:信用卡)<br />
     *     status:状态(0:待付-创建的订单未支付成功1:已付-订单已经支付成功2:已撤销-待支付订单有效期一天，过期后自动撤销
     *         3:阻断交易-订单因为高风险而被阻断4:失败5:处理中)<br />
     *     refundtotal:累计退款金额，单位分<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码-200002<br />
     *     errormsg:错误信息-未查询到该笔订单信息<br />
     *     sign:签名
     */
    public function merchantQueryServerPaySingle($orderId = '', $ybOrderId = '')
    {
        if (empty($orderId) && empty($ybOrderId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能同时为空');
        }

        // 组织数据
        $requestParams = array();
        // 商户订单号，最长50位-选填(俩参数不可同时为空，都不为空时以易宝交易流水号为准)
        !empty($orderId) && $requestParams['requestno'] = (string)$orderId;
        // 易宝交易流水号-选填
        !empty($ybOrderId) && $requestParams['yborderid'] = (string)$ybOrderId;

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('merchantQueryServerPaySingle', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 5.2 退货退款接口（新投资通-4.10退款接口）
     * 给用户退货退款<br />
     * HTTP请求方式：POST<br />
     * 传入参数：<br />
     *     amount:退款金额，单位分<br />
     *     orderid:客户退货订单号<br />
     *     origyborderid:原易宝交易流水号<br />
     *     cause:退款说明，最长1024字符-选填
     * 返回参数-成功：<br />
     *     merchantno:商户编号<br />
     *     requestno:退款请求号<br />
     *     yborderid:易宝退货流水号<br />
     *     status:订单状态(PROCESSING：退款处理中|REFUND_FAIL：退款失败|FAIL：系统异常)
     *     amount:退款金额<br />
     *     cardtop:卡号前6位<br />
     *     cardlast:卡号后4位<br />
     *     bankcode:银行编码<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息<br />
     *     sign:签名<br />
     * 返回参数-其他：<br />
     *     errorcode:错误码<br />
     *     errormsg:错误信息
     * 注：此时返回的退款成功的信息只是说明易宝接收到了退款请求，具体到账时间需要5-10个工作日
     */
    public function merchantQueryServerDirectRefund($params)
    {
        if (empty($params['requestno']) || empty($params['paymentyborderid']) || bccomp($params['amount'], 0.00, 2) <= 0)
        {
            return array('respCode' => 1001, 'respMsg' => '参数缺失或不能为空');
        }

        // 组织数据
        $requestParams = array(
            'requestno' => (string)$params['requestno'], // 客户退货订单号-必填
            'paymentyborderid' => (string)$params['paymentyborderid'], // 原易宝交易流水号-必填
            'amount' => (string)$params['amount'], // 退款金额，单位：元，,确到两位小数,大于等于 0.01，小于等于订单金额
            'requesttime' => date('Y-m-d H:i:s'),
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('merchantQueryServerDirectRefund', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 5.3 退货退款记录查询
     * 根据退货订单号查询退货退款记录时使用<br />
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $orderId:商户退货订单号<br />
     *     $ybOrderId:易宝退货流水号<br />
     * 返回参数-成功：<br />
     *     merchantaccount:商户编号<br />
     *     orderid:商户退货订单号<br />
     *     yborderid:易宝退货流水号<br />
     *     origyborderid:原易宝交易流水号<br />
     *     amount:退货金额，单位分<br />
     *     currency:交易币种，默认为人民币156(当前仅支持人民币)<br />
     *     fee:退货手续费，单位分，当前为0<br />
     *     ordertime:下单时间戳<br />
     *     closetime:交易时间戳<br />
     *     cause:退款原因
     *     status:状态(4:处理中5:退款成功，具体到账时间信用卡一般1-2个工作日，储蓄卡一般5-10个工作日)<br />
     *     sign:签名<br />
     * 返回参数-失败：<br />
     *     error_code:错误码<br />
     *     error:错误信息<br />
     *     sign:签名
     * 返回参数-其他：<br />
     *     error_code:错误码<br />
     *     error:错误信息
     */
    public function merchantQueryServerRefundSingle($orderId = '', $ybOrderId = '')
    {
        if (empty($orderId) && empty($ybOrderId))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能同时为空');
        }

        // 组织数据
        $requestParams = array();
        // 商户退货订单号，最长50位-选填(俩参数不可同时为空，都不为空时以易宝退货流水号为准)
        !empty($orderId) && $requestParams['orderid'] = (string)$orderId;
        // 易宝退货流水号-选填
        !empty($ybOrderId) && $requestParams['yborderid'] = (string)$ybOrderId;

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('merchantQueryServerRefundSingle', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !isset($response['error_code']) && !isset($response['error']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 5.4 获取消费清算对账单记录
     * 按时间段获取消费清算对账单，该接口实现的功能同商户后台手动下载清算对账文件效果
     * 
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $startDate:开始时间<br />
     *     $endDate:结束时间<br />
     * 返回参数-成功：<br />
     *     文本字符串，格式如下<br />
     *     商户账户编号,清算日期,下单日期,交易时间,客户消费订单号,消费交易流水号,消费金额,实收金额,收款方式,收款方手续费,支付产品,商品类别,商品名称,支付卡类型<br />
     *     YB01000000144,2014-03-07,2014-03-07 10:33:14,2014-03-07 10:33:29,jHS21406w81394159593,411403073799417377,0.02,0.01,实收,0.01,支付API,1,泣鬼神暗黑鬼斧-huang,银行贷记卡<br />
     *     消费金额：0.16<br />
     *     总交易笔数：8<br />
     *     总手续费：0.8<br />
     *     总实收金额：0.8<br />
     * 返回参数-失败：<br />
     *     error_code:错误码-200039<br />
     *     error:错误信息-时间间隔超过31天<br />
     *     sign:签名<br />
     * 注：开始时间与结束时间的时间差不要超过31天，获取清算对账记录时，如果数据过多注意请求超时，如设置为30s或1m
     */
    public function merchantQueryServerPayClearData($startDate, $endDate)
    {
        if (empty($startDate) OR empty($endDate))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = array(
            'startdate' => (string)$startDate, // 开始时间，格式2016-02-18-必填
            'enddate' => (string)$endDate, // 结束时间，格式2016-02-18-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('merchantQueryServerPayClearData', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !is_array($response) && !isset($response['error_code']) && !isset($response['error']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    /**
     * 5.5 获取退款清算对账记录
     * 根据时间段获取退款清算对账记录，该接口实现的功能同商户后台手动下载清算对账文件效果
     *
     * HTTP请求方式：GET<br />
     * 传入参数：<br />
     *     $startDate:开始时间<br />
     *     $endDate:结束时间<br />
     * 返回参数-成功：<br />
     *     文本字符串，格式如下<br />
     *     收款账户编号,清算日期,提交退款日期,退款完成时间,客户退款订单号,退款交易流水号,原消费交易流水号,退款类型,退款金额,收费方式,退款退费,账户实际扣款金额,支付产品,商品类别,商品名称,支付卡类型<br />
     *     10012409909,2015-08-27,2015-08-27 21:49:17,2015-08-27 21:49:18,refund10012409909227224,201508277855752122,411508277841237621,退货退款,2.0,实收,0.02,1.98,移动端WEB支付,1,小薇印象,银行贷记卡<br />
     *     总退款金额：18.32<br />
     *     总退款笔数：31<br />
     *     总退款退费：0.05<br />
     *     总账户实际扣款金额：18.27<br />
     * 返回参数-失败：<br />
     *     error_code:错误码<br />
     *     error:错误信息<br />
     *     sign:签名<br />
     * 注：开始时间与结束时间的时间差不要超过31天，获取清算对账记录时，如果数据过多注意请求超时，如设置为30s或1m
     */
    public function merchantQueryServerRefundClearData($startDate, $endDate)
    {
        if (empty($startDate) OR empty($endDate))
        {
            return array('respCode' => 1001, 'respMsg' => '参数不能为空');
        }

        // 组织数据
        $requestParams = array(
            'startdate' => (string)$startDate, // 开始时间，格式2016-02-18-必填
            'enddate' => (string)$endDate, // 结束时间，格式2016-02-18-必填
        );

        // 请求接口
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->request('merchantQueryServerRefundClearData', $requestParams);
        if (true === $result['ret'])
        {
            $response = $result['result'];
            if (!empty($response) && !is_array($response) && !isset($response['error_code']) && !isset($response['error']))
            {
                // 返回接口数据
                return array('respCode' => '00', 'data' => $response);
            }
            return array('respCode' => $response['error_code'], 'respMsg' => $response['error']);
        }
        return array('respCode' => $result['error_code'], 'respMsg' => $result['error_msg']);
    }

    ############################################################################
    /**
     * 设置充值状态缓存
     */
    public function setChargeStatusCache($userId, $orderSn)
    {
        $redis = self::getRedisSentinels();
        if ($redis)
        {
            return $redis->setex("CHARGE_{$userId}_{$orderSn}", 86400, 1);
        }
    }

    /**
     * 获取充值状态
     */
    public function getChargeStatusCache($userId, $orderSn)
    {
        $redis = self::getRedisSentinels();
        if ($redis)
        {
            return $redis->get("CHARGE_{$userId}_{$orderSn}");
        }
    }

    /**
     * 生成脱敏卡号
     * @param int $cardTop 银行卡号前6位
     * @param int $cardLast 银行卡号后4位
     * @param int $length 中间脱敏字符的长度
     */
    public static function getFormatBankCard($cardTop, $cardLast, $length = 10)
    {
        return sprintf('%s%s%s', $cardTop, str_repeat('*', $length), $cardLast);
    }

    /**
     * 该银行名称是否在易宝支持的16家银行中
     * @param string $bankName
     * @return boolean
     */
    public static function isInYeepayBankList($bankName)
    {
        $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
        if (!empty($bankName) && !empty($quickBankList) && in_array($bankName, $quickBankList))
        {
            return true;
        }
        return false;
    }

    /**
     * 该银行名称是否在支持的16家银行中
     * @param string $bankCode
     * @return boolean
     */
    public static function isInBankListByCode($bankCode)
    {
        empty($quickBankList) && $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'WXLC_BANKLIST');
        if (!empty($bankCode) && !empty($quickBankList[$bankCode]))
        {
            return true;
        }
        return false;
    }

    /**
     * 获取RedisDataCache
     */
    public static function getRedis()
    {
        $redis = \SiteApp::init()->dataCache;
        if (!$redis) {
            PaymentApi::log(sprintf('%s is Failed,Line:%d.', __METHOD__, __LINE__));
            throw new \Exception('获取RedisDataCache异常');
        }
        return $redis;
    }

    /**
     * 获取Redis哨兵
     */
    public static function getRedisSentinels()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            PaymentApi::log(sprintf('%s is Failed,Line:%d.', __METHOD__, __LINE__));
            throw new \Exception('获取Redis哨兵异常');
        }
        return $redis;
    }

    /**
     * 产生随机字符
     * @param $length
     * @param $numeric
     */
    public static function random($length, $numeric = 0)
    {
        $seed = base_convert(md5(print_r($_SERVER, 1).microtime()), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for($i = 0; $i < $length; $i++)
        {
            $hash .= $seed[mt_rand(0, $max)];
        }
        return $hash;
    }



    /**
     * 发送邮件-不带邮件模版
     * @param array $emailList
     * @param string $emailTitle
     * @param string $emailBody
     */
    public static function sendEmail($emailList = array(), $emailTitle, $emailBody)
    {
        if (!is_array($emailList) || empty($emailList))
        {
            return false;
        }
        $emailBody = "<p>{$emailTitle}</p><p>{$emailBody}</p>";
        $msgcenter = new \msgcenter();
        $msgcenter->setMsg(join(',', $emailList), 0, $emailBody, false, $emailTitle);
        return $msgcenter->save();
    }

    /**
     * 获取易宝补单最小订单ID
     */
    public function getMinRepairOrderId()
    {
        $repairOrderRetryList = self::getRedisSentinels()->sMembers(self::CACHEKEY_YEEPAY_ORDERREPAIR_RETRYLIST);
        if (empty($repairOrderRetryList) || !is_array($repairOrderRetryList))
        {
            // 获取配置的订单ID
            $repairOrderMinId = app_conf('YEEPAY_ORDER_REPAIR_MINID');
        }else{
            // 获取最小的订单ID
            $repairOrderMinId = min($repairOrderRetryList);
        }
        $repairOrderMinId > 0 || $repairOrderMinId = self::DEFAULT_REPAIR_ORDER_MINID;
        return array('minId'=>$repairOrderMinId, 'repairOrderRetryList'=>(array)$repairOrderRetryList);
    }

    /**
     * 设置易宝补单ID
     * @param int $id
     */
    public function addMinRepairOrderId($id)
    {
        return self::getRedisSentinels()->sAdd(self::CACHEKEY_YEEPAY_ORDERREPAIR_RETRYLIST, $id);
    }

    /**
     * 设置易宝补单列表总数
     * @param int $id
     */
    public function getRepairRetryListCount()
    {
        return self::getRedisSentinels()->sCard(self::CACHEKEY_YEEPAY_ORDERREPAIR_RETRYLIST);
    }

    /**
     * 清空易宝补单列表
     */
    public function clearRepairRetryList()
    {
        return self::getRedisSentinels()->del(self::CACHEKEY_YEEPAY_ORDERREPAIR_RETRYLIST);
    }

    /**
     * 踢出指定易宝补单列表中的某个ID
     * @param int $id
     */
    public function remRepairRetryId($id)
    {
        return self::getRedisSentinels()->sRem(self::CACHEKEY_YEEPAY_ORDERREPAIR_RETRYLIST, $id);
    }

    /**
     * 根据配置的进程数，踢出指定易宝补单列表中的某个ID
     * @param int $id 充值订单ID
     * @param int $repairOrderProcess 易宝补单进程数量
     */
    public function remRepairRetryIdByProcess($id, $repairOrderProcess = 0)
    {
        // 当前重试列表中，订单ID的个数
        $repairRetryListCount = $this->getRepairRetryListCount();
        // 获取配置的[易宝补单进程数量]
        $repairOrderProcess > 0 || $repairOrderProcess = app_conf('YEEPAY_ORDER_REPAIR_PROCESS');
        $repairOrderProcess > 0 || $repairOrderProcess = self::DEFAULT_REPAIR_ORDER_PROCESS;
        // [重试ID数]超过[补单进程数]时，再踢出该订单ID
        if($repairRetryListCount > $repairOrderProcess)
        {
            // 踢出该订单ID
            $this->remRepairRetryId($id);
            return true;
        }
        return false;
    }

    /**
     * 充值成功处理
     */
    private function _yeePayChargeSuccess($paymentNoticeInfo, $params, $amountLimit = '')
    {
        if ($paymentNoticeInfo['is_paid'] == PaymentNoticeModel::IS_PAID_SUCCESS)
        {
            return array('respCode' => '00', 'respMsg' => '充值单支付成功已受理');
        }

        if ($paymentNoticeInfo['is_paid'] == PaymentNoticeModel::IS_PAID_FAIL)
        {
            PaymentApi::log(sprintf('%s|订单ID:%d，订单编号：%s|拒绝受理，充值单已经为失败状态|易宝的充值状态为成功，理财为失败', __METHOD__, $paymentNoticeInfo['id'], $paymentNoticeInfo['notice_sn']), Logger::ERR);
            return array('respCode' => 1001, 'respMsg' => '拒绝受理, 充值单已经为失败状态');
        }

        try {
            // 易宝的交易流水号
            $ybOrderId = addslashes($params['yborderid']);
            // 支付时间，时间戳
            $payTime = isset($params['closetime']) && !empty($params['closetime']) ? ($params['closetime'] - date('Z')) : 0;
            $GLOBALS['db']->startTrans();
            $chargeService = new ChargeService();
            $ret = $chargeService->paidSuccess($paymentNoticeInfo, $ybOrderId, $amountLimit, $payTime);
            if ($ret === false)
            {
                throw new \Exception('充值单支付成功受理失败');
            }

            // 易宝充值扣费
            $this->_transferYeepayPayerMoney($paymentNoticeInfo);

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log(sprintf('%s|orderId:%d|noticeSn:%s|充值单支付成功受理异常,_yeePayChargeSuccess()受理异常|Exception：%s', __METHOD__, $paymentNoticeInfo['id'], $paymentNoticeInfo['notice_sn'], $e->getMessage()), Logger::ERR);
            Alarm::push('yeepay_callback_order', '充值单支付成功受理异常', '_yeePayChargeSuccess()受理异常:' . $e->getMessage() . '|orderId:' . $paymentNoticeInfo['id']);
            return array('respCode' => 1002, 'respMsg' => '充值单支付成功受理异常：' . $e->getMessage());
        }

        // 触发O2O请求
        $paymentService = new \core\service\PaymentService();
        $paymentService->chargeTriggerO2O($paymentNoticeInfo);

        // 为该用户设置充值状态缓存
        $this->setChargeStatusCache($paymentNoticeInfo['user_id'], $paymentNoticeInfo['notice_sn']);

        return array('respCode' => '00', 'respMsg' => '充值单支付成功受理成功');
    }

    /**
     * 充值失败处理
     */
    private function _yeePayChargeFailue($paymentNoticeInfo, $params)
    {
        if ($paymentNoticeInfo['is_paid'] == PaymentNoticeModel::IS_PAID_FAIL)
        {
            return array('respCode' => '00', 'respMsg' => '充值单支付失败已受理');
        }

        if ($paymentNoticeInfo['is_paid'] == PaymentNoticeModel::IS_PAID_SUCCESS)
        {
            PaymentApi::log(sprintf('%s|拒绝受理，充值单已经为成功状态|易宝的充值状态为失败，理财为成功|订单ID：%d', __METHOD__, $paymentNoticeInfo['id']), Logger::ERR);
            return array('respCode' => 1001, 'respMsg' => '拒绝受理, 充值单已经为成功状态');
        }

        // 易宝的交易流水号
        $ybOrderId = isset($params['yborderid']) ? addslashes($params['yborderid']) : '';
        // 充值订单表的备注字段(易宝的错误编号、错误消息)
        $memo = (isset($params['errorcode']) && isset($params['errormsg'])) ? $params['errorcode'] . '|' . $params['errormsg'] : '';
        // 更新充值订单状态为失败
        $GLOBALS['db']->update('firstp2p_payment_notice', array('is_paid'=>PaymentNoticeModel::IS_PAID_FAIL, 'outer_notice_sn'=>$ybOrderId, 'memo'=>$memo, 'update_time'=>get_gmtime()), sprintf('id=%d AND is_paid IN (%d, %d)', $paymentNoticeInfo['id'], PaymentNoticeModel::IS_PAID_NO, PaymentNoticeModel::IS_PAID_ING));
        if ($GLOBALS['db']->affected_rows() != 1)
        {
            PaymentApi::log(sprintf('%s|orderId:%d|noticeSn:%s|充值单支付失败受理失败', __METHOD__, $paymentNoticeInfo['id'], $paymentNoticeInfo['notice_sn']), Logger::ERR);
            Alarm::push('yeepay_callback_order', '充值单支付失败受理失败', "订单状态更新失败|orderId:{$paymentNoticeInfo['id']}");
            return array('respCode' => 1002, 'respMsg' => '充值单支付失败受理失败');
        }

        return array('respCode' => '00', 'respMsg' => '充值单支付失败受理成功');
    }

    /**
     * 易宝充值扣费逻辑
     * @param array $paymentNoticeInfo 充值信息
     * @throws \Exception
     */
    private function _transferYeepayPayerMoney($paymentNoticeInfo)
    {
        if (empty($paymentNoticeInfo))
        {
            throw new \Exception('订单信息不存在');
        }

        // 获取[易宝转账用户ID]
        $yeepayTranferUid = app_conf('YEEPAY_TRANFER_UID');
        if (!is_numeric($yeepayTranferUid) || $yeepayTranferUid <= 0)
        {
            throw new \Exception('尚未配置易宝转账帐号UID，导致无法插入转账队列');
        }
        // 获取[易宝转账用户]信息
        $userModel = UserModel::instance()->find($yeepayTranferUid);
        if (!$userModel)
        {
            throw new \Exception('易宝转账用户不存在');
        }

        // [易宝转账用户]转账处理
        $syncRemoteData = array();
        $syncRemoteData[] = array(
            'outOrderId' => $paymentNoticeInfo['notice_sn'],
            'payerId' => $yeepayTranferUid, // 付款者用户ID
            'receiverId' => $paymentNoticeInfo['user_id'], // 接收者用户ID
            'repaymentAmount' => bcmul($paymentNoticeInfo['money'], 100), // 转账金额，以分为单位
            'curType' => 'CNY',
            'bizType' => FinanceQueueModel::PAYQUEUE_BIZTYPE_8,
        );
        $financeQueueResult = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
        if (empty($financeQueueResult))
        {
            throw new \Exception('同步资金平台插入转账队列失败');
        }

        // [易宝转账用户]扣除余额处理
        $userModel->changeMoneyAsyn = true;
        $userModel->changeMoney(-$paymentNoticeInfo['money'], '易宝充值扣费', $paymentNoticeInfo['notice_sn'], 0, 0, 0, 1);
        $userModel->changeMoneyAsyn = false;

        // 获取[易宝转账阈值]，单位元
        $yeepayTranferThreshold = app_conf('YEEPAY_TRANFER_THRESHOLD') ? app_conf('YEEPAY_TRANFER_THRESHOLD') : 0;
        // [易宝转账用户余额]低于[后台易宝转账阈值]
        if (bccomp($userModel->money, $yeepayTranferThreshold, 2) <= 0)
        {
            // 记录日志、告警等
            $msg = sprintf('[易宝转账用户余额]低于[后台易宝转账阈值]|订单ID：%d，订单编号：%s|易宝转账用户ID：%s，易宝转账用户余额：%s元，后台易宝转账阈值：%s元', $paymentNoticeInfo['id'], $paymentNoticeInfo['notice_sn'], $yeepayTranferUid, $userModel->money, $yeepayTranferThreshold);
            PaymentApi::log($msg, Logger::ERR);
            // 通过Alarm进行汇总告警
            Alarm::push('yeepay_callback_tranfer', '[易宝转账用户余额]低于[后台易宝转账阈值]', $msg);
            // 发送邮件、短信告警
            //$this->_emailMobileAlarmForYeepay($msg, $msg, '[易宝转账用户余额]低于[后台易宝转账阈值]');
        }
        // [易宝转账用户余额]低于[充值订单金额]
        if (bccomp($userModel->money, $paymentNoticeInfo['money'], 2) <= 0)
        {
            // 记录日志、告警等
            $msg = sprintf('[易宝转账用户余额]低于[充值订单金额]|订单ID：%d，订单编号：%s|易宝转账用户ID：%s，易宝转账用户余额：%s元，充值订单金额：%s元', $paymentNoticeInfo['id'], $paymentNoticeInfo['notice_sn'], $yeepayTranferUid, $userModel->money, $paymentNoticeInfo['money']);
            PaymentApi::log($msg, Logger::ERR);
            // 通过Alarm进行汇总告警
            Alarm::push('yeepay_callback_tranfer', '[易宝转账用户余额]低于[充值订单金额]', $msg);
            // 发送邮件、短信告警
            //$this->_emailMobileAlarmForYeepay($msg, $msg, '[易宝转账用户余额]低于[充值订单金额]');
        }
        unset($userModel, $paymentNoticeInfo);
    }

    /**
     * 读取银行名称
     */
    public function getBankNameByCode($bankCode) {
        $bankCodes = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
        if (!empty($bankCodes[$bankCode])) {
            return $bankCodes[$bankCode];
        }
        return '未知银行';
    }

    /**
     * 日志记录
     */
    private static function log($body, $level = Logger::INFO)
    {
        $destination = APP_ROOT_PATH.'log/logger/YeepayPaymentService.'.date('y_m').'.log';
        Logger::wLog($body, $level, Logger::FILE, $destination);
    }

    /**
     * 检查用户绑定的先锋支付银行卡是否在易宝的银行卡列表中
     * @param array $bankcard 用户在先锋支付绑定的银行卡卡号
     * @param array $yeepayCardList 易宝的绑卡列表
     * @return mixed <boolean|array>  boolean false 用户未绑定该卡, array 用户已经绑定此卡且有效
     */
    public function checkCardIsExists($bankcard, $yeepayCardList)
    {
        if (!is_string($bankcard) || !is_array($yeepayCardList))
        {
            return false;
        }
        foreach ($yeepayCardList as $yeepayCard)
        {
            // 如果用户的银行卡存在于易宝的绑卡列表里,则直接返回卡信息
            if (substr($bankcard, 0, 6) == $yeepayCard['cardtop'] && substr($bankcard, strlen($bankcard) - 4, 4) == $yeepayCard['cardlast'])
            {
                return $yeepayCard;
            }
        }
        return false;
    }

    /**
     * 获取客服电话
     * @param string $key api_conf表的name字段
     * @param int $siteId 站点ID
     * @param int $confType 配置类型
     */
    public static function getCustomerPhone($key = 'param_service_telephone', $siteId = 1, $confType = 0) {
        // 获取客服电话
        $configInfo = \SiteApp::init()->dataCache->call(new \libs\rpc\Rpc(), 'local', array('ApiConfService\getApiAdvConf', array($key, $siteId, $confType)), 3600);
        if (!empty($configInfo[0]['value'])) {
            return $configInfo[0]['value'];
        } else {
            return '95782';
        }
    }
}
