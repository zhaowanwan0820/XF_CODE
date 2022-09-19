<?php
/**
 *
 */

namespace core\service\darkmoon;

use NCFGroup\Protos\Contract\RequestSetDealCId;
use NCFGroup\Protos\Contract\RequestUpdateDealCId;
use NCFGroup\Protos\Contract\RequestGetDealCId;
use NCFGroup\Protos\Contract\RequestSignDealContract;
use NCFGroup\Protos\Contract\RequestSendContract;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Protos\Contract\RequestGetTplsByDealId;
use NCFGroup\Protos\Contract\RequestGetContractInfoByContractId;
use NCFGroup\Protos\Contract\RequestGetContractByLoadId;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use core\service\ContractInvokerService;
use core\service\contract\ContractUtilsService;
use core\service\contract\ContractRenderService;
use core\service\contract\ContractRemoterService;
use core\service\darkmoon\DarkMoonService;
use core\service\ContractNewService;
use core\service\AccountService;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\dao\darkmoon\DarkmoonDealModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\FastDfsModel;
use core\dao\UserModel;
use libs\utils\Logger;
use libs\utils\Rpc;


class ContractService
{

    const SOURCETYPE = 200;
    const C_TYPE = 3; //contract_category_deal中type字段
    const TSA_SUCCESS = 1; // 打戳成功-合同记录的状态
    const PREFIX_LOAN = "TPL_DARK_MOON_LOAN_CONTRACT";
    const PREFIX_FXTSS = "TPL_JYS_FXTSS";
    const PREFIX_HGCNH = "TPL_JYS_HGCNH";
    const PREFIX_CYRHYGZ = "TPL_JYS_CYRHYGZ";
    const PREFIX_CPSMS = "TPL_JYS_CPSMS";


    //上标保存合同分类
    public function saveContact($dealId,$categoryId){

        $log_info = array(__CLASS__, __FUNCTION__, $dealId, $categoryId);
        if($dealId == 0 || $categoryId == 0){
            Logger::error(implode(" | ",array_merge($log_info,array("参数不正确"))));
            return false;
        }

        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetDealCId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(self::C_TYPE);
        $contractRequest->setSourceType(self::SOURCETYPE);
        $response = $rpc->go('NCFGroup\Contract\Services\Category',"getDealCId",$contractRequest);
        $Category = $response->data;
        if(empty($Category)){
            $cidRequest = new RequestSetDealCId();
            $method = 'setDealCId';
        }else{
            $method = 'updateDealCId';
            $cidRequest = new RequestUpdateDealCId();
        }

        $cidRequest->setDealId(intval($dealId));
        $cidRequest->setCategoryId(intval($categoryId));
        $cidRequest->setType(self::C_TYPE);
        $cidRequest->setSourceType(self::SOURCETYPE);

        $contractResponse = $rpc->go("\NCFGroup\Contract\Services\Category",$method,$cidRequest);

        if($contractResponse->errorCode != 0 || !$contractResponse){
            Logger::error(implode(" | ",array_merge($log_info,array(json_encode($contractResponse)))));
            return false;
        }
        return true;
    }

    /**
     * (目前, 调用sendLoanContract方法时就是投资户生成并且签署合同了，所以只剩借款人了),
     * 所以role为1,id为用户userId
     * @param
     *  dealId 标id
     *  role   角色  1:借款人
     *  *            2:担保方 ($id 为agency_id)
     *               3:咨询方 ($id 为advisory_id)
     *               5:受信方 ($id 为entrust_agency_id)
     *               6:渠道方 ($id 为canal_agency_id)
     *               4:所有方,此时id没用
     *  id 该角色的id
     *@return true|false
     **/
    public function sign($dealId,$role,$id){
        try {
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestSignDealContract();
            $contractRequest->setDealId(intval($dealId));
            $contractRequest->setRole(intval($role));
            $contractRequest->setId(intval($id));
            $contractRequest->setSourceType(self::SOURCETYPE);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","signDealContract",$contractRequest);

            if($response->errorCode <> 0){
                throw new \Exception("contract sign false");
            }
            Logger::info(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"签署成功", 'dealId:'.$dealId, 'role:'.$role, 'id'.$id)));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"签署失败", 'dealId:'.$dealId)));
            return false;
        }
    }

    /**
     * 生成需要签署方的合同记录
     * 借款人和投资人都在本系统注册过，才能调用
     * @param $dealId
     * @return false | true
     */
    public function sendLoanContract($loadId){
        if(empty($loadId)){
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"loadId信息不存在", 'loadId:'.$loadId)));
            return false;
        }
        $dealLoad = DarkmoonDealLoadModel::instance()->find($loadId);
        if(empty($dealLoad)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的投资信息不存在", 'loadId:'.$loadId)));
             return false;
        }
        $deal = DarkmoonDealModel::instance()->find($dealLoad['deal_id']);
        if(empty($deal)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的信息不存在", 'loadId:'.$loadId)));
             return false;
        }
        $loanUser = UserModel::instance()->find($dealLoad['user_id']); //投资人
        if(empty($loanUser)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"投资人信息不存在", 'loadId:'.$loadId)));
             return false;
        }
        $borrowUser = UserModel::instance()->find($deal['user_id']); //借款人
        if(empty($borrowUser)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"借款人信息不存在", 'loadId:'.$loadId)));
             return false;
        }

        return $this->_sendContract($deal['id'], $borrowUser['id'], $loanUser['id'], $loadId, false);

    }

    /**
     * 生成不需要签署方的合同记录
     * 一个标只能调用一次
     * @param $dealId
     * @return false | true
     */
    public function fullSendContract($dealId){
        $deal = DarkmoonDealModel::instance()->find($dealId, 'id', true);
        if(empty($deal)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的信息不存在", 'dealId:'.$dealId)));
             return false;
        }
        return $this->_sendContract($dealId,0,0,0,true);
    }


    /**
     *保存合同-落库
     *
     **/
    private function _sendContract($dealId, $borrowId, $userId, $loadId, $isFull=false){
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetDealCId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(self::C_TYPE);
        $contractRequest->setSourceType(self::SOURCETYPE);
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);
        if($response && $response->errorCode == 0){
            $createTime = time();
            $sendRequest = new RequestSendContract();
            $sendRequest->setDealId(intval($dealId));;
            $sendRequest->setBorrowUserId(intval($borrowId));
            $sendRequest->setDealLoadId(intval($loadId));
            $sendRequest->setAdvisoryAgencyId(0);
            $sendRequest->setGuaranteeAgencyId(0);
            $sendRequest->setEntrustAgencyId(0);
            $sendRequest->setCanalAgencyId(0);
            $sendRequest->setDealType(self::C_TYPE);
            $sendRequest->setSourceType(self::SOURCETYPE);
            $sendRequest->setIsFull($isFull);
            $sendRequest->setLenderUserId(intval($userId));
            $sendRequest->setCreateTime($createTime);
            $sendResponse = $rpc->go("\NCFGroup\Contract\Services\SendContract","send",$sendRequest);
            if($sendResponse->errorCode > 0){
                Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"合同落库失败", 'dealId:'.$dealId)));
                return false;
            }
            Logger::info(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"合同落库成功", 'dealId:'.$dealId)));
            return true;
        }
        Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"合同落库失败", 'dealId:'.$dealId)));
        return false;
    }


    public function getContractList($deal_id,$is_seen_when_bid = false){
            try {
            // 获取合同模板 list
            $request = new RequestGetTplsByDealId();
            $request->setDealId(intval($deal_id));
            $request->setType(self::C_TYPE);
            $request->setSourceType(self::SOURCETYPE);
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Tpl", "getTplsByDealId", $request);
            if (0 == $response->getErrorCode()) {
                $res_tpls = array();
                foreach($response->list['data'] as $one_tpl) {
                    if ($is_seen_when_bid && !($one_tpl['tpl_identifier_info']['isSeenWhenBid'] == $is_seen_when_bid)) { // 如果取投资时可见的标，则跳过不是这个标识的模板
                        continue;
                    } else {
                        $res_tpls[] = array(
                            'id' => $one_tpl['id'],
                            'title' => $one_tpl['contractTitle'],
                            'content' => $one_tpl['content'],
                        );
                    }
                }
                return $res_tpls;
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同列表失败，标id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }

    }

    public function getBorrowUserContratList($dealId,$userId,$p = 1){
        $dealId = intval($dealId);
        $userId = intval($userId);
        if (empty($dealId) || empty($userId)) {
            Logger::error(sprintf('获取合同列表失败，标id：%d，用户id： %d，失败原因：%s，file：%s, line:%s', $deal_id,$userId,"参数错误", __FILE__, __LINE__));
            return array("list" =>false,"count"=>0);
        }
        $rpc = new Rpc('contractRpc');
        $where = "borrow_user_id = '{$userId}' ";
        $contractRequest = new RequestGetContractByDealId();
        $contractRequest->setDealId($dealId);
        $contractRequest->setPageNo(intval($p));
        $contractRequest->setWhere(trim($where));
        $contractRequest->setSourceType(self::SOURCETYPE);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByDealId", $contractRequest);
        if($response->error_code != 0 ){
            Logger::error(sprintf('获取借人合同失败，deal_id：%d，userId：%都，失败原因：%s，file：%s, line:%s', $dealId,$userId, $response->getErrorMsg(), __FILE__, __LINE__));
            return array("list" =>false,"count"=>0);
        }
        return array("list"=>$response->list,'count' =>$response->count);
    }


    /**
     * 这个方法有坑最多取十条数据
     * 通过条件获取合同内容
     * @param intval $dealId
     * @param string $where
     * @return array
     */
    public function getContractListByWhere($dealId,$where = ""){
        $dealId = intval($dealId);
        if (empty($dealId)) {
            Logger::error(sprintf('获取合同列表失败，标id：%d，失败原因：%s，file：%s, line:%s', $deal_id,"参数错误", __FILE__, __LINE__));
            return array("list" =>array(),"count"=>0);
        }
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByDealId();
        $contractRequest->setDealId($dealId);
        $contractRequest->setPageNo(1);
        $contractRequest->setWhere(trim($where));
        $contractRequest->setSourceType(self::SOURCETYPE);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByDealId", $contractRequest);
        if($response->error_code != 0 ){
            Logger::error(sprintf('获取借人合同失败，deal_id：%d，userId：%都，失败原因：%s，file：%s, line:%s', $dealId,$userId, $response->getErrorMsg(), __FILE__, __LINE__));
            return array("list" =>array(),"count"=>0);
        }
        return array("list"=>$response->list,'count' =>$response->count);
        }

    //获取不需要签的合同
    public function getSystemContactList($dealId){
         $result = $this->getContractListByWhere($dealId,"user_id = 0 and borrow_user_id = 0 and deal_load_id = 0");
         return $result['list'];
    }

    /**
     * @param dealId 标id
     * tplId contract_template的id
     * loadId 投资id
     * @return array
     *
     */
    public function viewContract($dealId, $tplId, $loadId){
        try {
            if (empty($tplId) || empty($dealId) || empty($loadId)) {
                throw new \Exception(sprintf('参数有误，参数：%s', implode(' | ', func_get_args())));
            }

            $dealLoad = DarkmoonDealLoadModel::instance()->find($loadId);
            if(empty($dealLoad)){
                Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的投资信息不存在", 'loadId:'.$loadId)));
                throw new \Exception(sprintf('标的投资信息不存在 ：%s', implode(' | ', func_get_args())));
            }

            $deal = DarkmoonDealModel::instance()->find($dealId);
            if(empty($deal)){
                Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的信息不存在", 'loadId:'.$loadId)));
                throw new \Exception(sprintf('标的信息不存在 ：%s', implode(' | ', func_get_args())));
            }
            $loanUser = UserModel::instance()->find($dealLoad['user_id']); //投资人
            if(empty($loanUser)){
                Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"投资人信息不存在", 'loadId:'.$loadId)));
                throw new \Exception(sprintf('投资人信息不存在 ：%s', implode(' | ', func_get_args())));
            }

            $contract_info = ContractRemoterService::getContractTplById($tplId);
            if (empty($contract_info)) {
                throw new \Exception('合同模板不存在');
            } else {
                $notice = $this->getNotice($dealId, $dealLoad['user_id'], array(), $dealLoad['id']);
                $contract_info['content'] = ContractUtilsService::fetchContent($notice, $contract_info['content']);
            }

            return array('title' => $contract_info['contractTitle'], 'content' => $contract_info['content']);
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同模板失败，合同模板id：%d，失败原因：%s，标id：%d，投资id：%d，file：%s, line:%s', $tpl_id,$dealId,$loadId, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取渲染合同的变量
     * 此方法汇总了暗月项目所有合同的模板变量，所以任何模板获取变量 只需调用此方法即可
     * @param dealId
     * userId
     * contract_info 合同
     * loadId 投资记录id
     * contract_info和loadId，二者之一必传,否则返回false
     * @return array|false
     *
     */
    public function getNotice($dealId, $userId, $contract_info, $loadId = 0){
        $deal = DarkmoonDealModel::instance()->find($dealId, '*', true);
        if(empty($deal)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的信息不存在", 'dealId:'.$dealId)));
             return false;
        }
        $loanUser = UserModel::instance()->find($userId); //投资人
        // 投资人id不为空时，才会判断投资人是否存在
        // 因为合同中有不需要签署方的合同，所以本方法也要支持userId为0的情况
        if(!empty($userId) && empty($loanUser)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"投资人信息不存在", 'dealId:'.$dealId)));
             return false;
        }

        $borrowUser = UserModel::instance()->find($deal['user_id']); //借款人
        if(empty($borrowUser)){
             Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"借款人信息不存在 " , 'dealId:'.$dealId)));
             return false;
        }
        if(empty($contract_info) && empty($loadId)){
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"投资记录id和合同记录，二者之一必传 " , 'dealId:'.$dealId)));
            return false;
        }

        $borrowInfo =  ContractRenderService::getBorrowInfo($deal['user_id']);
        $loan_bank_info = $this->getUserBank($userId);
        $loanInfo = ContractRenderService::getLoanInfo($loanUser, $loan_bank_info); // 甲方 - 借出方
        $agencyInfo = ContractRenderService::getAgencyInfo($deal['agency_id']);
        //目前所有合同信息都会信息，不用做脱敏处理
/*
        if (empty($contract_info)) {
            $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
            $notice['number'] = '[]';
            // 借款方
            $notice['borrow_name'] = '***投资成功后才可查看';
            $notice['borrow_user_number'] = "***投资成功后才可查看";
            $notice['borrow_license'] = '***投资成功后才可查看';
            $notice['borrow_agency_realname'] = '***投资成功后才可查看';
            $notice['borrow_agency_idno'] = '***投资成功后才可查看';
            $notice['company_license'] = '***投资成功后才可查看';
            $notice['company_address_current'] = "***投资成功后才可查看";

            // 委托方
            $notice['entrust_name'] = '***投资成功后才可查看';
            $notice['entrust_license'] = '***投资成功后才可查看';
            $notice['entrust_agent_real_name'] = '***投资成功后才可查看';
            $notice['entrust_agent_user_number'] = '***投资成功后才可查看';
            $notice['entrust_agent_user_idno'] = '***投资成功后才可查看';
            $notice['canal_agent_user_idno'] = '***投资成功后才可查看';

            $notice['start_time'] = '[]';
            $notice['end_time'] = '[]';
            $notice['borrow_sign_time'] = '合同签署之日';
            $notice['advisory_sign_time'] = '合同签署之日';
            $notice['agency_sign_time'] = '合同签署之日';
            $notice['entrust_sign_time'] = '合同签署之日';
            $notice['canal_sign_time'] = '合同签署之日';
        }else{
 */
        list($notice['borrow_sign_time'], $notice['advisory_sign_time'], $notice['agency_sign_time'], $notice['entrust_sign_time']) = self::getRoleSignTime($contract_info);
        $notice['number'] = $contract_info['number'];

        // 投资方
        $loadId = empty($loadId) ? $contract_info['dealLoadId'] : $loadId;
        $loadInfo = DarkmoonDealLoadModel::instance()->find($loadId);
        //兼容没有生成合同而需要预览合同
        $money = !empty($money) ? $money : $loadInfo['money'];
        // 投资人签署时间，兼容没有签署的合同
        $notice['sign_time'] = !empty($contract_info['userSignTime']) ?  date("Y年m月d日",$contract_info['userSignTime']) : date("Y年m月d日",time());

        // 借款方
        $notice['borrow_name']                 = $borrowInfo['borrow_name'];
        $notice['borrow_user_number']          = $borrowInfo['borrow_user_number']; // 会员编号
        $notice['borrow_license']              = $borrowInfo['borrow_license']; // 营业执照号
        $notice['borrow_agency_realname']      = $borrowInfo['borrow_agency_realname']; // 代理人姓名
        $notice['borrow_agency_idno']          = $borrowInfo['borrow_agency_idno']; // 代理人证件号
        $notice['company_license']             = $borrowInfo['borrow_license'];
        $notice['borrow_legalbody_name']         = $borrowInfo['borrow_legalbody_name']; // 企业法人
        $notice['borrow_registration_address']  = $borrowInfo['borrow_registration_address'];//注册地址

        //合同起始时间
        $contract_start_time = $contract_info['createTime'];
        $notice['start_time'] = date("Y-m-d",$contract_start_time);
        // $notice['end_time'] = ($deal['loantype'] == 5) ? date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time)) : date("Y-m-d",strtotime("+".$deal['repay_time']." month",  $contract_start_time));

//        }

        $notice['project_borrow_amount'] = $deal['borrow_amount'];
        $notice['loan_money'] = $money;
        $notice['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        $notice['borrow_money'] = $deal['borrow_amount'];
        $notice['jys_record_number'] = $deal['jys_record_number'];
        $notice['rate'] = format_rate_for_cont($deal['rate']);
        $notice['repay_time_unit'] = (5 == $deal['loantype']) ? $deal['repay_time'] . "天" : $deal['repay_time'] . "个月";
        $notice['use_info'] = $deal['use_info'];
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['prepayment_day_restrict'] = $deal['prepay_days_limit'];
        $notice['overdue_ratio'] = format_rate_for_cont($deal['prepay_rate']);

        // ---------------- 甲方 - 借出方 ----------------
        $notice['loan_name_info'] = $loanInfo['loan_name_info'];
        $notice['loan_username_info'] = $loanInfo['loan_username_info'];
        $notice['loan_credentials_info'] = $loanInfo['loan_credentials_info'];
        $notice['loan_bank_user_info'] = $loanInfo['loan_bank_user_info'];
        $notice['loan_bank_no_info'] = $loanInfo['loan_bank_no_info'];
        $notice['loan_bank_name_info'] = $loanInfo['loan_bank_name_info'];
        $notice['loan_name_info_transfer'] = $loanInfo['loan_name_info_transfer'];
        $notice['loan_username_info_transfer'] = $loanInfo['loan_username_info_transfer'];
        $notice['loan_credentials_info_transfer'] = $loanInfo['loan_credentials_info_transfer'];
        $notice['loan_bank_user_info_transfer'] = $loanInfo['loan_bank_user_info_transfer'];
        $notice['loan_bank_no_info_transfer'] = $loanInfo['loan_bank_no_info_transfer'];
        $notice['loan_bank_name_info_transfer'] = $loanInfo['loan_bank_name_info_transfer'];
        $notice['loan_major_name'] = $loanInfo['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loanInfo['loan_major_condentials_no'];
        $notice['loan_user_number'] = $loanInfo['loan_user_number'];
        //新增线下交易所银行卡变量
        $notice['loan_dark_bank_no_info'] = $loadInfo['bank_id'];
        $notice['loan_dark_bank_name_info'] = $loadInfo['bank_name'];


        // ---------------- 戊方 - 保证方 ----------------
        $notice['agency_name'] = $agencyInfo['agency_name'];
        $notice['agency_agent_user_number'] = $agencyInfo['agency_agent_user_number'];
        $notice['agency_license'] = $agencyInfo['agency_license'];
        $notice['agency_agent_real_name'] = $agencyInfo['agency_agent_real_name'];
        $notice['agency_agent_user_idno'] = $agencyInfo['agency_agent_user_idno'];
        // other
        $notice['agency_agent_user_name'] = $agencyInfo['agency_agent_user_name'];
        $notice['agency_user_realname'] = $agencyInfo['realname'];
        $notice['agency_address'] = $agencyInfo['address'];
        $notice['agency_mobile'] = $agencyInfo['mobile'];
        $notice['agency_postcode'] = $agencyInfo['postcode'];
        $notice['agency_fax'] = $agencyInfo['fax'];
        $notice['agency_platform_realname'] = $agencyInfo['agency_platform_realname'];
        // ---------------- over - 保证方 -------------------

        return $notice;
    }

    /**
     * 获取除甲方之外，其他角色的签署时间
     */
    static private function getRoleSignTime($contract_info){
        if($contract_info['borrowerSignTime'] > 0){
            $borrow_sign_time = "<span id='borrow_sign_time'>".date('Y年m月d日',$contract_info['borrowerSignTime'])."</span>";
        }else{
            $borrow_sign_time = "<span id='borrow_sign_time'>合同签署之日</span>";
        }


        if($contract_info['advisorySignTime'] > 0){
            $advisory_sign_time = "<span id='borrow_sign_time'>".date('Y年m月d日',$contract_info['advisorySignTime'])."</span>";
        }else{
            $advisory_sign_time = "<span id='advisory_sign_time'>合同签署之日</span>";
        }


        if($contract_info['agencySignTime'] > 0){
            $agency_sign_time = "<span id='borrow_sign_time'>".date('Y年m月d日',$contract_info['agencySignTime'])."</span>";
        }else{
            $agency_sign_time = "<span id='agency_sign_time'>合同签署之日</span>";

        }



        if($contract_info['entrustAgencySignTime'] > 0){
            $entrust_sign_time = "<span id='entrust_sign_time'>".date('Y年m月d日',$contract_info['entrustAgencySignTime'])."</span>";
        }else{
            $entrust_sign_time = "<span id='entrust_sign_time'>合同签署之日</span>";
        }


        return array($borrow_sign_time, $advisory_sign_time, $agency_sign_time, $entrust_sign_time);
    }

    /**
     * 根据用户id获取银行卡信息
     * 从libs\common\app.php 复制过来
     * @author wenyanlei  2013-7-15
     * @param  $userid    用户id
     * @return array
     */
    private function getUserBank( $userid = 0 ){
        if($userid <= 0) return array();
        $bank_list = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM ".DB_PREFIX."bank ORDER BY is_rec DESC,sort DESC,id ASC");
        //用户银行卡信息
        $bankcard_info = \core\dao\UserBankcardModel::instance()->getNewCardByUserId($userid);
        if($bankcard_info){
            foreach($bank_list as $k=>$v){
                if($v['id'] == $bankcard_info['bank_id']){
                    $bankcard_info['bankname'] = $v['name'];
                    break;
                }
            }
            return $bankcard_info;
        }
        return array();
    }

    /**
     *根据合同id和标id获取渲染后的合同
     *@return array
     *       content 元素中是渲染后的合同
     * */
    public function getContract($contract_id ,$dealId,$userId = 0){
        $dealId = intval($dealId);
        $contract_id = intval($contract_id);
        $userId = intval($userId);

        if($dealId <= 0 || $contract_id <= 0){
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,$dealId,$contract_id,'参数错误')));
            return false;
        }

        $notice = array();
        // 获取合同模板
        $request = new RequestGetContractInfoByContractId();
        $request->setServiceId($dealId);
        $request->setServiceType(ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL);
        $request->setContractId($contract_id);
        $request->setSourceType(self::SOURCETYPE);
        $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractInfoByContractId", $request);

        if(empty($response) ||  $response->error_code != 0){
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,$dealId,$contract_id,"error:" .$response->getErrorMsg())));
            return array("content"=>"");
        }

        //渲染的数据为空，直接把渲染后的模板变为空
        $contractInfo = $response->data;
        //优先使用合同里面的用户id
        $userId = $contractInfo['userId']?$contractInfo['userId']:$userId;
        $notice = $this->getNotice($dealId,$userId,$contractInfo);
        $contractInfo['content'] = empty($notice)? "" : ContractUtilsService::fetchContent($notice, $contractInfo['content']);
        return $contractInfo;
    }

    //创建pdf
    public function createPdf($contract_id ,$dealId){
        $cont =$this->getContract($contract_id,$dealId);
        $file_name = $cont['number'] . ".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        if(!file_exists($file_path)){
            \FP::import("libs.tcpdf.tcpdf");
            \FP::import("libs.tcpdf.mkpdf");
            set_time_limit(300);$mkpdf = new \Mkpdf ();
            $mkpdf->mk($file_path, $cont['content']);
        }
        return $file_path;
    }

    //用模板创建pdf
    public function createTplPdf($tpl_id,$deal_id,$dealLoadId){
        $cont =$this->viewContract($deal_id, $tpl_id, $dealLoadId);
        $title = isset($cont['number'])?$cont['number']:$cont['title'].'_'.$deal_id.$dealLoadIdoadId.$tpl_id;
        $file_name = $title . ".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        if(!file_exists($file_path)){
            \FP::import("libs.tcpdf.tcpdf");
            \FP::import("libs.tcpdf.mkpdf");
            set_time_limit(300);
            $mkpdf = new \Mkpdf ();
            $mkpdf->mk($file_path, $cont['content']);
        }
        return $file_path;
    }

    // 下载
    public function download($contract_id ,$dealId,$userId = 0)
    {
        $cont =$this->getContract($contract_id,$dealId,$userId);
        $file_name = $cont['number'] . ".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        if(!file_exists($file_path)){
            \FP::import("libs.tcpdf.tcpdf");
            \FP::import("libs.tcpdf.mkpdf");
            set_time_limit(300);
            $mkpdf = new \Mkpdf ();
            $mkpdf->mk($file_path, $cont['content']);
        }
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;
    }

    // 查看下载
    public function viewDownload($deal_id, $tpl_id, $dealLoadId)
    {
        $cont =$this->viewContract($deal_id, $tpl_id, $dealLoadId);
        $title = isset($cont['number'])?$cont['number']:$cont['title'].'_'.$deal_id.$dealLoadId.$tpl_id;
        $file_name = $title . ".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        if(!file_exists($file_path)){
            \FP::import("libs.tcpdf.tcpdf");
            \FP::import("libs.tcpdf.mkpdf");
            set_time_limit(300);
            $mkpdf = new \Mkpdf ();
            $mkpdf->mk($file_path, $cont['content']);
        }
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;
    }

    /**
     * 下载打戳pdf
     * @param intval $contract_id
     * @param intval $dealId
     * @return boolean
     */
    public function downloadTsa($contract_id,$dealId){
        $contract_info = $this->getContract($contract_id,$dealId);
        if(empty($contract_info['number'])){
            return false;
        }
        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contract_info['number']);
        if(!empty($ret) && !empty($ret[0])){
            $fileInfo = end($ret);
            $dfs = new FastDfsModel();
            $fileContent = $dfs->readTobuff($fileInfo['group_id'],$fileInfo['path']);
            if(!empty($fileContent)){
                header ( "Content-type: application/octet-stream" );
                header ( 'Content-Disposition: attachment; filename="'.$contract_info['number'].'.pdf"');
                echo $fileContent;
                exit;
            }else{
                ContractUtilsService::writeSignLog(sprintf('signed contract file is lost [contractId:%d]', $contract_id));
                return false;
            }
        }
        else{
            // 如果记录表中没有信息则
            ContractUtilsService::writeSignLog(sprintf('contract file is signing [contractId:%d]', $contract_id));
            return false;
        }
    }

    public function createPdfTsa($contract_id,$dealId){
        $contract_info = $this->getContract($contract_id,$dealId);
        if(empty($contract_info['number'])){
            return false;
        }
        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contract_info['number']);
        if(!empty($ret) && !empty($ret[0])){
            $fileInfo = end($ret);
            $dfs = new FastDfsModel();
            $fileContent = $dfs->readTobuff($fileInfo['group_id'],$fileInfo['path']);
            $file_name = $contract_info['number'] . "_tsa.pdf";
            $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
            $document = file_put_contents($file_path,$fileContent);
            if(empty($document)){
                Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,'file_group_id : '.$fileInfo['group_id'],'file_info : '.json_encode($fileInfo))));
                return false;
            }
        }else{
            ContractUtilsService::writeSignLog(sprintf('contract file is signing [ContractUtilsServiceId:%d]', $contract_id));
            return false;
        }
        return $file_path;
    }


    /**
     * 根据标的id 获取合同列表
     * @param int $deal_load_id
     * @return array
     */
    public function getContractListByDealLoadId($deal_load_id)
    {
        try {
            $deal_load_id = intval($deal_load_id);
            $deal_load_info = DarkmoonDealLoadModel::instance()->findViaSlave($deal_load_id);
            $deal_info = DarkmoonDealModel::instance()->findViaSlave($deal_load_info['deal_id']);
            if(empty($deal_load_info) || empty($deal_info)){
                throw new \Exception('没有相关投资记录');
            }

            $request = new RequestGetContractByLoadId();
            $request->setDealId(intval($deal_info['id']));
            $request->setLoadId(intval($deal_load_id));
            $request->setUserId(intval($deal_load_info['user_id']));
            $request->setSourceType(self::SOURCETYPE);
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract","getContractByLoadId",$request);
            if($response->getErrorCode() == 0){
                $cont_list = $response->getList();
            } else {
                throw new \Exception($response->getErrorMsg());
            }
            return $cont_list;
        } catch (\Exception $e) {
            Logger::error(sprintf('获取单笔投资的合同列表失败，投资id：%d，失败原因：%s，file：%s, line:%s', $deal_load_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 通过模板标识获取模板
     */
    public function getTplByPrefix($dealId,$tpl_prefix = self::PREFIX_CPSMS){
        $rpc = new Rpc('contractRpc');
        $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
        $request->setDeal_id(intval($dealId));
        $request->setType(self::C_TYPE);
        $request->setSourceType(self::SOURCETYPE);
        $request->setTpl_prefix($tpl_prefix);
        $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
        ));

        if($response->error_code){
            Logger::error(sprintf('获取模板失败，deal_id：%d，prefix：%s，失败原因：%s，file：%s, line:%s', $dealId,$tpl_prefix, $response->getErrorMsg(), __FILE__, __LINE__));
            return array();
        }

        return array(
                'id'=>$response->data[0]['id'],
                'title'=>$response->data[0]['contractTitle'],
                'isTpl' => 1 //标明是模板，不是实际合同
        );
    }

    /**
     * 更新标的状态为4-已完成-已签署(检查完所有合同记录都打戳了才会更新)
     * @param dealId
     * @return ture|false
     * @throw Exception
     */
    public function updateDealAfterCheckTsa($dealId){
        // 1 标的是否存在
        $deal = DarkmoonDealModel::instance()->find($dealId);
        if(empty($deal)){
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"标的信息不存在", 'dealId:'.$dealId)));
            throw new \Exception('标的信息不存在');
        }
        while(true){
            // 1 获取所有合同记录
            $contracts = $this->getContracsByDealId($dealId);
            if(empty($contracts)){
                Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"合同记录不存在 ", 'dealId:'.$dealId)));
                throw new \Exception('合同记录不存在 dealId:'.$dealId);
            }
            // 2 检查是否签署
            $isAllTsa = true; //是否全部签署完
            foreach($contracts as $contract){
                if($contract['status'] != self::TSA_SUCCESS){
                    $isAllTsa = false;
                }
            }
            if($isAllTsa === false){
                // 3 没有签署则休眠10分钟
                sleep(300); //休眠10分钟
            }else{
                Logger::info(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"全部签署 ", 'dealId:'.$dealId)));
                break;
           }
        }

        // 4 全部签署了，则更新标的状态
        $s = new DarkMoonService();
        if($s->finishTimeStamp($dealId)){
            Logger::info(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"全部签署 ", 'dealId:'.$dealId)));
            return true;
        }else{
            Logger::error(implode(" | ",array(__FILE__,__FUNCTION__,__LINE__,"更新为已签署失败 ", 'dealId:'.$dealId)));
            throw new \Exception('更新失败 dealId:'.$dealId);
        }

    }

    /**
     * 获取该标的id的所有合同
     * $dealId
     */
    public function getContracsByDealId($deal_id){
        try {
            $deal_id = intval($deal_id);
            $deal_info = DarkmoonDealModel::instance()->findViaSlave($deal_id);
            if(empty($deal_info)){
                throw new \Exception(sprintf('此项目不存在，标的 id：%d', $deal_id));
            }
            $pageSize = 1000;
            $result = array();

            // 获取合同模板
            $request = new RequestGetContractByDealId();
            $request->setDealId($deal_id);
            $request->setSourceType(DarkmoonDealModel::DEAL_TYPE_OFFLINE_EXCHANGE);
            $request->setPageNo(1);
            $request->setPageSize($pageSize);
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractByDealId", $request);

            if(!empty($response)){
                if (0 === $response->getErrorCode()) {
                    $result = $response->getList();

                    if($response->getCount() > $pageSize){
                        $countInfo = $response->getCount();
                        $count = $countInfo['num'];
                        $page = ceil($count/$pageSize);
                        for($i = 2; $i <= $page; $i++){
                            $pageRequest = new RequestGetContractByDealId();
                            $pageRequest->setDealId($deal_id);
                            $pageRequest->setSourceType(DarkmoonDealModel::DEAL_TYPE_OFFLINE_EXCHANGE);
                            $pageRequest->setPageNo($i);
                            $pageRequest->setPageSize($pageSize);
                            $pageResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractByDealId", $pageRequest);
                            if(!empty($pageResponse)){
                                if (0 === $pageResponse->getErrorCode()) {
                                    $result = array_merge($result,$pageResponse->getList());
                                }else {
                                    throw new \Exception($response->getErrorMsg());
                                }
                            }
                        }
                    }

                    return $result;

                } else {
                    throw new \Exception($response->getErrorMsg());
                }
            }
        }  catch (\Exception $e) {
            Logger::error(sprintf('获取暗月标的合同失败 ，标的 id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }

    }

}
