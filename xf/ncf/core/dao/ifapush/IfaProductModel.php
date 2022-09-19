<?php
namespace core\dao\ifapush;

use core\dao\ifapush\IfaBaseModel;


class IfaProductModel extends IfaBaseModel
{
    public function isNeedReport($dealId)
    {
        $cnt = $this->count("sourceFinancingCode='{$dealId}'");
        return $cnt > 0 ? false : true;
    }
}