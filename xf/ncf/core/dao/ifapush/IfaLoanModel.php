<?php
namespace core\dao\ifapush;

use core\dao\ifapush\IfaBaseModel;
use core\enum\MsgbusEnum;

class IfaLoanModel extends IfaBaseModel
{
    public function isNeedReport($topic,$params){
        $cnt = 0;
        if ($topic == MsgbusEnum::TOPIC_DT_TRANSFER){
            $orderId = $params['orderId'];
            $cnt = $this->count("transferId='{$orderId}'");
        }
        return $cnt > 0 ? false : true;
    }
}