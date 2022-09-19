<?php
/**
 * 列表页
 * @author 杨庆<yangqing@ucfgroup.com>
 **/

namespace api\controllers\fund;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Index extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
            "count" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $list = $this->rpc->local('FundService\getList', array($data['offset'],$data['count']));

        $this->json_data = $list['list'];
    }
}
