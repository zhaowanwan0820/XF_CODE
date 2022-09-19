<?php
namespace core\dao\deal;
use core\dao\BaseModel;

/**
 * 放款后收费记录表
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */
class FeeAfterGrantModel extends BaseModel {


    /**
     * 通过标的id检查该条代扣缴费记录是否存在
     *  如果存在则返回记录详情, 否则返回null
     *
     * @param integer $dealId 标的编号
     *
     * @return null|array
     */
    public function isDealIdExists($dealId, $statusArray = [])
    {
        $condition = '';
        if (!empty($statusArray))
        {
            $condition = ' AND charge_result IN ('.implode(',', $statusArray).') ';
        }
        $sql = "SELECT * FROM ".$this->tableName()." WHERE deal_id = '{$dealId}' {$condition}";
        $record = $this->db->getRow($sql);
        return $record;
    }

    /**
     * 通过外部订单号查询代扣缴费记录是否存在
     *      如果存在则返回记录详情, 否则返回null
     * @param integer $orderId 外部订单号
     *
     * @return array|null
     */
    public function getRecordByOrderId($orderId, $queryWithLock = false)
    {
        $condition = '';
        if ($queryWithLock)
        {
            $condition .= ' FOR UPDATE ';
        }
        $sql = "SELECT * FROM ".$this->tableName()." WHERE out_order_id = '{$orderId}' {$condition}";
        $record = $this->db->getRow($sql);
        return $record;

    }
//更新表
    public function saveFeeAfterGrant($id, $data)
    {

        $condition = sprintf("`id` = '%d'", $id);
        $data['update_time'] = time();
        return $this->updateBy($data, $condition);
    }
}
