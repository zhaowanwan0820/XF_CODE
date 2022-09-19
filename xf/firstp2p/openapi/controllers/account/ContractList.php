<?php

namespace openapi\controllers\account;

use libs\rpchRpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;

/**
 * 合同详细列表
 */
class ContractList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            // 标的id
            "id" => array('filter' => 'int'),
            // 分页
            "pn" => array('filter' => 'int', 'option' => array('optional' => true)),
            'ps' => array("filter" => "int", 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $pageNum = $data['pn'] <= 0 ? 1 : $data['pn'];
        $pageSize = $data['ps'] <= 30 ? 30 : $data['ps'];
        $dealId = $data['id'];
        $userName = $data['userName'];
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $dealInfo = $this->rpc->local('DealService\getDeal', array($dealId, true, true));
        if (empty($dealInfo)) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }
        $ret = array();
        $dealRet = array();
        $dealRet['dealName'] = $dealInfo['name'];
        if($dealInfo['deal_type'] == 0){
            $deal_one['loantypeName'] = $GLOBALS['dict']['LOAN_TYPE_CN'][$dealInfo['loantype']];
        }else{
            $deal_one['loantypeName'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
        }
        $dealRet['borrow_amount'] = $dealInfo['borrow_amount'];
        $dealRet['isBxt'] = $dealInfo['isBxt'];
        $dealRet['maxRate'] = $dealInfo['max_rate'];
        $dealRet['incomeFeeRate'] = $dealInfo['income_fee_rate'];
        $dealRet['period'] = ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] . '天' : $dealInfo['repay_time'] . '个月';
        $dealRet['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($dealInfo['deal_type']));
        $dealRet['income_base_rate'] = $dealInfo['income_base_rate'];

        // $deal_user_info = $this->rpc->local('UserService\getUser',array($dealInfo['user_id']));
        // $dealRet['userRealName'] = $deal_user_info['real_name'];
        $dealRet['userRealName'] = get_user_realname($dealInfo['user_id']);

        $userId = $userInfo->userId;
        $userName = $userInfo->userName;
        $userInfo = array('id' => $userId, 'user_name' => $userName);
        //copy hahahah
        $user_agency_info = $this->rpc->local('UserService\getUserAgencyInfoNew', array($userInfo));
        $agency_info = $user_agency_info['agency_info'];
        $is_agency = intval($user_agency_info['is_agency']);


        //委托机构信息
        $user_entrust_info = $this->rpc->local('UserService\getUserEntrustInfo', array($userInfo));
        $entrust_info = $user_entrust_info['entrust_info'];
        $is_entrust = intval($user_entrust_info['is_entrust']);

        //用户角色（资产管理方）
        if (((substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ( (substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NQYZR') OR ( is_numeric($dealInfo['contract_tpl_type']))) {
            $user_advisory_info = $this->rpc->local('UserService\getUserAdvisoryInfo', array($userInfo));
            $advisory_info = $user_advisory_info['advisory_info'];
            $is_advisory = intval($user_advisory_info['is_advisory']);
        }

        $is_borrower = ($userId == $dealInfo['user_id']) ? 1 : 0;
        //合同列表
        if ($is_advisory) {
            $params = array(0, $dealId, 3, 1, 10, $advisory_info['agency_id']);
        } elseif ($is_agency) {
            $params = array(0, $dealId, 2, 1, 10, $agency_info['agency_id']);
        }elseif ($is_entrust) {
            $params = array(0, $dealId, 4, 1, 10, $entrust_info['agency_id']);
        } elseif ($is_borrower) {
            $params = array($userId, $dealId, 1, 1);
        } else {
            $params = array($userId, $dealId, 0, 1);
        }

        //双读逻辑,如果contract_tpl_type为数字则合同存储在合同服务中
        if (is_numeric($dealInfo['contract_tpl_type'])) {
            $contList = $this->rpc->local('ContractNewService\getDealContList', $params);
        } else {
            $contList = $this->rpc->local('ContractService\getDealContList', $params);
        }

        $is_have_sign = 1; //是否已经签署通过
        $sign_num = $contList['count']; //已经签署条数
        $dealRet['sign_num'] = $sign_num;

        if ($is_agency || $is_advisory || $is_borrower|| $is_entrust) {
            if ($is_advisory) {
                $params = array($dealId, $userId, $is_advisory, $contList['count'], $advisory_info['agency_id']);
            } else {
                $params = array($dealId, $userId, $is_agency, $contList['count'], $agency_info['agency_id']);
            }
            if (is_numeric($dealInfo['contract_tpl_type'])) {
                if ($is_borrower) {
                    $params = array($dealId, 1, $userId);
                }
                if ($is_agency) {
                    $params = array($dealId, 2, $dealInfo['agency_id']);
                }
                if ($is_advisory) {
                    $params = array($dealId, 3, $dealInfo['advisory_id']);
                }
                if ($is_entrust) {
                    $params = array($dealId, 5, $dealInfo['entrust_agency_id']);
                }
                $sign_info = $this->rpc->local('ContractNewService\getContSignNum', $params);
            } else {
                $sign_info = $this->rpc->local('ContractService\getContSignNum', $params);
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
