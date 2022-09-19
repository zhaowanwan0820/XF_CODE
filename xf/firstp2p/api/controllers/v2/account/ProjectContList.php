<?php

namespace api\controllers\account;

use app\models\dao\Deal;
use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\DealService;
use core\service\ContractNewService;
use core\service\UserService;
use core\service\ContractService;

use core\dao\DealProjectModel;

/**
 * 项目合同详细列表
 */
class ProjectContList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            // 项目id
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
        $projectId = $data['id'];
        $dealProjectModel = new DealProjectModel();
        $project = $dealProjectModel->find(intval($projectId));
        $firstDeal = $dealProjectModel->getFirstDealByProjectId($projectId);
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $dealInfo = $this->rpc->local('DealService\getDeal', array($firstDeal['id'], true, true));
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
        $projectRet = array();
        $projectRet['projectId'] = $projectId;
        $projectRet['projectName'] = $project['name'];
        $projectRet['loanTypeName'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
        $projectRet['borrowAmount'] = $project['borrow_amount'];
        $projectRet['isBxt'] = $dealInfo['isBxt'];
        $projectRet['maxRate'] = $dealInfo['max_rate'];
        $projectRet['incomeFeeRate'] = $dealInfo['income_fee_rate'];
        $projectRet['period'] = ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] . '天' : $dealInfo['repay_time'] . '个月';
        $projectRet['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($dealInfo['deal_type']));
        $projectRet['income_base_rate'] = $dealInfo['income_base_rate'];

        // $deal_user_info = $this->rpc->local('UserService\getUser',array($dealInfo['user_id']));
        // $dealRet['userRealName'] = $deal_user_info['real_name'];
        $projectRet['userRealName'] = get_user_realname($dealInfo['user_id']);

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

        //用户角色（资产管理方）
        if (((substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ( (substr($dealInfo['contract_tpl_type'], 0, 5)) === 'NQYZR') OR ( is_numeric($dealInfo['contract_tpl_type']))) {
            $user_advisory_info = $this->rpc->local('UserService\getUserAdvisoryInfo', array($userInfo));
            $advisory_info = $user_advisory_info['advisory_info'];
            $is_advisory = intval($user_advisory_info['is_advisory']);
        }

        $is_borrower = ($userId == $dealInfo['user_id']) ? 1 : 0;
        //合同列表
        if ($is_advisory) {
            $params = array(0, $projectId, 3, 1, 10, $advisory_info['agency_id']);
        } elseif ($is_agency) {
            $params = array(0, $projectId, 2, 1, 10, $agency_info['agency_id']);
        }elseif ($is_entrust) {
            $params = array(0, $projectId, 4, 1, 10, $entrust_info['agency_id']);
        } elseif ($is_borrower) {
            $params = array($userId, $projectId, 1, 1);
        } else {
            $params = array($userId, $projectId, 0, 1);
        }

        //取项目合同
        $contList = $this->rpc->local('ContractNewService\getProjectContList', $params);

        $is_have_sign = 1; //是否已经签署通过
        $sign_num = $contList['count']; //已经签署条数
        $contList['list'] = empty($contList['list']) ? [] : $contList['list'];
        $dealRet['sign_num'] = $sign_num;

        if ($is_agency || $is_advisory || $is_borrower|| $is_entrust) {
            if ($is_advisory) {
                $params = array($projectId, $userId, $is_advisory, $contList['count'], $advisory_info['agency_id']);
            } else {
                $params = array($projectId, $userId, $is_agency, $contList['count'], $agency_info['agency_id']);
            }

            if ($is_borrower) {
                $params = array($projectId, 1, $userId);
            }
            if ($is_agency) {
                $params = array($projectId, 2, $dealInfo['agency_id']);
            }
            if ($is_advisory) {
                $params = array($projectId, 3, $dealInfo['advisory_id']);
            }
            if ($is_entrust) {
                $params = array($projectId, 5, $dealInfo['entrust_agency_id']);
            }
            $sign_info = $this->rpc->local('ContractNewService\getProjectContSignNum', $params);

            $is_have_sign = $sign_info['is_sign_all'];
            $sign_num = $sign_info['sign_num'];
        }
        $ret['projectInfo'] = $projectRet;
        $ret['projectInfo']['isHaveSign'] = $is_have_sign;
        $ret['projectInfo']['sign_num'] = $sign_num;
        $ret['contlist'] = $contList;
        $this->json_data = $ret;
    }

}
