<?php
/**
 * 合同二次签署
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

class Contresign extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'cid' => array('filter' => 'int'),
                'did' => array('filter' => 'int'),
                'tag' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }

    }

    public function invoke() {

        $data = $this->form->data;
        $tag = $data ['tag'];
        $cont_id = intval ($data ['cid']);
        $deal_id = intval ($data ['did']);

        if($cont_id <= 0 || $deal_id <=0 || !in_array($tag, array('pass','nopass'))){
            return app_redirect(url("index"));
        }

        //合同为空或者已签署
        $cont_info = $this->rpc->local('ContractService\getContract', array($cont_id));
        if(empty($cont_info) || $cont_info['resign_status'] > 0){
            return app_redirect(url("index"));
        }

        $user_id = $GLOBALS ['user_info'] ['id'];
        $status = ($tag == 'pass') ? 1 : 2;
        $params = array($cont_info, array('id' => $user_id, 'user_name' => $GLOBALS['user_info']['user_name']), $status);
        $sign_result = $this->rpc->local('ContractService\resignOneContNew', $params);

        if ($sign_result) {
            return app_redirect("/account/contlist/$deal_id?p=".$data ['p']);
        }
        return app_redirect(url("index"));
    }
}

