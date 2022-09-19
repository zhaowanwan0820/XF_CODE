<?php
/**
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace core\dao;

/**
 * BanklistModel class
 *
 * @author <pengchanglu@ucfgroup.com>
 **/
class BanklistModel extends BaseModel {

    public function getBanklist($city="", $p="",$b="") {
        $params = array(":city" => $city, ":branch" => $b, ":province" => $p);
        $conditions = array();
        if (!empty($p)) {
            $conditions[] = "province = ':province'";
        }
        if (!empty($b)) {
            $conditions[] = "branch = ':branch'";
        }
        $query = implode(" AND ", array_merge(array("city = ':city'"), $conditions));
        $list = $this->findAll($query, true, "*", $params);
        if(!$list && !empty($city)){
            $query = implode(" AND ", array_merge(array("city LIKE '%:city%'"), $conditions));
            $list = $this->findAll($query, true, "*", $params);
        }
        return $list;
    }

    /**
     * 根据银行卡联行号查询公司支行信息
     * @param int $bankId
     * @param string $field
     * @param string $isSlave
     * @return \libs\db\model
     */
    public function getBankInfoByBankId($bankId, $field='*', $isSlave = true) {
        return $this->findBy("bank_id=':bank_id' LIMIT 1", $field, array(':bank_id' => $bankId), $isSlave);
    }

    /**
     * 更新银行支行信息
     * @param int $id
     * @param array $data
     */
    public function saveBankListById($id, $data) {
        if ($id === 0) {
            $this->db->autoExecute('firstp2p_banklist', $data, 'INSERT');
        } else {
            $this->db->autoExecute('firstp2p_banklist', $data, 'UPDATE', "id='{$id}'");
        }
        return $this->db->affected_rows() > 0 ? true : false;
    }
}