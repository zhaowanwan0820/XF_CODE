<?php

namespace core\dao;
use libs\utils\Site;
use core\dao\UserModel;
use core\dao\DealModel;

/**
 * 转账订单表
 **/
class TransferOrderModel extends BaseModel {

    public function createOrder($data) {

        $data = $this->_prepareData($data);
        $data['create_time'] = time();
        $this->setRow($data);
        $this->_isNew = true;
        $res = $this->save();
        if (!$res) {
            return false;
        }
        return $this->id;
    }

    public function updateOrder($id, $data) {

        $data['id'] = $id;
        $data = $this->_prepareData($data);
        $this->setRow($data);
        $this->_isNew = false;
        $res = $this->save();
        if (!$res) {
            return false;
        }
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function searchOrder($bizOrderId, $bizType, $bizSubType) {
        $condition = 'biz_order_id = ' . intval($bizOrderId) . ' AND biz_type = ' . intval($bizType) . ' AND biz_subtype = ' . intval($bizSubType);
        $orderInfo = $this->findBy($condition);
        return $this->_prepareOrder($orderInfo);
    }

    public function getOrder($id) {
        $orderInfo = $this->find($id);
        return $this->_prepareOrder($orderInfo);
    }

    private function _prepareData($data) {
        foreach ($data as $field => $value) {
            $data[$field] = intval($value);
        }
        return $data;
    }

    private function _prepareOrder($orderInfo) {

        if (empty($orderInfo)) {
            return [];
        }

        return $orderInfo;
    }
}
