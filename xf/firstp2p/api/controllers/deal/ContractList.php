<?php

namespace api\controllers\deal;

use libs\web\Form;
use libs\utils\Aes;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\service\DealLoanTypeService;

class ContractList extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            "money" => array("filter" => "float", "message" => "金额格式错误"),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (!is_numeric($data['id'])){
            $dealId = Aes::decryptForDeal($data['id']);
        }else{
            $dealId = intval($data['id']);
        }
        $dealColumnsStr = 'id, name, rate, repay_time, loantype, borrow_amount, load_money, deal_status, min_loan_money, min_loan_money, deal_crowd, contract_tpl_type, deal_type, type_id';
        $deal = $this->rpc->local('DealService\getManualColumnsVal', array($dealId, $dealColumnsStr));

        if (empty($deal)) {
            //网信查不到的直接转到普惠
            $phWapUrl = app_conf('NCFPH_WAP_HOST') . '/deal/contract_list?id=' . Aes::encryptForDeal($dealId) . "&money={$data['money']}&token=" . $data['token'];
            return app_redirect($phWapUrl);
        }

        $deal['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($deal['deal_type']));
        $deal['isDealExchange'] = $this->rpc->local('DealService\isDealExchange', array($deal['deal_type']));

        $result['contract'] = array();
        $result['deal_type'] = $deal['deal_type'];
        if ($deal['contract_tpl_type']) {
            $cont_url = 'https://' . get_host() . "/deal/contractpre?token={$data['token']}&money={$data['money']}&id={$deal['id']}";
            if ($this->rpc->local("ContractNewService\isAttachmentContract", array($deal['contract_tpl_type']))) {
                $contract_list = $this->rpc->local("ContractNewService\getContractAttachmentByDealLoad", array($deal['id']));
                foreach ($contract_list as $cont) {
                    $contract[] = array(
                        "nameSrc" => $cont['name'],
                        'name' => urlencode($cont['name']),
                        'url' => $cont['url'],
                    );
                }
            } else {
                // 获取合同模板信息列表
                $contract_list = $this->rpc->local('ContractInvokerService\getContractList', array('remoter', $deal['id'], true));
                foreach ($contract_list as $one_contract) {
                    $contract[] = array(
                        "nameSrc" => $one_contract['title'],
                        "name" => urlencode($one_contract['title']),
                        "url" => urlencode($cont_url . '&tplId=' . $one_contract['id'])
                    );
                }
            }
            $result['is_attachment'] = $is_attachment;
            $result['contract'] = $contract;
        }
        $this->tpl->assign('isDealZX', $deal['isDealZX'] == true ? 1 : 0);
        $this->tpl->assign('deal', $result);
        $this->tpl->assign('data', $data);
    }

    public function _after_invoke()
    {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }

    public function valid_money($value)
    {
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
