<?php

namespace core\service;

use libs\utils\Logger;

class SparowService
{
    const SALT = 'VAjHTxJ6JyjZDF6o';

    public function __construct($gameCode = '', $platform = 'ncfwx')
    {
        $this->gameCode = $gameCode;
        $this->platform = $platform;
    }

    /**
     * O2O调用游戏接口
     */
    public function gameO2O($userId, $index = '', $token = '')
    {
        $this->gamePushTrigger($userId, $index, $token);
        // if (empty($index)) {
        //     return $this->gameTimes($userId, $token);
        // } else {
        //     return $this->gameSeqPush($userId, $index, $token);
        // }
    }

    /**
     * 自动判断推送类型，按照index进行游戏机会或宝箱的变更
     */
    public function gamePushTrigger($userId, $index, $token)
    {
        $params = [
            'userId' => $userId,
            'index' => $index,
            'token' => $token,
        ];
        return $this->api('pushTrigger', $params);
    }

    /**
     * 获取游戏链接
     * @return ['link' => 'http://xxxxxxx?code=xxx&from=xxx']
     */
    public function getGameLink()
    {
        return $this->api('getLink', []);
    }

    /**
     * 推送宝箱
     */
    public function gameSeqPush($userId, $index, $token)
    {
        $params = [
            'userId' => $userId,
            'index' => $index,
            'token' => $token,
        ];
        return $this->api('seqPush', $params);
    }

    /**
     * 增加游戏次数
     */
    public function gameTimes($userId, $token)
    {
        $params = [
            'userId' => $userId,
            'token' => $token,
        ];
        return $this->api('playTimes', $params);
    }

    /**
     * AR领奖
     */
    public function arAward($token)
    {
        return $this->api('arAward', ['token' => $token]);
    }

    /**
     * 获取未开启宝箱个数
     */
    public function getGameSeqPushCnt($userId)
    {
        return $this->api('getSeqPushCnt', ['userId' => $userId]);
    }

    public function api($api, $params)
    {

        $url = app_conf('SPAROW_API') . '/' . $api;

        $params['code'] = $this->gameCode;
        $params['from'] = app_conf('SPAROW_FROM');
        $params['timestamp'] = time();
        $params['v'] = 2;

        $sign = \NCFGroup\Common\Library\SignatureLib::generate($params, self::SALT);
        $params['sign'] = strtoupper($sign);

        try {
            Logger::info(implode('|', [__METHOD__, $url, json_encode($params, JSON_UNESCAPED_UNICODE)]));
            $res = \libs\utils\Curl::post($url, $params);
            Logger::info(implode('|', [__METHOD__, $res]));
            $res = json_decode($res, true);
            if ($res && $res['code'] == 0) {
                return isset($res['data']) ? $res['data'] : true;
            }

        } catch (\Exception $e) {
            Logger::info(implode('|', [__METHOD__, $e->getMessage()]));
        }
        return false;
    }

    public function getIncomeStatus($userId)
    {
        return $this->api('getIncomeStatus', ['userId' => $userId]);
    }

}
