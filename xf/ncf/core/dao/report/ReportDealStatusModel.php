<?php
namespace core\dao\report;

use core\dao\report\ReportBaseModel;


class ReportDealStatusModel extends ReportBaseModel{

    public function hasDealOrStatus($dealId,$status)
    {
        $cnt = $this->count("deal_id='{$dealId}'and deal_status='{$status}'");
        return $cnt > 0 ? true : false;
    }
}