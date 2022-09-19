<?php

require_once(dirname(__FILE__) . '/../app/init.php');

use libs\db\Db;
use libs\utils\FileCache;
use libs\utils\Monitor;

// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep upgrade_sms_notice.php | grep -v grep | grep -v sudo | grep -v {$pid} | grep -v vi | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}
set_time_limit(0);
ini_set('memory_limit', '1024M');
\libs\utils\Script::start();

class UpgradeSmsNotice
{
    /**
     * 短信接口地址
     */
    const SMS_API_URL = 'http://43.243.130.33:8860/sendSms';
    const REQUEST_TIMEOUT = 5;
    const SMS_VERIFY_CORP_ID = '500072';
    const SMS_VERIFY_PASSWORD = 'Q4TFJOXNR7';

    const UPGRADE_SMS_START_USERID_CACHE_KEY = 'UPGRADE_SMS_START_USERID_CACHE_KEY';

    private $_db_slave;
    private $_start_userid = 0;
    private $_max_userid = 0;
    private $_content = '【网信理财】平台将于4月29日（本周六）0点至24点进行系统升级，期间官网和APP将暂停服务。请您提前做好投资安排，给您带来不便敬请谅解！';
    //业务标识
    private $_sms_uid = '20170428';
    private $_speed = 600;

    public function __construct($start_userid = null)
    {
        $this->_db_slave = Db::getInstance('firstp2p', 'slave');

        //如果传参指定开始userid，则以此id开始
        if (empty($start_userid)) {
            $fileCache = FileCache::getInstance();
            $start_userid = $fileCache->get(self::UPGRADE_SMS_START_USERID_CACHE_KEY, true);
            if ($start_userid === false) {
                $start_userid = 1;
                $fileCache->set(self::UPGRADE_SMS_START_USERID_CACHE_KEY, $start_userid);
            }
        }

        $this->_start_userid = $start_userid;

        $this->_max_userid = $this->_db_slave->getOne('select max(id) FROM `firstp2p_user`');

        \libs\utils\Script::log("UPGRADE SMS NOTICE. start_userid:{$this->_start_userid}, max_userid:{$this->_max_userid}");
    }

    public function run()
    {
        for ($i = $this->_start_userid; $i <= $this->_max_userid; $i += $this->_speed) {
            $startTime = microtime(true);
            $max = $i + $this->_speed -1;
            \libs\utils\Script::log("UPGRADE SMS NOTICE. send sms to userid between {$i} and {$max}");
            $rows = $this->_db_slave->getAll("SELECT id,mobile,mobile_code,money,lock_money FROM `firstp2p_user` WHERE id BETWEEN {$i} AND {$max}");

            if (empty($rows)) {
                continue;
            }

            $mobiles = [];
            foreach($rows as $row) {
                //国际手机号不发
                if ($row['mobile_code'] != '86') {
                    continue;
                }

                if ($row['money'] >0 || $row['lock_money'] > 0) {
                    $mobiles[] = $row['mobile'];
                    continue;
                }

                //待还本金大于0也要发
                $norepay_principal = $this->_db_slave->getOne("SELECT norepay_principal FROM `firstp2p_user_loan_repay_statistics` WHERE user_id={$row['id']}");
                if ((int)$norepay_principal > 0 ) {
                    $mobiles[] = $row['mobile'];
                }
            }

            // 发短信
            $ret = $this->sendSms($mobiles);

            //记录下次开始userid
            $fileCache = FileCache::getInstance();
            $fileCache->set(self::UPGRADE_SMS_START_USERID_CACHE_KEY, $i + $this->_speed);

            //控制至少每秒执行一次循环
            $cost = round(microtime(true) - $startTime, 3);
            $cost = intval(1000000 * $cost);
            if ($cost < 1000000) {
                $sleep = 1000000 - $cost;
                //usleep($sleep);
                \libs\utils\Script::log("UPGRADE SMS NOTICE. usleep {$sleep}");
            }
        }
    }

    private function sendSms($mobile)
    {
        if (!is_array($mobile)) {
            \libs\utils\Script::log("UPGRADE SMS NOTICE. parameter mobile must be an array");
            return false;
        }

        $count = count($mobile);
        if ($count < 1) {
            \libs\utils\Script::log("UPGRADE SMS NOTICE. mobiles<1");
            return false;
        }

        $params = array(
            'cust_code' => self::SMS_VERIFY_CORP_ID,
            'content' => $this->_content,
            'destMobiles' => implode(',', $mobile),
            'uid' => $this->_sms_uid,
        );

        $params['sign'] = md5($this->_content.self::SMS_VERIFY_PASSWORD);
        $result = $this->request(self::SMS_API_URL, $params);
        $chargeNum = $this->checkResponse($result);
        if ($chargeNum === false) {
            Monitor::add('WEILAISMS_SENDVERIFY_FAILED', $count);
            \libs\utils\Script::log("UPGRADE SMS NOTICE. 提交数量:{$count}, 成功提交:{$chargeNum}");
            return false;
        }

        Monitor::add('WEILAISMS_SENDVERIFY_SUCCESS', $chargeNum);
        if ($chargeNum < $count) {
            Monitor::add('WEILAISMS_SENDVERIFY_FAILED', $count - $chargeNum);
        }
        \libs\utils\Script::log("UPGRADE SMS NOTICE. 提交数量:{$count}, 成功提交:{$chargeNum}");
        return true;
    }

    private function checkResponse($response)
    {
        //响应为空退出发告警
        if (empty($response)) {
            \libs\utils\Script::log("UPGRADE SMS NOTICE. response empty exit");
            exit;
        }
        // 文档见 http://43.243.130.33:8099/support/http-3.0.jsp#nav4
        $result = json_decode($response, true);

        if (false === $result) {
            \libs\utils\Script::log("UPGRADE SMS NOTICE. json decode error exit");
            exit;
        }

        //提交失败
        if ($result['respCode'] != '0') {
            \libs\utils\Script::log("UPGRADE SMS NOTICE. commit fail exit");
            exit;
        }

        if (!isset($result['totalChargeNum'])) {
            \libs\utils\Script::log("UPGRADE SMS NOTICE. miss totalChargeNum exit");
            exit;
        }

        if ((int)$result['totalChargeNum'] < 1) {
            return false;
        }

        return $result['totalChargeNum'];
    }

    private function request($url, $data)
    {
        if (empty($url)) {
            return false;
        }

        $params = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (substr($url, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $startTime = microtime(true);
        $result =  curl_exec($ch);

        $cost = round(microtime(true) - $startTime, 3);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        \libs\utils\Script::log("UPGRADE SMS NOTICE. mobile:{$data['mobile']},cost:{$cost}, errno:{$errno}, error:{$error}, httpCode:{$httpCode}, result:{$result}");
        return $result;
    }

}

$notice = new UpgradeSmsNotice($argv[1]);
$notice->run();

\libs\utils\Script::end();
