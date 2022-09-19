<?php

namespace core\service\candy;

use libs\utils\Logger;
use libs\utils\Monitor;
use libs\utils\Alarm;
use core\service\candy\CandyActivityService;
use NCFGroup\Common\Library\Curl;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Common\Library\Registry\Registry;

/**
 * Candy服务接口对接
 */
class CandyService
{

    // 信宝余额按操作类型修改接口
    const CANDY_CHANGE_URI = '/account/changeByType';
    // 信宝余额按操作类型修改接口
    const CANDY_TOKEN_URI = '/account/changeByToken';

    // 分享文章
    const SOURCE_TYPE_SHARE = 101;
    // 余额得信宝
    const SOURCE_TYPE_BALANCE = 103;
    // 每日签到
    const SOURCE_TYPE_CHECKIN = 104;
    // 邀请好友
    const SOURCE_TYPE_INVITE = 105;

    // 余额得信宝金额下限
    const BALANCE_MIN_AMOUNT = 5000;

    // todo 以下配置和常量临时应用，待020上线新系统调用信宝服务后会删除
    // 通过信力定义的投资类型映射到信宝投资类型，临时转换
    public static $sourceTypeConf = array(
        CandyActivityService::SOURCE_TYPE_P2P => array(
            'key' => '普惠出借',
            'value' => 7,
        ),
        CandyActivityService::SOURCE_TYPE_ZHUANXIANG => array(
            'key' => '投资尊享',
            'value' => 7,
        ),
        CandyActivityService::SOURCE_TYPE_DT => array(
            'key' => '智多新',
            'value' => 7,
        ),
    );

    /**
     * 按操作类型修改信宝余额
     */
    public static function changeAmountByType($userId, $token, $sourceType, $sourceValue, $note)
    {
        $result = self::request(self::CANDY_CHANGE_URI, array(
            'userId' => intval($userId),
            'token' => strval($token),
            'sourceValue' => strval($sourceValue),
            'sourceType' => strval($sourceType),
            'note' => strval($note),
        ));

        if ($result['code'] != 0) {
            throw new \Exception('Candy服务异常:'.$result['message']);
        }

        return $result['data'];
    }

    /**
     * todo 临时应用方法，待020上线新系统调用信宝服务后会删除
     * 按投资类型修改信宝余额
     */
    public static function changeAmountByActivity($activitySourceType, $token, $userId, $sourceValue, $sourceValueExtra = 0)
    {
        switch ($activitySourceType) {
            // 普惠
            case CandyActivityService::SOURCE_TYPE_P2P:
            // 专享
            case CandyActivityService::SOURCE_TYPE_ZHUANXIANG:
                // $sourceValueExtra为实际投资额
                $ratio = self::calcActivityRatio($sourceValueExtra);
                // $sourceValue为年化投资额
                $value = bcmul($sourceValue/10000 * self::$sourceTypeConf[$activitySourceType]['value'], $ratio, 3);
                break;
            // 智多新
            case CandyActivityService::SOURCE_TYPE_DT:
                $days = $sourceValueExtra;
                // 小于30天不给信力
                if ($days < 30) {
                    return 0;
                }

                // $sourceValue为实际投资额
                $ratio = self::calcActivityRatio($sourceValue);
                // $sourceValueExtra为锁定期天数
                $annualizedAmount = $sourceValue * $sourceValueExtra / 360;
                $value = bcmul($annualizedAmount/10000 * self::$sourceTypeConf[$activitySourceType]['value'], $ratio, 3);
                break;
            default:
                throw new \Exception('不支持的sourceType:'.$activitySourceType);
        }
        self::changeAmountByToken($userId, $token, self::$sourceTypeConf[$activitySourceType]['key'], $value, "source value:{$sourceValue}, extra:{$sourceValueExtra}");
        return $value;
    }

    /**
     * todo 临时应用方法，待020上线新系统调用信宝服务后会删除
     * 根据投资额计算信力系数
     */
    public static function calcActivityRatio($money)
    {
        if ($money < 50000) {
            return 1;
        }

        if ($money < 100000) {
            return 1.2;
        }

        if ($money < 200000) {
            return 1.5;
        }

        return 2;
    }

    /**
     * 通过token修改信宝余额
     */
    public static function changeAmountByToken($userId, $token, $type, $amount, $note)
    {
        $result = self::request(self::CANDY_TOKEN_URI, array(
            'userId' => intval($userId),
            'token' => strval($token),
            'type' => strval($type),
            'amount' => strval($amount),
            'note' => strval($note),
        ));

        if ($result['code'] != 0) {
            throw new \Exception('Candy服务异常:'.$result['message']);
        }

        return $result['data'];
    }


    private static function request($uri, array $data)
    {
        $params = json_encode($data, JSON_UNESCAPED_UNICODE);

        $curl = Curl::instance()->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Request-Id:'. Logger::getLogId()]);
        $result = $curl->post(self::getCandyUrl($uri), $params);
        Logger::info("candy response. cost:{$curl->resultInfo['cost']}, code:{$curl->resultInfo['code']}, params:{$params}, result:{$result}");

        if (empty($result) || $curl->resultInfo['code'] != 200) {
            Monitor::add('CANDY_REQUEST_FAIL');
            Alarm::push('candy', 'CANDY接口异常', "cost:{$curl->resultInfo['cost']}, code:{$curl->resultInfo['code']}, params:{$params}, result:{$result}");
            throw new \Exception('Candy接口请求失败');
        }

        return json_decode($result, true);
    }

    private static function getCandyUrl($apiName)
    {
        try {
            $result = Registry::getServiceInfo('/ncfwx/service/candy');
            return "http://{$result['IP']}:{$result['Port']}{$apiName}";
        } catch (\Exception $e) {
            Logger::info("candy get url fail. error:" . $e->getMessage());
            $candyUrl = $GLOBALS['sys_config']['CANDY']['HOST'];
            $num = array_rand($candyUrl);
            return $candyUrl[$num].$apiName;
        }

    }

}
