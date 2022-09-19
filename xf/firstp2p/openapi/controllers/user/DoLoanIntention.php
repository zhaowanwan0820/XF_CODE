<?php

/**
 * @abstract  用户借款
 * @author    wangge<wangge@ucfgroup.com>
 * @date      2015-10-27
 */
namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestLoanIntention;

class DoLoanIntention extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "money"  => array("filter" => "reg", "option" => array("regexp" => '/^\d{4,7}$/'), "message" => "借款金额格式错误"),
            "time"   => array("filter" => "reg", "option" => array("regexp" => '/^\d{1,2}$/'), "message" => "借款期限格式错误"),
            "phone"  => array("filter" => "reg", "option" => array("regexp" => '/^\d{11}$/'), "message" => "联系电话格式错误"),
            "addr"   => array("filter" => "length", "option" => array("min" => 1), "message" => "联系地址不能为空"),
            "verify" => array("filter" => "length", "option" => array("min" => 1), "message" => "验证码不能为空" ),
            // 码子，不传的时候默认变现通
            'code' => array('filter' => 'string'),
            // 公司
            'company' => array('filter' => 'string'),
            // 职级
            'wl' => array('filter'=>'string'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        $key = $this->authTokenCaptchaKey($data['oauth_token']);
        $verify = \SiteApp::init()->cache->get($key);
        if (md5($data['verify']) != $verify) {
            $this->setErr("ERR_VERIFY_ILLEGAL");
            return false;
        }

        $request = new RequestLoanIntention();
        $request->setUserId($userInfo->getUserId());
        $request->setMoney(intval($data['money']));
        $request->setTime(intval($data['time']));
        $request->setPhone($data['phone']);
        $request->setAddr($data['addr']);
        // 新加的三个属性
        $request->setCode($data['code']);
        $request->setCompany($data['company']);
        $request->setWl($data['wl']);

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpLoanIntention',
            'method'  => 'addLoanIntention',
            'args'    => $request
        ));

        if (!$response->resCode) {
            $this->json_data = array();
            return true;
        }

        $this->setErr("ERR_MANUAL_REASON", $response->resMsg);
        return false;
    }

}
