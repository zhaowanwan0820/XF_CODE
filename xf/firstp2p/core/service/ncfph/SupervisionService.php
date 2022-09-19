<?php

namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;
use core\dao\PaymentNoticeModel;
use core\dao\SupervisionWithdrawModel;
use core\dao\SupervisionTransferModel;

class SupervisionService
{
    public function formFactory($srv, $userId, $userPurpose, $param, $from)
    {
        $params = compact('srv', 'userId', 'userPurpose', 'param', 'from');
        Logger::info('supervisionFormFactory:'.explode(',', $params));
        return ApiService::rpc("ncfph", "supervision/getFormData", $params);
    }

    public function modifyCardCheck($userId, $userPurpose, $outOrderId)
    {
        $params = compact('userId', 'userPurpose', 'outOrderId');
        Logger::info('supervisionModifyCardCheck:'.explode(',', $params));
        return ApiService::rpc("ncfph", "supervision/modifyCardCheck", $params);
    }

    public function redoWithdraw($userId, $outOrderId)
    {
        $params = compact('userId', 'outOrderId');
        Logger::info('supervisionRedoWithdraw:'.explode(',', $params));
        return ApiService::rpc("ncfph", "withdraw/redo", $params);
    }

    /**
     * 普惠存管充值接口
     * @param int $accountId 账户ID
     * @param int $amount 充值金额，单位分
     * @param int $outOrderId 外部订单号
     * @return array
     */
    public static function chargeCreateOrder($accountId, $amount, $outOrderId, $platform = PaymentNoticeModel::PLATFORM_SUPERVISION)
    {
        $params = compact('accountId', 'amount', 'outOrderId', 'platform');
        Logger::info('supervisionChargeCreateOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/chargeCreateOrder", $params);
    }

    /**
     * 获取普惠存管充值数据接口
     * @param int $outOrderId 外部订单号
     * @return array
     */
    public static function chargeGetOrder($outOrderId)
    {
        $params = compact('outOrderId');
        Logger::info('supervisionChargeGetOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/chargeGetOrder", $params);
    }

    /**
     * 获取账户的普惠存管充值记录
     * @param int $accountId 账户ID
     * @return array
     */
    public static function chargeGetLogs($accountId, $ctime = 0, $count = 0, $offset = 0)
    {
        $params = compact('accountId', 'ctime', 'count', 'offset');
        Logger::info('supervisionChargeGetLogs:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/chargeGetLogs", $params);
    }

    /**
     * 普惠存管提现接口
     * @param int $accountId 账户ID
     * @param int $amount 充值金额，单位分
     * @param int $outOrderId 外部订单号
     * @return array
     */
    public static function withdrawCreateOrder($accountId, $amount, $outOrderId, $bidId = 0, $type = SupervisionWithdrawModel::TYPE_TO_BANKCARD, $limitId = 0)
    {
        $params = compact('accountId', 'amount', 'outOrderId', 'bidId', 'type', 'limitId');
        Logger::info('supervisionWithdrawCreateOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/withdrawCreateOrder", $params);
    }

    /**
     * 获取普惠存管提现数据接口
     * @param int $accountId 账户ID
     * @param int $bidId 标的ID
     * @param int $outOrderId 外部订单号
     * @return array
     */
    public static function withdrawGetOrder($accountId = 0, $bidId = 0, $outOrderId = 0)
    {
        $params = compact('accountId', 'bidId', 'outOrderId');
        Logger::info('supervisionWithdrawGetOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/withdrawGetOrder", $params);
    }

    /**
     * 获取普惠存管用户最后一笔成功提现数据接口
     * @param int $accountId 账户ID
     * @return array
     */
    public static function GetUserLastWithdraw($userId)
    {
        $params = compact('userId');
        Logger::info('supervisionWithdrawGetOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/getUserLastWithdraw", $params);
    }


    /**
     * 普惠存管划转接口
     * @param int $accountId 账户ID
     * @param int $amount 充值金额，单位分
     * @param int $outOrderId 外部订单号
     * @param int $direction 划转方向
     * @param int $needChangeMoney 是否操作资金
     * @return array
     */
    public static function transferCreateOrder($accountId, $amount, $outOrderId, $direction = SupervisionTransferModel::DIRECTION_TO_SUPERVISION, $needChangeMoney = 0)
    {
        $params = compact('accountId', 'amount', 'outOrderId', 'direction', 'needChangeMoney');
        Logger::info('supervisionTransferCreateOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/transferCreateOrder", $params);
    }

    /**
     * 获取普惠存管划转数据接口
     * @param int $outOrderId 外部订单号
     * @return array
     */
    public static function transferGetOrder($outOrderId = 0)
    {
        $params = compact('outOrderId');
        Logger::info('supervisionTransferGetOrder:'.json_encode($params));
        return ApiService::rpc("ncfph", "supervision/transferGetOrder", $params);
    }

    /**
     * 获取普惠配置信息
     * @param string $name 配置键值
     * @return string
     */
    public static function GetAppConf($name)
    {
        $params = compact('name');
        $ret = ApiService::rpc("ncfph", "supervision/getAppConf", $params);
        Logger::info('supervisionGetAppConf:'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 读取用户最后一次网贷充值记录
     */
    public static function GetUserLastCharge($userId)
    {
        $params = compact('userId');
        $ret = ApiService::rpc("ncfph", "supervision/getUserLastCharge", $params);
        Logger::info(__FUNCTION__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 是否可以走发起提现
     * @param integer $accountId 用户账户id
     * @return integer 0 不能走 1可以走
     */
    public static function CanFastWithdraw($accountId, $amount = 0)
    {
        $params = compact('accountId', 'amount');
        $ret = ApiService::rpc('ncfph', "supervision/CanFastWithdraw", $params);
        Logger::info(__FUNCTION__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 获取普惠的充值限额信息，单位元
     * @param int $bankId 银行ID
     * @param int $userId 用户ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public static function getPhChargeLimit($bankId, $userId = 0, $payChannel = '')
    {
        $params = compact('bankId', 'userId', 'payChannel');
        $ret = ApiService::rpc('ncfph', "supervision/GetPhChargeLimit", $params);
        Logger::info(__METHOD__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 获取普惠的充值限额信息-m站，单位分
     * @param int $userId 用户ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public static function getPhChargeLimitH5($userId, $payChannel = '')
    {
        $params = compact('userId', 'payChannel');
        $ret = ApiService::rpc('ncfph', "supervision/GetPhChargeLimitH5", $params);
        Logger::info(__METHOD__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 获取普惠的充值限额信息列表
     * @param string $payChannel 充值渠道
     * @param string $bankCode 银行简码
     * @return array
     */
    public static function getPhChargeLimitList($payChannel, $bankCode = '')
    {
        $params = compact('payChannel', 'bankCode');
        $ret = ApiService::rpc('ncfph', "supervision/GetPhChargeLimitList", $params);
        Logger::info(__METHOD__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 新增/更新普惠的充值限额信息
     * @param string $payChannel 充值渠道
     * @param string $bankCode 银行简码
     * @param string $type 充值类型
     * @param array $limitInfo 限额数据
     * @return array
     */
    public static function setPhChargeLimit($payChannel, $bankCode, $type, $limitInfo)
    {
        $params = compact('payChannel', 'bankCode', 'type', 'limitInfo');
        $ret = ApiService::rpc('ncfph', "supervision/SetPhChargeLimit", $params);
        Logger::info(__METHOD__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 获取某条普惠的充值限额信息
     * @param string $payChannel 充值渠道
     * @param string $code 银行简码
     * @return array
     */
    public static function getPhChargeLimitOne($payChannel, $code)
    {
        $params = compact('payChannel', 'code');
        $ret = ApiService::rpc('ncfph', "supervision/GetPhChargeLimitOne", $params);
        Logger::info(__METHOD__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

    /**
     * 删除某条普惠的充值限额信息
     * @param int $id 限额ID
     * @return array
     */
    public static function delPhChargeLimitOne($id)
    {
        $params = compact('id');
        $ret = ApiService::rpc('ncfph', "supervision/DelPhChargeLimit", $params);
        Logger::info(__METHOD__.':'.json_encode($params).', response:'.json_encode($ret));
        return $ret;
    }

}
