<?php
/**
 * ContractService.php
 */

namespace core\service\contract;


use core\service\BaseService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\contract\ContractService;
use core\service\contract\CategoryService;
use core\service\contract\TplService;
use core\service\contract\ContractSignService;
use core\service\deal\DealAgencyService;
use core\service\msgbus\MsgbusService;
use core\service\msgbox\MsgboxService;
use core\service\email\SendEmailService;

use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\enum\UserEnum;
use core\enum\DealLoadEnum;
use core\enum\MsgBoxEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractEnum;
use core\enum\contract\ContractCategoryEnum;

use core\dao\contract\DealContractModel;
use core\dao\contract\ContractContentModel;
use core\dao\dealloan\LoanOplogModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;


use libs\db\Db;
use libs\utils\Logger;
use libs\utils\Monitor;
use libs\tcpdf\Mkpdf;


/**
 * Class ContractService
 * @package core\service
 */
class ContractNewService extends BaseService {

    /**
     * 查询合同
     *
     * @param int $id 合同id
     * @return array
     */
    public function getContract($id, $dealId){
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        //在合同服务中获取合同
        $response =  ContractService::getContractByCid(intval($dealId),intval($id),ContractServiceEnum::SOURCE_TYPE_PH);
        if(empty($response)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail', 'dealId:'.$dealId, 'c_id:'.$id)));
            return false;
        }
        return $response[0];
    }

   /**
     * 查询合同
     *
     * @return array
     */
    public function getContractByDealNum($num, $dealId){
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        //在合同服务中获取合同
        $response = ContractService::getContractByDealNum(intval($dealId),$num,ContractServiceEnum::SOURCE_TYPE_PH);
        if(empty($response)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail', 'dealId:'.$dealId, 'num:'.$num)));
            return false;
        }
        return $response[0];
    }

    /**
     * 合同
     *
     * @return array
     */
    public function showContract($id,$dealId){
        $cont_info = $this->getContract($id, $dealId);
        //如果没有合同纪录，直接返回
        if(empty($cont_info)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail', 'dealId:'.$dealId, 'c_id:'.$id)));
            return false;
        }
        return $cont_info;

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
        }
        if(empty($cont)){
            return false;
        }

        $file_name = $number.".pdf";
        $file_path = ROOT_PATH.'runtime/'.$file_name;
        $mkpdf = new Mkpdf ();
        $mkpdf->mk($file_path, $cont['content']);
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;
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
            $role = ContractEnum::ROLE_LENDER;
            if($deal_userid == $cont_userid){
                $role = ContractEnum::ROLE_BORROWER;
            }
        }else{
            $role = ContractEnum::ROLE_GUARANTOR;
            if($cont_userid == 0 && $cont_agencyid > 0){
                $role = ContractEnum::ROLE_AGENCY;
            }
        }
        return $role;
    }

    /**
     * 获取某个用户拥有合同的标id(去重)
     *
     * @param int $user_id
     * @param bool $make_page
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getDealidByUseridNew($user_id, $make_page, $page = 1, $page_size = 0,$isP2p = false){
        if($isP2p === false){
            $condition = 'user_id = ":user_id" ORDER BY `id` DESC';
        } else {
            $condition = sprintf('user_id = ":user_id" AND deal_type IN ( %s) ORDER BY `id` DESC', DealEnum::DEAL_TYPE_ALL_P2P);
        }
        $limit = '';
        if($make_page){
            $page = intval($page) >= 1 ? intval($page) : 1;
            $page_size = intval($page_size) > 0 ? intval($page_size) : app_conf("PAGE_SIZE");
            $limit = sprintf(" LIMIT %d, %d", ($page - 1) * $page_size, $page_size);
        }

        $params = array(
            ':user_id' => intval($user_id),
        );
        $deal_model = new DealLoadModel();
        $dealIds = $deal_model->findAllViaSlave($condition . $limit, true, 'DISTINCT(`deal_id`) as deal_id', $params);
        $count = $deal_model->findAllViaSlave($condition, true, 'COUNT(DISTINCT(`deal_id`)) as count', $params);
        return array(
            'list' => $dealIds,
            'count' => $count['0']['count'],
        );
    }


    /**
     * 普通用户合同借款列表
     *
     * @param int $user_id 用户id
     * @param string $limit 分页查询
     * @param boolen $is_show_attachment 是否获取附件合同标的
     * @return array
     */
    public function getUserContDeals($user_id, $make_page = true, $page = 1, $page_size = 0, $is_show_attachment = true, $isP2p = false){
        if (false === $is_show_attachment) {
            // 获取附件合同的分类id
            $categories = CategoryService::getCategoryLikeTypeTag("ATTACHMENT%");
            $not_in_cont = '';
            $attachment_category_id_arr = array();
            foreach ($categories as $category) {
                $attachment_category_id_arr[] = $category['id'];
            }
            $attachment_category_ids = implode(',', $attachment_category_id_arr);
            if (!empty($attachment_category_ids)) {
                $not_in_cont = " AND `contract_tpl_type` NOT IN ({$attachment_category_ids}) ";
            }
        }
        $count = 0;
        $list = array();
        $result = $this->getDealidByUseridNew($user_id, $make_page, $page, $page_size,$isP2p);
        $ids_arr = $result['list'];
        if($ids_arr){
            $fields = 'id,name,user_id,borrow_amount,income_fee_rate,loantype,repay_time,type_id,deal_type';
            $deal_model = DealModel::instance();
            $ids_res = implode(',', array_map('array_shift', $ids_arr));
            $list = $deal_model->findAllViaSlave(sprintf("is_delete = 0 AND id IN (%s) %s ORDER BY FIELD(`id`, %s)", $ids_res, $not_in_cont, $ids_res), true, $fields);
            $count =  $result['count'] - (count($ids_arr) - count($list));  // 排除一些附件合同标的的数量
        }
        return array('count' => $count, 'list' => $list);
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
        $agency_service = new DealAgencyService();
        $deal_contract_model = new DealContractModel();
        $deal_list = array();
        //判断用户是否为担保
        $user_agency_info = $agency_service->getUserAgencyInfoNew(array('id'=>$user_info));
        $is_agency = intval($user_agency_info['is_agency']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $user_advisory_info = $agency_service->getUserAdvisoryInfo(array('id'=>$user_info));
        $is_advisory = intval($user_advisory_info['is_advisory']);
        //判断是否为新合同签署流程并判断是否为资产管理方
        $user_entrust_info = $agency_service->getUserEntrustInfo(array('id'=>$user_info));
        $is_entrust = intval($user_entrust_info['is_entrust']);
        //判断是否为新合同签署流程并判断是否为渠道方
        $user_canal_info = $agency_service->getUserCanalInfo(array('id'=>$user_info));

        $is_canal = intval($user_canal_info['is_canal']);

        $deal_model = new DealModel();
        $is_borrow = $deal_model->isBorrowUser($user_id);
        //$userRole用户实际角色 $role用户前台选择查看的角色
        if($is_agency == 1){
            $userRole = ContractEnum::ROLE_AGENCY;
        }else if($is_advisory == 1){
            $userRole = ContractEnum::ROLE_ADVISORY;
        }else if($is_entrust == 1){
            $userRole = ContractEnum::ROLE_ENTRUST;
        }else if($is_canal == 1){
            $userRole = ContractEnum::ROLE_CANAL;
        }else{
            if ($is_borrow) {
                $userRole = ContractEnum::ROLE_BORROWER;
            } else {
                $userRole = ContractEnum::ROLE_LENDER;
            }
        }
        if($role == null){
            $role = $userRole;
        }

        //取合同列表新逻辑
        if($role == ContractEnum::ROLE_LENDER){
            $deal_list = $this->getUserContDeals($user_id, true, $page, $page_size, false, $isP2p);
        }elseif($role == ContractEnum::ROLE_BORROWER && $is_borrow){
                $deal_list = $this->getBorrowUserContDeals($user_id, $page, $page_size, $isP2p);
        }elseif($role == ContractEnum::ROLE_AGENCY && $is_agency){
                $deal_list = $this->getAgencyUserContDeals($user_agency_info['agency_info'], $page, $page_size, $isP2p);
        }elseif($role == ContractEnum::ROLE_ADVISORY && $is_advisory){
                $deal_list = $this->getAgencyUserContDeals($user_advisory_info['advisory_info'], $page, $page_size, $isP2p);
        }elseif($role == ContractEnum::ROLE_ENTRUST && $is_entrust){
             $deal_list = $this->getAgencyUserContDeals($user_entrust_info['entrust_info'], $page, $page_size, $isP2p);
        }elseif($role == ContractEnum::ROLE_CANAL && $is_canal){
            $deal_list = $this->getAgencyUserContDeals($user_canal_info['canal_info'], $page, $page_size, $isP2p);
        } else {
            return false;
        }
        $deal_list['is_agency'] = $is_agency == 1?true:false;
        $deal_list['is_advisory'] = $is_advisory == 1?true:false;
        $deal_list['is_entrust'] = $is_entrust == 1?true:false;
        $deal_list['is_canal'] = $is_canal == 1?true:false;
        $deal_list['is_borrow'] = $is_borrow;


        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_BXT);
        if(isset($deal_list['list'])){
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
                $deal_one['old_name'] = $deal_one['name'];
                $deal_one['name'] = msubstr($deal_one['name'], 0, 24);
                $deal_one['borrow_amount_format_detail'] = format_price($deal_one['borrow_amount'] / 10000,false);
                $deal_one['income_fee_rate_format'] = number_format($deal_one['income_fee_rate'], 2);
                $deal_one['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal_one['loantype']];
                $deal_one['is_have_sign'] = $is_have_sign;
                $deal_one['user_real_name'] = UserService::getFormatUserName($deal_one['user_id']);
            }
        }
        $deal_list['role'] = $userRole;

        return $deal_list;
    }


    /**
     * 获取特定角色优先顺序的默认角色 - 强顺序型：因为前端的下拉选项顺序固定
     * @param array $role_list
     * @return int $role
     */
    private function setDefaultRole($role_list){
        if ($role_list['is_borrow']) {
            $role = ContractEnum::ROLE_BORROWER;
        } elseif ($role_list['is_agency']) {
            $role = ContractEnum::ROLE_AGENCY;
        } elseif ($role_list['is_advisory']) {
            $role = ContractEnum::ROLE_ADVISORY;
        } elseif ($role_list['is_entrust']) {
            $role = ContractEnum::ROLE_ENTRUST;
        } else {
            $role = '';
        }

        return $role;
    }

    /**
     * 获取特定角色优先顺序的默认角色
     * @param type 用户角色
     * @param pageSize 不能改变，合同服务只支持pageSize为10
     * @return int $role
     */
    public function getDealContList($userId, $dealId, $type, $page = 1, $pageSize = 10, $agencyId = 0){
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        $response = ContractService::getRoleContractByDealId(intval($dealId),intval($userId),intval($agencyId),intval($page),intval($type), ContractServiceEnum::SOURCE_TYPE_PH);
        //在合同服务中获取合同
        $result['list'] = $response['list'];
        $result['count'] = $response['count']['num'];
        return $result;
    }

    /**
     * 根据dealId获取所有的合同模板
     * @return array
     */
    public function getTplsByDeal($dealId){
        $dealService = new DealService();
        $deal = $dealService->getDeal(intval($dealId));
        if($deal == false){
            return false;
        }

        //在合同服务中获取合同
        $response = CategoryService::getDealCId(intval($dealId), ContractServiceEnum::TYPE_P2P, ContractServiceEnum::SOURCE_TYPE_PH);
        if(!empty($response)){
            $tplsResponse = TplService::getTplsByCid(intval($response['categoryId']), (float)$response['contractVersion']);
            if(is_array($tplsResponse)){
                return $tplsResponse;
            }
        }
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
        $categories = CategoryService::getCategoryLikeTypeTag("ATTACHMENT%");
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
            $in_cont .= " AND `deal_type` IN (".DealEnum::DEAL_TYPE_ALL_P2P.")";
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

            $deal = $dealService->getDeal($v['deal_id'], true, false, true);
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
            ':agency_id' => intval($agencyInfo['id']),
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
            $condition .= " AND deal_type IN (".DealEnum::DEAL_TYPE_ALL_P2P.")";
        }

        $count = $dealContractModel->countViaSlave($condition, $params);
        $condition .= "ORDER BY `status`, `sign_time` DESC, `create_time` DESC LIMIT :start, :page_size";
        $list = $dealContractModel->findAllViaSlave($condition, true, "*", $params);

        if ($list) {
            $deal_service = new DealService();
            foreach ($list as $k => $v) {
                $deal = $deal_service->getDeal($v['deal_id'], true, false, true);
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
        $function = "\core\service\contract\ContractNewService::signDealContNew";
        $params = array(
            'dealId' => intval($dealId),
            'role' => $role,
            'id' => intval($id),
            'admID' => $admID,
            'autoSign' => $autoSign,
        );
        $jobs_model->priority = JobsEnum::SIGN_CONTRACT;
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
     * @return boolean
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

            $response = ContractService::signDealContract(intval($dealId), intval($role), intval($id), $autoSign, ContractServiceEnum::SOURCE_TYPE_PH);
            if($response != true){
                throw new \Exception("contract sign false");
            }

            if($role == 1){
                if(!$this->signByRole($dealId,$id,0,0, false, $admID)){
                    throw new \Exception("DealContractModel signByRole false role:".$role);
                }
            }elseif($role == 2){
                if(!$this->signByRole($dealId,$id,1,$deal['agency_id'])){
                    throw new \Exception("DealContractModel signByRole false role:".$role);
                }
            }elseif($role == 3){
                if(!$this->signByRole($dealId,$id,1,$deal['advisory_id'])){
                    throw new \Exception("DealContractModel signByRole false role:".$role);
                }
            }elseif($role == 4){
                if(!$this->signByRole($dealId,0,0,0,true)){
                    throw new \Exception("DealContractModel signByRole false role:".$role);
                }
            }elseif($role == 5){
                if(!$this->signByRole($dealId,$id,1,$deal['entrust_agency_id'])){
                    throw new \Exception("DealContractModel signByRole false role:".$role);
                }
            }elseif($role == 6){
                if(!$this->signByRole($dealId,$id,1,$deal['canal_agency_id'])){
                    throw new \Exception("DealContractModel signByRole false role:".$role);
                }
            }


            $signInfo = $this->getContSignNum($dealId,0,0);

            $dealContractModel = new DealContractModel();
            //补签逻辑 （如果dealcontract签署记录全部完成,contract service存在未签署记录,则补签)
            if((!$signInfo['is_sign_all']) && ($dealContractModel->getDealContractSignInfo(intval($dealId),0) == 0)){
                $response = ContractService::signDealContract(intval($dealId), 4, intval($id), $autoSign, ContractServiceEnum::SOURCE_TYPE_PH);
                if($response->errorCode != true){
                    throw new \Exception("contract sign  all false");
                }

                $signInfo = $this->getContSignNum($dealId,0,0);
            }

            if($signInfo['is_sign_all']){
                // 使用消息队列发送下发合同的邮件和短信
                $message = array('dealId'=>$dealId);
                MsgbusService::produce(MsgbusEnum::TOPIC_CONTRACT_MSG,$message);
                $jb = new JobsModel();
                $func = '\core\service\contract\ContractNewService::sendMsg';
                $params = array('dealId' => $dealId );
                $jb->priority = JobsEnum::JOBS_PRIORITY_MSGBUS;
                $res = $jb->addJob($func,$params,false,1);
                if(!$res){
                    throw new \Exception('add insert jobs fail');
                }
            }
            Monitor::add('CS_CONTRACT_SIGN_SUCCESS');
            return true;
        } catch (\Exception $e) {
            Monitor::add('CS_CONTRACT_SIGN_FAIL');
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'CS_CONTRACT_SIGN_FAIL',"dealId:{$dealId},role:{$role},id:{$id} errMsg:".$e->getMessage())));
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
        $response = ContractService::getDealSignNum(intval($dealId),intval($role),intval($id),ContractServiceEnum::SOURCE_TYPE_PH);
        if(isset($response['total']) && isset($response['signed'])){
            $contract_count = $response['total'];
            $sign_count = $response['signed'];
        }
        //返回数据
        $res['is_sign_all'] = ($contract_count > $sign_count) || (0 == $contract_count) ? false : true;
        $res['contract_num'] = $contract_count;
        $res['sign_num'] = $sign_count;

        return $res;
    }

    /**
     *按照分类ID获取合同模板分类
     */
    public function getCategoryByCid($id){
        if(empty($id)){
            return array();
        }
        return CategoryService::getCategoryById(intval($id));
    }

    /***
     * 根据时间段，获取当日所有标的的打戳信息包含合同服务标的
     * 去掉了$dealInfo['contract_tpl_type']非数字的情况，也就是去掉了老合同逻辑
     * @param start_time, 开始时间（时间戳）
     * @param end_time, 结束时间（时间戳）
     */
    public function getDealTsaInfo($start_time,$end_time){
        $dealService = new DealService();
        $loan_oplog_model = new LoanOplogModel();
        $oplogs = $loan_oplog_model->getDealIdByTime($start_time,$end_time);

        $result = array();
        if(count($oplogs) > 0){
            foreach($oplogs as $oplog){
                $dealInfo = $dealService->getDeal($oplog['deal_id']);
                if(is_numeric($dealInfo['contract_tpl_type'])){

                    $response = ContractService::getDealTsaInfo(intval($oplog['deal_id'], ContractServiceEnum::SOURCE_TYPE_PH));
                    if(!empty($response)){
                        if(($response['total'] - $response['signed']) > 0){
                            $result[$oplog['deal_id']] = false;
                        }
                    }else{
                        $result[$oplog['deal_id']] = false;
                    }
                }
            }
        }
        return $result;
    }

    /***
     * 签署时间戳，更新contract表状态
     * @param dealId, 标的ID
     * @param number, 合同编号
     */
    public function signTsaCallback($dealId,$number){
        $contractServcie = new ContractService();
        try {
            $GLOBALS['db']->startTrans();
            if ($contractServcie->signTsaCallback($dealId, $number) === false) {
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
    public function getContractCategorys(){
        $categorys = CategoryService::getCategorys(ContractCategoryEnum::CATEGORY_IS_DLETE_NO,ContractServiceEnum::TYPE_P2P, ContractServiceEnum::SOURCE_TYPE_PH);

        if(empty($categorys) || !is_array($categorys)){
            return false;
        }

        return $categorys;
    }

    /**
     * 判断是否为附件合同
     * @author <fanjingwen@ucfgroup.com>
     * @param  int  $contract_tpl_type 对应deal表字段
     * @return boolean
     */
    public function isAttachmentContract($contract_tpl_type){
        $contract_category_info = $this->getCategoryByCid($contract_tpl_type);
        return (!empty($contract_category_info) && in_array($contract_category_info['typeTag'], ContractEnum::$tpl_type_tag_attachment));
    }

    /**
     * 获取合同附件
     * @param  int $deal_id
     * @return array
     */
    public function getContractAttachmentByDealLoad($deal_id) {
        if (empty($deal_id)) {
            return array();
        }
        $deal = DealModel::instance()->findViaSlave($deal_id);
        return (new ContractService())->getContractAttachmentByDealLoad($deal);
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
        $dealType = ($dealType !== null) ? $dealType : null;
        $name = !empty($name) ? $name : null;
        $pageSize = empty($pageSize) ? 30 : intval($pageSize);
        $pageNum = empty($pageNum) ? 1 : intval($pageNum);

        $response = CategoryService::getCategoryList(ContractServiceEnum::TYPE_P2P,$dealType,
            $pageNum,$name,ContractCategoryEnum::USE_STATUS_NOW,null,ContractCategoryEnum::CATEGORY_IS_DLETE_NO,$pageSize);

        return $response;
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
        $db = Db::getInstance('msg_box');
        try {
            if ($dealId <= 0) {
                throw new \Exception('参数错误');
            }
            $dealService = new DealService();
            $deal = $deal = $dealService->getDeal($dealId);
            if (empty($deal)) {
                throw new \Exception('标的不存在');
            }
            //智多鑫不发送消息
            if ($dealService->isDealDT($dealId)) {
                Logger::info(implode(',', array(__CLASS__, __FUNCTION__, __LINE__,"下发合同  dealId:{$dealId} 为智多鑫，不用发送站内信和邮件 ")));
                return true;
            }

            $site_url = get_deal_domain($dealId);
            $contract_url = $site_url . "/account/contract";

            // 获取合同列表(网贷)
            $response = ContractService::getContractByDealId($dealId, null, '', ContractServiceEnum::SOURCE_TYPE_PH);

            if (empty($response['list'])) {
                $response = ContractService::getContractByDealId($dealId, null, '', ContractServiceEnum::SOURCE_TYPE_PH);
            }
            if (empty($response['list'])) {
                Logger::info(implode(',', array(__CLASS__, __FUNCTION__, __LINE__,"下发合同 dealId:{$dealId} 没有合同 不发送站内信和邮件 ")));
                return true;
            }
            $list = $response['list'];
            $contract['deal_name'] = get_deal_title($deal['name'], '', $dealId);
            $contract['title'] = '"' . $contract['deal_name'] . '"的合同已经下发';

            $users = array();
            $isSendMsgForUser = array();//记录是否下发合同消息
            foreach ($list as $one) {
                if ($one['user_id'] <> 0) {
                    if ($one['is_send'] == 0 && !isset($users[$one['user_id']])) {
                        $users[$one['user_id']] =  $one['user_id'];
                        //预约投资不单独给用户下发合同消息，若用户同时存在普通投资和预约投资，默认下发合同消息
                        $dealLoad = DealLoadModel::instance()->find($one['deal_load_id']);
                        if (!isset($isSendMsgForUser[$one['user_id']]) || !$isSendMsgForUser[$one['user_id']]) {
                            $isSendMsgForUser[$one['user_id']] = $dealLoad['source_type'] == DealLoadEnum::$SOURCE_TYPE['reservation'] ? false : true;
                        }
                        unset($dealLoad); //下面不会再使用，因此在这里重置该变量，释放内存
                    }
                }
            }

            // 借款人
            if(!isset($users[$one['user_id']])){
                $users[$deal['user_id']] = $deal['user_id'];
                $isSendMsgForUser[$deal['user_id']] = true;
            }

            $msgbox = new MsgboxService();
            //投资人,借款人
            $user_id_collection = array();
            $emailDatas = array();

            $db->startTrans();
            foreach ($users as $user) {
                $user_info = UserService::getUserById($user, "id,user_type,user_name,email");
                if (isset($user_info['user_type']) && (int)$user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
                    $userName = $user_info['user_name'];
                } else {
                    $userName = UserService::getFormatUserName($user_info['id']);
                }
                // 邮件
                if (!empty($user_info['email'])) {
                    $notice_email = array(
                        'user_name' => $userName,
                        'deal_url' => $site_url . '/deal/' . $dealId,
                        'deal_name' => $contract['deal_name'],
                        'help_url' => $site_url . url("index", "helpcenter"),
                        'site_url' => $site_url,
                        'site_name' => app_conf("SHOP_TITLE"),
                        'msg_cof_setting_url' => $site_url . url("index", "uc_msg#setting"),
                        'contract_url' => $contract_url,
                    );
                    $emailDatas[] = array(
                        'userEmail' => $user_info['email'],//用户邮箱,必填
                        'userId' => $user,//用户ID,必填
                        'contentData' => $notice_email,//邮件内容,必填
                        'tplName' => 'TPL_SEND_CONTRACT_EMAIL',//邮件模板名
                        'title' => $contract['title'],//必填
                        'site' => get_deal_domain_title($dealId),
                        'data' => array(),
                    );
                }

                // 站内信 (预约投资不单独给用户下发合同消息)
                if ($isSendMsgForUser[$user] && !in_array($user, $user_id_collection)) {
                    $content = sprintf('您投资的“%s”合同已下发。', $contract['deal_name']);
                    $msgbox->create($user, MsgBoxEnum::TYPE_CONTRACT_SEND, '合同下发', $content);
                }
                unset($user_info,$userName);//下面不会再使用，因此在这里重置该变量，释放内存
            }
            // 现在有客诉反馈邮件收不到，所以把发邮件逻辑放在这里
            // jobs失败了也可以重试
            if(!empty($emailDatas)){
                $emailResult = SendEmailService::batchSendEmail($emailDatas);
                // 当用户设置了不接受邮件时，SendEmailService::batchSendEmail会返回0
                // 所以判断失败，只能使用全等于false来进行判断
                if ($emailResult === false) {
                    throw new \Exception("SendEmailService::batchSendEmail 调用失败");
                }
            }
            // 合同回调发送状态
            $result = ContractService::sendContractStatus(intval($dealId), ContractServiceEnum::SOURCE_TYPE_PH);
            if (!$result) {
                throw new \Exception("ContractService::sendContractStatus 调用失败");
            }
            $db->commit();
            Logger::info(implode(',', array(__CLASS__, __FUNCTION__, __LINE__,"下发合同 dealId:{$dealId} success")));
        } catch (\Exception $ex) {
            $db->rollback();
            Logger::error(implode(',', array(__CLASS__, __FUNCTION__, __LINE__, "下发合同失败 dealId:{$dealId}", $ex->getMessage())));
            throw $ex;
        }
        return true;
    }
}
