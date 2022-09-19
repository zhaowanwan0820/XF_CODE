<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/9/12
 * Time: 17:59
 */

namespace openapi\controllers\retail;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\DealAgencyService;

/**
 * 获取机构信息
 *
 * Class GetAgencyInfo
 * @package openapi\controllers\retail
 */
class GetAgencyInfo extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "pageNum" => array("filter" => "int"),
            "pageSize" => array("filter" => "int"),
            "name" => array("filter" => "string"),
            "type" => array("filter" => "int")
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
        if (empty($params['type'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'type参数有误');
            return false;
        }
        $ret = $this->rpc->local('DealAgencyService\getListByTypeName', array($params['type'], htmlspecialchars($params['name']), $params['pageNum'], $params['pageSize'], 1));

        $this->json_data = $ret;
    }

}
