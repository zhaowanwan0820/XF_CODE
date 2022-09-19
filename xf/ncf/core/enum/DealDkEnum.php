<?php
namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealDkEnum extends AbstractEnum {

    const DK_STATUS_NONE  = 0; // 未查询到代扣状态
    const DK_STATUS_SUCC  = 1; // 代扣成功
    const DK_STATUS_FAIL  = 2; // 代扣失败
    const DK_STATUS_DOING = 3; // 代扣进行中


    const ERR_DEAL_FIND_NULL = '21011';
    const ERR_DEAL_REPAY_ID = '21030';
    const ERR_DEAL_APPROVE_NUMBER = '21041';
    const ERR_DEAL_REPAY_DONE = '21042';
    const ERR_DEAL_REPAY_SELF = '21043';

    const ERR_CODE_SYS = '40000';
    const ERR_CODE_PARAMS = '40010';
    const ERR_CODE_TIME_BEYOND = '40011';
    const ERR_CODE_DKING = '40012';
    const ERR_CODE_STATUS_FORBID = '40013';
    const ERR_CODE_NOTDK = '40014';
    const ERR_CODE_NOT_EXISTS = '40015';
    const ERR_CODE_NORESULT = '40016';
    const ERR_CODE_NO_REPORT = '40017';

    const BUSINESS_STATUS_NONE = 0; //未还款
    const BUSINESS_STATUS_SUCC = 1; //还款完成
    const BUSINESS_STATUS_REPAYING = 2; //还款中

    public static $errCodeMsg = array(
        self::ERR_CODE_SYS => '系统错误,更新还款信息失败',
        self::ERR_CODE_PARAMS => '参数错误',
        self::ERR_CODE_TIME_BEYOND => '已过标的最晚调整时间',
        self::ERR_CODE_DKING => '代扣进行时段不能进行还款方式变更',
        self::ERR_CODE_STATUS_FORBID => '当前状态不允许更改还款方式',
        self::ERR_CODE_NOTDK => '当前标的非代扣还款',
        self::ERR_CODE_NOT_EXISTS => '标的还款信息不存在',
        self::ERR_CODE_NORESULT => '未查询到代扣信息',
        self::ERR_CODE_NO_REPORT => '未报备标的不允许更改还款方式',
        self::ERR_DEAL_FIND_NULL => '未查询到标的',
        self::ERR_DEAL_REPAY_ID => '标的还款ID异常',
        self::ERR_DEAL_APPROVE_NUMBER => '标的放款审批单号异常',
        self::ERR_DEAL_REPAY_DONE => '该期还款已完成',
        self::ERR_DEAL_REPAY_SELF => '使用网贷账户还款',
    );

    const UPDATE_REPAY_TYPE_LATEST_TIME = '15:30:00';

}