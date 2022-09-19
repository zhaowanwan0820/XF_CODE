<?php

/**
 * 多投宝已投项目合同信息
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 * Date: 2015-12-14
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\contract\ContractService;
use core\enum\contract\ContractServiceEnum;
use core\service\duotou\DuotouService;

class FinplanContract extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'loanId' => array('filter' => 'int'),
            'projectId' => array('filter' => 'int'),
            'status' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $params = $this->form->data;

        $user_id = intval($GLOBALS['user_info']['id']);
        //获取智多新用户投资时间s
        $request = array(
            'id' =>  intval($params['loanId']),
        );

        $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanById', $request));
        //根据投资时间来获取对应合同
        $contractlist[] = array(
            'dealId' => intval($params['projectId']),
            'loanId' => intval($params['loanId']),
            'dealStatus' => intval($params['status']),
            'createTime' => intval($response['data']['createTime']),
        );

        // 顾问协议
        $title = '智多新借款合同';
        $contractResponse = ContractService::getLoansContract(ContractServiceEnum::TYPE_DT, $contractlist, $user_id);

        foreach($contractResponse[$params['loanId']] as $v){
            $v['number'] = str_pad($v['number'],34,0,STR_PAD_LEFT);
            $result[] = $v;
        }
        ajax_return($result);
    }
}
