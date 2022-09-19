<?php
/**
 * 基金调用银行列表接口 
 * @date 2015-08-10
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
namespace api\controllers\fund;

use libs\web\Form;

use api\controllers\FundBaseAction;

class Banks extends FundBaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'signature' => array('filter'=>"required", 'message'=> '签名不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $rs = $this->rpc->local("BankService\getBankUserByPaymentMethod");
        $banks = array();
        foreach ($rs as $r) {
            $banks[] = array(
                            'id' => $r->id,
                            'name' => $r->name,
                            'deposit' => $r->deposit,
                            'short_name'=> $r->short_name,
                            'img' => $r->img,
                            'sort' => $r->sort,
                            'adminid' => $r->adminid,
                        );
        }

        $this->json_data = $banks;
    }
}