<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CommonEnum extends AbstractEnum {

    // 严重错误告警的key
    const ALARM_PUSH_FATAL_ERROR_KEY = 'creditloan_exception';

    // token缓存key配置
    const TOKEN_CACHE_KEY = 'CREDITLOAN_REDIS_TOKEN_KEY';

    // mortage dealInfo 缓存key配置
    const MORTAGE_DEAL_INFO_KEYNAME = 'CREDITLOAN_REDIS_MORTAGE_DEAL_INFO';

    // 加密密钥
    const ENCRYPT_KEY = '65523293db241ea6f516e01e820bea13672f031a3531dd75810df1b7b8bceba5';
    // IV
    const ENCRYPT_IV = '49b50fb37e4418eb047768e728a09129';

    // chcode 渠道号
    const CH_CODE = '10004';

    // prodId 产品编号
    const PRODUCT_ID = '4001';

    // 即富系统响应成功
    const STATUS_SUC = 1;
    // 即富系统响应失败
    const STATUS_FAIL = 0;
    const STATUS_PROCESS = 99;
    const CODE_SUCCESS = '0000';

    // 请求缓存时间
    const SYSTEM_REQUEST_CACHE_TIME = 86400;
    // 审核申请请求缓存
    const SYSTEM_APPLY_REQUEST = 'CREDITLOAN_REDIS_APPLY_REQUEST_';
    // 提现申请请求缓存
    const SYSTEM_WITHDRAW_REQUEST = 'CREDITLOAN_REDIS_WITHDRAW_REQUEST_';
    // 还款请求缓存
    const SYSTEM_REPAY_REQUEST = 'CREDITLOAN_REDIS_REPAY_REQUEST_';


    // 网信系统响应即富成功
    const RESPONSE_SUCCESS = '0000';

    // 响应失败
    const RESPONSE_FAILURE = '9999';

    // 风控数据 - 用户id类型
    const KEHU_ID_TYPE = '10100';

    // 风控数据 - 资产类型
    const PINGTAI_ZICHANG_CLASS = '01200';

    // 风控数据 - 数据类型
    const PINGTAI_ZICHANG_LICAI_DATATYPE_NON_DETAIL =  '0'; // 非明细
    const PINGTAI_ZICHANG_LICAI_DATATYPE_DETAIL =  '1'; // 明细

    // 银行还款方式
    const REPAYMENT_METHOD_EQUAL_PRINCIPAL = 1; // 等额本金
    const REPAYMENT_METHOD_EQUAL_PRINCIPAL_INTEREST = 2; //等额本息
    const REPAYMENT_METHOD_PRINCIPAL_INTEREST = 3; //等本等息
    const REPAYMENT_METHOD_ONE_TIME_PRINCIPAL_INTEREST = 4; //一次性还本付息

    // 查询订单 - 查询类型
    const QUERY_ORDER_TYPE_WITHDRAW = 1; // 提现
    const QUERY_ORDER_TYPE_REPAY = 2; // 还款

    //证件照片类型
    const ID_CARD_TYPE_FRONTPHOTO = 1; //正面
    const ID_CARD_TYPE_BACKPHOTO = 2; //反面
    const ID_CARD_TYPE_HANDHOLDPHOTO = 3; //持证照

    public static $idcardTypeMap = [
        'frontPhoto' => self::ID_CARD_TYPE_FRONTPHOTO,
        'backPhoto' => self::ID_CARD_TYPE_BACKPHOTO,
        'handHoldPhoto' => self::ID_CARD_TYPE_HANDHOLDPHOTO,
    ];

}
