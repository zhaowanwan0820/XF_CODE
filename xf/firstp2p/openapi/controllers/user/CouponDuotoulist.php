<?php

/**
 * @abstract  智多鑫邀请码结算
 * @author    zhangyao<zhangyao1@ucfgroup.com>
 * @date      2018-11-05
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class CouponDuotoulist extends AdminProxyBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "consume_user_id" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
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
        if (!trim($params['consume_user_id']) && !trim($params['mobile'])) {
            $this->setErr("ERR_PARAMS_ERROR", "参数不能为空");
            return false;
        }

        $response = $this->revokeAdmin(array('consume_user_id' => $params['consume_user_id'], 'mobile' => $params['mobile'], 'p' => $params['page_num']));
        $list = $this->outputRes($response['list']);
        if(empty($list)){
            $this->json_data = [];
            return true;
        }

        foreach($list as $key => $value){
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time'] + self::TIMEDIFF);
        }

        $this->json_data = array(
            'list' => $this->getValueFromStyle($list, ['consume_user_name','consume_real_name','refer_real_name']),
            'nowPage' => intval($response['nowPage']),
            'totalPages' => intval($response['totalPages']),
            'totalRows' => intval($response['totalRows']),
        );
        return true;
    }
}
