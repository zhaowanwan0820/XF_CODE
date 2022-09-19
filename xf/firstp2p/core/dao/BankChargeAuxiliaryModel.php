<?php
/**
 * BankChargeAuxiliary class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * BankChargeAuxiliary class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class BankChargeAuxiliaryModel extends BaseModel {
    public function getListByCharge($charge_id) {
        $condition = "status=0 AND charge_id=':charge_id'";
        return $this->findAll($condition, true, '*', array(
                            ':charge_id' => $charge_id,
                        )
                    );
    }
}
