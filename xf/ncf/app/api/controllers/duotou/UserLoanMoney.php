<?php

/**
 * UserLoanMoney.php
 *
 * @date 2017-03-05
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use core\service\duotou\DuotouService;
use core\service\bwlist\BwlistService;
/**
 * 用户持有资产及收益信息接口
 *
 * Class LoadList
 * @package api\controllers\duotou
 */
class UserLoanMoney extends DuotouBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required")
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->user;
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;

        $token = $data['token'];

        $url = '';
        if (BwlistService::inList('DT_INTEREST_WHITE', $userId)){
            $url = app_conf('NCFPH_WAP_URL').'/duotou/zdx_report?token='.$token;
        }

        $vars = array('userId' => $userId,);
        $responseMoney = $this->callByObject(array('NCFGroup\Duotou\Services\UserStats','getUserDuotouInfo',$vars));
        if(!$responseMoney) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
        }

        $res = array(
            'totalLoanMoney' => number_format($responseMoney['data']['remainMoney'],2),// 持有资产
            'totalRepayInterest' => number_format($responseMoney['data']['totalInterest'],2),// 累计收益
            'totalNoRepayInterest' => number_format($responseMoney['data']['totalNoRepayInterest'],2),//累计未到账收益
            'yuebaoh5' => $url,
        );

        $this->json_data = $res;
    }
}
