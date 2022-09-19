<?php

namespace NCFGroup\Common\Library\Risk;

use NCFGroup\Common\Library\CommonLogger as Logger;
use NCFGroup\Common\Library\Curl;

class Huoyan
{
    // 反作弊返回值拒绝
    const DECISION_REFUSE = "REFUSE";
    // 反作弊返回值人脸识别
    const DECISION_FACE = "FACE";

    // 阻断接口
    const CHECK_URI = '/risk/check';

    // 上报接口
    const REPORT_URI = '/risk/report';

    // 补充说明接口
    const SUPPLY_URI = '/risk/supply';

    public static function check($data)
    {
        try {
            $params = json_encode($data, JSON_FORCE_OBJECT);

            $curl = Curl::instance();
            $result = $curl->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8'])->setTimeout(1)
                ->post(self::getUrl(self::CHECK_URI), $params);

            Logger::info("huoyan check. cost:{$curl->resultInfo['cost']}, code:{$curl->resultInfo['code']}, params:{$params}, result:{$result}");

            $res = json_decode($result, true);
            if (isset($res['data']['Decision']) && $res['data']['Decision'] === self::DECISION_REFUSE) {
                return false;
            }
            if (isset($res['data']['Decision']) && $res['data']['Decision'] === self::DECISION_FACE) {
                return self::DECISION_FACE;
            }
        } catch (\Exception $e) {
            Logger::info('huoyan check exception. error:'.$e->getMessage());
        }

        return true;
    }

    public static function report($data)
    {
        $params = json_encode($data, JSON_FORCE_OBJECT);

        $curl = Curl::instance();
        $result = $curl->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8'])->setTimeout(1)
            ->post(self::getUrl(self::REPORT_URI), $params);
        Logger::info("huoyan report. cost:{$curl->resultInfo['cost']}, code:{$curl->resultInfo['code']}, params:{$params}, result:{$result}");
    }

    public static function getUrl($apiName)
    {
        $huoyanUrl = getDi()->getConfig()->huoyan->url->toArray();
        $num = array_rand($huoyanUrl);
        return $huoyanUrl[$num].$apiName;
    }

}
