<?php
namespace core\dao\ifapush;

use core\dao\ifapush\IfaBaseModel;


class IfaDealModel extends IfaBaseModel
{
    public function isNeedReport($dealId)
    {
        $cnt = $this->count("sourceProductCode='{$dealId}'");
        return $cnt > 0 ? true : false;
    }
}