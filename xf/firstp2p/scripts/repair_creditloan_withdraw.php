<?php
/**
 * 银信通-处理提现订单回调失败的脚本
 *
 * @package     scripts
 * @author      guofeng3
 ********************************** 80 Columns *********************************
 */
require_once(dirname(__FILE__) . '/../app/init.php');

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);

use \libs\utils\Script;
use core\service\UniteBankPaymentService;

class RepairCreditloanWithdraw {
    private $uniteBankPaymentObj;
    private $outOrderId;
    public function __construct($outOrderId) {
        $this->outOrderId = addslashes($outOrderId);
        $this->uniteBankPaymentObj = new UniteBankPaymentService();
    }

    public function run() {
        if (empty($this->outOrderId)) {
            Script::log(sprintf('%s::%s|%s', __CLASS__, __FUNCTION__, 'params[outOrderId] is not found!'));
            return false;
        }

        $result = [];
        try{
            Script::log(sprintf('%s::%s|outOrderId:%s', __CLASS__, __FUNCTION__, $this->outOrderId));
            // 先锋支付发送还款指令处理结果回调
            $withdrawParams = ['outOrderId'=>$this->outOrderId];
            $result = $this->uniteBankPaymentObj->withdrawTrustBankNotifyCallback($withdrawParams);
            if (empty($result['respCode']) || $result['respCode'] != '00') {
                throw new \Exception('银信通提现回调失败:' . $result['respMsg']);
            }
            Script::log(sprintf('%s::%s|银信通提现回调成功，result:%s', __CLASS__, __FUNCTION__, json_encode($result)));
        } catch (\Exception $e) {
            Script::log(sprintf('%s::%s|exceptionMsg:%s', __CLASS__, __FUNCTION__, $e->getMessage()));
            $result = ['respCode'=>'02', 'respMsg'=>$e->getMessage()];
        }
        return $result;
    }
}

Script::start();
if (empty($argv[1])) {
    exit("params[outOrderId] is not found!\n");
}
// 同时仅允许一个脚本运行
$cmd = sprintf('ps aux | grep \'%s\' | grep -v grep | grep -v vim | grep -v %d', basename(__FILE__), posix_getpid());
$handle = popen($cmd, 'r');
$scriptCmd = fread($handle, 1024);
if ($scriptCmd) {
    exit("repair_creditloan_withdraw is running!\n");
}

$outOrderId = addslashes($argv[1]);
$obj = new RepairCreditloanWithdraw($outOrderId);
$obj->run();
Script::end();
