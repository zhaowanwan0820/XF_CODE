<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class Gamelog extends AdminProxyBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "userId" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
            "userMobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/", "optional" => true)),
            "pageNo" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $params = $this->form->data;
        if(empty($params['userId']) && empty($params['userMobile'])){
            $this->json_data = array();
            return true;
        }
        $reqParams = array('token'=> \es_session::id());
        if(!empty($params['pageNo'])){
            $reqParams['pageNo'] = max(1, intval($params['pageNo']));
        }
        $reqParams['userId'] = $params['userId'];
        $reqParams['userMobile'] = $params['userMobile'];

        $response = $this->revokeAdmin($reqParams);
        $reqParams['ajaxTotalCount'] = 1;
        $totalRows = $this->revokeAdmin($reqParams);
        $pageSize = isset($response['page']['pageSize']) ? intval($response['page']['pageSize']) : 10;
        $aRet = array(
            'nowPage'=>$response['page']['pageNo'],
            'totalPages'=>ceil($totalRows/$pageSize),
            'totalRows'=> intval($totalRows),
            'list' => array(),
        );
        foreach ($response['list'] as $key => $value) {
            $temp = array();
            $temp['ownerUserId'] = $value['ownerUserId'];
            $temp['userMobile'] = $value['userMobile'];
            $temp['eventName'] = $value['event'] ? $value['event']['eventName'] : '';
            $temp['prizeName'] = $value['prize'] ? $value['prize']['prizeName'] : '';
            $temp['createTime'] = date('Y-m-d H:i:s', $value['createTime']);

            $aRet['list'][] = $temp;
        }
        $this->json_data = $aRet;
        return true;
    }
}
