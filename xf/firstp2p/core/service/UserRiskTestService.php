<?php
namespace core\service;

/**
 * 用户风险评测
 * @author longbo
 */
use libs\utils\Logger;

class UserRiskTestService extends BaseService
{
    const USER_RISK_TEST_KEY = 'user_risk_test_log';

    const STATUS_UNTEST = 0;
    const STATUS_TESTED = 1;
    const STATUS_RETEST = 2;

    /**
     * redis存了用户评测结果
     * @param int $user_id
     * @param int $score
     * @return int
     */
    public static function setTestResult($user_id, $score)
    {
        //$redis = \SiteApp::init()->cache;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $res = $redis->hSet(
            self::USER_RISK_TEST_KEY,
            strval($user_id),
            json_encode(array('score' => intval($score), 'time' => time()))
        );
        return $res;
    }

    /**
     * get user 评测结果
     * @param int $user_id
     * @return int
     */
    public static function getTestResult($user_id)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $res = $redis->hGet(
            self::USER_RISK_TEST_KEY,
            strval($user_id)
        );
        $res_data = json_decode($res, true);
        if (empty($res_data)) {
            return self::STATUS_UNTEST;
        }
        if (intval($res_data['time']) < (time() - 365*24*3600)) {
            return self::STATUS_RETEST;
        } else {
            return self::STATUS_TESTED;
        }
    }
    
}

