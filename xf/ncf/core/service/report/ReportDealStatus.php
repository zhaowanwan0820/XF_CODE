<?php

namespace core\service\report;

error_reporting(E_ALL);
use core\dao\deal\DealModel;
use core\dao\report\ReportDealStatusModel;
use core\service\report\ReportBase;
use NCFGroup\Common\Library\Idworker;


class ReportDealStatus extends ReportBase
{
    public $dealId;

    public $status;

    public function __construct($dealId,$status)
    {
        $this->dealId = $dealId;
        $this->status = $status;
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
    }

    public function collectData()
    {
        $data = [
            'deal_id' => $this->dealId, //标的id
            'project_id' => $this->dealInfo->project_id, // 项目编号
            'deal_status' =>$this->status, //项目状态
            'create_time' => time(),
            'update_time' => time(),
        ];
        return $data;
    }


}