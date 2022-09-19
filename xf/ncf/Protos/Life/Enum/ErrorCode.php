<?php

namespace NCFGroup\Protos\Life\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ErrorCode extends AbstractEnum {
    // protos常量
    const RPC_SUCCESS = 0;
    const RPC_FAILD = 1;

    // 支付系统常量
    const RESPONSE_CODE = '00000';
    const RESPONSE_NOTIFY_CODE = 'SUCCESS';
    const RESPONSE_SUCCESS = 'S';
    const RESPONSE_FAILURE = 'F';
    const RESPONSE_PROCESSING = 'I';
    //虚拟号码接口返回值
    const VN_RESPONSE_CODE_SUCCESS = 0;

    // 系统相关
    const SUCCESS = '0';
    const MISS_PARAMETERS = '100001';
    const PARAMETERS_ERROR = '100002';
    const DB_UNKNOW_ERROR = '100003';
    const DB_INSERT_ERROR = '100004';
    const DB_UPDATE_ERROR = '100005';
    const ERR_IDWORKER = '100006';
    const PLATFORM_UNDEFINED = '100007';
    const DB_DESKEY_ERROR = '100008';
    const UCFPAY_REQUEST_ERROR = '100009';
    const CONFIG_ERROR = '100010';
    const UCFPAY_RESULT_ERROR = '100011';
    const SIGN_VERIFY_ERROR = '100012';
    const UCFPAY_NOTIFY_FAILED = '100013';
    const JOBS_ADD_FAILED = '100014';
    const JOBS_EXEC_FAILED = '100015';
    const JOBS_NETWORK_FAILED = '100016';
    const NETWORK_REQUEST_TIMEOUT = '100017';
    const STATUS_UNDEFINED = '100018';
    const PAYMENT_NOTIFY_BUSINESS_FAILED = '100019';
    const ERR_DATETIME_RANGE = '100020';
    const ERR_MONEY_RANGE = '100021';
    const VIRTUAL_NUMBER_CLOSE = '100022';
    const ERR_REQUEST_FREQUENTLY = '100023';
    const ERR_TOKEN_DECRY = '100024';
    const ERR_TOKEN_CHECK = '100025';

    // 网信出行相关
    const WXCX_COMMON_ERROR = '110000';
    const WXCX_CREATE_FAILED = '110001';
    const WXCX_CREATEDETAIL_FAILED = '110002';
    const WXCX_CREATEINVOICE_FAILED = '110003';
    const WXCX_UPDATEINVOICE_FAILED = '110004';
    const WXCX_UPDATEUSERINVOICE_FAILED = '110005';
    const WXCX_CALLTHIRD_FAILED = '110006';
    const WXCX_INVOICEMONEY_NOTENOUGH = '110007';
    const WXCX_USER_NOTEXIST = '110008';
    const WXCX_TRIPORDER_NOTEXIST = '110009';
    const WXCX_TRIPORDER_HASPROCESS = '110010';
    const WXCX_TRIPORDERUPDATE_FAILED = '110011';
    const WXCX_CREATESTATUS_FAILED = '110012';
    const WXCX_TRIPORDERDETAILUPDATE_FAILED = '110013';
    const WXCX_INVOICEORDER_NOTEXIST = '110014';
    const WXCX_STATUS_FAILED = '110015';
    const WXCX_USER_MOBILE_EMPTY = '110016';
    const ERR_PARAMS_ERROR = '110017';
    const ERR_MANUAL_REASON = '110018';
    const ERR_SYSTEM_ACTION_PERMISSION = '110019';
    const ERR_SYSTEM_CLIENTID = '110020';
    const ERR_SYSTEM_TIME = '110021';
    const ERR_SYSTEM_SIGN_NULL = '110022';
    const ERR_SYSTEM_SIGN = '110023';
    const ERR_ACTION_UNDEFINED = '110024';
    const WXCX_PAYMENT_HASDISPOSE = '110025';
    const WXCX_PAYMENT_MERCHANT_NOTEXIST = '110026';
    const WXCX_PAYMENTMAP_CREATEFAILED = '110027';
    const WXCX_PAYMENTMAP_UPDATEFAILED = '110028';
    const WXCX_PAYMENTORDER_CREATEFAILED = '110029';
    const WXCX_PAYMENTORDER_UPDATEFAILED = '110030';
    const WXCX_PAYMENTORDER_NOTEXIST = '110031';
    const WXCX_SECTION_CREATEFAILED = '110032';
    const WXCX_SECTION_UPDATEFAILED = '110033';
    const WXCX_SECTION_NOTEXIST = '110034';
    const WXCX_PARAMS_ERROR = '110035';
    const WXCX_MERCHANT_CREATEFAILED = '110036';
    const WXCX_MERCHANT_UPDATEFAILED = '110037';
    const WXCX_MERCHANT_NOTEXIST = '110038';
    const WXCX_SECPAYMENT_CREATEFAILED = '110039';
    const WXCX_SECPAYMENT_UPDATEFAILED = '110040';
    const WXCX_BINDCARD_CREATEFAILED = '110041';
    const WXCX_BINDCARD_UPDATEFAILED = '110042';
    const WXCX_BINDCARD_EMPTY = '110043';
    const WXCX_BINDCARD_AUTHFAILED = '110044';
    const WXCX_BINDCARD_NOTEXIST = '110045';
    const WXCX_CONFIG_SAVEFAILED = '110046';
    const WXCX_CREATE_ORDER_BOOKTIME_TOOLARGE = '110047';
    const WXCX_REFUND_AMOUNT_FAIL = '110048';
    const WXCX_REFUNDORDER_CREATEFAILED = '110049';
    const WXCX_REFUNDORDER_UPDATEFAILED = '110050';
    const WXCX_REFUNDORDER_GETFAILED = '110051';
    const WXCX_REFUNDORDER_GET_REFUNDING_FAILED = '110052';
    const WXCX_REFUNDORDER_AND_UCFPAY_AMOUNTDIFF = '110053';
    const WXCX_PAYMENTORDER_NOTSUCCESS = '110054';
    const WXCX_USERAUTHFAILED = '110055';
    const WXCX_GETUSER_ADDRESS_LIST_FAILED = '110056';
    const WXCX_GETUSER_DEFAULT_ADDRESS_LIST_FAILED = '110057';
    const WXCX_ADDUSER_ADDRESS_FAILED = '110058';
    const WXCX_EDITUSER_ADDRESS_FAILED = '110059';
    const WXCX_INVOICE_LIST_NULL = '110060';
    const PAYMENT_CONSUMEORDER_NOTEXIST = '110061';
    const ERR_SYSTEM_MERCHANTID = '110062';
    const WXCX_USER_BINDCARD_FAILED = '110063';
    const UCFPAY_NOTIFY_QUERY_FAILED = '110064';
    const UCFPAY_NOTIFY_STATUS_FAILED = '110065';
    const UCFPAY_NOTIFY_AMOUNT_FAILED = '110066';
    const PAYMENT_STATUS_HAS_SUCCESS = '110067';
    const PAYMENT_STATUS_HAS_FAILED = '110068';
    const PAYMENT_STATUS_HAS_PROCESS = '110069';
    const UCFPAY_NOTIFY_BUSINESS_FAILED = '110070';
    const WXCX_TRIP_CHILD_ORDER_NOTEXIST = '110071';
    const PAYMENT_BUSINESS_UPDATE_FAILED = '110072';
    const PAYMENT_BUSINESSORDER_NOTEXIST = '110073';
    const WXCX_BUSINESS_ORDER_HASDISPOSE = '110074';
    const PAYMENT_ORDER_NOTSAME = '110075';
    const PAYMENT_AMOUNT_NOTSAME = '110076';
    const UCFPAY_NOPWDPAY_STATUS_RETRY = '110077';
    const WXCX_PAYMENTSUBORDER_NOTEXIST = '110078';
    const WXCX_PAYMENTSUBORDER_HAS_FINISH = '110079';
    const ERR_NOTIFY_PARAMS_ERROR = '110080';
    const PAYMENT_ORDER_DIVISION_NOTEXIST = '110081';
    const PAYMENT_ORDER_DIVISION_HAS_FINISH = '110082';
    const WXCX_TRIP_DIVISION_NOTEXIST = '110083';
    const WXCX_TRIP_DIVISION_HAS_FINISH = '110084';
    const WXCX_TRIP_DIVISION_MAP_NOTEXIST = '110085';
    const WXCX_TRIP_DIVISION_MAP_FINISH = '110086';
    const WXCX_PAYMENTSUBORDER_SUCCESS_NOTEXIST = '110087';
    const WXCX_USERLOG_ADD_FAILED = '110088';
    const WXCX_PAYMENT_ORDER_HASDISPOSE = '110089';
    const WXCX_TRIP_ORDER_HAS_FINISH = '110090';
    const WXCX_TRIP_ORDER_HAS_DISPOSE = '110091';
    const WXCX_TRIP_ORDER_NOT_SUCCESS = '110092';
    const WXCX_USER_NOT_WHITELIST = '110093';
    const CREATE_ORDER_UNAUTHORIZED_CODE = '110094';
    const CREATE_ORDER_BLACK_LIST_CODE = '110095';
    const CREATE_ORDER_NOT_CARD_CODE = '110096';
    const CREATE_OVERRUN_CODE = '110097';
    const CREATE_ORDER_ARREARS_MONEY_CODE = '110098';
    const CREATE_ORDER_UNPAID_CODE = '110099';
    const CREATE_ORDER_UNDER_WAY_CODE = '110100';
    const CANCEL_ORDER_TRY_COST_CODE = '110101';
    const CANCEL_ORDER_TRY_NO_COST_CODE = '110102';
    const WXCX_VNCALLRECORD_ADD_FAILED = '110103';
    const WXCX_TRIPORDER_NOTWORKING = '110104';
    const WXCX_USER_HASNOT_IDPASS = '110105';
    const WXCX_TRIPMAP_CREATEFAILED = '110106';
    const WXCX_TRIPMAP_UPDATEFAILED = '110107';
    // 虚拟号码
    const VN_BIND_FAILED   = '110108';
    const VN_UNBIND_FAILED = '110109';
    const VN_UPDATE_FAILED = '110110';
    const VN_NOTIFY_FAILED = '110111';
    const WXCX_SYSTEM_PAYMENTORDER_FAILED = '110112';
    const WXCX_USER_PAYMENTORDER_FAILED = '110113';
    const JOBS_PAYMENT_SYSTEM_ADD_FAILED = '110114';
    const JOBS_TRIP_SYSTEM_ADD_FAILED = '110115';
    const WXCX_BONUS_SEND_FAILED = '110116';
    const WXCX_BONUS_TRIP_UPDATE_FAILED = '110117';
    const WXCX_PAYMENT_DISPOSEING = '110118';
    const WXCX_BONUS_TRIP_HAS_SEND = '110119'; 
    const P2P_USER_BINDCARD_FAILED = '110120';
    const PAYMENT_CARDTYPE_NOT_SUPPORT = '110121';
    const PAYMENT_VERIFY_PASSWD_FAILED = '110122';
    const WXCX_CANNOT_UNBIND_CARD = '110123';
    const WXCX_INVOICE_COMPANYCODE_EMPTY = '110124';
    const WXCX_INVOICEMONEY_HAS_PROCESS = '110125';
    const JOBS_TRIP_ADD_FAILED = '110126';
    const DIVISION_ORDER_UPDATE_FAILED = '110127';
    const WXCX_PAYMENT_HASTIMEOUT = '110128';
    const WXCX_PAYMENTORDER_HAS_FINISH = '110129';
    const WXCX_TRIPORDER_HAS_FINISH = '110130';
    const WXCX_TRIPORDER_HAS_PROCESS = '110131';
    const WXCX_BONUS_UID_EMPTY = '110132';
    const WXCX_INVOICE_HAS_FINISH = '110133';

    // 错误码描述
    public static $errMsg = array (
        self::SUCCESS => 'SUCCESS',
        self::RESPONSE_NOTIFY_CODE => '成功', 
        self::MISS_PARAMETERS => 'Miss parameters %s!',
        self::PARAMETERS_ERROR => 'Parameters error',
        self::DB_UNKNOW_ERROR => 'Database unknow error',
        self::DB_INSERT_ERROR => '插入数据失败',
        self::DB_UPDATE_ERROR => '更新数据失败',
        self::ERR_IDWORKER => '获取GID失败',
        self::PLATFORM_UNDEFINED => '未定义的平台',
        self::DB_DESKEY_ERROR => 'db des key is null',
        self::UCFPAY_REQUEST_ERROR => '支付系统返回结果的格式不正确',
        self::CONFIG_ERROR => '配置错误',
        self::UCFPAY_RESULT_ERROR => '支付系统数据解析错误',
        self::SIGN_VERIFY_ERROR => '签名校验失败',
        self::UCFPAY_NOTIFY_FAILED => '支付系统异步回调失败',
        self::JOBS_ADD_FAILED => '添加Jobs失败',
        self::JOBS_EXEC_FAILED => 'Jobs执行失败',
        self::JOBS_NETWORK_FAILED => '网络请求超时或受理失败',
        self::NETWORK_REQUEST_TIMEOUT => '网络请求超时',
        self::STATUS_UNDEFINED => '未定义或不合法的状态',
        self::PAYMENT_NOTIFY_BUSINESS_FAILED => '收银台异步回调第三方业务失败',
        self::ERR_DATETIME_RANGE => '时间区间不正确',
        self::ERR_MONEY_RANGE => '金额区间不正确',
        self::VIRTUAL_NUMBER_CLOSE => '虚拟号码服务开关已关闭',
        self::ERR_REQUEST_FREQUENTLY => '请求过于频繁，请稍后再试',
        self::ERR_TOKEN_DECRY => '用户token解析失败',
        self::ERR_TOKEN_CHECK => '用户token验证失败',

        // 网信出行
        self::WXCX_COMMON_ERROR => '系统错误',
        self::WXCX_CREATE_FAILED => '创建网信出行订单失败',
        self::WXCX_CREATEDETAIL_FAILED => '创建网信出行详情订单失败',
        self::WXCX_CREATEINVOICE_FAILED => '提交发票申请数据失败',
        self::WXCX_UPDATEINVOICE_FAILED => '更新发票申请数据失败',
        self::WXCX_UPDATEUSERINVOICE_FAILED => '更新可开发票余额失败',
        self::WXCX_CALLTHIRD_FAILED => '调用第三方出行平台接口失败',
        self::WXCX_INVOICEMONEY_NOTENOUGH => '可开发票金额不足',
        self::WXCX_USER_NOTEXIST => '用户不存在',
        self::WXCX_TRIPORDER_NOTEXIST => '网信出行订单不存在',
        self::WXCX_TRIPORDER_HASPROCESS => '网信出行订单状态已处理',
        self::WXCX_TRIPORDERUPDATE_FAILED => '网信出行订单更新失败',
        self::WXCX_CREATESTATUS_FAILED => '插入网信出行订单状态表失败',
        self::WXCX_TRIPORDERDETAILUPDATE_FAILED => '网信出行订单详情更新失败',
        self::WXCX_INVOICEORDER_NOTEXIST => '网信出行发票订单不存在',
        self::WXCX_STATUS_FAILED => '状态参数错误',
        self::WXCX_USER_MOBILE_EMPTY => '用户手机号为空',
        self::ERR_PARAMS_ERROR => '请求参数不正确',
        self::ERR_MANUAL_REASON => '自定义错误',
        self::ERR_SYSTEM_ACTION_PERMISSION => '无权限访问此接口',
        self::ERR_SYSTEM_CLIENTID => 'client ID 无效',
        self::ERR_SYSTEM_MERCHANTID => 'merchant_id无效',
        self::ERR_SYSTEM_TIME => 'timestamp无效',
        self::ERR_SYSTEM_SIGN_NULL => '缺少sign参数',
        self::ERR_SYSTEM_SIGN => '签名校验失败',
        self::ERR_ACTION_UNDEFINED => 'Action Is Not Found',
        self::WXCX_PAYMENT_HASDISPOSE => '该出行订单已处理或正在处理中',
        self::WXCX_PAYMENT_MERCHANT_NOTEXIST => '商户信息不存在',
        self::WXCX_PAYMENTMAP_CREATEFAILED => '创建订单支付关系记录失败',
        self::WXCX_PAYMENTMAP_UPDATEFAILED => '更新订单支付关系记录失败',
        self::WXCX_PAYMENTORDER_CREATEFAILED => '创建支付订单失败',
        self::WXCX_PAYMENTORDER_UPDATEFAILED => '更新支付订单失败',
        self::WXCX_PAYMENTORDER_NOTEXIST => '支付订单不存在',
        self::WXCX_PAYMENTORDER_NOTSUCCESS => '支付订单未完成支付',
        self::WXCX_SECTION_CREATEFAILED => '创建版块失败',
        self::WXCX_SECTION_UPDATEFAILED => '更新版块失败',
        self::WXCX_SECTION_NOTEXIST => '版块信息不存在',
        self::WXCX_PARAMS_ERROR => '参数错误或不合法',
        self::WXCX_MERCHANT_CREATEFAILED => '创建商户失败',
        self::WXCX_MERCHANT_UPDATEFAILED => '更新商户失败',
        self::WXCX_MERCHANT_NOTEXIST => '商户信息不存在',
        self::WXCX_SECPAYMENT_CREATEFAILED => '创建版块支付方式失败',
        self::WXCX_SECPAYMENT_UPDATEFAILED => '更新版块支付方式失败',
        self::WXCX_BINDCARD_CREATEFAILED => '用户绑卡记录创建失败',
        self::WXCX_BINDCARD_UPDATEFAILED => '用户绑卡记录更新失败',
        self::WXCX_BINDCARD_EMPTY => '您尚未绑定信用卡',
        self::WXCX_BINDCARD_AUTHFAILED => '信用卡预授权失败',
        self::WXCX_BINDCARD_NOTEXIST => '该绑卡数据不存在或已解绑',
        self::WXCX_CONFIG_SAVEFAILED => '配置信息保存失败',
        self::WXCX_CREATE_ORDER_BOOKTIME_TOOLARGE => '用车时间不能超过当前时间三个月',
        self::WXCX_REFUND_AMOUNT_FAIL => '退款金额大于可退金额',
        self::WXCX_REFUNDORDER_CREATEFAILED => '退款单创建失败',
        self::WXCX_REFUNDORDER_UPDATEFAILED => '更新退款单失败',
        self::WXCX_REFUNDORDER_GETFAILED => '退款单查找失败',
        self::WXCX_REFUNDORDER_GET_REFUNDING_FAILED => '未找到退款中的退款单',
        self::WXCX_REFUNDORDER_AND_UCFPAY_AMOUNTDIFF => '退款单和先锋退款结果金额不一致',
        self::WXCX_USERAUTHFAILED => '进入网信出行授权失败',
        self::WXCX_GETUSER_ADDRESS_LIST_FAILED => '获取邮寄地址失败',
        self::WXCX_GETUSER_DEFAULT_ADDRESS_LIST_FAILED => '获取用户默认邮寄地址失败',
        self::WXCX_ADDUSER_ADDRESS_FAILED => '添加用户邮寄地址失败',
        self::WXCX_EDITUSER_ADDRESS_FAILED => '编辑用户邮寄地址失败',
        self::WXCX_INVOICE_LIST_NULL => '可开发票列表为空',
        self::WXCX_PAYMENT_DISPOSEING => '该订单正在处理中',
        self::PAYMENT_BUSINESSORDER_NOTEXIST => '该业务订单不存在',
        self::WXCX_USER_BINDCARD_FAILED => '绑卡失败',
        self::UCFPAY_NOTIFY_QUERY_FAILED => '先锋支付快捷免密支付异步通知，订单查询接口报错',
        self::UCFPAY_NOTIFY_STATUS_FAILED => '先锋支付快捷免密支付异步通知的订单状态跟订单查询接口的状态不一致',
        self::UCFPAY_NOTIFY_AMOUNT_FAILED => '先锋支付快捷免密支付异步通知的金额有误',
        self::PAYMENT_STATUS_HAS_SUCCESS => '支付订单已经是成功状态',
        self::PAYMENT_STATUS_HAS_FAILED => '支付订单已经是失败状态',
        self::PAYMENT_STATUS_HAS_PROCESS => '支付订单已经是处理中状态',
        self::UCFPAY_NOTIFY_BUSINESS_FAILED => '异步通知第三方业务失败',
        self::WXCX_TRIP_CHILD_ORDER_NOTEXIST => '业务子订单不存在',
        self::PAYMENT_BUSINESS_UPDATE_FAILED => '业务订单更新失败',
        self::WXCX_BUSINESS_ORDER_HASDISPOSE => '该订单已处理',
        self::PAYMENT_ORDER_NOTSAME => '支付订单中的消费订单号不一致',
        self::PAYMENT_AMOUNT_NOTSAME => '支付订单中的金额不一致',
        self::UCFPAY_NOPWDPAY_STATUS_RETRY => '先锋支付快捷免密支付返回处理中，继续重试',
        self::WXCX_PAYMENTSUBORDER_NOTEXIST => '支付子订单不存在',
        self::WXCX_PAYMENTSUBORDER_HAS_FINISH => '支付子订单已终态',
        self::ERR_NOTIFY_PARAMS_ERROR => '回调参数不正确',
        self::PAYMENT_ORDER_DIVISION_NOTEXIST => '分账订单不存在',
        self::PAYMENT_ORDER_DIVISION_HAS_FINISH => '分账订单已终态',
        self::WXCX_TRIP_DIVISION_NOTEXIST => '出行分账订单不存在',
        self::WXCX_TRIP_DIVISION_HAS_FINISH => '出行分账订单已终态',
        self::WXCX_TRIP_DIVISION_MAP_NOTEXIST => '出行分账关系不存在',
        self::WXCX_TRIP_DIVISION_MAP_FINISH => '出行分账关系已终态',
        self::WXCX_PAYMENTSUBORDER_SUCCESS_NOTEXIST => '该支付订单没有成功的子订单',
        self::WXCX_USERLOG_ADD_FAILED => '用户交易记录添加失败',
        self::WXCX_PAYMENT_ORDER_HASDISPOSE => '该支付订单已处理或正在处理中',
        self::WXCX_TRIP_ORDER_HAS_FINISH => '该出行订单已经退过款',
        self::WXCX_TRIP_ORDER_HAS_DISPOSE => '该出行订单有成功或正在处理中的退款订单',
        self::WXCX_TRIP_ORDER_NOT_SUCCESS => '该出行订单尚未支付成功',
        self::WXCX_USER_NOT_WHITELIST => '用户不在白名单列表内',
        self::CREATE_ORDER_UNAUTHORIZED_CODE => '用户尚未授权网信出行',
        self::CREATE_ORDER_BLACK_LIST_CODE => '因风控因素暂不支持下单，如有疑问请联系客服%s',
        self::CREATE_ORDER_NOT_CARD_CODE => '用车前，请先绑定您的一张%s以便支付车费',
        self::CREATE_OVERRUN_CODE => '您已超过叫车次数限制，待服务中订单结束后再下单',
        self::CREATE_ORDER_ARREARS_MONEY_CODE => '近期您的消费欠款已达到%s元，请补交',
        self::CREATE_ORDER_UNPAID_CODE => '您有尚未支付的订单，暂时无法下单',
        self::CREATE_ORDER_UNDER_WAY_CODE => '您有进行中的订单，暂时无法下单',
        self::CANCEL_ORDER_TRY_COST_CODE => '操作超时，现取消需收费%s元',
        self::CANCEL_ORDER_TRY_NO_COST_CODE => '再等等吧，快为您找到司机了',
        self::WXCX_VNCALLRECORD_ADD_FAILED => '添加虚拟号码话单推送记录失败',
        self::WXCX_TRIPORDER_NOTWORKING => '不是进行中订单',
        self::WXCX_USER_HASNOT_IDPASS => '该用户尚未实名认证',
        self::WXCX_TRIPMAP_CREATEFAILED => '创建出行支付关系记录失败',
        self::WXCX_TRIPMAP_UPDATEFAILED => '更新出行支付关系记录失败',
        //虚拟号码
        self::VN_BIND_FAILED   => '虚拟号码绑定失败',
        self::VN_UNBIND_FAILED => '虚拟号码解绑失败',
        self::VN_UPDATE_FAILED => '虚拟号码更新失败',
        self::VN_NOTIFY_FAILED => '话单记录推送失败',
        self::WXCX_SYSTEM_PAYMENTORDER_FAILED => '网信出行系统代扣失败',
        self::WXCX_USER_PAYMENTORDER_FAILED => '网信出行用户支付失败',
        self::JOBS_PAYMENT_SYSTEM_ADD_FAILED => '添加收银台系统代扣Jobs失败',
        self::JOBS_TRIP_SYSTEM_ADD_FAILED => '添加网信出行系统代扣Jobs失败',
        self::WXCX_BONUS_SEND_FAILED => '网信出行红包发送超时或发送失败',
        self::WXCX_BONUS_TRIP_UPDATE_FAILED => '出行订单红包信息更新失败',
        self::WXCX_BONUS_TRIP_HAS_SEND => '出行订单红包已经赠送过了',
        self::P2P_USER_BINDCARD_FAILED => '用户尚未绑定理财卡',
        self::PAYMENT_CARDTYPE_NOT_SUPPORT => '该商户版块暂不支持该银行卡类型',
        self::PAYMENT_VERIFY_PASSWD_FAILED => '交易密码验证失败',
        self::WXCX_CANNOT_UNBIND_CARD => '因风控因素暂不支持解绑此银行卡',
        self::WXCX_INVOICE_COMPANYCODE_EMPTY => '纳税人识别号不能为空',
        self::WXCX_INVOICEMONEY_HAS_PROCESS => '有处理中的开发票订单',
        self::JOBS_TRIP_ADD_FAILED => '添加网信出行Jobs失败',
        self::DIVISION_ORDER_UPDATE_FAILED => '更新分账订单失败',
        self::WXCX_PAYMENT_HASTIMEOUT => '该支付主订单已支付超时',
        self::WXCX_PAYMENTORDER_HAS_FINISH => '支付订单已终态',
        self::WXCX_TRIPORDER_HAS_FINISH => '该出行订单支付状态已终态，无需重复支付',
        self::WXCX_TRIPORDER_HAS_PROCESS => '该出行订单支付处理中，无需重复支付',
        self::WXCX_BONUS_UID_EMPTY => '红包出资方用户ID不能为空',
        self::WXCX_INVOICE_HAS_FINISH => '发票状态已终态',
    );

    // 先锋支付系统相关错误码
    const UCFPAY_COMMON_ERROR = '00001';//通用码
    const UCFPAY_SUCCESS = '00000';//成功
    const UCFPAY_ORDER_PROCESS = '00002';//订单处理中
    const UCFPAY_AMOUNT_ERROR = '00009';//支付金额不在规定范围内
    const UCFPAY_CARD_HAS_BIND = '00035';//您已经绑定过该卡
    const UCFPAY_CARD_NOT_BIND = '00036'; //用户未绑卡
    const UCFPAY_CARD_NOT_FOUND = '00038'; //没有查询到指定的银行卡
    const UCFPAY_BANK_NOTSUPPORT = '00041';//暂不支持该银行
    const UCFPAY_BANK_UPGRADE = '00063';//银行系统升级中，请您稍后再试
    const UCFPAY_PAY_AMOUNT_LIMITED = '00101';//交易超出限额/次数
    const UCFPAY_PARAMS_ERROR = '10000';//参数不合法
    const UCFPAY_PARAMS_VALUE_ERROR = '10001';//参数值传入错误
    const UCFPAY_SERVICE_NOTSUPPORT = '10002';//业务不支持
    const UCFPAY_CHANNEL_NOTOPEN = '10003';//渠道未开通
    const UCFPAY_BANK_RETURN_ERROR = '10004';//银行返回错误
    const UCFPAY_REPEAT_ORDER_ERROR = '10005';//订单重复提交
    const UCFPAY_USER_CODE_NOTEXIST = '10007';//用户或商户编号不存在
    const UCFPAY_TRANS_RECORD_NOTEXIST = '10009';//交易记录不存在
    const UCFPAY_BANLANCE_NOTENOUGH = '10010';//账户余额不足
    const UCFPAY_NOCARD_NOTOPEN = '10011';//未开通无卡支付
    const UCFPAY_REFUND_TIMES_LIMITED = '10012';//退款次数超限
    const UCFPAY_REFUND_AMOUNT_LIMITED = '10013';//累计退款金额超限
    const UCFPAY_CARD_PARAMS_ERROR = '10018';//参数错误
    const UCFPAY_USER_INFO_ERROR = '10024';//姓名、身份证、卡号不一致
    const UCFPAY_BANK_AMOUNT_LIMITED = '10025';//超银行限额/金额超限
    const UCFPAY_ACCOUNT_NOTEXIST = '10026';//账户不存在
    const UCFPAY_BANK_NET_ERROR = '10027';//银行通讯异常
    const UCFPAY_ACCOUNT_STATUS_ERROR = '10028';//账户状态异常
    const UCFPAY_PRODUCT_CODE_ERROR = '10031';//产品编码配置异常
    const UCFPAY_IDENTIFY_CODE_ERROR = '10032';//验证码校验失败
    const UCFPAY_SUBREFUND_CANNOT = '10033';//组合支付不支持部分退款
    const UCFPAY_ORDER_STATUS_ERROR = '10036';//订单状态异常
    const UCFPAY_PAY_CHANNEL_BUSY = '10037';//支付渠道系统繁忙，请稍后再试
    const UCFPAY_PAY_FAILED = '10038';//支付失败，请稍候再试
    const UCFPAY_PAY_TIMES_LIMITED = '10039';//支付次数超过发卡银行限制，本次支付失败
    const UCFPAY_BANKCARD_NOTSUPPORT = '10040';//暂不支持此类型银行卡，请更换其他银行卡
    const UCFPAY_BANKCARD_AMOUNT_LIMITED = '10042';//支付金额超过银行卡月累计支付限额
    const UCFPAY_PAY_AMOUNT_TOOLOW = '10043';//单笔支付金额不能低于最小限额
    const UCFPAY_PAY_ELEMENT_NOTFULL = '10048';//交易要素不完整，请核实后再试
    const UCFPAY_BALANCE_NOTENOUGH_TOOMUCH = '10050';//余额不足次数过多
    const UCFPAY_PAY_TIMES_TOOMUCH = '10053';//支付次数过多，请24小时后再试
    const UCFPAY_SYSTEM_ERROR = '20000';//系统内部错误
    const UCFPAY_SERVICE_OVERTIME = '20001';//服务调用超时
    const UCFPAY_INNER_SERVICE_ERROR = '20002';//支付平台内部服务调用错误
    const UCFPAY_NET_ERROR = '20003';//通讯异常
    const UCFPAY_SMS_IDENTIFY_LIMITED = '20004';//短信校验次数超限
    const UCFPAY_SMS_IDENTIFY_FAILED = '20005';//短信校验失败
    const UCFPAY_SMS_SEND_TIMES_LIMITED = '20006';//短信发送次数超限
    const UCFPAY_SMS_SEND_FAILED = '20007';//短信发送失败
    const UCFPAY_RISK_IDENTIFY_ERROR = '20009';//风控校验不通过
    const UCFPAY_PAY_SINGLE_TOOMUCH = '30001';//金额超过单笔限额
    const UCFPAY_PAY_DAY_TOOMUCH = '30002';//金额超过日累计限额
    const UCFPAY_SERVICE_PARAMS_ERROR = '99016';//服务请求参数无效/参数无效
    const UCFPAY_SIGN_FAILED = '99020';//验签失败/签名校验失败
    const UCFPAY_SERVICE_NOTEXIST = '99021';//service不存在
    const UCFPAY_SIGN_KEY_NOTEXIST = '99022';//sign key不存在
    const UCFPAY_SIGN_VERIFY_FAILED = '99023';//verify sign failure
    const UCFPAY_SERVICE_ERROR = '99024';//服务调用超时/服务调用异常
    const UCFPAY_REDIRECT_URL_ERROR = '99025';//转发URL异常
    const UCFPAY_PARAMS_HANDLE_ERROR = '99026';//参数处理异常/参数异常
    const UCFPAY_SERVICE_EMPTY = '99027';//service为空
    const UCFPAY_MERCHANTID_EMPTY = '99028';//merchantId为空
    const UCFPAY_MERCHANTKEY_NOTEXIST = '99029';//商户秘钥不存在
    const UCFPAY_REPEAT_REQUEST_ERROR = '99030';//防重复请求码校验失败
    const UCFPAY_SERVICE_VERSION_ERROR = '99031';//服务版本号version非法/服务版本号version错误
    const UCFPAY_IP_ERROR = '99032';//请求IP非法
    const UCFPAY_ERROR_TYPE_UNDEFINED = '99999';//未定义错误类型

    // 支付与网信生活的错误码映射
    public static $errMsgMap = array (
        self::UCFPAY_COMMON_ERROR => ['code'=>'200000', 'msg'=>'支付系统通用错误', 'payMsg'=>'通用码'],
        self::UCFPAY_SUCCESS => ['code'=>'0000', 'msg'=>'调用成功', 'payMsg'=>'成功'],
        self::UCFPAY_ORDER_PROCESS => ['code'=>'0002', 'msg'=>'订单处理中，请稍候再试', 'payMsg'=>'订单处理中'],
        self::UCFPAY_AMOUNT_ERROR => ['code'=>'0001', 'msg'=>'请确认支付金额无误', 'payMsg'=>'支付金额不在规定范围内'],
        self::UCFPAY_CARD_HAS_BIND => ['code'=>'0004', 'msg'=>'该银行卡已经绑定成功', 'payMsg'=>'您已经绑定过该卡'],
        self::UCFPAY_CARD_NOT_BIND => ['code'=>'0004', 'msg'=>'您尚未绑定这张银行卡', 'payMsg'=>'您尚未绑定这张银行卡'],
        self::UCFPAY_CARD_NOT_FOUND => ['code'=>'1008', 'msg'=>'没有查询到该银行卡', 'payMsg'=>'没有查询到指定的银行卡'],
        self::UCFPAY_BANK_NOTSUPPORT => ['code'=>'1009', 'msg'=>'暂不支持该银行', 'payMsg'=>'暂不支持该银行'],
        self::UCFPAY_BANK_UPGRADE => ['code'=>'0003', 'msg'=>'银行系统升级中，请您稍候再试', 'payMsg'=>'银行系统升级中，请您稍后再试'],
        self::UCFPAY_PAY_AMOUNT_LIMITED => ['code'=>'1005', 'msg'=>'支付金额/次数超限', 'payMsg'=>'交易超出限额/次数'],
        self::UCFPAY_PARAMS_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'参数不合法'],
        self::UCFPAY_PARAMS_VALUE_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'参数值传入错误'],
        self::UCFPAY_SERVICE_NOTSUPPORT => ['code'=>'2001', 'msg'=>'暂不支持，请联系客服', 'payMsg'=>'业务不支持'],
        self::UCFPAY_CHANNEL_NOTOPEN => ['code'=>'2001', 'msg'=>'暂不支持，请联系客服', 'payMsg'=>'渠道未开通'],
        self::UCFPAY_BANK_RETURN_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'银行返回错误'],
        self::UCFPAY_REPEAT_ORDER_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'订单重复提交'],
        self::UCFPAY_USER_CODE_NOTEXIST => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'用户或商户编号不存在'],
        self::UCFPAY_TRANS_RECORD_NOTEXIST => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'交易记录不存在'],
        self::UCFPAY_BANLANCE_NOTENOUGH => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'账户余额不足'],
        self::UCFPAY_NOCARD_NOTOPEN => ['code'=>'2001', 'msg'=>'暂不支持，请联系客服', 'payMsg'=>'未开通无卡支付'],
        self::UCFPAY_REFUND_TIMES_LIMITED => ['code'=>'1002', 'msg'=>'退款次数超限', 'payMsg'=>'退款次数超限'],
        self::UCFPAY_REFUND_AMOUNT_LIMITED => ['code'=>'1003', 'msg'=>'累计退款金额超限', 'payMsg'=>'累计退款金额超限'],
        self::UCFPAY_CARD_PARAMS_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'参数错误'],
        self::UCFPAY_USER_INFO_ERROR => ['code'=>'1004', 'msg'=>'请确认使用本人银行卡', 'payMsg'=>'姓名、身份证、卡号不一致'],
        self::UCFPAY_BANK_AMOUNT_LIMITED => ['code'=>'1005', 'msg'=>'支付金额/次数超限', 'payMsg'=>'超银行限额/金额超限'],
        self::UCFPAY_ACCOUNT_NOTEXIST => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'账户不存在'],
        self::UCFPAY_BANK_NET_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'银行通讯异常'],
        self::UCFPAY_ACCOUNT_STATUS_ERROR => ['code'=>'9999', 'msg'=>'请联系客服', 'payMsg'=>'账户状态异常'],
        self::UCFPAY_PRODUCT_CODE_ERROR => ['code'=>'9999', 'msg'=>'请联系客服', 'payMsg'=>'产品编码配置异常'],
        self::UCFPAY_IDENTIFY_CODE_ERROR => ['code'=>'1006', 'msg'=>'验证码校验失败，请重试', 'payMsg'=>'验证码校验失败'],
        self::UCFPAY_SUBREFUND_CANNOT => ['code'=>'2001', 'msg'=>'暂不支持，请联系客服', 'payMsg'=>'组合支付不支持部分退款'],
        self::UCFPAY_ORDER_STATUS_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'订单状态异常'],
        self::UCFPAY_PAY_CHANNEL_BUSY => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'支付渠道系统繁忙，请稍后再试'],
        self::UCFPAY_PAY_FAILED => ['code'=>'1007', 'msg'=>'支付失败', 'payMsg'=>'支付失败，请稍候再试'],
        self::UCFPAY_PAY_TIMES_LIMITED => ['code'=>'1008', 'msg'=>'支付次数超限', 'payMsg'=>'支付次数超过发卡银行限制，本次支付失败'],
        self::UCFPAY_BANKCARD_NOTSUPPORT => ['code'=>'1009', 'msg'=>'暂不支持该银行卡', 'payMsg'=>'暂不支持此类型银行卡，请更换其他银行卡'],
        self::UCFPAY_BANKCARD_AMOUNT_LIMITED => ['code'=>'1010', 'msg'=>'支付金额超月累计限额', 'payMsg'=>'支付金额超过银行卡月累计支付限额'],
        self::UCFPAY_PAY_AMOUNT_TOOLOW => ['code'=>'0001', 'msg'=>'请确认支付金额无误', 'payMsg'=>'单笔支付金额不能低于最小限额'],
        self::UCFPAY_PAY_ELEMENT_NOTFULL => ['code'=>'9999', 'msg'=>'请联系客服', 'payMsg'=>'交易要素不完整，请核实后再试'],
        self::UCFPAY_BALANCE_NOTENOUGH_TOOMUCH => ['code'=>'1005', 'msg'=>'支付金额/次数超限', 'payMsg'=>'余额不足次数过多'],
        self::UCFPAY_PAY_TIMES_TOOMUCH => ['code'=>'1008', 'msg'=>'支付次数超限', 'payMsg'=>'支付次数过多，请24小时后再试'],
        self::UCFPAY_SYSTEM_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'系统内部错误'],
        self::UCFPAY_SERVICE_OVERTIME => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'服务调用超时'],
        self::UCFPAY_INNER_SERVICE_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'支付平台内部服务调用错误'],
        self::UCFPAY_NET_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'通讯异常'],
        self::UCFPAY_SMS_IDENTIFY_LIMITED => ['code'=>'1011', 'msg'=>'短信校验次数超限', 'payMsg'=>'短信校验次数超限'],
        self::UCFPAY_SMS_IDENTIFY_FAILED => ['code'=>'1012', 'msg'=>'短信校验失败，请重试', 'payMsg'=>'短信校验失败'],
        self::UCFPAY_SMS_SEND_TIMES_LIMITED => ['code'=>'1013', 'msg'=>'短信发送次数超限', 'payMsg'=>'短信发送次数超限'],
        self::UCFPAY_SMS_SEND_FAILED => ['code'=>'1014', 'msg'=>'短信发送失败，请重试', 'payMsg'=>'短信发送失败'],
        self::UCFPAY_RISK_IDENTIFY_ERROR => ['code'=>'2002', 'msg'=>'风控校验未通过，请联系客服', 'payMsg'=>'风控校验不通过'],
        self::UCFPAY_PAY_SINGLE_TOOMUCH => ['code'=>'1005', 'msg'=>'金额超过单笔限额', 'payMsg'=>'金额超过单笔限额'],
        self::UCFPAY_PAY_DAY_TOOMUCH => ['code'=>'1005', 'msg'=>'金额超过日累计限额', 'payMsg'=>'金额超过日累计限额'],
        self::UCFPAY_SERVICE_PARAMS_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'服务请求参数无效/参数无效'],
        self::UCFPAY_SIGN_FAILED => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'验签失败/签名校验失败'],
        self::UCFPAY_SERVICE_NOTEXIST => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'service不存在'],
        self::UCFPAY_SIGN_KEY_NOTEXIST => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'sign key不存在'],
        self::UCFPAY_SIGN_VERIFY_FAILED => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'verify sign failure'],
        self::UCFPAY_SERVICE_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'服务调用超时/服务调用异常'],
        self::UCFPAY_REDIRECT_URL_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'转发URL异常'],
        self::UCFPAY_PARAMS_HANDLE_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'参数处理异常/参数异常'],
        self::UCFPAY_SERVICE_EMPTY => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'service为空'],
        self::UCFPAY_MERCHANTID_EMPTY => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'merchantId为空'],
        self::UCFPAY_MERCHANTKEY_NOTEXIST => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'商户秘钥不存在'],
        self::UCFPAY_REPEAT_REQUEST_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'防重复请求码校验失败'],
        self::UCFPAY_SERVICE_VERSION_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'服务版本号version非法/服务版本号version错误'],
        self::UCFPAY_IP_ERROR => ['code'=>'1001', 'msg'=>'请刷新后重试', 'payMsg'=>'请求IP非法'],
        self::UCFPAY_ERROR_TYPE_UNDEFINED => ['code'=>'9999', 'msg'=>'请联系客服', 'payMsg'=>'未定义错误类型'],
    );

    // 先锋支付免密付款无异步回调的错误码列表
    public static $payNoNotifyErrCode = array(
        self::UCFPAY_CARD_HAS_BIND,
        self::UCFPAY_CARD_NOT_BIND,
        self::UCFPAY_CARD_NOT_FOUND,
        self::UCFPAY_PARAMS_ERROR,
        self::UCFPAY_PARAMS_VALUE_ERROR,
        self::UCFPAY_CHANNEL_NOTOPEN,
        self::UCFPAY_USER_CODE_NOTEXIST,
        self::UCFPAY_CARD_PARAMS_ERROR,
        self::UCFPAY_BANK_AMOUNT_LIMITED,
        self::UCFPAY_ACCOUNT_STATUS_ERROR,
        self::UCFPAY_BANKCARD_AMOUNT_LIMITED,
        self::UCFPAY_BALANCE_NOTENOUGH_TOOMUCH,
        self::UCFPAY_PAY_TIMES_TOOMUCH,
        self::UCFPAY_SYSTEM_ERROR,
        self::UCFPAY_RISK_IDENTIFY_ERROR,
        self::UCFPAY_PAY_SINGLE_TOOMUCH,
        self::UCFPAY_PAY_DAY_TOOMUCH,
        self::UCFPAY_PAY_FAILED,
        self::UCFPAY_BANK_NOTSUPPORT,
        self::UCFPAY_BANKCARD_NOTSUPPORT,
        self::UCFPAY_PAY_ELEMENT_NOTFULL,
        self::UCFPAY_AMOUNT_ERROR,
        self::UCFPAY_BANK_UPGRADE,
        self::UCFPAY_PAY_AMOUNT_LIMITED,
    );
}
