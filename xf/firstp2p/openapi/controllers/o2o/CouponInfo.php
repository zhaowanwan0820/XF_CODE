<?php
/**
 * 优惠券详情接口
 *
 * Date: 2015/6/10
 * Time: 15:56
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;

class CouponInfo extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            'couponId' => array('filter' => 'required'),
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
        $rpcParams = array($user_id);
        $couponDetail = $this->rpc->local('O2OService\getCouponInfo', array($data['couponId']));
        if ($couponDetail === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }

        $urls = parse_url($couponDetail['p2pExchangeUrl']);
        $params = explode("&", $urls['query']);

        foreach($params as $one){
            $tmp = explode("=",$one);
            $couponDetail[$tmp[0]] = $tmp[1];
        }

        $couponDetail['p2pExchangeUrl'] = str_replace("gift", "o2o", $couponDetail['p2pExchangeUrl']);
        $this->json_data = $couponDetail;
        return true;
    }
}
