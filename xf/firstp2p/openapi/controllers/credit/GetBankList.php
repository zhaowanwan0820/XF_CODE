<?php
/**
 * Created by PhpStorm.
 * User: gengkuan
 * Date: 2018/11/2
 * Time: 15:00
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use core\dao\DealModel;
use openapi\controllers\BaseAction;

/**
 * 	获取银行项管信息接口
 *
 * Class GetBankList
 * @package openapi\controllers\credit
 */

class GetBankList extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "branch" => array("filter" => "string", "message" => "branch is required"),
            "province" => array("filter" => "string"),
            "c" => array("filter" => "string"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke()
    {
        $params = $this->form->data;
        if (empty($params['branch'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'branch');
            return false;
        }
        $ret = $this->rpc->local('BanklistService\getBanklist',array(htmlspecialchars($params['c']), htmlspecialchars($params['province']), htmlspecialchars($params['branch'])));
        if (empty($ret)) {
            $this->setErr("ERR_MANUAL_REASON", '没有查询到相关的标的记录');
            return false;
        }
        $this->json_data = $ret;
    }

}
