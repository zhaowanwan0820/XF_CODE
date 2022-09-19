<?php

namespace api\controllers\account;

use libs\rpc\Rpc;
use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\ncfph\AccountService;
use api\conf\Error;

/**
 * 签署合同
 */
class ContractSignAll extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "ERR_AUTH_FAIL"),
            "id" => array("filter" => "int", "message" => "id is error"),
            "type" => array("filter" => "int", 'option' => array('optional' => true)),  // 不传或者为0，是网信；为1，是普惠
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {

        $data = $this->form->data;
        $dealId = $data['id'];
        $user = $this->getUserByToken();
        if (!$user) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        if (isset($data['type']) && $data['type'] == 1) {
            // 访问普惠的接口
            $accountServcie = new AccountService();
            $result = $accountServcie->getContractRole($dealId, $user['id']);

            $sign_info = false;
            if ($result['is_borrower']) {
                $role = 1;
            } elseif ($result['is_agency']) {
                $role = 3;
            } elseif ($result['is_advisory']) {
                $role = 4;
            } elseif ($result['is_canal']) {
                $role = 5;
            } elseif ($result['is_entrust']) {
                $role = 6;
            }
            $sign_info = $accountServcie->contSignAjax($user['id'], $dealId, $role);
        } else {
            $dealInfo = $this->rpc->local('DealService\getDeal', array($dealId, true, false));
            if (empty($dealInfo)) {
                throw new \Exception('合同签署失败');
            }

            $userId = $user['id'];
            $userName = $user['user_name'];

            //判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
            $params = array(array('id' => $userId, 'user_name' => $userName));
            $user_role = $this->rpc->local('UserService\getUserAgencyInfoNew', $params);
            $advisory_role = $this->rpc->local('UserService\getUserAdvisoryInfo', $params);
            $entrust_role = $this->rpc->local('UserService\getUserEntrustInfo', $params);
            $is_agency = intval($user_role['is_agency']);
            $is_advisory = intval($advisory_role['is_advisory']);
            $is_entrust = intval($entrust_role['is_entrust']);
            $is_borrower = ($userId == $dealInfo['user_id']) ? 1 : 0;

            if ($is_agency) {
                $agency_id = $user_role['agency_info']['agency_id'];
            } elseif ($is_advisory) {
                $agency_id = $advisory_role['advisory_info']['agency_id'];
            } elseif ($is_entrust) {
                $agency_id = $entrust_role['entrust_info']['agency_id'];
            }

            //判断是否已经签署
            if ($is_agency || $is_borrower || $is_advisory || $is_entrust) {
                // 有资格签
                if ($is_borrower) {
                    $sign_info = $this->rpc->local('ContractService\getContSignNum', array($dealId, $userId, 0, 0));
                } else {
                    $sign_info = $this->rpc->local('ContractService\getContSignNum', array($dealId, $userId, 1, $agency_id));
                }
                if (!$sign_info || $sign_info['status'] != 0) {
                    throw new \Exception('合同已经签署');
                }
            } else {
                // 木有资格签署
                throw new \Exception('合同不存在');
            }

            if (is_numeric($dealInfo['contract_tpl_type'])) {
                if ($is_borrower) {
                    $role = 1;
                } elseif ($is_agency) {
                    $role = 2;
                } elseif ($is_advisory) {
                    $role = 3;
                } elseif ($is_entrust) {
                    $role = 5;
                }

                $sign_info = $this->rpc->local('ContractNewService\signAll', array($dealId, $role, $userId));
            } else {
                $sign_info = $this->rpc->local('ContractService\signAll', array($dealId, $userId, $is_agency ? $is_agency : $is_advisory, $agency_id));
            }
        }

        if (!empty($sign_info)) {
            $ret = 'success';
        } else {
            $ret = 'failed';
            throw new \Exception('合同签署失败!');
        }
        $this->json_data = $ret;
    }
}

