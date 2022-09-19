<?php
/**
 * 根据标ID获取合同信息
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/7/26
 * Time: 16:45
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\contract\ContractInvokerService;
use openapi\conf\adddealconf\common\CommonConf;

class GetContractByDealId extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "deal_id" => array("filter" => "required", "message" => "deal_id is required"),//标的ID
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
        $platform = $this->product_type;
        if (empty($platform) || empty($params['deal_id'])) {
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，请检查参数');
            return false;
        }
        $contractInvokerService = new ContractInvokerService();
        $result = $contractInvokerService->getContractByDealId('author',intval($params['deal_id']), $platform);
        $res = array();
        if (!empty($result['list'])){
            $res = $this->handleRes($result['list']);
        }
        $this->json_data = $res;
    }

    public function handleRes($data) {
        $i = 0;
        foreach ($data as $value) {
            foreach ($value as $key => $value) {
                if (!is_int($key)) $tmp["$key"] = $value;
            }
            $res[$i]['id'] = $tmp['id'];
            $res[$i]['title'] = $tmp['title'];
            $res[$i]['number'] = $tmp['number'];
            $res[$i]['deal_id'] = $tmp['deal_id'];
            $i++;
        }
        return $res;
    }

}