<?php
/**
 * 获取用户优惠券列表
 *
 * Date: 2015/7/14
 * Time: 11:48
 * author: CaiDa
 */
namespace openapi\controllers\o2o;
use libs\web\Form;
use openapi\controllers\BaseAction;

class AjaxCouponList extends BaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'status'=>array('filter' => 'int','option' => array('optional' => true)),
            'page'=>array('filter' => 'int','option' => array('optional' => true)),
            'page_size'=>array('filter'=>'int','option'=>array('optional' => true)),
            'oauth_token' => array("filter" => "required", "message" => "token is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $userId = $userInfo->userId;
        $status = empty($data['status']) ? 0 : intval($data['status']);
        $page   = empty($data['page']) ? 1 : intval($data['page']);
        $pageSize = empty($data['page_size']) ? 10 : intval($data['page_size']);
        $rpcParams = array($userId, $status, $page, $pageSize);
        $response = $this->rpc->local('O2OService\getUserCouponList', $rpcParams);
        $this->json_data = $response;
        return true;
    }
}
