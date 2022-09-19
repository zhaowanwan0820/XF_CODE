<?php

namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ErrorCode extends AbstractEnum {

    /** 系统相关 */
    const SUCCESS = '0';
    const MISS_PARAMETERS = '1001';
    const PARAMETERS_ERROR = '1002';
    const DB_UNKNOW_ERROR = '1003';
    const SERVICE_BUSY = '1004';
    const SYSTEM_ERROR = '1005';

    /** 用户相关 */
    const USER_NOT_BIND_BANKCARD = '1100';
    const USER_CREDIT_AMOUNT_REFRESH_FAIL = '1101';
    const USER_CREDIT_STATUS_ERR = '1102';
    const USER_CREDIT_AUDIT_FAIL = '1103';
    const USER_CREDIT_UPDATE_FAIL = '1104';
    const USER_CREDIT_INSERT_FAIL = '1105';
    const USER_CREDIT_UPLOAD_ID_CARD_PHOTOS_FAIL = '1106';

    /** 借款相关 **/
    const LOAN_AMOUNT_NOT_ENOUGTH = '1200';
    const LOAN_APPLY_FAIL = '1201';
    const LOAN_ORDER_ID_ERR = '1202';
    const LOAN_UPDATE_FAIL = '1203';

    public static $errMsg = array(
        /** 系统相关 */
        self::SUCCESS => 'success',
        self::MISS_PARAMETERS => 'Miss parameters %s!',
        self::DB_UNKNOW_ERROR => 'Database unknow error',
        self::PARAMETERS_ERROR => 'Parameters error',
        self::SERVICE_BUSY => '当前购买人数过多，请您稍后重试',
        self::SYSTEM_ERROR => '系统异常',

        /** 用户相关 */
        self::USER_NOT_BIND_BANKCARD => '请先绑定银行卡',
        self::USER_CREDIT_AMOUNT_REFRESH_FAIL => '用户信用额度刷新失败',
        self::USER_CREDIT_STATUS_ERR => '用户信用状态错误',
        self::USER_CREDIT_STATUS_ERR => '用户信用状态错误',
        self::USER_CREDIT_AUDIT_FAIL => '您的授信申请正在审核中或审核失败，请稍后重试',
        self::USER_CREDIT_UPDATE_FAIL => '用户信用信息更新失败',
        self::USER_CREDIT_INSERT_FAIL => '用户信用信息添加失败',
        self::USER_CREDIT_UPLOAD_ID_CARD_PHOTOS_FAIL => '上传身份证照片失败',

        /** 借款相关 **/
        self::LOAN_AMOUNT_NOT_ENOUGTH => '借款失败，超过借款可用额度',
        self::LOAN_APPLY_FAIL => '借款申请失败，请稍后重试',
        self::LOAN_ORDER_ID_ERR => '借款订单号错误',
        self::LOAN_UPDATE_FAIL => '借款更新失败',

    );
}
