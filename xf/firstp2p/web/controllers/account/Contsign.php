<?php
/**
 * 签署合同
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 合同单个签署
 * @userLock
 */
class Contsign extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'did' => array('filter' => 'int'),//借款id
                'cid' => array('filter' => 'int'),//合同id
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $cont_id = intval ($data ['cid']);
        $deal_id = intval ($data ['did']);
        $user_info = $GLOBALS ['user_info'];

        $redirect_url = url("index");
        if($deal_id <= 0 || $cont_id <= 0){
            return app_redirect($redirect_url);
        }

        //合同为空或者已签署
        $cont_info = $this->rpc->local('ContractService\getContract', array($cont_id));
        if(empty($cont_info) || $cont_info['sign_time'] > 0){
            return app_redirect($redirect_url);
        }

        //单个签署合同
        $params = array($cont_info, array('id' => $user_info['id'], 'user_name' => $user_info['user_name']));
        $sign_result = $this->rpc->local('ContractService\signOneContNew', $params);
        $redirect_url = $sign_result ? "/account/contlist/".$deal_id."?p=".intval($data['p']) : $redirect_url;
        return app_redirect($redirect_url);
    }
}

