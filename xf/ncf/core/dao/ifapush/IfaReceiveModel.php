<?php
namespace core\dao\ifapush;

use core\dao\ifapush\IfaBaseModel;


class IfaReceiveModel extends IfaBaseModel
{
    public function isNeedReport($orderId)
    {
        $cnt = $this->count("transferId='{$orderId}'");
        return $cnt != '0' ? false : true;
    }
}