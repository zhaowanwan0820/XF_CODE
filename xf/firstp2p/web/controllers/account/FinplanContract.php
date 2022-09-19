<?php

/**
 * 多投宝已投项目合同信息
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 * Date: 2015-12-14
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Protos\Duotou\Enum\DealLoanEnum;
use libs\utils\Rpc;
use NCFGroup\Protos\Contract\RequestGetLoansContract;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

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

    //todo 智多新合同 顾问协议
    public function invoke() {
        $params = $this->form->data;

        $user_id = intval($GLOBALS['user_info']['id']);
        //获取智多新用户投资时间
        $rpc = new Rpc('duotouRpc');
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'id' =>  intval($params['loanId']),
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanById', $request);
        //根据投资时间来获取对应合同
        $contractlist[] = array(
            'dealId' => intval($params['projectId']),
            'loanId' => intval($params['loanId']),
            'dealStatus' => intval($params['status']),
            'createTime' => intval($response['data']['createTime']),
        );

        $title = '智多新借款合同';
        $contractRpc = new Rpc('contractRpc');
        $requestContract = new RequestGetLoansContract;
        $requestContract->setType(1);
        $requestContract->setUserId($user_id);
        $requestContract->setDealInfo($contractlist);
        $contractResponse = $contractRpc->go('NCFGroup\Contract\Services\Contract','getLoansContract',$requestContract);

        foreach($contractResponse->data[$params['loanId']] as $v){
            $v['number'] = str_pad($v['number'],32,0,STR_PAD_LEFT);
            $result[] = $v;
        }

        ajax_return($result);
    }
}
