<?php
/**
 * 获取授权方指定日期标的列表
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/7/25
 * Time: 17:58
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\adddealconf\common\CommonConf;


class GetDealList extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "date" => array("filter" => "required", "message" => "date is required"),//日期2016/6/28
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

        $platform = CommonConf::getAllowPlateormClientId($params['client_id']);
        if (empty($platform)) {
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，请检查参数');
            return false;
        }
        $result = $this->rpc->local('ContractInvokerService\getDealList', array('author',$platform,$params['date']));
        $this->json_data = $result;
    }

}

