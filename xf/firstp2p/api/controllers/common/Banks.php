<?php
/**
 * 基础通用接口 
 */
namespace api\controllers\common;

use api\controllers\AppBaseAction;

class Banks extends AppBaseAction {
    public function invoke() {
        $rs = $this->rpc->local("BankService\getBankUserByPaymentMethod");
        $banks = array();
        foreach ($rs as $r) {
            $banks[] = array(
                            'id' => $r->id,
                            'name' => $r->name,
                            'deposit' => $r->deposit,
                        );
        }

        $this->json_data = $banks;
    }
}
