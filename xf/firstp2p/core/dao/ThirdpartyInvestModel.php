<?php
/**
 * ThirdpartyInvestModel class file.
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

class ThirdpartyInvestModel extends BaseModel {

    public function _tableName($merchantId) {
        return 'firstp2p_thirdparty_invest_'.($merchantId%32);
    }

    /**
     * 根据conditionString,使用$data提供数据更新thirdparty_invest
     */
    public function updateRecord($data, $conditionString)
    {
        $tableName = $this->_tableName($data['merchantId']);
        unset($data['merchantId']);
        $this->db->autoExecute($tableName, $data, 'UPDATE', $conditionString);
        $updateRowsNum = $this->db->affected_rows();
        return $updateRowsNum > 0 ? true : false;
    }


    /**
     * 根据merchantId和outOrderId获取订单信息
     */
    public function getRecordByMerchantIdOutOrderId($merchantId, $outOrderId, $orderStatus = null)
    {
        $tableName = $this->_tableName($merchantId);
        // 根据外部订单号查找thirdparty_invest记录
        $sql = "SELECT id,amount,userId,merchantId,outOrderId FROM {$tableName} WHERE outOrderId = '{$outOrderId}' AND merchantId = '{$merchantId}'";
        if (!empty($orderStatus))
        {
            $sql .= " AND orderStatus = '{$orderStatus}' ";
        }
        $data = $this->db->get_slave()->getRow($sql);
        return $data;
    }

}
