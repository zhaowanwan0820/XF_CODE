<?php
namespace core\dao\report;

use core\dao\report\ReportBaseModel;


class ReportDealModel extends ReportBaseModel
{
    public function hasDeal($dealId)
    {
        $cnt = $this->count("deal_id='{$dealId}'");
        return $cnt > 0 ? true : false;
    }

    public function getDealInfo($dealId){
        $dealInfo = $this->findBy('deal_id = '.$dealId);
        return $dealInfo ? $dealInfo :false;
    }
}