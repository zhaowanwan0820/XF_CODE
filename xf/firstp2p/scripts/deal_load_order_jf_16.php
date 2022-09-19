<?php
/**
 * 每日投标记录同步即付宝
 * 每天23:30执行，统计每天14:30-23:00的投资记录，前后冗余半小时
 */

require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");

use core\dao\ThirdpartyOrderModel;
use libs\vfs\fds\FdsFTP;
use libs\utils\Alarm;
use libs\utils\Aes;

class DealLoadOrderJF {

    public function run($date = '') {

        $day = empty($date) ? date('Ymd', time()) : date('Ymd',strtotime($date));

        /** 时间扩大半小时,即每天14:00-23:30 */
        $startTime = strtotime($day) + 14*3600;
        $endTime = strtotime($day) + 23.5*3600;

        /** 文件名和路径 */
        $fileName = sprintf("deal_load_jf_%s.txt", $day . "2300");
        /** 本地临时存储目录 */
        $localTmpFile = APP_ROOT_PATH.'runtime/' . $fileName;

        $siteJf = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];

        $totalRecord = 0;
        $totalMoney = 0;
        $data = "";

        $tpoModel = new ThirdpartyOrderModel();
        $list = $tpoModel->getOrderListByTime($siteJf, $startTime, $endTime);

        foreach ($list as $v) {
            if(empty($v)) {
                continue;
            }
            $arr = array(
                $v['order_id'],
                $v['order_status'],
                Aes::encryptForJFB($v['user_id']),
                Aes::encryptForJFB($v['mobile']),
                $v['deal_id'],
                $v['deal_loan_id'],
                $v['buy_amount'],
                date('Y-m-d H:i:s',$v['create_time']),
                $v['bid_transfer_id']
                );
            $data.= implode("|",$arr) . "\n";
            $totalRecord+=1;
            $totalMoney=bcadd($totalMoney,$v['buy_amount'],2);
        }

        $txtData="Total|".$totalRecord."|".$totalMoney."\n" . trim($data);
        $remoteFilePath = $GLOBALS['components_config']['jifubao']['ftp_dir'] .'dealload/'. date('Y-m-d') . '/';
        return $this->syncToJf($localTmpFile,$remoteFilePath,$fileName,$txtData);
    }

    /**
     * @param $localFile        本地文件
     * @param $remoteFilePath   远程ftp路径
     * @param $remoteFileName   远程ftp文件名
     * @param $txtData          要上传的数据
     * @return bool
     */
    private function syncToJf($localTmpFile,$remoteFilePath,$remoteFileName,$txtData) {
        $res = file_put_contents($localTmpFile, $txtData);
        if($res===false) {
            echo "save to tmp file error :".$localTmpFile;
            return false;
        }
        try {
            $ftp = new FdsFTP($GLOBALS['components_config']['jifubao']['ftp']);
            $ftpRes = $ftp->write($remoteFilePath, $remoteFileName, $localTmpFile);
        }catch (\Exception $e) {
            echo "upload to ftp error:".$remoteFilePath . "|" . $remoteFileName . "|" . $localTmpFile;
            return false;
        }
        return true;
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);

set_time_limit(0);
ini_set('memory_limit', '1024M');

$date = $argv[1];
$obj = new DealLoadOrderJF();
$res = $obj->run($date);

if($res) {
    $msg = "sync deal order info to jf success!";
}else{
    $msg = "sync deal order into to jf fail!";
    Alarm::push('jifu', '投标记录同步异常', json_encode($msg));
}
