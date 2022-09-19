<?php
/**
 * 获取用户未领取优惠券列表
 *
 * Date: 2015/7/14
 * Time: 11:48
 * author: CaiDa
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;

class UnPickList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
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
        $page   = empty($data['page']) ? 1 : intval($data['page']);
        $pageSize   = empty($data['page_size']) ? 10 : intval($data['page_size']);
        $rpcParams = array($userId, $page, $pageSize);
        $unPickList = $this->rpc->local('O2OService\getUnpickList', $rpcParams);
        $this->json_data = $unPickList;
        return true;
    }
}
