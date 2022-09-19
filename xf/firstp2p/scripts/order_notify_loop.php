<?php
/**
 * 生日福利券
 * 网信会为生日的VIP用户发放生日专属福利；
 * 不同等级的VIP会员，享受不同的生日福利。
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../app/init.php';

use core\dao\OrderNotifyModel;
use NCFGroup\Common\Library\SignatureLib;
use libs\utils\Curl;
use libs\utils\Logger;

class OrderNotifyTask
{

    const SALT = 'ea3ec7647b';

    private $clientId = null;
    private $orderId = null;

    function __construct($clientId, $orderId)
    {
        $this->clientId = $clientId;
        $this->orderId = $orderId;
    }


    // 脚本执行
    public function run()
    {

        Logger::info(implode('|', [__METHOD__, 'START']));

        $total = $success = 0;

        if (!empty($this->clientId) && !empty($this->orderId)) {
            // 处理特殊的订单
            $info = OrderNotifyModel::instance()->findViaOrderId($this->clientId, $this->orderId);

            if ($info) {
                $total++;
                $res = $this->notify($info);
                if ($res) $success++;
            }

        } else {

            $page = 1;
            $size = 100;


            while (true) {

                $list = OrderNotifyModel::instance()->findToNotify($page, $size);
                if (count($list) == 0) break;
                $page++;
                $total++;

                foreach ($list as $item) {
                    $res = $this->notify($item);
                    if ($res) $success++;
                }
            }
        }

        Logger::info(implode('|', [__METHOD__, 'END', $total, $success]));

    }

    private function notify($info)
    {
        $id = $info['id'];
        $url = $info['notify_url'];
        $params = $info['notify_params'];
        $notifyCnt = $info['notify_cnt'];

        $locker = new Locker("NOTIFY_{$id}");
        if ($locker->isLocked()) {
            Logger::info(implode('|', [__METHOD__, 'locked', $id]));
            return false;
        }
        $params = json_decode($params, true);
        try {

            $params['timestamp'] = time();
            $sign = SignatureLib::generate($params, self::SALT);
            $params['sign'] = $sign;
            $res = Curl::post($url, $params);
            Logger::info(implode('|', [__METHOD__, $id, $url, json_encode($params), json_encode($res, JSON_UNESCAPED_UNICODE)]));
            $res = json_decode($res, true);

            if (empty($res)) throw new \Exception("response is empty");
            if ($res['errorCode'] > 0) throw new \Exception($res['errorMsg']);

            // 回调成功
            OrderNotifyModel::instance()->updateNotifyStatus($id, OrderNotifyModel::STATUS_SUCCESS, 0, '');

        } catch (\Exception $e) {
            Logger::info(implode('|', [__METHOD__, $id, $e->getMessage()]));
            $nextTime = $this->getNextTime($notifyCnt + 1);
            OrderNotifyModel::instance()->updateNotifyStatus($id, OrderNotifyModel::STATUS_FAIL, $nextTime, $e->getMessage());
            return false;
        }
        return true;
    }

    private function getNextTime($cnt)
    {
        if ($cnt <= 5) return time() + 5;
        if ($cnt >= 20) return time() + 86400;
        $cnt = $cnt - 5;
        return time() + $cnt * $cnt * $cnt * 5;
    }
}

/**
 * 自动释放锁
 */
class Locker
{

    function __construct($key)
    {
        $this->key = md5("RDS_KEY_NOTIFY_{$key}");
        $this->rds = \SiteApp::init()->dataCache->getRedisInstance();
        $this->counter = $this->rds->incr($this->key);
    }

    public function isLocked()
    {
        if ($this->counter > 1) return true;
        return false;
    }

    function __destruct()
    {
        $count = $this->rds->decr($this->key);
        if ($count <= 0) {
            $this->rds->del($this->key);
        }
    }
}

$shortopts = "c::o::";

$longopts = [
    "clientId::",
    "orderId::",
    "help",
];

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php order_notify_loop [args...]
    --clientId 客户端ID
    --orderId 订单号
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$clientId = $opts['clientId'] ?: null;
$orderId = $opts['orderId'] ?: null;

try {

    (new OrderNotifyTask($clientId, $orderId))->run();

} catch (\Exception $ex) {

    Logger::info(implode('|', [__METHOD__, $ex->getMessage()]));

}
