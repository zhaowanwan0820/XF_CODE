<?php
/**
 * 用户访问日志服务
 *
 * @date 2019-01-02
 * @author weiwei12@ucfgroup.com
 */

namespace core\service;

use core\enum\UserAccessLogEnum;
use core\enum\DeviceEnum;
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
    public static function produceLog($userId, $logType, $logInfo, $extraInfo, $newInfo = '', $device = DeviceEnum::DEVICE_UNKNOWN, $logStatus = UserAccessLogEnum::STATUS_SUCCESS, $platform = UserAccessLogEnum::PLATFORM_P2P) {
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
            'finger'        => Risk::getFinger(),
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

}
