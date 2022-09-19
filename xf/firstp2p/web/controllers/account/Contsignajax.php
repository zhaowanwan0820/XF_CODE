<?php
/**
 * ajax方式签署全部合同
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

use core\dao\DealProjectModel;

/**
 * 合同批量签署
 * @userLock
 */
class Contsignajax extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'id' => array('filter' => 'int'),//借款id
                'role' => array('filter' => 'int'),
                'type' => array('filter' => 'int'),//类型:0标的,1:项目
        );
        $this->form->validate();
    }

    public function invoke() {

        $data = $this->form->data;
        $user_id = intval($GLOBALS ['user_info']['id']);
        $return_res['status'] = 0;
        $type = empty($data['type'])?0:intval($data['type']);

        if($data['type'] == 1){
            $project_id = intval($data ['id']);
            if($user_id == 0 || $project_id <= 0){
                return self::return_json($return_res);
            }
            $deal_project_model = new DealProjectModel();
            $deal_info = $deal_project_model->getFirstDealByProjectId($project_id);
        }else{
            $deal_id = intval($data ['id']);
            if($user_id == 0 || $deal_id <= 0){
                return self::return_json($return_res);
            }
            $deal_info = $this->rpc->local('DealService\getDeal',array($deal_id, true, false));
        }

        $role = intval($data ['role']);

        if(empty($deal_info)){
            return self::return_json($return_res);
        }
        //判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
        $params = array(array('id' => $user_id, 'user_name' => $GLOBALS ['user_info']['user_name']));
        $user_role = $this->rpc->local('UserService\getUserAgencyInfoNew', $params);
        $advisory_role = $this->rpc->local('UserService\getUserAdvisoryInfo', $params);
        $entrust_role = $this->rpc->local('UserService\getUserEntrustInfo', $params);
        $canal_role = $this->rpc->local('UserService\getUserCanalInfo', $params);
        $is_agency = intval($user_role['is_agency']);
        $is_advisory = intval($advisory_role['is_advisory']);
        $is_entrust = intval($entrust_role['is_entrust']);
        $is_canal = intval($canal_role['is_canal']);
        $is_borrower = ($user_id == $deal_info['user_id']) ? 1 : 0;
        if($is_agency){
            $agency_id = $user_role['agency_info']['agency_id'];
        }elseif($is_advisory){
            $agency_id = $advisory_role['advisory_info']['agency_id'];
        }elseif($is_entrust){
            $agency_id = $entrust_role['entrust_info']['agency_id'];
        }elseif($is_canal){
            $agency_id = $canal_role['canal_info']['agency_id'];
        }

        if(is_numeric($deal_info['contract_tpl_type'])){
            if(($role == 1)&&($is_borrower)){
                $signRole = 1;
            }
            if(($role == 3)&&($is_agency)){
                $signRole = 2;
            }
            if(($role == 4)&&($is_advisory)){
                $signRole = 3;
            }
            if(($role == 5)&&($is_entrust)){
                $signRole = 5;
            }
            if(($role == 6)&&($is_canal)){
                $signRole = 6;
            }
            if($type == 1){
                $sign_info = $this->rpc->local('ContractNewService\signProjectCont',array($project_id, $signRole, $user_id));
            }else{
                $sign_info = $this->rpc->local('ContractNewService\signAll',array($deal_id, $signRole, $user_id));
            }

        }else{
            $sign_info = $this->rpc->local('ContractService\signAll',array($deal_id, $user_id,$is_agency?$is_agency:$is_advisory, $agency_id));
        }
        $return_res['status'] = $sign_info ? 1 : 0;
        return self::return_json($return_res);
    }

    public static function return_json($data){
        echo json_encode($data);
        return false;
    }
}

