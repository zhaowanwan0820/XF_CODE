<?php
/**
 * 下载合同
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/7/27
 * Time: 11:15
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;
use core\service\contract\ContractInvokerService;
use openapi\conf\adddealconf\common\CommonConf;

class DownContract extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "num" => array("filter" => "required", "message" => "num is required"),//合同编号
            "deal_id" => array("filter" => "required", "message" => "deal_id is required"),//合同编号
            "contract_id" => array("filter" => "required", "message" => "contract_id is required"), //合同id
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $contract_id = isset($data['contract_id'])?$data['contract_id']:0;
        $checkCounts = Block::check('WESHARE_CONTRACT_DOWN_MINUTE','weshare_contract_down_minute');
        if ($checkCounts === false) {
            $this->setErr('ERR_MANUAL_REASON','请不要频繁发送请求');
            return false;
        }

        if (empty($data['deal_id']) || empty($data['num'])) {
            $this->setErr("ERR_PARAMS_ERROR", '参数不能为空');
            return false;
        }
        $platform = $this->product_type;
        if (empty($platform)) {
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，请检查参数');
            return false;
        }
        $contractInvokerService = new ContractInvokerService();
        $result = $contractInvokerService->downContract('author',htmlentities($data['num']),intval($data['deal_id']), $platform,$contract_id);
        if (empty($result)) {
            $this->setErr('ERR_MANUAL_REASON','下载合同失败');
            return false;
        }
    }

}