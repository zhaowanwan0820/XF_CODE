<?php

/**
 * NewUserCenter.php
 *
 * @date 2017-06-15
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace openapi\controllers\account;

use openapi\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

/**
 * 新手专区接口
 *
 * Class NewUserCenter
 * @package openapi\controllers\account
 */
class NewUserCenter extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "oauth_token" => array("filter" => "string", "option" => array('optional' => true)),
                "siteId" => array("filter" => "int", "message" => "id error"),
                "clientInviteCode" => array("filter" => "string", "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $siteId = !empty($params['siteId']) ? intval($params['siteId']) : 1;
        if(!empty($params['oauth_token'])){
            $userInfo = $this->getUserByAccessToken();
            if (empty($userInfo)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
        }
        $userId = isset($userInfo) ? $userInfo->userId : null;
        $clientInviteCode = !empty($params['clientInviteCode']) ? trim($params['clientInviteCode']) : null;
        $request = new SimpleRequestBase();
        $request->setParamArray(array('userId' => $userId, 'siteId' => $siteId, 'clientInviteCode' => $clientInviteCode, 'time' =>date("Y-m-d")));
        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpNewUserCenter',
                'method' => 'getNewUserCenterInfo',
                'args' => $request
        ));
        if($response->code != 0){
            $this->setErr('ERR_SYSTEM',$response->resMessage);
            return false;
        }
        if(empty($response->res['imgList'])){
            $this->setErr('ERR_SYSTEM','图片配置不能为空');
            return false;
        }

        $this->json_data = $response;
    }

}
