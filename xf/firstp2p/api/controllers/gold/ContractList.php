<?php

namespace api\controllers\gold;

use libs\web\Form;
use api\conf\ConstDefine;
use core\service\DealLoanTypeService;
use api\controllers\GoldBaseAction;

class ContractList extends GoldBaseAction {

    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'dealId' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            "buyAmount" => array("filter" => "float", "message" => "克数格式错误"),
            "buyPrice" => array("filter" => "float", "message" => "黄金价格参数错误"),
            "type" => array('filter' => 'int','message' => 'type must int','option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        if(!empty($data['type'])){
            if($data['type'] != self::GOLD_CURRENT_TYPE){
                $this->setErr('ERR_PARAMS_ERROR');
                return false;
            }
            $cont_url = 'https://' . get_host() . "/gold/contract?token={$data['token']}&buyAmount={$data['buyAmount']}&dealId={$data['dealId']}&buyPrice={$data['buyPrice']}&contractTitle='黃金消費服务协议'&type=1";
            $this->tpl->assign('title','黃金消費服务协议' );
            $this->tpl->assign('cont_url', $cont_url);
            $this->tpl->assign('type', $data['type']);   
            return;
        }
        $result['contract'] = array();
        $contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array(intval($data['dealId']),'gold'));
        $contractTitle = urlencode($contpre['gold_cont']['contractTitle']);
        $cont_url = 'https://' . get_host() . "/gold/contract?token={$data['token']}&buyAmount={$data['buyAmount']}&dealId={$data['dealId']}&buyPrice={$data['buyPrice']}&contractTitle={$contractTitle}";

        $contract[] = array(
                "nameSrc" => $contpre['gold_cont']['contractTitle'],
                "name" => urlencode($contpre['gold_cont']['contractTitle']),
                "url" => urlencode($cont_url)
        );

        $result['is_attachment'] = $is_attachment;
        $result['contract'] = $contract;
        if(empty($contract["0"]["name"])){
            $result['contract'] = array();
        }
        $this->tpl->assign('deal', $result);
        $this->tpl->assign('data', $data);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }


}
