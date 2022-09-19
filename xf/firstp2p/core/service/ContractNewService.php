<?php
/**
 * ContractService.php
 *
 * @date 2014-03-25
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealProjectModel;
use core\service\DealService;
use core\service\DealAgencyService;
use core\service\EarningService;
use core\service\UserService;
use core\service\ContractSignService;
use core\service\contract\ContractUtilsService;
use core\dao\LoanOplogModel;
use core\event\ContractSignEvent;
use core\event\ReserveDealContractCacheEvent;
use libs\utils\Logger;

use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\ContractModel;
use core\dao\ContractRenewModel;
use core\dao\ContractContentModel;
use core\dao\UserModel;
use core\dao\DealLoadModel;
use core\dao\DealLoanTypeModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\DealContractModel;
use core\dao\JobsModel;
use core\service\TaskService;
use core\service\DealLoanTypeService;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;

use NCFGroup\Protos\Contract\RequestGetCategoryById;
use NCFGroup\Protos\Contract\RequestGetDealSignNum;
use NCFGroup\Protos\Contract\RequestSignDealContract;
use NCFGroup\Protos\Contract\RequestSignProjectContract;
use NCFGroup\Protos\Contract\RequestGetDealTsaInfo;
use NCFGroup\Protos\Contract\RequestGetCategorys;
use NCFGroup\Protos\Contract\RequestCategoryList;
use NCFGroup\Protos\Contract\RequestGetCategoriesLikeTypeTag;
use NCFGroup\Protos\Contract\RequestGetProjectContractByUserRole;
use NCFGroup\Protos\Contract\RequestGetProjectSignNum;

use NCFGroup\Protos\Contract\RequestGetContractByCid;
use NCFGroup\Protos\Contract\RequestGetContractByProject;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Protos\Contract\RequestGetContractByProjectId;
use NCFGroup\Protos\Contract\RequestGetRoleContractByDealId;
use NCFGroup\Protos\Contract\RequestGetContractByDealNum;
use NCFGroup\Protos\Contract\RequestGetDealCId;
use NCFGroup\Protos\Contract\RequestGetTplByCid;
use NCFGroup\Protos\Contract\RequestGetRoleContractByProjectId;
use NCFGroup\Protos\Contract\RequestGetContractByLoadId;
use libs\utils\Rpc;
use openapi\controllers\account\Contract;
use web\controllers\rss\Deal;
use NCFGroup\Protos\Gold\RequestCommon;
use core\service\GoldService;

/**
 * Class ContractService
 * @package core\service
 */
class ContractNewService extends BaseService {

    const ROLE_BORROWER = 1; //借款人
    const ROLE_LENDER = 2; //出借人
    const ROLE_GUARANTOR = 3; //保证人（已无保证人）
    const ROLE_AGENCY = 3; //担保公司
    const ROLE_ADVISORY = 4;//资产管理方
    const ROLE_ENTRUST = 5;//资产管理方
    const ROLE_CANAL = 6;//渠道方

    const CONT_LOAN = 1;//借款合同
    const CONT_GUARANT = 4;//保证合同
    const CONT_LENDER = 5;//出借人平台服务协议
    const CONT_ASSETS = 7;//资产收益权回购通知
    const CONT_ENTRUST = 8;//委托投资协议

    //交易所
    const CONT_SUBSCRIBE = 20; //交易所-认购协议
    const CONT_PERCEPTION = 21; //交易所--风险认知书
    const CONT_RAISE = 22; //交易所-募集说明书
    const CONT_QUALIFIED = 23; //交易所-合格投资者标准

    //专享
    const PROJECT_ENTRUST = 99; //项目委托投资说明

    /**
     * 查询合同
     *
     * @param int $id 合同id
     * @return array
     */
    public function getContract($id, $dealId, $need_content=false, $old=false){
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByCid();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setId(intval($id));
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByCid",$contractRequest);
        if($response->errorCode == 0){
            $contact = $response->data[0];
        }else{
            $contact = ContractModel::instance()->find($id, '*', true);
            if($need_content){
                if($old){
                    $renewContractInfo = '';
                }else{
                    $renewContractInfo = \core\dao\ContractRenewModel::instance()->findByViaSlave("`number`='".$contact['number']."'");
                }

                if($renewContractInfo){
                    $text = $renewContractInfo['content'];
                }
                if($text == ''){
                    $text = ContractContentModel::instance()->find($id);
                }
                $contact['content'] = $text;
            }
            return $contact;
        }

        return $contact;
    }

    /**
     * 查询合同
     *
     * @param int $id 合同id
     * @return array
     */
    public function getContractByProject($id, $projectId){
        $project = DealProjectModel::instance()->find(intval($projectId));

        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByProject();
        $contractRequest->setProjectId(intval($projectId));
        $contractRequest->setId(intval($id));
        $contractRequest->setSourceType($project['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByProject",$contractRequest);
        if($response->errorCode == 0){
            $contract = $response->data[0];
        }

        return $contract;
    }

    /**
     * 查询合同
     *
     * @param int $id 合同id
     * @return array
     */
    public function getContractByGoldDeal($id, $dealId,$type){
        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByCid();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setId(intval($id));
        $contractRequest->setSourceType($type);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByCid",$contractRequest);
        if($response->errorCode == 0){
            $contract = $response->data[0];
        }
        return $contract;
    }
    public function getContractByDealNum($num, $dealId){
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByDealNum();
        $contractRequest->setDealId($dealId);
        $contractRequest->setNum($num);
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByDealNum",$contractRequest);

        if($response->errorCode == 0){
            return $response->data[0];
        }else{
            return false;
        }
    }

    public function showContract($id,$dealId,$projectId=0,$type=0){
        if($type == 0){
            $cont_info = $this->getContract($id, $dealId, true);
        }elseif($type == 100){
            $cont_info = $this->getContractByGoldDeal($id, $dealId,$type);
        } else{
            $cont_info = $this->getContractByProject($id, $projectId);
        }
        //如果没有合同纪录，直接返回
        if(empty($cont_info)){
            return false;
        }
        // 如果只是没有合同内容，补发合同修复先,不打戳
        if(empty($cont_info['content'])){
            if($type == 0){
                $dealId = $cont_info['deal_id'];
                $content = $this->contractRenewById($dealId, '', $id ,false);
                $cont_info = $this->getContract($id,$dealId ,true);
                $cont_info['content'] = $content;
            }else if($type == 1){
                $projectId = $cont_info['project_id'];
                $content = $this->getProjectContractCont($projectId, $cont_info['type'] ,$id ,false);
                $cont_info['content'] = $content;
            }else if($type == 100){
                $dealId = $cont_info['deal_id'];
                $content = $this->getGoldContractCont($dealId, '', $id ,false);
                $cont_info['content'] = $content;
            }

        }

        return $cont_info;

    }


    /**
     * 根据借款ID补发指定合同类型
     * @param $deal_id
     * @return bool
     */
    public function contractRenewById($dealId, $cont_type = '', $contId, $needTsa = false){

        \FP::import("libs.common.app");
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($dealId);
        $contract = $this->getContract($contId,$dealId);
        $contract_libs = new \system\libs\updateContract();  //引入合同操作类
        $num = 0;
        if($deal && $contract){
            // 引入异步队列
            $taskService = new GTaskService();

            $contract_libs = new \system\libs\updateContractNew();  //引入合同操作类
            $dealagency_service = new DealAgencyService();

            $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
            $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
            $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//资产管理方信息

            $guarantor_list = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']);

            $earning_service = new EarningService();
            $all_repay_money = sprintf("%.2f", $earning_service->getRepayMoney($deal['id']));
            $borrow_user_info['repay_money'] = $all_repay_money;
            $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
            $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
            $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
            $borrow_user_info['leasing_money'] = $deal['leasing_money'];
            $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
            $borrow_user_info['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
            $borrow_user_info['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
            $borrow_user_info['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

            $deal_load_model = new DealLoadModel();

            // 这部分代码用来判断重新生成的合同是否已经签署过
            //借款人id
            $borrower_id = $deal['user_id'];
            $contract_type = $contract['type'];

            if($contract['user_id'] == 0 && $contract['borrow_user_id'] <> 0){
                $role = self::getContractRole($contract['borrow_user_id'], $contract['type'], $contract['agency_id'], $deal['user_id']);
            }else{
                $role = self::getContractRole($contract['user_id'], $contract['type'], $contract['agency_id'], $deal['user_id']);

            }
            $load_id = $contract['deal_load_id'];
//            if($load_id <= 0){
//                $load_id = $this->getLoadInfoByCont($contract, $role, $borrow_user_info['user_id']);
//            }
                $tpls = $this->getTplsByDeal($dealId);
                //投资记录
                $res = 0;
                $loan_user_info = $deal_load_model->getLoadDetailInfo($deal['id'], $load_id);

                $loan_v2 = false;
                $borrower_protocal_v2 = false;
                foreach($tpls as $tpl){
                    if(strchr($tpl['name'],"TPL_LOAN_CONTRACT_V2_NQYZR") || strchr($tpl['name'],"TPL_LOAN_CONTRACT_V2_NGRZR")){
                        $loan_v2 = true;
                    }else if(strchr($tpl['name'],"TPL_BORROWER_PROTOCAL_V2_NQYZR") || strchr($tpl['name'],"TPL_BORROWER_PROTOCAL_V2_NGRZR")){
                        $borrower_protocal_v2 = true;
                    }
                }
                if($contract_type == 1){//借款合同
                    if($loan_v2){
                        $res = $contract_libs->push_loan_contract_v2($contract, $deal, $loan_user_info, $borrow_user_info);
                    }else{
                        $res = $contract_libs->push_loan_contract($contract, $deal, $loan_user_info, $borrow_user_info);
                    }
                }elseif($contract_type == 2){//委托担保合同
                    //担保公司、借款人
                    $res = $contract_libs->push_entrust_warrant_contract($contract, $deal, $guarantor_list, $loan_user_info, $borrow_user_info, $agency_info);
                }elseif($contract_type == 4){//保证合同
                        //出借人、担保公司
                    $res = $contract_libs->push_warrant_contract($contract, $deal, $loan_user_info, $borrow_user_info, $agency_info);
                }elseif($contract_type == 8){//新版委托合同
                    //出借人、担保公司
                    $res = $contract_libs->push_entrust_contract($contract, $deal, $loan_user_info, $borrow_user_info, $agency_info);
                }elseif($contract_type == 5){//出借人平台服务协议(借款人平台服务协议)
                    if($borrower_protocal_v2){
                        if($role == 1){//借款人
                            $res = $contract_libs->push_borrower_protocal_v2($contract, $deal, $borrow_user_info);
                        }elseif($role == 3){//资产管理方
                            $res = $contract_libs->push_borrower_protocal_v2($contract, $deal, $borrow_user_info);
                        }
                    }else{
                        if($role == 1){//借款人
                            $res = $contract_libs->push_borrower_protocal($contract, $deal, $borrow_user_info);
                        }elseif($role == 2){//出借人
                            $res = $contract_libs->push_lender_protocal($contract, $deal, $loan_user_info, $borrow_user_info);
                        }
                    }
                }elseif($contract_type == 7){//资产收益权回购通知
                    $res = $contract_libs->push_buyback_notification($contract, $deal, $loan_user_info, $borrow_user_info);
                }elseif($contract_type == self::PROJECT_ENTRUST){//资产收益权回购通知
                    $res = $contract_libs->push_project_entrust($contract, $deal);
                }elseif($contract_type == self::CONT_SUBSCRIBE){//资产收益权回购通知
                    $res = $contract_libs->push_exchange_contract($contract, $deal, $loan_user_info, $borrow_user_info,1);
                }elseif($contract_type == self::CONT_PERCEPTION){//资产收益权回购通知
                    $res = $contract_libs->push_exchange_contract($contract, $deal, $loan_user_info, $borrow_user_info,2);
                }elseif($contract_type == self::CONT_RAISE){//资产收益权回购通知
                    $res = $contract_libs->push_exchange_contract($contract, $deal, $loan_user_info, $borrow_user_info,3);
                }elseif($contract_type == self::CONT_QUALIFIED){//资产收益权回购通知
                    $res = $contract_libs->push_exchange_contract($contract, $deal, $loan_user_info, $borrow_user_info,4);
                }
                return $res;
        }
        return array('count' => 1, 'num' => $num);
    }

    /**
     * 根据借款ID补发指定合同类型
     * @param $deal_id
     * @return bool
     */
    public function getProjectContractCont($projectId, $cont_type = '', $contId){

        \FP::import("libs.common.app");
        $deal_service = new DealService();
        $dealProjectModel = new DealProjectModel();
        $deal = $dealProjectModel->getFirstDealByProjectId(intval($projectId));
        $contract = $this->getContractByProject($contId,$projectId);
        $num = 0;
        if($deal && $contract){

            $contract_libs = new \system\libs\updateContractNew();  //引入合同操作类
            $dealagency_service = new DealAgencyService();

            $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
            $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
            $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//资产管理方信息

            $guarantor_list = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']);

            $earning_service = new EarningService();
            $all_repay_money = sprintf("%.2f", $earning_service->getRepayMoney($deal['id']));
            $borrow_user_info['repay_money'] = $all_repay_money;
            $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
            $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
            $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
            $borrow_user_info['leasing_money'] = $deal['leasing_money'];
            $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
            $borrow_user_info['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
            $borrow_user_info['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
            $borrow_user_info['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

            $contract_type = $contract['type'];

            if($contract['user_id'] == 0 && $contract['borrow_user_id'] <> 0){
                $role = self::getContractRole($contract['borrow_user_id'], $contract['type'], $contract['agency_id'], $deal['user_id']);
            }else{
                $role = self::getContractRole($contract['user_id'], $contract['type'], $contract['agency_id'], $deal['user_id']);

            }

            $tpls = $this->getTplsByDeal($dealId);

            if($contract_type == 99){//借款合同
                $res = $contract_libs->push_project_loan_transfer($contract, $deal, $borrow_user_info);
            }

            return $res;
        }else{
            return false;
        }

    }

    /**
     * 黄金补发指定合同类型
     * @param $deal_id
     * @return bool
     */
    public function getGoldContractCont($dealId, $cont_type = '', $contId){
        \FP::import("libs.common.app");
        $rpc = new Rpc('goldRpc');
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$dealId));
        $response = $rpc->go("\NCFGroup\Gold\Services\Deal","getDealById",$request);
        $deal = $response['data'];
        if($response && ($response->errorCode != 0)) {
            throw new \Exception('RPC gold is fail!');
        }
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByCid();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setId(intval($contId));
        $contractRequest->setSourceType(100);
        $contractresponse = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByCid",$contractRequest);
        if($contractresponse->errorCode == 0){
            $contract = $contractresponse->data[0];
        }
        $load_id = $contract["deal_load_id"];
        $deal_service = new DealService();
        $num = 0;
        if($deal & $contract ){
            $contract_libs = new \system\libs\updateContractNew();  //引入合同操作类
            $dealagency_service = new DealAgencyService();
            $deal["user_id"] =  $deal["userId"];
            $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
            $borrow_user_info['repay_money'] = $all_repay_money;
            $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
            $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
            $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
            $borrow_user_info['leasing_money'] = $deal['leasing_money'];
            $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
            $borrow_user_info['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
            $borrow_user_info['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
            $borrow_user_info['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");
            $contract_type = $contract['type'];
            if($contract['user_id'] == 0 && $contract['borrow_user_id'] <> 0){
                $role = self::getContractRole($contract['borrow_user_id'], $contract['type'], $contract['agency_id'], $deal['user_id']);
            }else{
                $role = self::getContractRole($contract['user_id'], $contract['type'], $contract['agency_id'], $deal['user_id']);

            }
            $tpls = $this->getTplsByGoldDeal($dealId);
            //投资记录

            $loanRrequest = new RequestCommon();
            $rpc = new Rpc('goldRpc');
            $loanRrequest->setVars(array("id"=>$load_id));
            $loanResponse = $rpc->go("\NCFGroup\Gold\Services\DealLoad","getDealLoadById",$loanRrequest);

            $loan_user_info = $loanResponse['data'];
            $res = 0;

            $loan_v2 = false;
            $borrower_protocal_v2 = false;
            foreach($tpls as $tpl){

                if(strchr($tpl['name'],"TPL_LOAN_CONTRACT_V2_NQYZR") || strchr($tpl['name'],"TPL_LOAN_CONTRACT_V2_NGRZR")){
                    $loan_v2 = true;
                }else if(strchr($tpl['name'],"TPL_BORROWER_PROTOCAL_V2_NQYZR") || strchr($tpl['name'],"TPL_BORROWER_PROTOCAL_V2_NGRZR")){
                    $borrower_protocal_v2 = true;
                }

            }
            $res = $contract_libs->push_gold_loan_transfer($contract, $deal, $loan_user_info, $borrow_user_info);

            return $res;
        }else{
            return false;
        }

    }
    /**
     * 获取合同所属用户的角色
     *
     * @param $id 合同id
     * @return bool
     */
    public static function getContractRole($cont_userid, $cont_type, $cont_agencyid, $deal_userid){
        $role = 0;
        if($cont_userid > 0 && $cont_type != 3){
            $role = self::ROLE_LENDER;
            if($deal_userid == $cont_userid){
                $role = self::ROLE_BORROWER;
            }
        }else{
            $role = self::ROLE_GUARANTOR;
            if($cont_userid == 0 && $cont_agencyid > 0){
                $role = self::ROLE_AGENCY;
            }
        }
        return $role;
    }


    /**
     * 获取某个用户合同相关的借款列表 NEW
     *
     * @param int $user_id 用户id
     * @param string $limit 分页
     * @return array | boolean [false: 表示请求的角色有误]
     */
    public function getContDealList($user_info, $page = 1, $page_size = 10, $role = null ,$isP2p = false){
        $user_id = $user_info;
        $user_service = new UserService();
        $contract_model = new ContractModel();
        $deal_contract_model = new \core\dao\DealContractModel();
        $deal_list = array();

        //判断用户是否为担保
        $user_agency_info = $user_service->getUserAgencyInfoNew(array('id'=>$user_info));
        $is_agency = intval($user_agency_info['is_agency']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $user_advisory_info = $user_service->getUserAdvisoryInfo(array('id'=>$user_info));
        $is_advisory = intval($user_advisory_info['is_advisory']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $user_entrust_info = $user_service->getUserEntrustInfo(array('id'=>$user_info));
        $is_entrust = intval($user_entrust_info['is_entrust']);

        //判断是否为新合同签署流程并判断是否为渠道方
        $user_canal_info = $user_service->getUserCanalInfo(array('id'=>$user_info));

        $is_canal = intval($user_canal_info['is_canal']);

        $deal_model = new DealModel();
        $is_borrow = $deal_model->isBorrowUser($user_id);
        //$userRole用户实际角色 $role用户前台选择查看的角色

        if($is_agency == 1){
            $userRole = self::ROLE_AGENCY;
        }else if($is_advisory == 1){
            $userRole = self::ROLE_ADVISORY;
        }else if($is_entrust == 1){
            $userRole = self::ROLE_ENTRUST;
        }else if($is_canal == 1){
            $userRole = self::ROLE_CANAL;
        }else{
            if ($is_borrow) {
                $userRole = self::ROLE_BORROWER;
            } else {
                $userRole = self::ROLE_LENDER;
            }
        }
        if($role == null){
            $role = $userRole;
        }
        //取合同列表新逻辑
        if($role == self::ROLE_LENDER){
            $deal_list = $contract_model->getUserContDeals($user_id, true, $page, $page_size, false, $isP2p);
        }elseif($role == self::ROLE_BORROWER && $is_borrow){
                $deal_list = $this->getBorrowUserContDeals($user_id, $page, $page_size, $isP2p);
        }elseif($role == self::ROLE_AGENCY && $is_agency){
                $deal_list = $this->getAgencyUserContDeals($user_agency_info['agency_info'], $page, $page_size, $isP2p);
        }elseif($role == self::ROLE_ADVISORY && $is_advisory){
                $deal_list = $this->getAgencyUserContDeals($user_advisory_info['advisory_info'], $page, $page_size, $isP2p);
        }elseif($role == self::ROLE_ENTRUST && $is_entrust){
             $deal_list = $this->getAgencyUserContDeals($user_entrust_info['entrust_info'], $page, $page_size, $isP2p);
        }elseif($role == self::ROLE_CANAL && $is_canal){
            $deal_list = $this->getAgencyUserContDeals($user_canal_info['canal_info'], $page, $page_size, $isP2p);
        } else {
            return false;
        }

        $deal_list['is_agency'] = $is_agency == 1?true:false;
        $deal_list['is_advisory'] = $is_advisory == 1?true:false;
        $deal_list['is_entrust'] = $is_entrust == 1?true:false;
        $deal_list['is_canal'] = $is_canal == 1?true:false;
        $deal_list['is_borrow'] = $is_borrow;

        /*
        //判断是否为担保
        $user_agency_info = $user_service->getUserAgencyInfoNew(array('id'=>$user_info));
        $is_agency = intval($user_agency_info['is_agency']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $user_advisory_info = $user_service->getUserAdvisoryInfo(array('id'=>$user_info));
        $is_advisory = intval($user_advisory_info['is_advisory']);
        $deal_list = array();
        if($is_agency == 1){
//            $deal_list = $deal_contract_model->getAgencyUserContDeals($user_id, $user_agency_info['agency_info'], $page, $page_size);
            $deal_list = $this->getAgencyUserContDeals($user_agency_info['agency_info'], $page, $page_size);
        }
        else if($is_advisory == 1){
//            $deal_list = $deal_contract_model->getAgencyUserContDeals($user_id, $user_advisory_info['advisory_info'], $page, $page_size);
            $deal_list = $this->getAgencyUserContDeals($user_advisory_info['advisory_info'], $page, $page_size);
        }
        else
        {
            $deal_model = new DealModel();
            $is_borrower = $deal_model->isBorrowUser($user_id);
            if ($is_borrower) {
                $deal_list = $this->getBorrowUserContDeals($user_id, $page, $page_size);
//                $deal_list = $deal_contract_model->getBorrowUserContDeals($user_id, $page, $page_size);
            } else {
                $deal_list = $contract_model->getUserContDeals($user_id, true, $page, $page_size);
            }
        }
        */

        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);

        if(isset($deal_list['list'])){
            $deal_model = new DealModel();
            foreach($deal_list['list'] as &$deal_one){
                //当前登录用户 该借款是否已经签署通过
                $is_have_sign = 1;
                if(in_array($role,array(1,3,4,5,6))){
                    $is_have_sign = $deal_one['sign_status'];
                }
                $isBxt = 0;
                $dealExtInfo = DealExtModel::instance()->getDealExtByDealId($deal_one['id']);
                if($deal_one['type_id'] == $bxtTypeId){
                    $deal_one['max_rate'] = number_format($dealExtInfo['max_rate'],2);
                    $isBxt = 1;
                }
                $deal_one['income_base_rate'] = number_format($dealExtInfo['income_base_rate'],2);
                $deal_one['isBxt'] = $isBxt;
                $deal_user_info = UserModel::instance()->find($deal_one['user_id'], 'real_name', true);
                $deal_one['old_name'] = $deal_one['name'];
                $deal_one['name'] = msubstr($deal_one['name'], 0, 24);
                $deal_one['borrow_amount_format_detail'] = format_price($deal_one['borrow_amount'] / 10000,false);
                $deal_one['income_fee_rate_format'] = number_format($deal_one['income_fee_rate'], 2);
                $deal_one['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal_one['loantype']];
                $deal_one['is_have_sign'] = $is_have_sign;
                $deal_one['user_real_name'] = $deal_model->getDealUserName($deal_one['user_id']);
            }
        }
        $deal_list['role'] = $userRole;

        return $deal_list;
    }

    /**
     * 获取某个用户项目合同列表
     *
     * @param int $userId 用户id
     * @param int $page 当前页
     * @param int $pageSize 页面记录数
     * @param int $role 角色
     * @return array
     */
    public function getContProjectList($userId, $page = 1, $pageSize = 10, $role = null){

        $userId = intval($userId);
        $user_service = new UserService();
        $role_list = $user_service->getUserRoleListByUserId($userId);
        if (empty($role)) {
            $role = $this->setDefaultRole($role_list, $userId);
        }


        $agency_function_map = array(
            self::ROLE_AGENCY => 'getUserAgencyInfoNew',
            self::ROLE_ADVISORY => 'getUserAdvisoryInfo',
            self::ROLE_ENTRUST => 'getUserEntrustInfo',
            self::ROLE_CANAL => 'getUserCanalInfo',
        );

        if (isset($agency_function_map[$role])) {
            list($user_agency_info, $is_agency) = array_values(call_user_func(array($user_service, $agency_function_map[$role]), array('id' => $userId)));
            if ($is_agency) {
                $id = intval($user_agency_info['agency_id']);
            } else { // 说明此用户不具备这样的角色
                return false;
            }
        } else {
            $id = $userId;
        }

        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetProjectContractByUserRole();
        $contractRequest->setRole(intval($role));
        $contractRequest->setId($id);
        $contractRequest->setPageNo(intval($page));
        $contractRequest->setSourceType(DealModel::DEAL_TYPE_EXCLUSIVE);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getProjectContractByUserRole",$contractRequest);

        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);

        $projectList = $response->list;

        if(!empty($projectList)){
            $deal_model = new DealModel();
            $dealProjectModel = new DealProjectModel();
            foreach($projectList as &$projectOne){
                $dealInfo = $dealProjectModel->getFirstDealByProjectId($projectOne['project_id']);
                $project = $dealProjectModel->find($projectOne['project_id']);
                //当前登录用户 该借款是否已经签署通过
                $is_have_sign = 1;
                if(in_array($role,array(1,3,4,5,6))){
                    if(($role == self::ROLE_AGENCY) && ($projectOne['agency_sign_time'] == 0)){
                        $is_have_sign = 0;
                    }
                    if(($role == self::ROLE_ADVISORY) && ($projectOne['advisory_sign_time'] == 0)){
                        $is_have_sign = 0;
                    }
                    if(($role == self::ROLE_ENTRUST) && ($projectOne['entrust_agency_sign_time'] == 0)){
                        $is_have_sign = 0;
                    }
                    if(($role == self::ROLE_BORROWER) && ($projectOne['borrower_sign_time'] == 0)){
                        $is_have_sign = 0;
                    }
                    if(($role == self::ROLE_CANAL) && ($projectOne['canal_agency_sign_time'] == 0)){
                        $is_have_sign = 0;
                    }
                }
                $isBxt = 0;
                $dealExtInfo = DealExtModel::instance()->getDealExtByDealId($dealInfo['id']);
                if($dealInfo['type_id'] == $bxtTypeId){
                    $projectOne['max_rate'] = number_format($dealExtInfo['max_rate'],2);
                    $isBxt = 1;
                }
                $projectOne['income_base_rate'] = number_format($dealExtInfo['income_base_rate'],2);
                $projectOne['isBxt'] = $isBxt;
                $deal_user_info = UserModel::instance()->find($dealInfo['user_id'], 'real_name', true);
                $projectOne['name'] = $project['name'];
                $projectOne['borrow_amount'] = $project['borrow_amount'];
                $projectOne['borrow_amount_format_detail'] = format_price($project['borrow_amount'] / 10000,false);
                $projectOne['income_fee_rate_format'] = number_format($dealInfo['income_fee_rate'], 2);
                $projectOne['loantype'] = $dealInfo['loantype'];
                $projectOne['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
                $projectOne['is_have_sign'] = $is_have_sign;
                $projectOne['user_real_name'] = $deal_model->getDealUserName($dealInfo['user_id']);
                $projectOne['repay_time'] = $dealInfo['repay_time'];
                $projectOne['isDealZX'] = $dealInfo['deal_type'] == DealModel::DEAL_TYPE_EXCLUSIVE ? true:false;
            }

            $result['role'] = $role;
            $result['list'] = $projectList;
            $result['count'] = $response->count;
            return array_merge($result, $role_list);
        }else{
            return $role_list;
        }
    }

    /**
     * 获取特定角色优先顺序的默认角色 - 强顺序型：因为前端的下拉选项顺序固定
     * @param array $role_list
     * @return int $role
     */
    private function setDefaultRole($role_list)
    {
        if ($role_list['is_borrow']) {
            $role = self::ROLE_BORROWER;
        } elseif ($role_list['is_agency']) {
            $role = self::ROLE_AGENCY;
        } elseif ($role_list['is_advisory']) {
            $role = self::ROLE_ADVISORY;
        } elseif ($role_list['is_entrust']) {
            $role = self::ROLE_ENTRUST;
        } else {
            $role = '';
        }

        return $role;
    }


    public function getDealContList($userId, $dealId, $type, $page = 1, $pageSize = 10, $agencyId = 0){
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetRoleContractByDealId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(intval($type));
        $contractRequest->setAgencyId(intval($agencyId));
        $contractRequest->setUserId(intval($userId));
        $contractRequest->setPageNo(intval($page));
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getRoleContractByDealId",$contractRequest);
        $result['list'] = $response->list;
        $result['count'] = $response->count['num'];
        return $result;
    }

    //获取项目合同列表
    public function getProjectContList($userId, $projectId, $type, $page = 1, $pageSize = 10, $agencyId = 0){
        $dealService = new DealService();
        $dealProjectModel = new DealProjectModel();

        $deal = $dealProjectModel->getFirstDealByProjectId(intval($projectId));
        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetRoleContractByProjectId();
        $contractRequest->setProjectId(intval($projectId));
        $contractRequest->setType(intval($type));
        $contractRequest->setAgencyId(intval($agencyId));
        $contractRequest->setUserId(intval($userId));
        $contractRequest->setPageNo(intval($page));
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getRoleContractByProjectId",$contractRequest);
        $result['list'] = $response->list;
        $result['count'] = $response->count['num'];
        return $result;
    }

    public function getTplsByDeal($dealId){
        $dealService = new DealService();
        $deal = $dealService->getDeal(intval($dealId));
        if($deal == false){
            return false;
        }

        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetDealCid();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(0);
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);
        if($response->errorCode == 0){
            $cid = $response->data;
            $contractRequest = new RequestGetTplByCid();
            $contractRequest->setCategoryId(intval($cid['categoryId']));
            $contractRequest->setContractVersion((float)$cid['contractVersion']);
            $response = $rpc->go("\NCFGroup\Contract\Services\Tpl","getTplsByCid",$contractRequest);
            if(is_array($response->list))
            {
                return $response->list['data'];
            }
        }
    }
    public function getTplsByGoldDeal($dealId){

        //在合同服务中获取合同
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetDealCid();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(2);
        $contractRequest->setSourceType(100);
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);
        if($response->errorCode == 0){
            $cid = $response->data;
            $contractRequest = new RequestGetTplByCid();
            $contractRequest->setCategoryId(intval($cid['categoryId']));
            $contractRequest->setContractVersion((float)$cid['contractVersion']);
            $response = $rpc->go("\NCFGroup\Contract\Services\Tpl","getTplsByCid",$contractRequest);
            if(is_array($response->list))
            {
                return $response->list['data'];
            }
        }
    }

    /**
     * 合同下载
     * @param int $contId 合同id
     * @param int $dealId 标的id
     * @param string $number 合同编号
     * @return void
     */
    public function contractDownload($contId,$dealId,$number,$projectId,$type = 0){
        if($dealId > 0){
            $cont = $this->showContract($contId,$dealId,0,$type);
        }elseif($projectId > 0){
            $cont = $this->showContract($contId,0,$projectId,1);
        }

        if(empty($cont)){
            return false;
        }

        $file_name = $number.".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $mkpdf = new \Mkpdf ();
        $mkpdf->mk($file_path, $cont['content']);
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;
    }

    /**
     * 合同服务获取借款人合同
     * @param int $userId 借款人ID
     * @param int  $page 页数
     * @param int  $pageSize 每页记录数
     * @return array
     */
    public function getBorrowUserContDeals($userId,$page = 1, $pageSize = 10 ,$isP2p = false){
        // 获取附件合同的分类id
        $request_category = new RequestGetCategoriesLikeTypeTag();
        $request_category->setTypeTag("ATTACHMENT%");
        $rpc = new Rpc('contractRpc');
        $response_category = $rpc->go("\NCFGroup\Contract\Services\Category","getCategoryLikeTypeTag",$request_category);
        $categories = $response_category->getList();
        $not_in_cont = '';
        $in_cont = '';
        $attachment_category_id_arr = array();
        foreach ($categories as $category) {
            $attachment_category_id_arr[] = $category['id'];
        }
        $attachment_category_ids = implode(',', $attachment_category_id_arr);
        if (!empty($attachment_category_ids)) {
            $not_in_cont = " AND `contract_tpl_type` NOT IN ({$attachment_category_ids}) ";
        }

        if($isP2p){
            $in_cont .= " AND `deal_type` IN (".DealModel::DEAL_TYPE_ALL_P2P.")";
        }

        $limit = ($page-1)*$pageSize.','.$pageSize;
        $dealContractModel = new DealContractModel();
        $condition_count = " user_id = {$userId} " . $not_in_cont.$in_cont;
        $countDeal = $dealContractModel->count($condition_count);

        $params = array(
            ':user_id' => intval($userId),
            ':start' => ($page-1) * $pageSize,
            ':page_size' => $pageSize,
        );
        $condition = "`user_id` = ':user_id' " . $not_in_cont;

        $condition .= "ORDER BY `status`, `sign_time` DESC, `create_time` DESC LIMIT :start, :page_size";
        $deals = $dealContractModel->findAllViaSlave($condition, true, "*", $params);

        $dealContractModel = new DealContractModel();
        $dealService = new DealService();

        foreach($deals as $k=>&$v){

            $deal = $dealService->getDeal($v['deal_id'], true);
            $deals[$k] = $deal;

            $condition = "`deal_id` = ':dealId'  AND `user_id` = ':userId' AND status = 1 AND sign_time > 0";
            $params = array(
                ':userId' => intval($userId),
                ':dealId' => intval($v['id']),
            );
            $count = $dealContractModel->countViaSlave($condition,$params);

            if($count == 0){
                $deals[$k]['sign_status'] = false;
            }else{
                $deals[$k]['sign_status'] = true;
            }
        }

        $result['list'] = $deals;
        $result['count'] = $countDeal;

        return $result;
    }

    /**
     * 合同服务获取机构代理人合同
     *
     */

    public function getAgencyUserContDeals($agencyInfo, $page, $pageSize, $isP2p = false){
        $dealContractModel = new DealContractModel();
        $page_size = $pageSize > 0 ? $pageSize : app_conf("PAGE_SIZE");
        $params = array(
            ':agency_id' => intval($agencyInfo['agency_id']),
            ':start' => ($page-1) * $pageSize,
            ':page_size' => $pageSize,
        );

        $condition = "`agency_id` = ':agency_id'";

        if ($agencyInfo['is_hy']) {
            $condition .= " AND `contract_tpl_type` = 'HY'";
        } else {
            $condition .= " AND `contract_tpl_type` != 'HY'";
        }

        if($isP2p){
            $condition .= " AND deal_type IN (".DealModel::DEAL_TYPE_ALL_P2P.")";
        }

        $count = $dealContractModel->countViaSlave($condition, $params);
        $condition .= "ORDER BY `status`, `sign_time` DESC, `create_time` DESC LIMIT :start, :page_size";
        $list = $dealContractModel->findAllViaSlave($condition, true, "*", $params);

        if ($list) {
            $deal_service = new DealService();
            foreach ($list as $k => $v) {
                $deal = $deal_service->getDeal($v['deal_id'], true);
                $list[$k] = $deal;
                $list[$k]['sign_status'] = $v['status'];
            }
        }

        return array('count' => $count, 'list' => $list);

    }


    /**
     * 一键签署异步任务,前台点击添加异步任务
     * @param int  $dealId 标的id
     * @param int  $role 1:借款人,2:担保,3:资产
     * @param int  $id 角色id
     * @param int $admID [用于借款人代签，代签人后台账户id]
     * @return  array
     */

    public function signAll($dealId, $role, $id=0, $admID = 0, $autoSign = false) {

        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);

        //$this->checkContAllSign($dealId);
        $dealContractModel = new DealContractModel();
        if($role == 1){
            if(!$this->signByRole($dealId,$id,0,0,false,$admID,2)){
                return false;
            }
        }elseif($role == 2){
            if(!$this->signByRole($dealId,$id,1,$deal['agency_id'],false,0,2)){
                return false;
            }
        }elseif($role == 3){
            if(!$this->signByRole($dealId,$id,1,$deal['advisory_id'],false,0,2)){
                return false;
            }
        }elseif($role == 4){
            if(!$this->signByRole($dealId,0,0,0,true)){
                return false;
            }
        }elseif($role == 5){
            if(!$this->signByRole($dealId,$id,1,$deal['entrust_agency_id'],false,0,2)){
                return false;
            }
        }elseif($role == 6){
            if(!$this->signByRole($dealId,$id,1,$deal['canal_agency_id'],false,0,2)){
                return false;
            }
        }

        $jobs_model = new JobsModel();
        $function = "\core\service\ContractNewService::signDealContNew";
        $params = array(
            'dealId' => intval($dealId),
            'role' => $role,
            'id' => intval($id),
            'admID' => $admID,
            'autoSign' => $autoSign,
        );
        $jobs_model->priority = 133;
        $res = $jobs_model->addJob($function, $params);
        if ($res === false) {
            throw new \Exception("contract sign add jobs fail");
        }

        return true;
    }

    /**
     * 一键签署,借款人或担保公司单方的（前台调用）
     * @param $dealId 标的id
     * @param $role 角色 1:借款人,2:担保,3:资产,4:全部,5:委托机构,6:渠道机构
     * @param $id id
     * @param int $admID [用于借款人代签，代签人后台账户id]
     * @param int $autoSign
     * @return boolean $result
     */
    public function signDealContNew($dealId, $role = 4, $id = 0, $admID = 0, $autoSign = false){
        try {
            $dealService = new DealService();
            $deal = $dealService->getDeal($dealId);
            if($role == 1){
                $id = $deal['user_id'];
            }
            elseif($role == 2){
                $id = $deal['agency_id'];
            }elseif($role == 3){
                $id = $deal['advisory_id'];
            }elseif($role == 5){
                $id = $deal['entrust_agency_id'];
            }elseif($role == 6){
                $id = $deal['canal_agency_id'];
            }
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestSignDealContract();
            $contractRequest->setDealId(intval($dealId));
            $contractRequest->setRole(intval($role));
            $contractRequest->setId(intval($id));
            $contractRequest->setSourceType($deal['deal_type']);
            $contractRequest->setAutoSign($autoSign);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","signDealContract",$contractRequest);

            if($response->errorCode <> 0){
                \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                throw new \Exception("contract sign false | Contract signDealContract调用失败");
            }

            //$this->checkContAllSign($dealId);
            $dealContractModel = new DealContractModel();
            if($role == 1){
                if(!$this->signByRole($dealId,$id,0,0, false, $admID)){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("DealContractModel signByRole调用失败 | 借款方");
                }
            }elseif($role == 2){
                if(!$this->signByRole($dealId,$id,1,$deal['agency_id'])){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("DealContractModel signByRole调用失败 | 担保方");
                }
            }elseif($role == 3){
                if(!$this->signByRole($dealId,$id,1,$deal['advisory_id'])){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("DealContractModel signByRole调用失败 | 咨询方");
                }
            }elseif($role == 4){
                if(!$this->signByRole($dealId,0,0,0,true)){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("DealContractModel signByRole调用失败 | 全部签署");
                }
            }elseif($role == 5){
                if(!$this->signByRole($dealId,$id,1,$deal['entrust_agency_id'])){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("DealContractModel signByRole调用失败 | 受托方");
                }
            }elseif($role == 6){
                if(!$this->signByRole($dealId,$id,1,$deal['canal_agency_id'])){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("DealContractModel signByRole调用失败 | 渠道方");
                }
            }


            $signInfo = $this->getContSignNum($dealId,0,0);

            $dealContractModel = new DealContractModel();
            //补签逻辑 （如果dealcontract签署记录全部完成,contract service存在未签署记录,则补签)
            if((!$signInfo['is_sign_all']) && ($dealContractModel->getDealContractSignInfo(intval($dealId),0) == 0))
            {
                $contractRequest = new RequestSignDealContract();
                $contractRequest->setDealId(intval($dealId));
                $contractRequest->setRole(4);
                $contractRequest->setId(intval($id));
                $contractRequest->setSourceType($deal['deal_type']);
                $contractRequest->setAutoSign($autoSign);
                $response = $rpc->go("\NCFGroup\Contract\Services\Contract","signDealContract",$contractRequest);

                if($response->errorCode <> 0){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("dealContract中全部签署，但是合同库中没有全部签署。这时调用合同方法ContractService::signDealContract失败");
                }

                $signInfo = $this->getContSignNum($dealId,0,0);
            }

            if($signInfo['is_sign_all']){
                if(is_numeric($deal['contract_tpl_type'])){
                    $event = new \core\event\SendContractMsgEvent($dealId,1);
                }else{
                    $event = new \core\event\SendContractMsgEvent($dealId);
                }
                $task_obj = new GTaskService();
                $task_id = $task_obj->doBackground($event, 3);
                if (!$task_id) {
                    Logger::wLog('添加task失败|SendContractMsgEvent', Logger::INFO, Logger::FILE);
                }

                //记录随鑫约合同签署缓存
                $obj_reserve = new GTaskService();
                $event_reserve = new ReserveDealContractCacheEvent($dealId);
                $obj_reserve->doBackground($event_reserve, 1);
            }
            \libs\utils\Monitor::add('CS_CONTRACT_SIGN_SUCCESS');
            return true;
        } catch (\Exception $e) {
            Logger::error(implode('|', array(__FILE__,__FUNCTION__,__LINE__,'CS_CONTRACT_SIGN_FAIL',
                '参数:'."dealId:{$dealId} role:{$role} id:{$id} admID:{$admID} autoSign:{$autoSign}",
                '错误原因:'.$e->getMessage())));
            return false;
        }
    }
    /**
     * 一键签署,借款人或担保公司单方的（前台调用）
     * @param $dealId 标的id
     * @param $role 角色 1:借款人,2:担保,3:资产,4:全部,5:委托机构
     * @param $id id
     * @param int $admID [用于借款人代签，代签人后台账户id]
     */
    public function signGoldDealContNew($dealId, $role = 4, $id = 0, $admID = 0, $autoSign = false){
        try {
            $rpc = new Rpc('goldRpc');
            $request = new RequestCommon();
            $request->setVars(array("deal_id"=>$dealId));
            $response = $rpc->go("\NCFGroup\Gold\Services\Deal","getDealById",$request);
            $deal = $response['data'];
            if($role == 1){
                $id = $deal['userId'];
            }
            /*elseif($role == 2){
                $id = $deal['agency_id'];
            }elseif($role == 3){
                $id = $deal['advisory_id'];
            }elseif($role == 5){
                $id = $deal['entrust_agency_id'];
            }*/
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestSignDealContract();
            $contractRequest->setDealId(intval($dealId));
            $contractRequest->setRole(intval($role));
            $contractRequest->setId(intval($id));
            $contractRequest->setSourceType(100);
            $contractRequest->setAutoSign($autoSign);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","signDealContract",$contractRequest);

            if($response->errorCode <> 0){
                \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                throw new \Exception("contract sign false");
            }
           //$this->checkContAllSign($dealId);
            $goldService= new GoldService();
            if($role == 1){//黄金只有借款合同，暂时先不考虑其他的情况
                if($goldService->signGoldByRole($dealId,$id,0,0, false, $admID)){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("GoldService signByRole调用失败 | 借款方");
                }
            }elseif($role == 4){
                if($goldService->signGoldByRole($dealId,0,0,0,true)){
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                    throw new \Exception("GoldService signByRole调用失败 | 全部签署");
                }
            }
            \libs\utils\Monitor::add('CS_CONTRACT_SIGN_SUCCESS');
            return true;
        } catch (\Exception $e) {
            Logger::error(implode('|', array(__FILE__,__FUNCTION__,__LINE__,'CS_CONTRACT_SIGN_FAIL',
                '参数:'."dealId:{$dealId} role:{$role} id:{$id} admID:{$admID} autoSign:{$autoSign}",
                '错误原因:'.$e->getMessage())));
            return false;
        }
    }

    /**
     * 项目合同一键签署,借款人或担保公司单方的（前台调用）
     * @param $projectId 项目ID
     * @param $role 角色 1:借款人,2:担保,3:资产,4:全部,5:委托机构
     * @param $id id
     * @param int $admID [用于借款人代签，代签人后台账户id]
     */
    public function signProjectCont($projectId, $role = 4, $id = 0, $admID = 0, $autoSign = false){
        try {
            $dealProjectModel = new DealProjectModel();
            $deal = $dealProjectModel->getFirstDealByProjectId($projectId);

            if($role == 1){
                $id = $deal['user_id'];
            }
            elseif($role == 2){
                $id = $deal['agency_id'];
            }elseif($role == 3){
                $id = $deal['advisory_id'];
            }elseif($role == 5){
                $id = $deal['entrust_agency_id'];
            }elseif($role == 6){
                $id = $deal['canal_agency_id'];
            }
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestSignProjectContract();
            $contractRequest->setProjectId(intval($projectId));
            $contractRequest->setRole(intval($role));
            $contractRequest->setId(intval($id));
            $contractRequest->setSourceType($deal['deal_type']);
            $contractRequest->setAutoSign($autoSign);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","signProjectContract",$contractRequest);

            if($response->errorCode <> 0){
                \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                throw new \Exception("contract sign false");
            }else{

                $signInfo = $this->getProjectContSignNum($projectId,0,0);
                if($signInfo['is_sign_all']) {
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_SUCCESS');

                    //更新项目状态
                    $changeStatus = $dealProjectModel->changeProjectStatus($projectId, DealProjectModel::$PROJECT_BUSINESS_STATUS['transfer_loans_audit']);
                    if (!$changeStatus) {
                        Logger::error('CS_CONTRACT_SIGN_FAIL | 签署项目合同成功,更新项目状态失败。(projectId:' . $projectId . ')');
                        \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                        return false;
                    }
                }
                return true;
            }
        } catch (\Exception $e) {
            \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
            Logger::error(implode('|', array(__FILE__,__FUNCTION__,__LINE__,'CS_CONTRACT_SIGN_FAIL',
                '参数:'."projectId:{$projectId} role:{$role} id:{$id} admID:{$admID} autoSign:{$autoSign}",
                '错误原因:'.$e->getMessage())));
            return false;
        }
    }

    /**
     * 获取一个标某个用户已签署数据
     * @param $deal_id 标的id
     * @param $user_id 用户id
     * @param $is_agency 是否担保公司
     * @param $contract_count 已知合同总数
     * @return array
     */
    public function getContSignNum($dealId, $role, $id){

        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        //获取合同总数
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetDealSignNum();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setRole(intval($role));
        $contractRequest->setId(intval($id));
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getDealSignNum",$contractRequest);

        if(isset($response->list['total']) && isset($response->list['signed'])){
            $contract_count = $response->list['total'];
            $sign_count = $response->list['signed'];
        }
        //返回数据
        // 判断是否为公益标
        if ($dealService->isDealCrowdfunding($dealId)) {
            $res['is_sign_all'] = true;
        } else {
            $res['is_sign_all'] = ($contract_count > $sign_count) || (0 == $contract_count) ? false : true;
        }
        $res['contract_num'] = $contract_count;
        $res['sign_num'] = $sign_count;

        return $res;
    }

    /**
     * 获取一个项目某个用户已签署数据
     * @param $projectId 项目id
     * @param $role 角色（1:借款人,2:担保,3:资产管理,4:委托方,0:全部）
     * @param $id id
     * @return array
     */
    public function getProjectContSignNum($projectId, $role, $id){
        $project = DealProjectModel::instance()->find(intval($projectId));

        //获取合同总数
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetProjectSignNum();
        $contractRequest->setProjectId(intval($projectId));
        $contractRequest->setRole(intval($role));
        $contractRequest->setId(intval($id));
        $contractRequest->setSourceType($project['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getProjectSignNum",$contractRequest);

        if(isset($response->list['total']) && isset($response->list['signed'])){
            $contract_count = $response->list['total'];
            $sign_count = $response->list['signed'];
        }

        $res['is_sign_all'] = ($contract_count > $sign_count) || (0 == $contract_count) ? false : true;
        $res['contract_num'] = $contract_count;
        $res['sign_num'] = $sign_count;

        return $res;
    }

    public function getCategoryByCid($id){
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetCategoryById();
        $contractRequest->setCategoryId(intval($id));
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getCategoryById",$contractRequest);
        if(false === $response) {
            return false;
        }
        return $response->list;

    }

    /***
     * 根据时间段，获取当日所有标的的打戳信息包含合同服务标的
     * @param start_time, 开始时间（时间戳）
     * @param end_time, 结束时间（时间戳）
     */
    public function getDealTsaInfo($start_time,$end_time){

        $dealService = new DealService();
        $loan_oplog_model = new LoanOplogModel();
        $contract_model = new ContractModel();

        $oplogs = $loan_oplog_model->getDealIdByTime($start_time,$end_time);

        $rpc = new Rpc('contractRpc');
        $result = array();
        if(count($oplogs) > 0){
            foreach($oplogs as $oplog){
                $dealInfo = $dealService->getDeal($oplog['deal_id']);
                if(is_numeric($dealInfo['contract_tpl_type'])){
                    $contractRequest = new RequestGetDealTsaInfo();
                    $contractRequest->setDealId(intval($oplog['deal_id']));
                    $contractRequest->setSourceType($dealInfo['deal_type']);
                    $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getDealTsaInfo",$contractRequest);
                    if($response->errCode == 0){
                        if(($response->data['total'] - $response->data['signed']) > 0){
                            $result[$oplog['deal_id']] = false;
                        }
                    }else{
                        $result[$oplog['deal_id']] = false;
                    }
                }else{
                    $contract_count = $contract_model->countBySql("SELECT COUNT(id) FROM firstp2p_contract WHERE deal_id = '".$oplog['deal_id']."' AND status < 3;");
                    if($contract_count > 0){
                        $result[$oplog['deal_id']] = false;
                    }
                }
            }
        }

        return $result;
    }

    /*
    * 开始针对dealid这个标进行合同的异步签署(批量)
    */
    public function startSignAllContract($dealId,$type=0,$projectId=0){
        //$list = ContractModel::instance()->getContListByDealId($deal_id);
        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($dealId);
        if(!is_numeric($dealInfo['contract_tpl_type'])){
            $list = ContractModel::instance()->getContractIdNumbersByDealId($dealId);
        }else{
            $rpc = new Rpc('contractRpc');

            if($type == 1){
                $method = "getContractByProjectId";
                $contractRequest = new RequestGetContractByProjectId();
                $contractRequest->setProjectId(intval($projectId));
            }else{
                $method = "getContractByDealId";
                $contractRequest = new RequestGetContractByDealId();
                $contractRequest->setDealId(intval($dealId));
            }

            $contractRequest->setSourceType($dealInfo['deal_type']);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract",$method,$contractRequest);
            if($response->errCode == 0){
                $list = $response->list;
            }
        }

        //监控进入gm合同数量
        $monitorNum = count($list);
        $succ = 0;
        $errorContractIds = array();
        $obj = new GTaskService();
        foreach($list as $one){
            if($type == 1) {
                $ret = $this->startSignOneContract($obj, $one['id'], $one['number'], $dealId, $projectId, 1);
            }else{
                $ret = $this->startSignOneContract($obj, $one['id'], $one['number'], $dealId);
            }
            if($ret === true){
                $succ ++;
            }else{
                $errorContractIds[] = $one['id'];
            }
        }

        if($monitorNum != $succ){
            $alertData = array(
                'deal_id'=>$dealId,
                'needTsaCount'=>$monitorNum,
                'realTsaCount'=>$succ,
                'errorContractId'=>$errorContractIds,
            );
            \libs\utils\Alarm::push('tsacheck', 'tsacheck 时间戳入队报警', json_encode($alertData));
        }
        return true;
    }

    /*
    * 开始针对contractId这个标进行合同的异步签署(单发)
    */
    public function startSignOneContract($taskService, $contractId, $contractNum, $dealId, $projectId = 0, $type = 0){

        // 存在的且状态是已经打过的就不打了。米等用
        $exist = ContractFilesWithNumModel::instance()->getAllByContractNum($contractNum);
        if(!empty($exist) && $exist[0]['status'] == ContractFilesWithNumModel::TSA_STATUS_DONE){
            Logger::wLog(implode(" | ", array(__CLASS__, __FUNCTION__, $contractId, $contractNum, "already exist !")), Logger::INFO, Logger::FILE);
            return true;
        }else{
            if(empty($exist)){
                // 现插入一发,状态为0。
                if($type == 1){
                    $fileRet = ContractFilesWithNumModel::instance()->addNewRecord($contractId,
                        $contractNum,ContractFilesWithNumModel::FDFS_DEFAULT,ContractFilesWithNumModel::FDFS_DEFAULT,$projectId);
                }else{
                    $fileRet = ContractFilesWithNumModel::instance()->addNewRecord($contractId,
                        $contractNum,ContractFilesWithNumModel::FDFS_DEFAULT,ContractFilesWithNumModel::FDFS_DEFAULT,$dealId);
                }
            }
            $event = new ContractSignEvent($contractId,$dealId,$type,$projectId);
            $res = $taskService->doBackground($event, 20, Task::PRIORITY_NORMAL, null, 'domq_cpu');
            if (!$res) {
                Logger::wLog(implode(" | ", array(__CLASS__, __FUNCTION__, $contractId, $event)), Logger::INFO, Logger::FILE);
                return false;
            }
            return true;
        }
    }

    /***
     * 签署时间戳，更新contract表状态
     * @param dealId, 标的ID
     * @param number, 合同编号
     */
    public function signTsaCallback($dealId,$number){
        $contractModel = new ContractModel();
        try {
            $GLOBALS['db']->startTrans();
            if ($contractModel->signTsaCallback($dealId, $number) === false) {
                throw new \Exception("tsa contract sign fail");
            }
            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $number, $e->getMessage(), $e->getLine())));
            return false;
        }

    }

    /**
     * [getEntrustDealInfoList:获取代签状态的代理签署标的的信息列表（JIRA#3255）]
     * @author <fanjingwen@ucfgroup.com>
     * @param int $nowPage [现在的页码]
     * @param int $rowOfPage [每页显示的行数]
     * @param int $condStartTime [option：满标区间的开始时间，时间戳]
     * @param int $condEndTime [option：满标区间的结束时间，时间戳]
     * @param int $condDealID [option：标的id]
     * @param string $condUserIDs [option：标的所属用户的字符串数组，逗号分隔]
     * @return array ['count' => 符合条件的标的总数, 'list' => 标的信息列表]
     */
    public function getEntrustDealInfoList($nowPage, $rowOfPage, $condStartTime, $condEndTime, $condDealID, $condUserIDs)
    {
        $pageStart = $rowOfPage*($nowPage - 1);
        $listOfDealInfo = DealModel::instance()->getEntrustDealInfoList($pageStart, $rowOfPage, $condStartTime, $condEndTime, $condDealID, $condUserIDs);

        return $listOfDealInfo;
    }


    /*
     * 更改dealContract表的签署记录状态
     */
    private function signByRole($dealId, $userId, $isAgency=0,$agencyId = 0,$all=false, $admID = 0, $status = 1){
        $dealContractModel = new DealContractModel();
        $res = $dealContractModel->signByRole($dealId,$userId,$isAgency,$agencyId,$all,$admID,$status);
        return $res;
    }

    /*
     * 获取所有的合同分类
     */
    public function getContractCategorys($isDelete = 0){
        $rpc = new Rpc('contractRpc');
        $tplRequest = new RequestGetCategorys();

        $tplRequest->setIsDelete(intval($isDelete));
        $categorys = $rpc->go("\NCFGroup\Contract\Services\Category","getCategorys",$tplRequest);
        if(!is_array($categorys->list)){
            return false;
        }

        return $categorys->list;
    }

    /**
     * 根据投标ID获取合同信息 [此种情况下有合同分类[附件 or 非附件]]
     * @param int   $deal_load_id
     * @param bool  $deal       可空
     * @param bool  $deal_load  可空
     * @return array
     */
    public function getContractInCategoryByDealLoad($deal_load_id, $deal = false, $deal_load = false, $user_id = '') {

        if (empty($deal_load_id)) {
            return array();
        }
        if (empty($deal) || empty($deal_load)) {
            $deal_load = DealLoadModel::instance()->find($deal_load_id, '`deal_id`,`user_id`', true);
            $deal = DealModel::instance()->find($deal_load['deal_id'], '`id`,`parent_id`,`deal_type`', true);
        }
        if (empty($deal)) {
            return array();
        }

        $deal_service = new DealService();
        $deal['isDealZX'] = $deal_service->isDealEx($deal['deal_type']);
        // 获取合同类型标记name
        $contract_category_info = $this->getCategoryByCid($deal['contract_tpl_type']);
        $cont_list_new = array();
        if (!empty($contract_category_info) && in_array($contract_category_info['typeTag'], ContractModel::$tpl_type_tag_attachment)) { // 合同附件
            $cont_list_new = ContractModel::instance()->getContractAttachmentByDealLoad($deal->getRow());
            $is_attachment = true;
        } else {
            $cont_list = ContractModel::instance()->getContractByDealLoad($deal_load_id, $deal, $deal_load, array(1, 4, 5, 7, 8, 20, 21, 22, 23, 99), $user_id);
            $contract_service = new ContractService();
            $is_attachment = false;
            foreach($cont_list as $key => $one){
                $contract_sign_service = new ContractSignService();
                $tsaRet = $contract_sign_service->getSignedContractListByNum($one['number']);
                $tsaInfo = array();
                // 是否已经有戳
                $one['hasTsa'] = 0;
                // 是否补发了
                $one['hasRenew'] = 0;
                if(!empty($tsaRet) && !empty($tsaRet[0])){
                    $one['tsaStatus'] = 1;
                    $one['hasTsa'] = 1;
                    $tsaInfo['createTimeStr'] = date('Y-m-d H:i:s',$tsaRet[0]['create_time']);
                    $one['tsaInfo'] = $tsaInfo;
                }
                $reNew = $contract_service->contractRenewExist($one['id']);
                if(!empty($reNew)){
                    $one['hasRenew'] = 1;
                    $one['renewTime'] = date('Y-m-d H:i:s',$reNew['create_time']);
                }
                // -----------------临时摘掉时间戳展现----------------------
                //$one['hasTsa'] = 1;
                //$one['hasRenew'] = 0;
                // -----------------临时摘掉时间戳展现----------------------

                // 专享标 只显示委托投资协议,显示委托投资说明
                if ($deal['isDealZX'] && self::CONT_ENTRUST != $one['type']) {
                    if(($one['type'] <> 99) AND ($one['type'] <> 1)){
                        continue;
                    }
                }
                $cont_list_new[$key] = $one;
            }
        }

        return array('cont_list' => $cont_list_new, 'is_attachment' => $is_attachment);
    }

    public function getGoldContractInCategoryByDealLoad($deal_load_id, $deal_id,$user_id = '') {
        if (empty($deal_load_id)) {
            return array();
        }
        $rpc = new Rpc('goldRpc');
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$deal_id));
        $response = $rpc->go("\NCFGroup\Gold\Services\Deal","getDealById",$request);
        $deal = $response['data'];
        $deal['deal_type'] = 100;
        // 获取合同类型标记name
        $contract_category_info = $this->getCategoryByCid($deal['contractTplType']);
        $cont_list_new = array();
        $cont_list = ContractModel::instance()->getContractByDealLoad($deal_load_id, $deal, $deal_load_id, array(1, 4, 5, 7, 8, 99), $user_id);
        $contract_service = new ContractService();
            $is_attachment = false;
            foreach($cont_list as $key => $one){
                $contract_sign_service = new ContractSignService();
                $tsaRet = $contract_sign_service->getSignedContractListByNum($one['number']);
                $tsaInfo = array();
                // 是否已经有戳
                $one['hasTsa'] = 0;
                // 是否补发了
                $one['hasRenew'] = 0;
                if(!empty($tsaRet) && !empty($tsaRet[0])){
                    $one['tsaStatus'] = 1;
                    $one['hasTsa'] = 1;
                    $tsaInfo['createTimeStr'] = date('Y-m-d H:i:s',$tsaRet[0]['create_time']);
                    $one['tsaInfo'] = $tsaInfo;
                }
                $reNew = $contract_service->contractRenewExist($one['id']);
                if(!empty($reNew)){
                    $one['hasRenew'] = 1;
                    $one['renewTime'] = date('Y-m-d H:i:s',$reNew['create_time']);
                }
                // -----------------临时摘掉时间戳展现----------------------
                //$one['hasTsa'] = 1;
                //$one['hasRenew'] = 0;
                // -----------------临时摘掉时间戳展现----------------------
                $cont_list_new[$key] = $one;
            }

        return array('cont_list' => $cont_list_new, 'is_attachment' => $is_attachment);
    }

    public function getGoldContractIdByDealLoad($dealId,$deal_load_id,$user_id) {
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByLoadId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setLoadId(intval($deal_load_id));
        $contractRequest->setUserId($user_id);
        $contractRequest->setSourceType(100);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByLoadId",$contractRequest);
        if(!is_array($response->list)){
            return false;
        }
        return $response->list['0'];
    }

    public function getGoldContractSign($dealId) {//获取签署状态
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByLoadId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setLoadId(intval($deal_load_id));
        $contractRequest->setSourceType(100);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByLoadId",$contractRequest);
        if(!is_array($response->list)){
            return false;
        }
        return $categorys->list[0];
    }
    /**
     * 判断是否为附件合同
     * @author <fanjingwen@ucfgroup.com>
     * @param  int  $contract_tpl_type 对应deal表字段
     * @return boolean
     */
    public function isAttachmentContract($contract_tpl_type)
    {
        $contract_category_info = $this->getCategoryByCid($contract_tpl_type);
        return (!empty($contract_category_info) && in_array($contract_category_info['typeTag'], ContractModel::$tpl_type_tag_attachment));
    }

    /**
     * 获取合同附件
     * @param  int $deal_id
     * @return array
     */
    public function getContractAttachmentByDealLoad($deal_id)
    {
        if (empty($deal_id)) {
            return array();
        }

        $deal = DealModel::instance()->findViaSlave($deal_id);

        return ContractModel::instance()->getContractAttachmentByDealLoad($deal);
    }
    /**
     * 查看合同
     */
    public function showCont($id,$dealId,$userId){
        $cont_info = $this->getContractByGoldDeal($id, $dealId,100);
        //防止越权访问
        if(empty($cont_info) || $cont_info['user_id'] != $userId){
            return false;
        }
        $content = $this->getGoldContractCont($dealId, '', $id);
        $cont_info['content'] = $content;
        return $cont_info;
    }

    /**
     * 使用合同服务接口获取合同分类列表
     * 而不是使用firstp2p库中firstp2p_contract_category_tmp
     * firstp2p库中firstp2p_contract_category_tmp用于给对公信贷提供合同分类的视图
     * 根据合同名（可以不传，支持模糊查询） 查询合同相关信息
     * @param $name
     * @param $pageNum
     * @param $pageSize
     * @param $dealType
     * @return array $response
     */
    public function getListByTypeName($name, $pageNum, $pageSize, $dealType){
        $request = new RequestCategoryList();
        if($dealType !== null){
            $request->setSourceType(intval($dealType));
        }
        if(!empty($name)){
            $request->setTypeName($name);
        }
        $pageSize = empty($pageSize) ? 30 : intval($pageSize);
        $pageNum = empty($pageNum) ? 1 : intval($pageNum);
        $request->setPageNum($pageNum);
        $request->setPageSize($pageSize);
        $request->setUseStatus(1); //0-历史使用，1-当前使用
        $request->setType(0); //0-p2p项目(包括网贷，交易所，专享，小贷)
        // 目前说是要把个人借款和公司借款都返回
        // $request->setContractType(0);  //0-个人借款 1-公司借款
        $request->setIsDelete(0);
        $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Category","getCategoryList",$request);

        return $response->list;
    }

    /**
     * 合同签署完成后（还未打戳），发送邮件和站内信给各方
     * 发送邮件和站内信(投资人, 借款人)
     * 发送邮件(咨询机构, 担保机构)
     * 如果是智多鑫或者随鑫约，则不会发送
     * 给合同库中对应的合同记录标记为已发送状态
     * @param  int dealId
     * @return boolean result
     */
    public static function sendMsg($dealId){
        $dealId = intval($dealId);
        $sendRes = send_new_contract_sign_email($dealId);
        return $sendRes;
    }
}
