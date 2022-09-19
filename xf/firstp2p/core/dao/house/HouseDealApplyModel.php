<?php
/**
 * HouseInfo
 * User: sunxuefeng
 * Date: 2017/9/28 0028
 * Time: 15:40
 */

namespace core\dao\house;

use core\dao\BaseModel;

class HouseDealApplyModel extends BaseModel
{
    public function addApplyLog($applyLog)
    {
        if (!empty($applyLog)) {
            foreach ($applyLog as $key => $value) {
                if ($applyLog[$key] !== NULL && $applyLog[$key] !== '') {
                    $this->$key = $this->escape($applyLog[$key]);
                }
            }
            return $this->insert();
        } else {
            return false;
        }
    }

    public function findByOrderId($orderId)
    {
        if (!empty($orderId)) {
            $condition = ' order_id = '.intval($orderId);
            $result = $this->findBy($condition);
            return $result ? $result->getRow() : false;
        } else {
            return false;
        }
    }
}
