<?php

/**
 * 修改受托支付项目的银行卡账号
 * @author fanjingwen
 * @package openapi\controllers\asm
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use libs\utils\Logger;
use core\service\project\ProjectService;
use openapi\controllers\BaseAction;
use libs\utils\DBDes;

use openapi\conf\ConstDefine;

class DealProjectBankcardUpdater extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
            "bankcard" => array("filter" => "required", "message" => "bankcard is required"),
            "bank_id" => array("filter" => "required", "message" => "bank_id is required"),
            "bankzone" => array("filter" => "required", "message" => "bankzone is required"),
            "card_name" => array("filter" => "required", "message" => "card_name is required"),
            "card_type" => array("filter" => "required", "message" => "card_type is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        try {
            $params = $this->form->data;
            $approve_number = addslashes($params['approve_number']);
            $bankcard = DBDes::encryptOneValue(addslashes($params['bankcard']));
            $bank_id = intval($params['bank_id']);
            $bankzone = addslashes($params['bankzone']);
            $card_name = addslashes($params['card_name']);
            if (!isset($params['card_type']) || !in_array($params['card_type'], ConstDefine::$_CARD_TYPES)) {
                $this->setErr("ERR_PARAMS_ERROR", "card_type is error");
                return false;
            }

            // update
            $projectService = new ProjectService();
            $effectCount = $projectService->updateBankInfo($approve_number,$bankcard,$bank_id,$bankzone,$card_name,intval($params['card_type']));
            if ($effectCount>0 ) {
                // 成功则返回影响的标的数
                $this->json_data = array('affected_deal_count' => $effectCount);
                return true;
            } else {
                throw new \Exception( 'update failed', 1);
            }
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMsg = $e->getMessage();
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('approve_number: %s | new_bankcard: %s', $approve_number, $bankcard), 'error:' . $this->errorMsg, 'line:' . __LINE__)));
            return false;
        }
    }
}
