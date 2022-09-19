<?php
/**
 * Created by PhpStorm.
 * User: duxuefeng
 * Date: 2018/5/31
 * Time: 18:15
 */

namespace openapi\controllers\contract;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\contract\ContractViewerService;
/**
 * 预览合同
 *
 */
class TmpContractPre extends BaseAction
{

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "contractId" => array("filter" => "required", "message" => "contractId is required"),
            "tplId" => array("filter" => "required", "message" => "tplId is required"),
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

        // 1 contractId  tplId  是否存在
        if(!is_numeric($params['contractId']) || ($params['contractId'] <= 0)){
            $this->setErr("ERR_PARAMS_ERROR", "contractId参数错误");
            return false;
        }
        if(!is_numeric($params['tplId']) || ($params['tplId'] <= 0)){
            $this->setErr("ERR_PARAMS_ERROR", "tplId参数错误");
            return false;
        }

        // 2 渲染合同
        $tpl = ContractViewerService::getOneFetchedBeforeContractByTplId($params['contractId'], $params['tplId']);
        $this->tpl->assign('content', $tpl['content']);
        $this->tpl->assign('title', $tpl['title']);
        $this->template = "openapi/views/contract/tmp_contract_pre.html";
        return true;
    }

}
