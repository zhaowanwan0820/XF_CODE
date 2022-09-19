<?php
/**
 * 用户访问日志服务
 *
 * @date 2019-01-02
 * @author weiwei12@ucfgroup.com
 */

namespace core\service;

use core\dao\UserAccessLogModel;
use core\service\risk\RiskService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\Msgbus;
use libs\utils\Alarm;
use libs\utils\Logger;
use libs\utils\Site;
use libs\utils\Risk;

/**
 * @package core\service
 */
class UserAccessLogService extends BaseService {


    /**
     * 生产访问日志
     * @param $userId 用户id
     * @param $logType 日志类型
     * @param $logInfo 日志内容
     * @param $extraInfo 扩展信息
     * @param $newInfo 修改后数据
     */
    public static function produceLog($userId, $logType, $logInfo, $extraInfo, $newInfo = '', $device = DeviceEnum::DEVICE_UNKNOWN, $logStatus = UserAccessLogEnum::STATUS_SUCCESS, $platform = UserAccessLogEnum::PLATFORM_WX) {
        if (empty($userId) || empty($logType) || empty($logInfo)) {
            return false;
        }
        $log = [
            'order_id'      => Idworker::instance()->getId(),
            'user_id'       => $userId,
            'log_type'      => $logType,
            'log_info'      => $logInfo,
            'log_time'      => time(),
            'log_status'    => $logStatus,
            'log_id'        => Logger::getLogId(),
            'platform'      => $platform,
            'client_ip'     => get_real_ip(),
            'device'        => $device,
            'site_id'       => Site::getId(),
            'app_version'   => self::getAppVersion($device),
            'extra_info'    => $extraInfo,
            'new_info'      => $newInfo,
            'fingerprint'        => Risk::getFinger(),
        ];
        try {
            Msgbus::instance()->produce(UserAccessLogEnum::USER_ACCESS_LOG_TOPIC, $log);
            return true;
        } catch (\Exception $e) {
            Alarm::push('user_access_log', __METHOD__, sprintf('%s|%s', json_encode($log), $e->getMessage()));
            return false;
        }
    }

    /**
     * 获取客户端版本
     */
    public static function getAppVersion($device) {
        if (in_array($device, [DeviceEnum::DEVICE_IOS, DeviceEnum::DEVICE_ANDROID])) {
            return !empty($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        }
        return '';
    }

    /**
     * 获取支付设备
     */
    public static function getPaymentDevice($platform) {
        return isset(DeviceEnum::$paymentDeviceMap[$platform]) ? DeviceEnum::$paymentDeviceMap[$platform] : DeviceEnum::DEVICE_UNKNOWN;
    }

    /**
     * 获取设备
     */
    public static function getDevice($data){
        $device = DeviceEnum::DEVICE_UNKNOWN;
        if (empty($data)) {
            // 如果user_agent都不存在
            if (empty($_SERVER['HTTP_USER_AGENT'])) {
                return $device;
            }

            $data = $_SERVER['HTTP_USER_AGENT'];
        }

        if (stripos($data, 'Android') !== false) {
            $device = DeviceEnum::DEVICE_ANDROID;
        } elseif (stripos($data, 'iOS') !== false) {
            $device = DeviceEnum::DEVICE_IOS;
        } elseif (stripos($data, 'WAP') !== false) {
            $device = DeviceEnum::DEVICE_WAP;
        }

        return $device;
    }

    /**
     * 获取日志状态
     */
    public static function getLogStatus($orderStatus) {
        $logStatus = UserAccessLogEnum::STATUS_FAIL;
        if (in_array($orderStatus, [UserAccessLogEnum::STATUS_SUCCESS, UserAccessLogEnum::STATUS_FAIL])) {
            $logStatus = $orderStatus;
        }
        return $logStatus;
    }

    /**
     * 信息脱敏并格式化
     * 例如 姓名 身份证号 手机号等
     */
    private static function formatInfo($info) {
        if (!is_array($info)) {
            return $info;
        }
        if (!empty($info['password'])) {
            unset($info['password']);
        }
        if (!empty($info['mobile'])) {
            $info['mobile'] = moblieFormat($info['mobile']);
        }
        if (!empty($info['phone'])) {
            $info['phone'] = moblieFormat($info['phone']);
        }
        if (!empty($info['account']) && is_string($info['account'])) {
            $info['account'] = moblieFormat($info['account']);
        }
        if (!empty($info['idno'])) {
            $info['idno'] = idnoNewFormat($info['idno']);
        }
        if (!empty($info['bankcard']) && is_string($info['bankcard'])) {
            $info['bankcard'] = bankNoFormat($info['bankcard']);
        }
        if (!empty($info['cardNo']) && is_string($info['cardNo'])) {
            $info['cardNo'] = bankNoFormat($info['cardNo']);
        }
        if (!empty($info['realName']) && is_string($info['realName'])) {
            $info['realName'] = nameFormat($info['realName']);
        }
        if (!empty($info['card_name']) && is_string($info['card_name'])) {
            $info['card_name'] = nameFormat($info['card_name']);
        }
        if (!empty($info['user_name']) && is_string($info['user_name'])) {
            $info['user_name'] = user_name_format($info['user_name']);
        }
        return json_encode($info, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 保存访问日志
     */
    public static function saveLog($log) {
        if (empty($log) || empty($log['order_id'])) {
            return false;
        }

        //格式化扩展日志
        if (isset($log['extra_info'])) {
            $log['extra_info'] = self::formatInfo($log['extra_info']);
        }
        if (isset($log['new_info'])) {
            $log['new_info'] = self::formatInfo($log['new_info']);
        }

        //密等检查
        $userAccessLogModel = UserAccessLogModel::instance();
        if ($userAccessLogModel->logExist($log['order_id'])) {
            return true;
        }

        //添加日志
        try {
            $result = (bool) $userAccessLogModel->addLog($log);
        } catch (\Exception $e) {
            $result = false;
        }

        //上报数据到火眼
        $result && self::reportFactory($log);

        return $result;
    }

    /**
     * 获取平台 转换下
     * 和UserAccountEnum保持一致
     */
    public static function getPlatform($logPlatform) {
        if ($logPlatform == UserAccessLogEnum::PLATFORM_WX) {
            return UserAccountEnum::PLATFORM_WANGXIN;
        }
        return UserAccountEnum::PLATFORM_SUPERVISION;
    }

    /**
     * 上报数据到火眼
     */
    public static function reportFactory($log) {
        $logType = $log['log_type'];
        if (empty(UserAccessLogEnum::$reportTypeMap[$logType])) {
            return true;
        }
        $methodName = UserAccessLogEnum::$reportTypeMap[$logType];
        if (!method_exists(__CLASS__, $methodName)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'Missing method ' . $methodName)));
            return false;
        }
        return self::$methodName($log);
    }

    /**
     * 上报充值数据
     */
    public static function reportCharge($log) {
        $extra = !empty($log['extra_info']) ? json_decode($log['extra_info'], true) : [];
        if (empty($extra['chargeAmount']) || empty($extra['chargeChannel'])) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $log['extra_info'], 'Missing parameters')));
            return false;
        }
        $bizType = $log['log_status'] == UserAccessLogEnum::STATUS_INIT ? 'CHARGE' : 'CHARGE_CALLBACK';

        $user = \core\dao\UserModel::instance()->find($log['user_id'], '*', true);
        $extraData = [
            'user_id' => $log['user_id'],
            'user_name' => !empty($user['user_name']) ? $user['user_name'] : '',
            'group_id' => !empty($user['group_id']) ? $user['group_id'] : '',
            'amount' => $extra['chargeAmount'],
            'charge_channel' => $extra['chargeChannel'],
            'business_time' => $log['log_time'],
            'platform' => self::getPlatform($log['platform']),
            'account_type' => !empty($user['user_purpose']) ? $user['user_purpose'] : '',
            'order_id' => !empty($extra['orderId']) ? $extra['orderId'] : '',
        ];
        if ($bizType == 'CHARGE') {
            $extraData['ip'] = $log['client_ip'];
            $extraData['fingerprint'] = !empty($log['fingerprint']) ? $log['fingerprint'] : '';
        }
        return RiskService::report($bizType, $log['log_status'], $extraData);
    }

    /**
     * 上报提现数据
     */
    public static function reportWithdraw($log) {
        $extra = !empty($log['extra_info']) ? json_decode($log['extra_info'], true) : [];
        if (empty($extra['withdrawAmount'])) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $log['extra_info'], 'Missing parameters')));
            return false;
        }
        $bizType =  $log['log_status'] == UserAccessLogEnum::STATUS_INIT ? 'WITHDRAW' : 'WITHDRAW_CALLBACK';

        $user = \core\dao\UserModel::instance()->find($log['user_id'], '*', true);
        $extraData = [
            'user_id' => $log['user_id'],
            'user_name' => !empty($user['user_name']) ? $user['user_name'] : '',
            'group_id' => !empty($user['group_id']) ? $user['group_id'] : '',
            'amount' => $extra['withdrawAmount'],
            'platform' => self::getPlatform($log['platform']),
            'account_type' => !empty($user['user_purpose']) ? $user['user_purpose'] : '',
            'order_id' => !empty($extra['orderId']) ? $extra['orderId'] : '',
        ];
        if ($bizType == 'WITHDRAW') {
            $extraData['ip'] = $log['client_ip'];
            $extraData['fingerprint'] = !empty($log['fingerprint']) ? $log['fingerprint'] : '';
        }
        return RiskService::report($bizType, $log['log_status'], $extraData);
    }

    /**
     * 上报绑卡数据
     */
    public static function reportBind($log) {
        $extra = !empty($log['extra_info']) ? json_decode($log['extra_info'], true) : [];
        if (empty($extra['cardNo']) || empty($extra['bankName'])) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $log['extra_info'], 'Missing parameters')));
            return false;
        }
        $bizType = 'BIND';

        $extraData = [
            'user_id' => $log['user_id'],
            'card_no' => $extra['cardNo'],
            'bank_name' => $extra['bankName'],
            'ip' => $log['client_ip'],
            'fingerprint' => !empty($log['fingerprint']) ? $log['fingerprint'] : '',
        ];
        return RiskService::report($bizType, $log['log_status'], $extraData);
    }

    /**
     * 上报密码修改
     */
    public static function reportCPwd($log) {
        $bizType = 'CPWD';

        $user = \core\dao\UserModel::instance()->find($log['user_id'], '*', true);
        $extraData = [
            'user_id' => $log['user_id'],
            'user_name' => !empty($user['user_name']) ? $user['user_name'] : '',
            'mobile' => !empty($user['mobile']) ? $user['mobile'] : '',
            'change_password_verify' => 'phone',
            'ip' => $log['client_ip'],
            'fingerprint' => !empty($log['fingerprint']) ? $log['fingerprint'] : '',
            'order_id' => $log['order_id'],
        ];
        return RiskService::report($bizType, RiskService::STATUS_SUCCESS, $extraData);
    }

    /**
     * 上报换卡数据
     */
    public static function reportChangeCard($log) {
        $bizType = 'CHANGE_CARD';

        $user = \core\dao\UserModel::instance()->find($log['user_id'], '*', true);
        $new = !empty($log['new_info']) ? json_decode($log['new_info'], true) : [];
        if (empty($new['cardNo']) || empty($new['bankName'])) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $log['new_info'], 'Missing parameters')));
            return false;
        }
        $extraData = [
            'user_id' => $log['user_id'],
            'mobile' => !empty($user['mobile']) ? $user['mobile'] : '',
            'card_no' => $new['cardNo'],
            'bank_name' => $new['bankName'],
            'ip' => $log['client_ip'],
            'fingerprint' => !empty($log['fingerprint']) ? $log['fingerprint'] : '',
            'order_id' => $log['order_id'],
        ];
        return RiskService::report($bizType, RiskService::STATUS_SUCCESS, $extraData);
    }


}
