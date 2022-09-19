<?php

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\deal\DealService;
use core\service\dealload\DealLoadService;
use core\service\contract\ContractNewService;
use core\service\user\UserService;

/**
 * 合同详细列表
 */
class ContList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array(
            // 标的id
            "id" => array('filter' => 'int'),
            "token" => array("filter" => "required", 'message' => "token is required"),
            // 分页
            "pageNo" => array('filter' => 'int', 'option' => array('optional' => true)),
            'pageSize' => array("filter" => "int", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $pageNo = $data['pageNo'] <= 0 ? 1 : $data['pageNo'];
        $pageSize = $data['pageSize'] <= 0 ? 20 : $data['pageSize'];
        $dealId = $data['id'];

        $userInfo = $this->user;

        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($dealId, true, true, true);
        if (empty($dealInfo)) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        $dealLoadService = new DealLoadService();
        $isLoad = $dealLoadService->getUserDealLoad($userInfo['id'], $dealId);
        if (!$isLoad) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        $ret = array();
        $dealRet = array();
        $dealRet['dealId'] = $dealId;
        $dealRet['dealName'] = $dealInfo['name'];

        //普惠P2P标的展示普惠文案
        if($dealInfo['deal_type'] == 0){
            $dealRet['loanTypeName'] = $GLOBALS['dict']['LOAN_TYPE_CN'][$dealInfo['loantype']];
        }else{
            $dealRet['loanTypeName'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
        }
        $dealRet['borrowAmount'] = $dealInfo['borrow_amount'];
        $dealRet['isBxt'] = $dealInfo['isBxt'];
        $dealRet['maxRate'] = $dealInfo['max_rate'];
        $dealRet['incomeFeeRate'] = $dealInfo['income_fee_rate'];
        $dealRet['period'] = ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] . '天' : $dealInfo['repay_time'] . '个月';
        $dealRet['income_base_rate'] = $dealInfo['income_base_rate'];
        $dealRet['userRealName'] = UserService::getUserRealName($dealInfo['user_id']);

        $userId = $userInfo['id'];
        $userName = $userInfo['user_name'];
        $userInfo = array('id' => $userId, 'user_name' => $userName);
        //copy hahahah
        $roleRes = UserService::getUserRole($userInfo);
        if($roleRes === false){
            return $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
        }

        $user_agency_info = $roleRes['user_agency_info'];
        $agency_info = $user_agency_info['agency_info'];
        $is_agency = intval($user_agency_info['is_agency']);


        //委托机构信息
        $user_entrust_info = $roleRes['user_entrust_info'];
        $entrust_info = $user_entrust_info['entrust_info'];
        $is_entrust = intval($user_entrust_info['is_entrust']);

        //用户角色yi（资产管理方）
        if (((substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ( (substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NQYZR') OR ( is_numeric($dealInfo['contract_tpl_type']))) {
            $user_advisory_info = $roleRes['user_advisory_info'];
            $advisory_info = $user_advisory_info['advisory_info'];
            $is_advisory = intval($user_advisory_info['is_advisory']);
        }

        $is_borrower = ($userId == $dealInfo['user_id']) ? 1 : 0;
        //合同列表
        $agencyId = 0;
        if ($is_advisory) {
            $type = 3;
            $agencyId = $advisory_info['agency_id'];
            $id = $dealInfo['advisory_id'];
        } elseif ($is_agency) {
            $type = 2;
            $agencyId = $agency_info['agency_id'];
            $id = $dealInfo['agency_id'];
        }elseif ($is_entrust) {
            $type = 4;
            $agencyId = $entrust_info['agency_id'];
            $id = $dealInfo['entrust_agency_id'];
        } elseif ($is_borrower) {
            $type = 1;
            $id = $userId;
        } else {
            $type = 0;
        }

        $contractNewService = new ContractNewService();
        //双读逻辑,如果contract_tpl_type为数字则合同存储在合同服务中
        if (is_numeric($dealInfo['contract_tpl_type'])) {
            $contList = $contractNewService->getDealContList($userId, $dealId, $type, $pageNo, $pageSize, $agencyId);
        } else {
            return $this->setErr('ERR_DEAL_NOT_EXIST');
        }

        $is_have_sign = 1; //是否已经签署通过
        $sign_num = $contList['count']; //已经签署条数
        $contList['list'] = empty($contList['list']) ? [] : $contList['list'];
        $dealRet['sign_num'] = $sign_num;

        if ($is_agency || $is_advisory || $is_borrower|| $is_entrust) {
            if (is_numeric($dealInfo['contract_tpl_type'])) {
                $sign_info = $contractNewService->getContSignNum($dealId, $type, $id);
            } else {
                return $this->setErr('ERR_DEAL_NOT_EXIST');
            }

            $is_have_sign = $sign_info['is_sign_all'];
            $sign_num = $sign_info['sign_num'];
        }
        $ret['dealInfo'] = $dealRet;
        $ret['dealInfo']['isHaveSign'] = $is_have_sign;
        $ret['dealInfo']['sign_num'] = $sign_num;
        $ret['contlist'] = $contList;
        $this->json_data = $ret;
    }

}
