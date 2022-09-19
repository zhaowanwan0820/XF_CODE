<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class Discountgiven extends AdminProxyBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "fromUserId" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
            "fromMobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/", "optional" => true)),
            "toUserId" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
            "toMobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/", "optional" => true)),
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
        if(empty($params['fromUserId']) && empty($params['fromMobile']) && empty($params['toUserId']) && empty($params['toMobile'])){
            $this->json_data = array();
            return true;
        }
        $reqParams = array('token'=> \es_session::id());
        if(!empty($params['pageNo'])){
            $reqParams['pageNo'] = max(1, intval($params['pageNo']));
        }
        $reqParams['fromUserId'] = $params['fromUserId'];
        $reqParams['fromMobile'] = $params['fromMobile'];
        $reqParams['toUserId'] = $params['toUserId'];
        $reqParams['toMobile'] = $params['toMobile'];

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
            $temp['discountGroupId'] = $value['discountGroupId'];
            $temp['name'] = $value['name'];
            $temp['typeDesc'] = $value['typeDesc'];
            $temp['fromUserId'] = $value['fromUserId'];
            $temp['fromUserMobile'] = $value['fromUserMobile'];
            $temp['toUserId'] = $value['toUserId'];
            $temp['toMobile'] = $value['toMobile'];
            $temp['createTime'] = date('Y-m-d H:i:s', $value['createTime']);

            $aRet['list'][] = $temp;
        }
        $this->json_data = $aRet;
        return true;
    }
}
