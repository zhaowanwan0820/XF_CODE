<?php

namespace api\controllers\deal;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\service\contract\ContractNewService;
use core\service\contract\ContractRemoterService;
use core\service\deal\DealService;

class ContractList extends AppBaseAction {

    protected $redirectWapUrl = '/deal/contract_list';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true
                ),
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $dealId = intval($data['id']);
        $dealColumnsStr = 'id, name, rate, repay_time, loantype, borrow_amount, load_money, deal_status, min_loan_money, min_loan_money, deal_crowd, contract_tpl_type, deal_type, type_id';
        $dealService = new DealService();
        $deal = $dealService->getManualColumnsVal($dealId, $dealColumnsStr);

        $result['contract'] = array();
        $result['deal_type'] = $deal['deal_type'];
        if ($deal['contract_tpl_type']) {
            $cont_url = 'https://' . get_host() . "/deal/contractpre?token={$data['token']}&money={$data['money']}&id={$deal['id']}";
            $contractNewService = new ContractNewService();
            $is_attachment = false; // 是否是附件合同
            if ($contractNewService->isAttachmentContract($deal['contract_tpl_type'])) {
                $contract_list = $contractNewService->getContractAttachmentByDealLoad($deal['id']);
                foreach ($contract_list as $cont) {
                    $contract[] = array(
                        "nameSrc" => $cont['name'],
                        'name' => urlencode($cont['name']),
                        'url' => $cont['url'],
                        );
                }
                $is_attachment = true;
            } else {
                // 获取合同模板信息列表
                $contract_list = ContractRemoterService::getContractList($deal['id'], true);
                foreach ($contract_list as $one_contract) {
                    $contract[] = array(
                        "nameSrc" => $one_contract['title'],
                        "name" => urlencode($one_contract['title']),
                        "url" => urlencode($cont_url . '&tplId='. $one_contract['id'])
                    );
                }
            }
            $result['is_attachment'] = $is_attachment;
            $result['contract'] = $contract;
        }
        $this->json_data = array('deal' => $result);
    }

    public function valid_money($value) {
        if ($value == null) {
            return true;
        }
        if (floatval($value) == 0) {
            return false;
        }
        if (!preg_match("/^[-]{0,1}[\d]*(\.\d{1,2})?$/", $value)) {
            return false;
        }
        return true;
    }

}
