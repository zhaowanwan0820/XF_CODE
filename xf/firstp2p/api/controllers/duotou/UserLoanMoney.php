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
use libs\utils\Rpc;

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
            "token" => array("filter" => "required", "message" => "token is required"),);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }

    }

    public function invoke() {
        if (!$this->dtInvoke())
            return false;
        (new \core\service\ncfph\Proxy())->execute();// 代理请求普惠接口
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
            return false;
        }
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $data = $this->form->data;
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();

        $rpc = new Rpc('duotouRpc');
        if(!$rpc){
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $vars = array(
                'userId' => $userId,
        );
        $request->setVars($vars);
        $responseMoney = $rpc->go('NCFGroup\Duotou\Services\UserStats','getUserDuotouInfo',$request);
        if(!$responseMoney) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $res = array(
                'totalLoanMoney' => number_format($responseMoney['data']['remainMoney'],2),// 持有资产
                'totalRepayInterest' => number_format($responseMoney['data']['totalInterest'],2),// 累计收益
                'totalNoRepayInterest' => number_format($responseMoney['data']['totalNoRepayInterest'],2),//累计未到账收益
        );
        $this->json_data = $res;
    }


}
