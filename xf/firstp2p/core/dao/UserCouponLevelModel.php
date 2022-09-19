<?php

namespace core\dao;

class UserCouponLevelModel extends BaseModel
{
    public function getByName($name)
    {
        return $this->findByViaSlave("name = '{$name}'");
    }

    public function getByRebateRatio($rebateRatio)
    {
        return $this->findByViaSlave("rebate_ratio = '{$rebateRatio}'");
    }
}
