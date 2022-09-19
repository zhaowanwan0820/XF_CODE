<?php


/**
 * @abstract  黄金账户明细
 * @author    zhangyao<zhangyao1@ucfgroup.com>
 * @date      2018-10-24
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class AccountDetailGold extends AdminProxyBaseAction {
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
            $this->setErr("ERR_PARAMS_ERROR");
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

        $result = $this->outputRes($response['list'], true);
        $user_info = $response['user_info'];
        foreach($result as $key => $value){
            $result[$key]['userName'] = $user_info['user_name'];
            $result[$key]['mobile'] = $user_info['mobile'];
            $result[$key]['logTime'] = date('Y-m-d H:i:s', $value['logTime']);
        }

        $this->json_data = array(
            'list' => $result,
            'nowPage' => intval($response['nowPage']),
            'totalPages' => intval($response['totalPages']),
            'totalRows' => intval($response['totalRows']),
        );
        return true;
    }
}
