<?php

/**
 * @abstract openapi 获取贷款收益
 * @author zhangzhuyan <zhangzhuyan@ucfgroup.com>
 * @since 2016-01-06
 */

namespace openapi\controllers\earning;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RequestBankList;
use NCFGroup\Protos\Ptp\RequestEarningDealsIncome;
use libs\web\Form;
use openapi\controllers\BaseAction;

class GetDealsIncome extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "showAll" => array("filter" => "bool", "option" => array("optional" => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->show($this->form->getErrorMsg(), -2);
        }
    }

    public function invoke()
    {
        $isShowAll = (bool)$this->form->data['showAll'];

        $request = new RequestEarningDealsIncome;
        try {
            $request->isShowAll = $isShowAll;
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $earningResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpEarning',
            'method' => 'getDealsIncome',
            'args' => $request,
        ));

        if (empty($earningResponse)) {
            $this->errorCode = -1;
            $this->errorMsg = "get earning failed";
            return false;
        }

        $this->json_data = $earningResponse->income;
        return true;
    }

}
