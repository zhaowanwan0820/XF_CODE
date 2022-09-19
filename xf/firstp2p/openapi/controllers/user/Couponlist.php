<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class Couponlist extends AdminProxyBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "ownerUserId" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
            "ownerMobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/", "optional" => true)),
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
        if(empty($params['ownerUserId']) && empty($params['ownerMobile'])){
            $this->json_data = array();
            return true;
        }
        $reqParams = array('isArchive' => 0, 'token'=> \es_session::id());
        if(!empty($params['pageNo'])){
            $reqParams['pageNo'] = max(1, intval($params['pageNo']));
        }
        $reqParams['ownerUserId'] = $params['ownerUserId'];
        $reqParams['ownerMobile'] = $params['ownerMobile'];

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
            $temp['couponGroupId'] = $value['couponGroupId'];
            $temp['productName'] = $value['productName'];
            $temp['sourceInfo'] = $value['sourceInfo'];
            $temp['ownerUserId'] = $value['ownerUserId'];
            $temp['ownerMobile'] = $value['ownerMobile'];
            $temp['createTime'] = date('Y-m-d H:i:s', $value['createTime']);
            $temp['useEndTime'] = date('Y-m-d H:i:s', $value['useEndTime']);
            $temp['statusInfo'] = $value['statusInfo'];

            $aRet['list'][] = $temp;
        }
        $this->json_data = $aRet;
        return true;
    }
}
