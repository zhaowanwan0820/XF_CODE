<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;
use libs\utils\Curl;

class Bonus extends AdminProxyBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "userId" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
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
        if(empty($params['userId'])){
            $this->json_data = array();
            return true;
        }
        $reqParams = array('token'=> \es_session::id(), "userId" => intval($params['userId']));
        if(!empty($params['pageNo'])){
            $reqParams['pageNo'] = max(1, intval($params['pageNo']));
        }

        $response = $this->revokeAdmin($reqParams);
        $itemTypeDict = $response['itemTypeDict'];
        $aRet = array(
            "nowPage"=>intval($response['page']['pageNo']),
            "totalPages"=>intval($response['page']['totalPage']),
            "totalRows"=>intval($response['page']['totalSize']),
            "list" => array(),
            );
        foreach ($response['bonusLogList'] as $key => $value) {
            $temp = array();
            $temp['userId'] = $value['userId'];
            $temp['money'] = $value['money'];
            $status = "";
            if (array_key_exists($value['status'], $itemTypeDict)) {
                if (is_array($itemTypeDict[$value['status']])) {
                    $status = "获取";
                } else {
                    $status = $itemTypeDict[$value['status']];
                }
            }
            $status .= "[".$value['status']."]";
            $temp['status'] = $status;
            $temp['info'] = $value['info'];
            $temp['createTime'] = date('Y-m-d H:i:s', $value['createTime']);
            $temp['expireTime'] = $value['expireTime'] > 0 ? date('Y-m-d H:i:s', $value['expireTime']) : '-';
            $aRet['list'][] = $temp;
        }
        $this->json_data = $aRet;
        return true;
    }
}
