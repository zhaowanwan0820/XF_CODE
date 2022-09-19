<?php
/**
 * ajax方式签署全部合同
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\utils\Logger;
use libs\web\Form;
use web\controllers\BaseAction;

use core\dao\project\DealProjectModel;
use core\service\deal\DealAgencyService;
use core\service\deal\DealService;
use core\service\contract\ContractNewService;
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
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $user_id = intval($GLOBALS ['user_info']['id']);
        $return_res['status'] = 0;

        $deal_id = intval($data ['id']);
        if($user_id == 0 || $deal_id <= 0) {
            return self::return_json($return_res);
        }
        Logger::info(implode(' | ',array(__FILE__,__LINE__,'params:'.json_encode($data))));
        $deal_info = (new DealService())->getDealInfo($deal_id);
        $role = intval($data ['role']);
        if(empty($deal_info)){
            return self::return_json($return_res);
        }
        //判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
        $params = array('id' => $user_id, 'user_name' => $GLOBALS ['user_info']['user_name']);
        $agencyService = new DealAgencyService();
        $user_role = $agencyService->getUserAgencyInfoNew($params);
        $advisory_role = $agencyService->getUserAdvisoryInfo($params);
        $entrust_role =$agencyService->getUserEntrustInfo($params);
        $canal_role = $agencyService->getUserCanalInfo($params);
        $is_agency = intval($user_role['is_agency']);
        $is_advisory = intval($advisory_role['is_advisory']);
        $is_entrust = intval($entrust_role['is_entrust']);
        $is_canal = intval($canal_role['is_canal']);
        $is_borrower = ($user_id == $deal_info['user_id']) ? 1 : 0;
        if($is_agency){
            $agency_id = $user_role['agency_info']['id'];
        }elseif($is_advisory){
            $agency_id = $advisory_role['advisory_info']['id'];
        }elseif($is_entrust){
            $agency_id = $entrust_role['entrust_info']['id'];
        }elseif($is_canal){
            $agency_id = $canal_role['canal_info']['id'];
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
            $sign_info = (new ContractNewService())->signAll($deal_id, $signRole, $user_id);
        }else{
            return self::return_json($return_res);
        }
        $return_res['status'] = $sign_info ? 1 : 0;
        Logger::info(implode(' | ',array(__FILE__,__LINE__,'params:'.json_encode($data),'sign_info:'.$return_res['status'])));
        return self::return_json($return_res);
    }

    public static function return_json($data){
        echo json_encode($data);
        return false;
    }
}

