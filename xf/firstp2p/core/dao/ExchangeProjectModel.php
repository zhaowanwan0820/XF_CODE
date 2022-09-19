<?php

namespace core\dao;

use libs\db\Db;

class ExchangeProjectModel extends BaseModel {
    const RATENUM = 100000;

    private function _writeDB() {
        return Db::getInstance('firstp2p', 'master');
    }

    private function _readDB() {
        return Db::getInstance('firstp2p', 'slave');
    }

    public function isExistStringField($sField, $sString){
        $sql = "SELECT count($sField) as cn FROM firstp2p_exchange_project WHERE `is_ok` = 1 and  `$sField` = '". self::escape_string($sString) ."';";
        $row = $this->_writeDB()->getRow($sql);
        return $row['cn'];
    }

    public function addProject($aProData){
        $row = array();
        $row['approve_number'] = trim($aProData['approve_number']);
        $row['name'] = trim($aProData['name']);
        $row['jys_number'] = trim($aProData['jys_number']);
        $row['jys_id'] = intval($aProData['jys_id']);
        $row['settle_type'] = intval($aProData['settle_type']);
        $row['fx_uid'] = intval($aProData['fx_uid']);
        $row['asset_type'] = intval($aProData['asset_type']);
        $row['consult_id'] = intval($aProData['consult_id']);
        $row['guarantee_id'] = intval($aProData['guarantee_id']);
        $row['invest_adviser_id'] = intval($aProData['invest_adviser_id']);
        $row['business_manage_id'] = intval($aProData['business_manage_id']);
        $row['consult_rate'] = intval(bcmul($aProData['consult_rate'], self::RATENUM));
        $row['consult_type'] = intval($aProData['consult_type']);
        $row['guarantee_rate'] = intval(bcmul($aProData['guarantee_rate'], self::RATENUM));
        $row['guarantee_type'] = intval($aProData['guarantee_type']);
        $row['invest_adviser_rate'] = intval(bcmul($aProData['invest_adviser_rate'], self::RATENUM));
        $row['invest_adviser_real_rate'] = intval(bcmul($aProData['invest_adviser_real_rate'], self::RATENUM));
        $row['invest_adviser_type'] = intval($aProData['invest_adviser_type']);
        $row['publish_server_rate'] = intval(bcmul($aProData['publish_server_rate'], self::RATENUM));
        $row['publish_server_real_rate'] = intval(bcmul($aProData['publish_server_real_rate'], self::RATENUM));
        $row['publish_server_type'] = intval($aProData['publish_server_type']);
        $row['hang_server_rate'] = intval(bcmul($aProData['hang_server_rate'], self::RATENUM));
        $row['hang_server_type'] = intval($aProData['hang_server_type']);
        $row['amount'] = intval($aProData['amount']);
        $row['repay_time'] = intval($aProData['repay_time']);
        $row['repay_type'] = intval($aProData['repay_type']);
        $row['expect_year_rate'] = intval(bcmul($aProData['expect_year_rate'], self::RATENUM));
        $row['lock_days'] = intval($aProData['lock_days']);
        $row['min_amount'] = intval($aProData['min_amount']);
        $row['ahead_repay_rate'] = intval(bcmul($aProData['ahead_repay_rate'], self::RATENUM));
        $row['cuid'] = -1;
        $row['money_todo'] = trim($aProData['money_todo']);

        return $this->_writeDB()->insert('firstp2p_exchange_project', $row);
    }

    public function synpro($id, $aProData){
        if(empty($aProData) || empty($id)){
            return true;
        }
        $sql = "UPDATE firstp2p_exchange_project SET ";
        $aInt = array("invest_adviser_id", "business_manage_id", "settle_type", 'consult_type', 'guarantee_type', 'invest_adviser_type', 'publish_server_type', 'hang_server_type', 'amount', 'repay_time', 'lock_days', 'min_amount', 'repay_type');
        foreach ($aInt as $field) {
            if(!isset($aProData[$field])){
                continue;
            }
            $sql .= " `$field` = ".intval($aProData[$field]) . ",";
        }
        //费率
        $a0to100 = array('consult_rate', 'guarantee_rate', 'invest_adviser_rate', 'invest_adviser_real_rate', 'publish_server_rate', 'publish_server_real_rate', 'hang_server_rate', 'expect_year_rate');
        foreach($a0to100 as $field){
            if(!isset($aProData[$field])){
                continue;
            }
            $sql .= " `$field` = ".intval(bcmul($aProData[$field], self::RATENUM)) . ",";
        }
        //字符串
        $aString = array('money_todo', 'jys_number');
        foreach($aString as $field){
            if(!isset($aProData[$field])){
                continue;
            }
            $sql .= " `$field` = '".self::escape_string(trim($aProData[$field], self::RATENUM)) . "',";
        }
        $sql = rtrim($sql, ',');
        $sql .= " WHERE `id` = ".intval($id);
        return $this->_writeDB()->query($sql);
    }

    public function getByApproveNumber($sApproveNumber){
        $sql = "SELECT * FROM firstp2p_exchange_project WHERE `approve_number` = '". self::escape_string($sApproveNumber) ."' LIMIT 1";
        return $this->_writeDB()->getRow($sql);
    }

    public function getById($iProId){
        $sql = "SELECT * FROM firstp2p_exchange_project WHERE `id` = ". intval($iProId) ." LIMIT 1";
        return $this->_writeDB()->getRow($sql);
    }
}
