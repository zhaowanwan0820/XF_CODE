<?php
/**
 * 基础通用接口
 */
namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;

class BranchBanks extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            "c" => array("filter" => "required"),
            "branch" => array("filter" => "string", 'option' => array('optional' => true)),
            "province" => array("filter" => "string", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $datas = $this->form->data;

        $rs = $this->rpc->local("BanklistService\getBanklist", array('city'=>$datas['c'], 'p'=>$datas['province'], 'b' => $datas['branch']));
        $banks = array();
        foreach ($rs as $r) {
            $banks[] = array(
                            'id' => $r['id'],
                            'bank_id' => $r['bank_id'],
                            'name' => $r['name'],
                            'branch' => $r['branch'],
                            'city' => $r['city'],
                            'province' => $r['province'],
                        );
        }

        $this->json_data = $banks;
    }
}
