<?php

/**
 * 企业用户信息完事验证
 * @author 文岭<liwenling@ucfgroup.com>
 */

namespace web\controllers\enterprise;

use web\controllers\BaseAction;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseRegisterModel;
use core\dao\UserBankcardModel;
use libs\web\Form;


class Validate extends BaseAction {

    public function init() {

    }

    public function invoke() {
        $data = $_POST;
        $userId = $GLOBALS['user_info']['id'];
        $serviceName = '';
        switch ($data['step']){
        case 2:
            $serviceName = 'EnterpriseService\validateStep2';
            break;
        case 3:
            $serviceName = 'EnterpriseService\validateStep3';
            break;
        case  4:
            $serviceName = 'EnterpriseService\validateStep4';
            break;
        default:
            $serviceName = 'EnterpriseService\validateStep1';
            break;
        }

        $checkResultData = $this->rpc->local($serviceName, array($userId, $data));
        $checkResult = ['code' => 0, 'data' => []];
        if (!empty($checkResultData)) {
            $checkResult['data'] = $checkResultData;
            $checkResult['code'] = -1;
        }
        ajax_return($checkResult);
    }
}
