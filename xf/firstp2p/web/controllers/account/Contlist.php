<?php
/**
 * 合同中心-合同列表
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;
use libs\web\Form;
use core\dao\DealProjectModel;
use core\dao\DealLoanTypeModel;
use web\controllers\BaseAction;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Contlist extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form('get');
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'id' => array('filter' => 'int'),
                'role' => array('filter' => 'int'),
                'type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {

        $data = $this->form->data;



        $role = intval($data['role']);

        $user_id = intval($GLOBALS['user_info']['id']);

        $page_num = $data['p'] <= 0 ? 1 : intval($data['p']);

        $id = intval($data['id']);

        $type = isset($data['type'])?intval($data['type']):0;
        if($type == 1){
            $dealProjectModel = new DealProjectModel();
            $project = $dealProjectModel->find($data['id'],'*',true);
            $project['borrow_amount'] = format_price($project['borrow_amount'] / 10000,false);
            $firstDeal = $dealProjectModel->getFirstDealByProjectId(intval($data['id']));
            $deal_id = $firstDeal['id'];
        }else{
            $deal_id = $id = intval($data['id']);
            //借款信息
        }

        $deal = $this->rpc->local('DealService\getDeal',array($deal_id, true, true));

        //专享标类型
        $zxDealTypeId = $this->rpc->local('DealLoanTypeService\getIdByTag', array(DealLoanTypeModel::TYPE_GLJH));

        if(empty($deal)){
            return app_redirect(url("index"));
        }
        $deal['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        $deal['borrow_amount'] = format_price($deal['borrow_amount'] / 10000,false);

        $deal_user_info = $this->rpc->local('UserService\getUser',array($deal['user_id']));
        if(empty($deal_user_info)){
            return app_redirect(url("index"));
        }
        $deal['user_real_name'] = $deal['user_deal_name'];

        //用户角色（借款人出借人、担保公司）
        $user_info = array('id' => $user_id, 'user_name' => $GLOBALS['user_info']['user_name']);
        $user_agency_info = $this->rpc->local('UserService\getUserAgencyInfoNew',array($user_info));
        $agency_info = $user_agency_info['agency_info'];
        $is_agency = intval($user_agency_info['is_agency']);

        //用户角色（资产管理方）
        if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR') OR (is_numeric($deal['contract_tpl_type']))){
            $user_advisory_info = $this->rpc->local('UserService\getUserAdvisoryInfo',array($user_info));
            $advisory_info = $user_advisory_info['advisory_info'];
            $is_advisory = intval($user_advisory_info['is_advisory']);
        }

        $user_entrust_info = $this->rpc->local('UserService\getUserEntrustInfo',array($user_info));
        $entrust_info = $user_entrust_info['entrust_info'];
        $is_entrust = intval($user_entrust_info['is_entrust']);

        $user_canal_info = $this->rpc->local('UserService\getUserCanalInfo',array($user_info));
        $canal_info = $user_canal_info['canal_info'];
        $is_canal = intval($user_canal_info['is_canal']);

        $is_borrower = ($user_id == $deal['user_id']) ? 1 : 0;

        //合同列表
        if(($role == 1) && ($is_borrower)){
            $params = array($user_id, $id, 1, $page_num);
        }else if (($role == 3) && ($is_agency)){
            $params = array(0, $id, 2, $page_num,10,$agency_info['agency_id']);
        }else if (($role == 4) && ($is_advisory)){
            $params = array(0, $id, 3, $page_num,10,$advisory_info['agency_id']);
        }else if (($role == 5) && ($is_entrust)){
            $params = array(0, $id, 4, $page_num,10,$entrust_info['agency_id']);
        }else if (($role == 6) && ($is_canal)){
            $params = array(0, $id, 5, $page_num,10,$canal_info['agency_id']);
        }else{
            $params = array($user_id, $id, 0, $page_num);
        }

        $isDt = $this->rpc->local('DealService\isDealDT',array($deal['id']));

        //双读逻辑,如果contract_tpl_type为数字则合同存储在合同服务中
        if (is_numeric($deal['contract_tpl_type'])) {
            if($type == 1){
                $result = $this->rpc->local('ContractNewService\getProjectContList', $params);
            }else{
                $result = $this->rpc->local('ContractNewService\getDealContList', $params);
            }
        } else {
            if ($is_borrower) {
                $params = array($user_id, $deal_id, 0, $page_num);
            }
            $result = $this->rpc->local('ContractService\getDealContList', $params);
        }

        //列表为空时，跳转到上级页面
        if($result['count'] == 0){
            return app_redirect("/account/contract");
        }

        foreach($result['list'] as $k => $v){
            if($v['isDt'] <> 1){
                $tsaRet = $this->rpc->local('ContractSignService\getSignedContractListByNum', array($v['number']));
                if(!empty($tsaRet) && !empty($tsaRet[0])){
                    $result['list'][$k]['hasTsa'] = 1;
                    $result['list'][$k]['tsaInfo']['createTimeStr'] = date('Y-m-d H:i:s',$tsaRet[0]['create_time']);
                }else{
                    $result['list'][$k]['hasTsa'] = 0;
                }
                $reNew = $this->rpc->local('ContractService\contractRenewExist', array($v['id']));
                if(!empty($reNew)){
                    $result['list'][$k]['hasRenew'] = 1;
                    $result['list'][$k]['renewTime'] = date('Y-m-d H:i:s',$reNew['create_time']);
                }else{
                    $result['list'][$k]['hasRenew'] = 0;
                }

                // -----------------临时摘掉时间戳展现----------------------
                //$result['list'][$k]['hasTsa'] = 1;
                //$result['list'][$k]['hasRenew'] = 0;
                // -----------------临时摘掉时间戳展现----------------------
            }
        }

        $is_have_sign = 1;//是否已经签署通过
        $sign_num = $result['count'];//已经签署条数
        if(($is_agency || $is_advisory|| $is_borrower||$is_entrust||$is_canal) &&($role<>2)){
            if($role == 3){
                $params = array($deal_id, $user_id, $is_advisory, $result['count'],$advisory_info['agency_id']);
            }elseif($role == 5){
                $params = array($deal_id, $user_id, $is_entrust, $result['count'],$entrust_info['agency_id']);
            }elseif($role == 6){
                $params = array($deal_id, $user_id, $is_canal, $result['count'],$canal_info['agency_id']);
            }else{
                $params = array($deal_id, $user_id, $is_agency, $result['count'],$agency_info['agency_id']);
            }
            if(is_numeric($deal['contract_tpl_type'])){
                if($role == 1){
                    $params = array($id,1,$user_id);
                }
                if($role == 3){
                    $params = array($id,2,$deal['agency_id']);
                }
                if($role == 4){
                    $params = array($id,3,$deal['advisory_id']);
                }
                if($role == 5){
                    $params = array($id,5,$deal['entrust_agency_id']);
                }
                if($role == 6){
                    $params = array($id,6,$deal['canal_agency_id']);
                }

                if($type == 1){
                    $sign_info = $this->rpc->local('ContractNewService\getProjectContSignNum', $params);
                }else{
                    $sign_info = $this->rpc->local('ContractNewService\getContSignNum', $params);
                }
            }else{
                $sign_info = $this->rpc->local('ContractService\getContSignNum', $params);
            }


            $is_have_sign = $sign_info['is_sign_all'];
            $sign_num = $sign_info['sign_num'];

            if(($sign_num > 0)&& $isDt){
                $sign_num = $result['count'];
                $is_have_sign = 1;
            }

        }else{
            $is_loan = true;
        }

        $page = new \Page ( $result['count'], app_conf ( "PAGE_SIZE" ) );

        if($type == 1){
            $this->tpl->assign ( "is_project", '1');
            $this->tpl->assign ( "project", $project );
        }

        if($isDt == 1){
            $this->tpl->assign ( "isDt", '1');
        }
        
        $this->tpl->assign ( "p", $page_num );
        $this->tpl->assign ( "deal", $deal );
        $this->tpl->assign ( "type", $type );
        $this->tpl->assign ( "role", $role );
        $this->tpl->assign ( "sign_num", $sign_num );
        $this->tpl->assign ( "is_agency", $is_agency );
        $this->tpl->assign ( "is_advisory", $is_advisory );
        $this->tpl->assign ( "is_borrower", $is_borrower );
        $this->tpl->assign ( "is_loan", $is_loan );
        $this->tpl->assign ( "is_have_sign", $is_have_sign );
        $this->tpl->assign ( "pages", $page->show () );
        $this->tpl->assign ( "contract", $result['list'] );
        $this->tpl->assign ( "zxDealTypeId", $zxDealTypeId );
        $this->tpl->assign ( "page_title", '我的合同' );
        $this->tpl->assign ( "inc_file", 'web/views/account/contract_list.html' );
        $this->template = "web/views/account/frame.html";
    }
}

