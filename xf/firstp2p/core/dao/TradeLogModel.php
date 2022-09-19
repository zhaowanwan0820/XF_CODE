<?php
/**
 * TradeLogModel class file.
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

class TradeLogModel extends BaseModel {


    /**
     * 根据conditionString,使用$data提供数据更新trade_log
     */
    public function updateRecord($data, $conditionString)
    {
        $this->db->autoExecute('firstp2p_trade_log', $data, 'UPDATE', $conditionString);
        $updateRowsNum = $this->db->affected_rows();
        return $updateRowsNum > 0 ? true : false;
    }


    /**
     * 根据merchantId和outOrderId获取订单信息
     */
    public function getRecordByMerchantIdOutOrderId($merchantId, $outOrderId)
    {
        // 根据外部订单号查找thirdparty_invest记录
        $sql = "SELECT id,amount,billId FROM firstp2p_trade_log WHERE outOrderId = '{$outOrderId}' AND merchantId = '{$merchantId}'";
        $data = $this->db->get_slave()->getRow($sql);
        return $data;
    }


    /**
     * 读取需要同步的第三方投资记录
     */
    public function findNewTrades($limit = 50)
    {
        $sql = "SELECT id FROM firstp2p_trade_log WHERE orderStatus = 'N' LIMIT {$limit}";
        $dataset = $this->db->getAll($sql);
        if (is_array($dataset))
        {
            foreach ($dataset as $rowData)
            {
                $this->db->query("UPDATE firstp2p_trade_log SET orderStatus = 'I' WHERE id = '{$rowData['id']}'");
            }
        }
        return $dataset;
    }

}
