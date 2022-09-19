<?php
/**
 * Created by PhpStorm.
 * User: gengkuan
 * Date: 2018/11/2
 * Time: 10:00
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 产品相关信息
 *
 * Class GetProductCategory
 * @package openapi\controllers\credit
 */
class GetProductCategory extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "pageNum" => array("filter" => "int"),
            "pageSize" => array("filter" => "int"),
            "name" => array("filter" => "string"),
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
        $ret = $this->rpc->local('DealLoanTypeService\getListByTypeName',array(htmlspecialchars($params['name']), $params['pageNum'], $params['pageSize']));
        $this->json_data = $ret;
    }

}
