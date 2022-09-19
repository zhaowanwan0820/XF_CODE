<?php

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\DealService;
use core\service\ContractNewService;
use core\service\UserService;
use core\service\ncfph\AccountService;
use core\service\ContractService;

/**
 * 合同详细列表
 */
class ContList extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array(
            // 标的id
            "id" => array('filter' => 'int'),
            "token" => array("filter" => "required", 'message' => "token is required"),
            // 分页
            "pageNo" => array('filter' => 'int', 'option' => array('optional' => true)),
            'pageSize' => array("filter" => "int", 'option' => array('optional' => true)),
            "type" => array("filter" => "int", 'option' => array('optional' => true)),  // 不传或者为0，是网信；为1，是普惠
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $pageNo = $data['pageNo'] <= 0 ? 1 : $data['pageNo'];
        $pageSize = $data['pageSize'] <= 0 ? 20 : $data['pageSize'];
        $dealId = $data['id'];
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (isset($data['type']) && $data['type'] == 1) {
            // 访问普惠的接口
            $accountServcie = new AccountService();
            $roleResult = $accountServcie->getContractRole($dealId, $userInfo['id']);

            $sign_info = false;
            if ($roleResult['is_borrower']) {
                $role = 1;
            } elseif ($roleResult['is_agency']) {
                $role = 3;
            } elseif ($roleResult['is_advisory']) {
                $role = 4;
            } elseif ($roleResult['is_canal']) {
                $role = 5;
            } elseif ($roleResult['is_entrust']) {
                $role = 6;
            } else {
                // 投资人
                $role = 0;
            }
            $result = $accountServcie->getContractList($dealId, $userInfo['id'], $role, $pageNo);
            $dealInfo = $result['deal'];
            if (empty($dealInfo)) {
                $this->setErr('ERR_DEAL_NOT_EXIST');
                return false;
            }
            $dealRet = array(
                'dealId' => $dealId,
                'dealName' => $dealInfo['name'],
                'loanTypeName' => $GLOBALS['dict']['LOAN_TYPE_CN'][$dealInfo['loantype']],
                // borrowAmount该借款总额的单位是万元，所以需要乘以10000
                'borrowAmount' => format_price($dealInfo['borrow_amount']*10000, false),
                'isBxt' => $dealInfo['isBxt'],
                'maxRate' => $dealInfo['max_rate'],
                'incomeFeeRate' => $dealInfo['income_fee_rate'],
                'period' => ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] . '天' : $dealInfo['repay_time'] . '个月',
                'userRealName' => get_user_realname($dealInfo['user_id']),
                'income_base_rate' => $dealInfo['income_base_rate'],
                'sign_num' => $result['sign_num'],
            );
            $is_have_sign = $result['is_have_sign'];
            $sign_num = $result['sign_num'];
            $contList = array();
            $contList['list'] = !empty($result['contract']) ? $result['contract'] : array();
            $contList['count'] = !empty($result['contract']) ? count($result['contract']) : 0;
        } else {
            // 访问网信
            $dealInfo = $this->rpc->local('DealService\getDeal', array($dealId, true, true));
            if (empty($dealInfo)) {
                $this->setErr('ERR_DEAL_NOT_EXIST');
                return false;
            }

            $isLoad = $this->rpc->local('DealLoadService\getUserDealLoad', array($userInfo['id'], $dealId));
            if (!$isLoad) {
                $this->setErr('ERR_DEAL_NOT_EXIST');
                return false;
            }

            $ret = array();
            $dealRet = array();
            $dealRet['dealId'] = $dealId;
            $dealRet['dealName'] = $dealInfo['name'];

            //普惠P2P标的展示普惠文案
            if ($dealInfo['deal_type'] == 0) {
                $dealRet['loanTypeName'] = $GLOBALS['dict']['LOAN_TYPE_CN'][$dealInfo['loantype']];
            } else {
                $dealRet['loanTypeName'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
            }
            $dealRet['borrowAmount'] = $dealInfo['borrow_amount'];
            $dealRet['isBxt'] = $dealInfo['isBxt'];
            $dealRet['maxRate'] = $dealInfo['max_rate'];
            $dealRet['incomeFeeRate'] = $dealInfo['income_fee_rate'];
            $dealRet['period'] = ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] . '天' : $dealInfo['repay_time'] . '个月';
            $dealRet['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($dealInfo['deal_type']));
            $dealRet['income_base_rate'] = $dealInfo['income_base_rate'];

            // $deal_user_info = $this->rpc->local('UserService\getUser',array($dealInfo['user_id']));
            // $dealRet['userRealName'] = $deal_user_info['real_name'];
            $dealRet['userRealName'] = get_user_realname($dealInfo['user_id']);

            $userId = $userInfo['id'];
            $userName = $userInfo['user_name'];
            $userInfo = array('id' => $userId, 'user_name' => $userName);
            //copy hahahah
            $user_agency_info = $this->rpc->local('UserService\getUserAgencyInfoNew', array($userInfo));
            $agency_info = $user_agency_info['agency_info'];
            $is_agency = intval($user_agency_info['is_agency']);


            //委托机构信息
            $user_entrust_info = $this->rpc->local('UserService\getUserEntrustInfo', array($userInfo));
            $entrust_info = $user_entrust_info['entrust_info'];
            $is_entrust = intval($user_entrust_info['is_entrust']);

            //用户角色yi（资产管理方）
            if (((substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ((substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NQYZR') OR (is_numeric($dealInfo['contract_tpl_type']))) {
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
            } elseif ($is_entrust) {
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
            $contList['list'] = empty($contList['list']) ? [] : $contList['list'];
            $dealRet['sign_num'] = $sign_num;

            if ($is_agency || $is_advisory || $is_borrower || $is_entrust) {
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
        }

        $ret['dealInfo'] = $dealRet;
        $ret['dealInfo']['isHaveSign'] = $is_have_sign;
        $ret['dealInfo']['sign_num'] = $sign_num;
        $ret['contlist'] = $contList;
        $this->json_data = $ret;
    }

}

