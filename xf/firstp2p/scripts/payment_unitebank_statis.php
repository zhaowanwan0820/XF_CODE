<?php
/**
 * 统计"备付金银联账户切换"涉及的用户转账信息
 */
require_once(dirname(__FILE__) . '/../app/init.php');

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
ini_set('memory_limit', '2048M');
set_time_limit(0);

use libs\utils\PaymentApi;
use libs\utils\Script;
use core\service\CouponBindService;
use core\service\UserGroupService;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealLoadModel;

class PaymentUnitebankStatis {
    private $fileName;
    private $logName;

    //对公银行
    private $pubBankList = [
        '中国建设银行' => '支持',
        '建设银行' => '支持',
        '建行' => '支持',
        '平安银行' => '支持',
        '光大银行' => '支持',
        '中国光大银行' => '支持',
        '中信银行' => '支持',
        '海口联合农商行' => '支持',
        '海口联合农商银行' => '支持',
        '海口联合农村商业银行' => '支持',
        '招商银行' => '支持',
        '工商银行' => '支持',
        '中国工商银行' => '支持',
        '中国银行' => '支持',
        '华夏银行' => '支持',
        '民生银行' => '支持',
        '中国民生银行' => '支持',
        '邮储银行' => '不支持',
        '邮政储蓄银行' => '不支持',
        '中国邮政储蓄银行' => '不支持',
        '农业银行' => '待验证',
        '中国农业银行' => '待验证',
        '北京农商行' => '待验证',
    ];

    //对私银行
    private $priBankList = [
        '中国建设银行' => '不支持',
        '建设银行' => '不支持',
        '建行' => '不支持',
        '平安银行' => '不支持',
        '光大银行' => '支持',
        '中国光大银行' => '支持',
        '中信银行' => '仅限PC网银',
        '海口联合农商行' => '不支持',
        '海口联合农商银行' => '不支持',
        '海口联合农村商业银行' => '不支持',
        '招商银行' => '支持',
        '工商银行' => '不支持',
        '中国工商银行' => '不支持',
        '中国银行' => '不支持',
        '华夏银行' => '不支持',
        '民生银行' => '不支持',
        '中国民生银行' => '不支持',
        '邮储银行' => '仅限PC网银',
        '邮政储蓄银行' => '仅限PC网银',
        '中国邮政储蓄银行' => '仅限PC网银',
        '农业银行' => '仅限银行APP',
        '中国农业银行' => '仅限银行APP',
        '北京农商行' => '仅限PC网银',
    ];

    public function __construct($fileName) {
        // 原始文件
        $this->fileName = $fileName;
        // 最终文件
        $this->logName = $this->fileName . '_final';
    }

    public function run() {
        if (empty($this->fileName)) {
            $message = sprintf('%s::%s|%s', __CLASS__, __FUNCTION__, 'params[fileName] is not found!');
            Script::log($message);
            PaymentApi::log($message);
            return false;
        }

        $handle = fopen($this->fileName, "r");
        if (!$handle) {
            $message = sprintf('%s::%s|%s', __CLASS__, __FUNCTION__, 'params[fileName] is error!');
            Script::log($message);
            PaymentApi::log($message);
            return false;
        }

        $logData = [];
        $message = sprintf('%s::%s|fileName:%s|Start', __CLASS__, __FUNCTION__, $this->fileName);
        Script::log($message);
        PaymentApi::log($message);
        while ( ($line = fgets($handle, 1024)) !== false) {
            $arr = explode("\t", $line);
            if (count($arr) != 9) {
                $message = sprintf('%s::%s|lineData:%s|该行数据不合法', __CLASS__, __FUNCTION__, $line);
                Script::log($message);
                PaymentApi::log($message);
                continue;
            }

            $lineArr = [];
            $userId = $lineArr[] = (int)$arr[2]; // 用户ID
            $realName = $lineArr[] = trim($arr[3]); // 账户名
            $bankName = $lineArr[] = trim($arr[4]); // 银行名称
            $money = $lineArr[] = trim($arr[5]); // 金额，单位元
            $bizType = trim($arr[6]); // 业务类型(LARGE:线上 OFFLINE:线下)
            $dateTime = $lineArr[] = trim($arr[8]); // 时间
            $remark = trim($arr[7]); // 备注

            // 检查用户名是否一致
            $userInfo = UserModel::instance()->find($userId, 'real_name', true);
            if (empty($userInfo)) {
                $message = sprintf('%s::%s|lineData:%s|userId:%d|该用户不存在', __CLASS__, __FUNCTION__, $line, $userId);
                Script::log($message);
                PaymentApi::log($message);
                continue;
            }
            $lineArr[] = $bizType;

            // 线下操作
            if ($bizType === 'OFFLINE') {
                if (strpos($remark, '投资') !== false) {
                    $lineArr[] = '投资';
                } elseif (strpos($remark, '借款') !== false) {
                    $lineArr[] = '借款';
                } else {
                    $lineArr[] = $remark;
                }
            } else {// 线上操作
                // 查询用户是"投资"、"借款"还是都有
                $lineArr[] = self::getUserIdentityName($userId);
            }

            // 获取该用户的邀请人的组别名称
            $lineArr[] = self::getReferGroupName($userId);

            // 备注字段
            if (strcmp($realName, $userInfo['real_name']) !== 0) {
                $lineArr[] = '账户名不一致';
            } else {
                $lineArr[] = '';
            }

            //对公支持
            $lineArr[] = isset($this->pubBankList[$bankName]) ? $this->pubBankList[$bankName] : '不支持';

            //对私支持
            $lineArr[] = isset($this->priBankList[$bankName]) ? $this->priBankList[$bankName] : '不支持';

            // 写入文件
            self::writeLog($this->logName, join("\t", $lineArr));
        }
        $message = sprintf('%s::%s|备付金银联账户切换数据统计完成，filePath:%s|End', __CLASS__, __FUNCTION__, $this->logName);
        Script::log($message);
        PaymentApi::log($message);
    }

    /**
     * 查询邀请人的组别名称（财富渠道）
     * @param int $userId
     */
    private static function getReferGroupName($userId) {
        // 根据userId获取邀请人的用户ID
        $couponBindObj = new CouponBindService();
        $couponInfo = $couponBindObj->getByUserId($userId);
        if (empty($couponInfo['refer_user_id'])) {
            return '无邀请渠道名称';
        }

        // 查询邀请人的groupId
        $referUserInfo = UserModel::instance()->find($couponInfo['refer_user_id'], 'id,group_id', true);
        if (empty($referUserInfo['group_id'])) {
            return '无渠道ID';
        }
        $userGroupObj = new UserGroupService();
        $userGroupInfo = $userGroupObj->getGroupInfo($referUserInfo['group_id']);
        return !empty($userGroupInfo['name']) ? $userGroupInfo['name'] : '无渠道名称';
    }

    /**
     * 查询用户是"投资"、"借款"还是都有
     * @param int $userId
     */
    private static function getUserIdentityName($userId) {
        $identityName = [];
        // 查询用户是否有【借款】记录
        $dealObj = new DealModel();
        $dealExist = $dealObj->hasExistByUserId($userId);
        if ($dealExist) {
            $identityName[] = '借款';
        }

        // 查询用户是否有【投资】记录
        $dealLoadObj = new DealLoadModel();
        $dealLoadExist = $dealLoadObj->hasExistByUserId($userId);
        if ($dealLoadExist) {
            $identityName[] = '投资';
        }
        return join('-', $identityName);
    }

    /**
     * 写入文件
     * @param string $filename 要写入的文件全路径名
     * @param string $writetext 文件内容
     * @param string $openmod 文件打开的mode
     * @return boolean
     */
    private static function writeLog($filename, $writetext, $openmod='ab+') {
        if($fp = fopen($filename, $openmod)) {
            flock($fp, LOCK_EX);
            fwrite($fp, $writetext . PHP_EOL);
            flock($fp, LOCK_UN);
            fclose($fp);
            return TRUE;
        }else{
            Script::log(sprintf('%s::%s|logData:%s|write error', __CLASS__, __FUNCTION__, $writetext));
            return FALSE;
        }
    }
}

if (empty($argv[1])) {
    exit("params[fileName] is not found!\n");
}

$fileName = $argv[1];
// 同时仅允许一个脚本一个日志文件运行
$cmd = sprintf('ps aux | grep \'%s %s\' | grep -v grep | grep -v vim | grep -v %d', basename(__FILE__), $fileName, posix_getpid());
$handle = popen($cmd, 'r');
$scriptCmd = fread($handle, 1024);
if ($scriptCmd) {
    exit("payment_unitebank_statis {$fileName} is running!\n");
}

Script::start();
$obj = new PaymentUnitebankStatis($fileName);
$obj->run();
Script::end();
