<?php


/**
 * @abstract  网信理财账户明细
 * @author    zhangyao<zhangyao1@ucfgroup.com>
 * @date      2018-10-24
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class AccountDetail extends AdminProxyBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
            "user_name" => array("filter" => "string", "option" => array("optional" => true)),
            "mobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/", "optional" => true)),
            "page_num" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $params = $this->form->data;
        $id = $params['id'];
        if(empty($params['id'])){
            $id = $this->getIdByNameOrMObile($params);
            if(!$id){
                $this->json_data = array();
                return true;
            }
        }

        $response = $this->revokeAdmin(array('id' => $id, 'p' => $params['page_num']));
        if(empty($response['list'])) {
            $this->json_data = array();
            return true;
        }

        foreach($response['list'] as $key => $value){
            $response['list'][$key]['log_time'] = date('Y-m-d H:i:s', $value['log_time'] + self::TIMEDIFF);
        }

        $this->json_data = array(
            'list' => $this->outputRes($response['list'], true),
            'nowPage' => intval($response['nowPage']),
            'totalPages' => intval($response['totalPages']),
            'totalRows' => intval($response['totalRows']),
        );
        return true;
    }
}
