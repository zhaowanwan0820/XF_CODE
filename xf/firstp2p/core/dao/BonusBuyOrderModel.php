<?php
/**
 * Bonus Buy Order class file.
 * @author zhangzhuyan@
 * @since  2016/04/13
 */

namespace core\dao;

/**
 * 买红包订单
 */
class BonusBuyOrderModel extends BaseModel
{
    /**
     * 插入订单记录
     * @param  [type] $orderID [description]
     * @return [type]        [description]
     */
    public function newOrder($orderID, $groupID)
    {
        $this->order_id = $orderID;
        $this->group_id = $groupID;
        return $this->insert();
    }


}
