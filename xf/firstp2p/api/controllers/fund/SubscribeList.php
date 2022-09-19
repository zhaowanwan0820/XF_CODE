<?php
/**
 * 基金详情页面H5 AJAX
 * @author 杨庆<yangqing@ucfgroup.com>
 **/

namespace api\controllers\fund;

use libs\web\Form;
use api\controllers\AppBaseAction;

class SubscribeList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
            "offset" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $offset = $this->form->data['offset'];

        $limit = 20;
        $ret = $this->rpc->local('FundSubscribeService\getList', array($id, $offset, $limit, 'create_time'));

        $this->json_data = array(
            'list' => $ret['list'],
            'page' => $ret['page'],
            );

    }
}
