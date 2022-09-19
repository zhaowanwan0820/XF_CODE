<?php
namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;

class ExpireEarning extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            //'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'id' => array('filter'=>'required', 'message'=> 'ERR_DEAL_NOT_EXIST'),
            'money' => array('filter'=>array($this, "valid_money"), 'message'=> 'ERR_MONEY_FORMAT'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function valid_money($value) {
        if ($value == null) {
            return true;
        }
        if (floatval($value) == 0) {
            return false;
        }
        if (!preg_match("/^[-]{0,1}[\d]*(\.\d{1,2})?$/", $value)) {
            return false;
        }
        return true;
    }

    public function invoke() {
        $data = $this->form->data;

        //$user_info = $this->getUserByToken();
        //if (!$user_info) {
        //    $this->setErr('ERR_GET_USER_FAIL');
        //    return false;
        //}
        $dealId = $data['id'];
        $money = $data['money'];
        //$earning = $this->rpc->local("EarningService\getEarningMoney", array($dealId, $money, true));
        $earning = \SiteApp::init()->dataCache->call($this->rpc, 'local', array("EarningService\getEarningMoney", array($dealId, $money, true)), 300);
        $result = array();
        $result['earning'] = number_format($earning, 2);
        $this->json_data = $result;
    }

}

