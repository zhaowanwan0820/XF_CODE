<?php

/**
 * @abstract 通过oauth_token获取用户信息
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionService;
use openapi\lib\Tools;

class Info extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "site_id" => array('filter' => 'int', 'option' => array('optional' => true)),
            );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userId = $this->getUserIdByAccessToken();
        $siteId = empty($data['site_id']) ? 1 : $data['site_id'];
        if (empty($userId)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userInfo = UserService::getUserById($userId);
        $accountSummary = AccountService::getUserSummary($userId);

        //存管
        $supervisionService = new SupervisionService();
        $svInfo =$supervisionService->svInfo($userId);
        $userInfo['svStatus'] = $svInfo['status'];
        $userInfo['isSvUser'] = $svInfo['isSvUser'];
        $userInfo['isActivated'] = empty($svInfo['isActivated']) ? 0 : $svInfo['isActivated'];
        $userInfo['isWxFreePayment'] = 0;
        if (!empty($svInfo['status'])) {
            $userInfo['isFreePayment'] = $svInfo['isFreePayment'];
            if($svInfo['isSvUser']){
                $userInfo['svAssets'] = bcadd($svInfo['svMoney'], $accountSummary['cg_principal'], 2);
                $userInfo['svBalance'] = isset($svInfo['svBalance']) ? $svInfo['svBalance']: 0;
                $userInfo['svFreeze'] = isset($svInfo['svFreeze']) ? $svInfo['svFreeze']: 0;
            }
        }

        $userInfo['open_id'] = Tools::getOpenID($userId);
        $this->json_data = array_merge($userInfo,$accountSummary);
        return true;
    }
}
