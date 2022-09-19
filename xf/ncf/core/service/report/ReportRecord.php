<?php
namespace core\service\report;

use core\dao\deal\DealExtModel;
use core\dao\deal\DealModel;
use core\dao\report\ReportRecordModel;
use core\dao\project\DealProjectModel;
use core\service\report\ReportBase;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;

class ReportRecord extends ReportBase
{
    public $dealId;

    public function __construct($dealId)
    {
        $this->dealId = $dealId;
    }

    public function collectData($recordType){ //1.标的信息  2.标的状态信息
        $report = new ReportBase();
        $sn = Idworker::instance()->getId();
        $data = array(
            'sn' => $sn,
            'ifa_sdata_sn' => $report->getIfaSBankCode().date("YmdHis").$this->getRecordType($recordType).$this->msectime(),
            'deal_id' => $this->dealId,
            'record_type' => $recordType,
            'create_time' => time(),
            'update_time' => time(),
        );
        return $data;
    }

    //返回当前的毫秒时间戳
    public function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 10000),-4);
        return $msectime;
    }

    public function getRecordType($type){
        $reportRecordType = '';
        switch($type){
            case 1:
                $reportRecordType = '31';
            case 2:
                $reportRecordType = '32';
            case 3:
                $reportRecordType = '00';
            case 4:
                $reportRecordType = '00';
        }
        return $reportRecordType;

    }
}