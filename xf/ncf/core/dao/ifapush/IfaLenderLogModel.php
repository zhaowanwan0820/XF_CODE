<?php
namespace core\dao\ifapush;

use core\dao\ifapush\IfaBaseModel;


class IfaLenderLogModel extends IfaBaseModel
{
    public function isNeedReport($transId){

        $cnt = $this->count("transId='{$transId}'");
        return $cnt > 0 ? false : true;
    }
}