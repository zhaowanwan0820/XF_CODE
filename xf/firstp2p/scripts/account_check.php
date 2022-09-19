<?php
require(dirname(__FILE__) . '/../app/init.php');

class accountChecking {

    public $db;

    public function __construct() {
        $this->db = $GLOBALS["db"]->get_slave();
    }

    public function process() {

        $lastTimePoint = "";
        try {
            // 获取上次的时间点和余额
            $sql = "SELECT create_time, balance FROM " . DB_PREFIX . "account_check ORDER BY id DESC LIMIT 1";
            $result = $this->db->query($sql);
            if ($result && $lastCheck = $this->db->fetchRow($result)) {
                $lastTimePoint = $lastCheck['create_time'];
            }

            // 获取本次时间点，并开始查询
            $currentTimePoint = get_gmtime();
            $wherePaymentNotice = "pay_time <= $currentTimePoint AND is_paid = 1";
            $whereMoneyApply = "time <= $currentTimePoint AND type = 2";
            $whereUserCarry = "withdraw_time <= $currentTimePoint AND withdraw_status = 1";
            if ($lastTimePoint) {
                $wherePaymentNotice .= " AND pay_time > $lastTimePoint";
                $whereMoneyApply .= " AND time > $lastTimePoint";
                $whereUserCarry .= " AND withdraw_time > $lastTimePoint";
            }
            // 获取用户表的总余额
            $sql = "SELECT SUM(money+lock_money) AS balance FROM " . DB_PREFIX . "user";
            $result = $this->db->query($sql);
            $balance = $this->db->fetchRow($result);
            // 获取这段时间的提现
            $sql = "SELECT SUM(money) AS withdraw FROM " . DB_PREFIX . "user_carry WHERE $whereUserCarry";
            $result = $this->db->query($sql);
            $withdraw = $this->db->fetchRow($result);
            // 获取这段时间的线下充值
            $sql = "SELECT SUM(money) AS charge_offline FROM " . DB_PREFIX . "money_apply WHERE $whereMoneyApply";
            $result = $this->db->query($sql);
            $chargeOffline = $this->db->fetchRow($result);
            // 获取这段时间的线上充值
            $sql = "SELECT SUM(money) AS charge_online FROM " . DB_PREFIX . "payment_notice WHERE $wherePaymentNotice";
            $result = $this->db->query($sql);
            $chargeOnline = $this->db->fetchRow($result);

            if ($lastCheck) {
                $expectBalance = $lastCheck['balance'] + $chargeOffline['charge_offline'] + $chargeOnline['charge_online'] - $withdraw['withdraw'];
                if (bccomp($expectBalance, floatval($balance['balance']), 2)) {
                    $diff = bcsub($expectBalance, floatval($balance['balance']), 2);
                    $sql = "INSERT INTO " . DB_PREFIX . "account_check (balance, charge_online, charge_offline, withdraw, create_time, status, diff)
                            VALUES(". floatval($balance['balance']) . ", " . floatval($chargeOnline['charge_online']) . ", "
                            . floatval($chargeOffline['charge_offline']) . ", " . floatval($withdraw['withdraw']) . ", $currentTimePoint, 0, $diff)";
                    // 存表
                    $result = $GLOBALS['db']->query($sql);
                    if (!$result) {
                        throw new Exception ("Insert to account_check error!");
                    }

                    // send email warning
                    $msg = to_date($lastTimePoint) . "---" . to_date($currentTimePoint)
                           . " 余额对账失败,数据异常,差额:" . $diff;
                    \libs\utils\Alarm::push('payment', "accountCheckFail",$msg);
                    return true;
                }
            }
            $sql = "INSERT INTO " . DB_PREFIX . "account_check (balance, charge_online, charge_offline, withdraw, create_time, status)
                    VALUES(". floatval($balance['balance']) . ", " . floatval($chargeOnline['charge_online']) . ", "
                    . floatval($chargeOffline['charge_offline']) . ", " . floatval($withdraw['withdraw']) . ", $currentTimePoint, 1)";
            // 存表
            $result = $GLOBALS['db']->query($sql);
            if (!$result) {
                throw new Exception ("Insert to account_check error!");
            }
        } catch (Exception $e) {
            $this->_log(to_date($currentTimePoint) . "\terror\t".$e->getMessage());
        }
        $this->_log(to_date($currentTimePoint) . "\tsuccess\t");
    }

    private function _log($msg) {
        file_put_contents(dirname(__FILE__). "/../log/account_checking_" . to_date(time(), "Ymd") . ".log", $msg . PHP_EOL, FILE_APPEND);
    }
}

$handle = new accountChecking();
$handle->process();
