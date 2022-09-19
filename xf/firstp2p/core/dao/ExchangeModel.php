<?php

namespace core\dao;

use libs\db\Db;

class ExchangeModel extends BaseModel {

    private function _writeDB() {
        return Db::getInstance('firstp2p', 'master');
    }

    private function _readDB() {
        return Db::getInstance('firstp2p', 'slave');
    }

    public function getBatchInfoById($id) {
        $sql = sprintf("SELECT * FROM firstp2p_exchange_batch WHERE id = %s LIMIT 1", intval($id));
        return $this->_writeDB()->getRow($sql);
    }

    public function getProjectInfoById($id) {
        $sql = sprintf("SELECT * FROM firstp2p_exchange_project WHERE id = %s LIMIT 1", intval($id));
        return $this->_writeDB()->getRow($sql);
    }

    public function getBatchRepayPlanByBatchId($batchId) {
        $sql = sprintf("SELECT * FROM firstp2p_exchange_batch_repay WHERE batch_id = %s AND is_plan = 1 ORDER BY id ASC", intval($batchId));
        return $this->_writeDB()->getAll($sql);
    }

    public function getOneLoadRepayPlanByBatchId($batchId) {
        $sql = sprintf("SELECT * FROM firstp2p_exchange_load_repay WHERE batch_id = %s AND is_plan = 1 LIMIT 1", intval($batchId));
        return $this->_writeDB()->getRow($sql);
    }

    public function getLoadListByBatchId($batchId) {
        $sql = sprintf("SELECT * FROM firstp2p_exchange_load WHERE status = 1 AND batch_id = %s", intval($batchId));
        return $this->_writeDB()->getAll($sql);
    }

    public function saveLoadRepay($row) {
        return $this->_writeDB()->insert('firstp2p_exchange_load_repay', $row);
    }

    public function saveBatchRepay($row) {
        return $this->_writeDB()->insert('firstp2p_exchange_batch_repay', $row);
    }

    public function getRepayList($conditions, $fields = '*') {
        if(!is_string($conditions)){
            return [];
        }

        $sql = sprintf("SELECT %s FROM firstp2p_exchange_batch_repay WHERE %s", $fields, $conditions);
        return $this->_writeDB()->getAll($sql);
    }

    public function getLoadRepayMoneyList($batchId) {
        $sql = "SELECT repay_id, SUM(principal) AS total_principal, SUM(interest) AS total_interest FROM firstp2p_exchange_load_repay WHERE is_plan = 1 AND batch_id = %s GROUP BY repay_id";
        $sql = sprintf($sql, intval($batchId));
        return $this->_writeDB()->getAll($sql);
    }

    public function batchMoneyWriteBack($id, $principal, $interest) {
        $sql  = "UPDATE firstp2p_exchange_batch_repay SET principal = %s, interest = %s, ";
        $sql .= "repay_money = principal + interest + invest_adviser_fee + publish_server_fee + consult_fee + guarantee_fee + hang_server_fee  WHERE id = %s";
        $sql  = sprintf($sql, $principal, $interest, $id);
        return $this->_writeDB()->query($sql);
    }

    public function delBatchRepayByBatchId($batchId) {
        $sql = sprintf("DELETE FROM firstp2p_exchange_batch_repay WHERE batch_id = %s", intval($batchId));
        return $this->_writeDB()->query($sql);
    }

    public function delLoadRepayByBatchId($batchId) {
        $sql = sprintf("DELETE FROM firstp2p_exchange_load_repay WHERE batch_id = %s", intval($batchId));
        return $this->_writeDB()->query($sql);
    }


    public function getLoadList($page = 1, $pageSize = 30, $fields) {
        $sql = sprintf("SELECT %s FROM firstp2p_exchange_load LIMIT %d, %d", $fields, ($page - 1) * $pageSize, $pageSize);
        return $this->_writeDB()->getAll($sql);
    }

    public function updateLoadData($updateData, $condition) {
        $this->db->autoExecute('firstp2p_exchange_load', $updateData, 'UPDATE', $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    //按条件获取批次列表
    public function getBatchList($conditions, $fields = '*') {
        if(!is_string($conditions)){
            return [];
        }

        $sql = sprintf("SELECT %s FROM firstp2p_exchange_batch WHERE %s", $fields, $conditions);
        return $this->_writeDB()->getAll($sql);
    }

}
