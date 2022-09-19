<?php
/**
 * 获取我的优惠券信息
 *
 * Date: 2015/6/10
 * Time: 15:56
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;

class MyCouponList extends BaseAction {
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            'status' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $user_id = $loginUser->userId;
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $page = $page ? $page : 1;

        // 默认传0，表示不做状态判断
        $status = isset($data['status']) ? intval($data['status']) : 0;
        $rpcParams = array($user_id, $status, $page);
        // 获取我的优惠券相关信息
        $couponList = $this->rpc->local('O2OService\getUserCouponList', $rpcParams);
        if ($couponList === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }

        $this->json_data = $couponList;
        return true;
    }
}