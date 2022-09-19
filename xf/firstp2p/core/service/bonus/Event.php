<?php
/**
 * @file Game.php
 * @synopsis
 *
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 *
 * @version v1.0
 * @date 2015-08-26
 */

namespace core\service\bonus;

use core\dao\BonusModel;
use core\dao\BonusConfModel;
use libs\utils\Logger;

class Event
{
    private $expire = 300;

    /**
     * 注册后生成活动红包.
     *
     * @param int    $owner_uid
     * @param string $mobile
     * @param array  $event_data
     * @param string $cn
     * @access public
     *
     * @return array
     */
    public function trigger($owner_uid, $mobile, $event_id, $event_data, $cn = '')
    {
        $event_data = json_decode($event_data, true);
        if (!is_array($event_data)) {
            return array();
        }

        return $this->event_game($owner_uid, $mobile, $event_id, $event_data, $cn);
    }

    /**
     * 画圈圈游戏.
     *
     * @param mixed  $owner_uid
     * @param mixed  $mobile
     * @param mixed  $event_data
     * @param string $cn
     * @access public
     */
    public function event_game($owner_uid, $mobile, $event_id, $event_data, $cn = '')
    {
        $now = time();
        $errMsg = '';
        if (abs($now - $event_data['timestamp'])  >= $this->expire) {
            $errMsg = '超时';
            $money = 0;
        } elseif (strtoupper($event_data['sign']) != $this->getSign($event_data, $event_id)) {
            $errMsg = '签名错误';
            $money = 0;
        } else {
            $money = round(floatval(base64_decode($event_data['rand_key'])), 2);
            $conf_key = 'BONUS_GAME_'.strtoupper($event_id);
            $max_money = BonusConfModel::get($conf_key);
            $max_money = $max_money > 0 ? $max_money : 5;
            if (bccomp($money, $max_money, 2) == 1) {
                $money = $max_money; //最大金额不能超过5元
            }
        }
        $money = 0;
        if ($money > 0) {
            //$result = $this->generateGameBonus($owner_uid, $mobile, $money);
        }
        $this->setLog($result, $owner_uid, $mobile, $money, $errMsg, $event_data);

        return array('event_id' => $event_id, 'money' => $money);
    }

    /**
     * 插入红包数据
     *
     * @param mixed $owner_uid
     * @param mixed $mobile
     * @param mixed $money
     * @param int   $expired_day
     * @access public
     */
    public function generateGameBonus($owner_uid, $mobile, $money, $expired_day = 7)
    {
        return BonusModel::instance()->insert_one($owner_uid, $money, $expired_day, BonusModel::BONUS_GAME_CIRCLE, $mobile);
    }

    /**
     * 记录日志.
     *
     * @param mixed $result
     * @param mixed $owner_uid
     * @param mixed $mobile
     * @param mixed $money
     * @param mixed $event_data
     * @access public
     */
    public function setLog($result, $owner_uid, $mobile, $money, $errMsg, $event_data)
    {
        $message = sprintf("result=%s|uid=%s|mobile=%s|money=%s|msg=%s|event_data=%s", $result, $owner_uid, substr_replace($mobile, '****', 3, 4), $money, $errMsg, json_encode($event_data));
        Logger::wLog($message.PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH."send_game_bonus".date('Ymd').'.log');
    }

    /**
     * 获取签名.
     *
     * @param array $params
     * @access public
     */
    public function getSign($params = array(), $vendorKey = '')
    {
        ksort($params);
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        $sign = '';
        foreach ($params as $k => $v) {
            $sign .= $k.$v;
        }
        return strtoupper(md5($vendorKey.$sign.$vendorKey));
    }
}
