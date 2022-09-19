<?php

namespace core\dao\ifapush;

class IfaTransferStatusModel extends IfaBaseModel
{
    public function isNeedReport($orderId)
    {
        $cnt = $this->count("transferId='{$orderId}'");
        return $cnt != '0' ? false : true;
    }
}
