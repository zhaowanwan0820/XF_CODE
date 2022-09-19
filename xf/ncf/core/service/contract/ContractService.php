<?php

namespace core\service\contract;

use core\service\BaseService;
use core\service\deal\DealService;
use core\service\contract\TplService;

use core\dao\contract\DealContractModel;
use core\dao\contract\ContractSignSwitchModel;

use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractEnum;
use core\enum\contract\ContractSignSwitchEnum;

use libs\utils\Logger;
/**
 * 优惠券相关接口
 */
class ContractService extends BaseService {

    private static $funcMap = array(

        /**
         * 获取多投宝合同列表信息
         * @return array
         */
        'getLoansContract' => array('type','dealInfo','userId'),
        /**
         * 根据dealId,loadId获取合同列表
         * 取出投资时生成的合同记录、以及各方角色无关的已盖戳合同记录（即各方id为0）
         * @return array
         */
        'getContractByLoadId' => array('dealId','loadId','userId','sourceType','needTplInfo'),
        /**
         * 根据dealId,num,sourceType获取合同列表
         * @return array
         */
        'getContractByDealNum' => array('dealId','num','sourceType'),
        /**
         * 根据dealId,id获取合同
         * @return array
         */
        'getContractByCid' => array('dealId','id','sourceType'),
        /**
         * 根据dealId,type获取合同
         * 获取老版合同才会使用该方法(老版合同: dealId <= 12123)
         * @return array
         */
        'getContractByType' => array('dealId','type','sourceType'),
        /**
         * 根据dealId,tpl_identifier_id获取合同
         * @return array
         */
        'getContractByTplIdentity' => array('dealId','tplIdentityId','sourceType'),
        /**
         * 根据dealId,type获取合同(合同服务化之前的合同)
         * @return array
         */
        'getOldContractByType' => array('dealId','type','sourceType'),
        /**
         * 根据projectId,id获取合同
         * 专享相关，暂时注释掉
         * @return array
         */
//        'getContractByProject' => array('projectId','id','sourceType'),
        /**
         * 根据dealId获取合同列表
         * @param pageNo，传null或者0，可以获取所有记录；传n(n>=1)，则回去第n页pageSize为10的记录
         * @return array data
         *              array list
         *              array count
         */
        'getContractByDealId' => array('dealId','pageNo','where','sourceType'),

        /**
         * 根据用户角色及dealId获取合同列表
         * type 0投资人,1借款人,2担保,3资产管理;4委托，5渠道
         * @return array
         */
        'getRoleContractByDealId' => array('dealId','userId','agencyId','pageNo','type','sourceType'),


        /**
         * 根据用户角色及项目Id获取合同列表
         * type 0投资人,1借款人,2担保,3资产管理;4委托，5渠道
         * 专享相关，暂时注释掉
         * @return array
         */
 //       'getRoleContractByProjectId' => array('projectId','userId','agencyId','pageNo','type','sourceType'),
        /**
         * 合同签署
         * role 1借款人,2担保,3资产管理,5委托,6渠道  4:全部
         * id 则是role对应签署方的userId或者agencyId
         * autoSign 如果某个借款方autoSign设为true的话，那么签署时间即为创建时间,否则为调用此方法的时间
         * @return int 1:成功 0:失败
         */
        'signDealContract' => array('dealId','role','id','autoSign','sourceType'),
         /**
         * 项目合同签署
         * 专享相关，暂时注释掉
         * @return array
         */
 //     'signProjectContract' => array('projectId','role','id','autoSign','sourceType'),
        /**
          * role 1借款人,2担保,3资产管理,5委托,6渠道 0投资人
          * id 则是role对应签署方的userId或者agencyId
          * 获取标的用户签署数据
          * @return array
          */
        'getDealSignNum' => array('dealId','role','id','sourceType'),
        /**
         * 项目合同签署
         * 专享相关，暂时注释掉
         * @return array
         */
//        'getProjectSignNum' => array('projectId','role','id','sourceType'),
         /**
          * 获取标的时间戳信息(加盖时间戳的合同数目和合同总数目)
          * @return array
          */
        'getDealTsaInfo' => array('dealId','sourceType'),
        /**
         * 时间戳回调
         * type 1:专享，0其他
         * @return array
         */
        'signTsaCallback' => array('dealId','number','projectId','type','sourceType'),
        /**
         * 发送回调
         * 更新合同表中is_send为1
         * @return array
         */
        'sendContractStatus' => array('dealId','sourceType'),
        /**
         * 根据dealId获取合同列表
         * @return array
         */
        'getContractIdNumByDealId' => array('dealId','pageNo','pageSize','sourceType'),
        /**
         * 根据projectId获取合同列表
         * 专享相关，暂时注释掉
         * @return array
         */
//        'getContractByProjectId' => array('projectId','pageNo','where','sourceType'),
        /**
         * 根据用户角色获取获项目合同
         * 专享相关，暂时注释掉
         * @return array
         */
 //       'getProjectContractByUserRole' => array('id','role','pageNo','pageSize','sourceType'),
        /**
         * 根据合同 id 和 标的 id 获取合同信息
         * serviceType: 1标的 2项目（专享）
         * serviceId: dealId或者projectId
         * @return array
         */
        'getContractInfoByContractId' => array('serviceId','serviceType','contractId','other','sourceType'),
        /**
         * 根据合同编号获取合同记录
         * @param      int    dealId
         * @param   string    number
         * @param      int    sourceType
         * @return array
         */
        'getContractByNumber' => array('dealId','number','sourceType'),



    );

    private static $defaultFunc = array(
        'save',
    );

    private static $defaultParamValue = array(
        'id' =>  '',
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (isset($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        // 对于方法中，后面几个未传，则会指定默认值
        if (in_array($name,self::$defaultFunc)) {
            foreach(self::$defaultParamValue as $key => $default){
                if(in_array($key,$argNames)){
                    $args[$key] = !isset($args[$key]) ? $default : $args[$key];
                }
            }
        }
        return self::rpc('contract', 'contract/'.$name, $args);
    }



    /**
     * 获取标的各方的签署状态(借款方,担保方,咨询方,受托方,渠道方)
     * @return array
     */

    public static function getContractSignStatus($dealId,$borrowUserId=0,$agencyId=0,$advisoryId=0,$entrustAgencyId=0,$canalAgencyId=0){
        if(empty($dealId)){
            return array();
        }
        if(empty($borrowUserId) && empty($agencyId) && empty($advisoryId) && empty($entrustAgencyId) && empty($canalAgencyId)){
            return array();
        }
        $result = array(
            'borrowUser' => array('id'=>$borrowUserId, 'status' => '/', 'statusCode' => -1),
            'agency' => array('id'=>$agencyId, 'status' => '/', 'statusCode' => -1),
            'advisory' => array('id'=>$advisoryId, 'status' => '/', 'statusCode' => -1),
            'entrustAgency' => array('id'=>$entrustAgencyId, 'status' => '/', 'statusCode' => -1),
            'canalAgency' => array('id'=>$canalAgencyId, 'status' => '/', 'statusCode' => -1),
        );

        //构建查询条件
        $condition = sprintf("`deal_type` = '0' AND `deal_id` = '%d' ", $dealId);
        if(!empty($borrowUserId)){
            $borrowSql = sprintf(" `user_id` = '%d' ", $borrowUserId);
        }

        $agencyIdArr =  array();
        if(!empty($agencyId)){
            $agencyIdArr[] = $agencyId;
        }
        if(!empty($advisoryId)){
            $agencyIdArr[] = $advisoryId;
        }
        if(!empty($entrustAgencyId)){
            $agencyIdArr[] = $entrustAgencyId;
        }
        if(!empty($canalAgencyId)){
            $agencyIdArr[] = $canalAgencyId;
        }
        if(!empty($agencyIdArr)){
            $agencySql = sprintf("`agency_id` IN (%s)", implode(',', $agencyIdArr));
        }
        if(!empty($borrowSql) && !empty($agencySql)){
            $whereSql = sprintf("( %s  OR  %s)",$borrowSql,$agencySql);
        }else{
            $whereSql = !empty($borrowSql) ? $borrowSql : $agencySql;
        }
        $condition .=  !empty($whereSql) ? " AND " .$whereSql : '';

        //获取数据
        $rows = DealContractModel::instance()->findAll($condition,true,'user_id,agency_id,status',array());
        if(empty($rows)){
            return $result;
        }
        //设置statusCode
        foreach($rows as $row){
            // 如果是借款人，则只设置借款的方签署状态；
            if($borrowUserId == $row['user_id']){
                $result['borrowUser']['statusCode'] = $row['status'];
                continue;
            }
            // 各机构方的签署状态
            switch($row['agency_id']){
            case $agencyId :
                $result['agency']['statusCode'] = $row['status'];
                break;
            case $advisoryId:
                $result['advisory']['statusCode'] = $row['status'];
                break;
            case $entrustAgencyId:
                $result['entrustAgency']['statusCode'] = $row['status'];
                break;
            case $canalAgencyId:
                $result['canalAgency']['statusCode'] = $row['status'];
                break;
            }
        }
        //更改状态为文字
        foreach($result as $k => $v){
            switch($v['statusCode']){
            case 0:
                $result[$k]['status'] = '未签署';
                break;
            case 1:
                $result[$k]['status'] = '已签署';
                break;
            case 2:
                $result[$k]['status'] = '签署中';
                break;
            }
        }
        return $result;
    }

    /**
     * 自动代理签署合同
     */
    public function autoAgencySignContract(){
        //获取实时代签打开的开关
        $contractSignSwitchModel = new ContractSignSwitchModel();
        $contractSwitches = $contractSignSwitchModel->getOpenedSwitches();
        if(!empty($contractSwitches)){
            foreach ($contractSwitches as $switch){
                $this->autoSignViaType($switch['type'],$switch['adm_id']);
            }
        }
        return true;
    }

    /**
     * 通过实时代签合同类型签署合同
     * @param int $type
     * @return boolean
     */
    private function autoSignViaType($type, $adm_id){
        //获取委托并且未签署的合同
        $contracts = $this->getUnsignedContactByType($type);
        if(!empty($contracts)){
            foreach($contracts as $contract){
                //不是借款人合同is_agency = 0
                $is_agency = $contract['agency_id'] != 0? 1:0;
                $result = $this->signAll($contract['deal_id'],$contract['user_id'],$is_agency, $contract['agency_id'] ,$adm_id, true);
                if(!$result){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'type:'.$type, 'error:签署合同添加jobs失败',json_encode($contract))));
                    continue;
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'type:'.$type, 'error:签署合同添加jobs成功',json_encode($contract))));
            }
        }
        return true;
    }

    /**
     * 通过实时代签合同类型获取未签署的合同
     * @param int $type
     * @return array
     */
    public function getUnsignedContactByType($type) {
        $dealContractModel = new DealContractModel();
        $condition = '';
        if($type == ContractSignSwitchEnum::TYPE_BORROW){//借款人合同
            $condition = 'dp.entrust_sign = 1 and dc.user_id != 0 and dc.agency_id = 0 and dc.user_id = dp.user_id';
        }elseif($type == ContractSignSwitchEnum::TYPE_AGENCY){//担保方合同
            $condition = 'dp.entrust_agency_sign = 1 and dc.user_id = 0 and dc.agency_id != 0 and dc.agency_id = d.agency_id';
        }elseif($type == ContractSignSwitchEnum::TYPE_ADVISORY){//资产管理方合同
            $condition = 'dp.entrust_advisory_sign = 1 and dc.user_id = 0 and dc.agency_id != 0 and dc.agency_id = d.advisory_id';
        }

        return $dealContractModel->getUnSignedContractByCondition($condition);
    }

    /**
     * 将一键签署换成异步任务，前台点击处理更新deal_contract表并添加异步任务
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     * @param $admID 后台代理借款人签署合同的id
     * @return bool
     */
    public function signAll($deal_id, $user_id, $is_agency=0, $agency_id = 0, $admID = 0 ,$autoSign = false) {
        $deal_contract_model = new DealContractModel();
        try {
            $GLOBALS['db']->startTrans();
            if ($deal_contract_model->startSign($deal_id, $user_id, $is_agency, $agency_id, $admID) === false) {
                throw new \Exception("strat contract sign fail");
            }

            $dealService = new DealService();
            $deal_info = $dealService->getDeal($deal_id, true, false);

            if (is_numeric($deal_info['contract_tpl_type'])) {
                //获取用户role
                if($is_agency == 0){
                    if($user_id == $deal_info['user_id']){
                        $role = 1;
                    }else{
                        throw new \Exception("borrow user error!");
                    }
                }else{
                    if($agency_id == $deal_info['agency_id']){
                        $role = 2;
                    }elseif($agency_id == $deal_info['advisory_id']){
                        $role = 3;
                    }else{
                        throw new \Exception("agency error");
                    }
                }

                $contractService = new ContractNewService();
                $sign_info = $contractService->signAll($deal_id, $role, $user_id, $admID ,$autoSign);
            } else {
                throw new \Exception("deal contract tpl type is fail");
            }
            if (!$sign_info || $sign_info['status'] != 0) {
                throw new \Exception("contract sign add jobs fail");
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $user_id, $is_agency, $e->getMessage(), $e->getLine())));
            return false;
        }
        return true;
    }


    /**
     * 获取 智多新 债权转让协议编号
     * @param int $contractType
     * @param int $dtDealId
     * @param int $p2pDealId
     * @param int $redemptionUserId
     * @param int $dealLoadId
     * @return string
     */
    public function createDtDealNumber($contractType,$dtDealId,$p2pDealId,$redemptionUserId,$dealLoadId){
        //合同类型（(01:多投P2P借款合同，09) ,多投标的ID，p2p 标的ID,赎回用户ID，投资ID
        // 36位
        $number = str_pad($contractType,2,"0",STR_PAD_LEFT).str_pad($dtDealId,6,"0",STR_PAD_LEFT).str_pad($p2pDealId,10,"0",STR_PAD_LEFT).str_pad($redemptionUserId,8,"0",STR_PAD_LEFT).str_pad($dealLoadId,10,"0",STR_PAD_LEFT);
        return $number;
    }

    /**
     * 根据 智多新 债权转让协议编号获取相关信息
     * @param string $number
     * @return array()
     */
    public static function getInfoFromDtDealNumber($number){
        $ret = array();
        if(strlen($number) !=  ContractEnum::LENGTH_DT_DEAL_NUMBER){
            return $ret;
        }
        $ret['contractType'] = intval(substr($number,0,2));
        $ret['dtDealId'] = intval(substr($number,2,6));
        $ret['p2pDealId'] = intval(substr($number,8,10));
        $ret['redemptionLoanId'] = intval(substr($number,18,8));
        $ret['dtLoanId'] = intval(substr($number,26,10));
        return $ret;
    }


    /**
     * 根据顾问协议编号获取相关信息
     * @param string $number
     * @return array()
     */
    public static function getInfoFromDtConsultNumber($number){
        $ret = array();
        if(strlen($number) != ContractEnum::LENGTH_DT_CONSULT_NUMBER){
            return $ret;
        }
        $ret['dtDealId'] = intval(substr($number, 0, 10));
        $ret['type'] = intval(substr($number, 10, 2));
        $ret['contractType'] = intval(substr($number, 12, 2));
        $ret['userId'] = intval(substr($number, 14, 10));
        $ret['dtLoanId'] = intval(substr($number, 24, 10));
        return $ret;
    }

    /**
     * 根据p2p标的合同编号获取相关信息
     * @param string $number
     * @return array()
     */
    public static function getInfoFromP2pDealNumber($number){
        $ret = array();
        if(strlen($number) == ContractEnum::LENGTH_P2P_DEAL_NUMBER_1){
            $ret['dealId'] = intval(substr($number, 0, 6));
            $ret['tplIdentifierId'] = intval(substr($number, 8, 2));
            $ret['userId'] = intval(substr($number, 10, 8));
            $ret['loadId'] = intval(substr($number, 18, 10));
        }elseif(strlen($number) == ContractEnum::LENGTH_P2P_DEAL_NUMBER_2){
            $ret['dealId'] = intval(substr($number, 0, 8));
            $ret['tplIdentifierId'] = intval(substr($number, 10, 2));
            $ret['userId'] = intval(substr($number, 12, 8));
            $ret['loadId'] = intval(substr($number, 20, 10));
        }elseif(strlen($number) == ContractEnum::LENGTH_P2P_DEAL_NUMBER_3){
            $ret['dealId'] = intval(substr($number, 0, 10));
            $ret['tplIdentifierId'] = intval(substr($number, 12, 3));
            $ret['userId'] = intval(substr($number, 15, 8));
            $ret['loadId'] = intval(substr($number, 23, 8));
        }
        return $ret;
    }

    /**
     * 标的合同编号
     */
    public static function createDealNumber($dealId,$tplIdentifierId,$userId,$loadId){
        // number 最多支持36位
        $dealId = intval($dealId);
        //编号扩容切兼容模式
        if($dealId >= 100000000){
            // 33位
            return str_pad($dealId,10,"0",STR_PAD_LEFT).'01'.str_pad($tplIdentifierId,3,"0",STR_PAD_LEFT).str_pad($userId,8,"0",STR_PAD_LEFT).str_pad($loadId,10,"0",STR_PAD_LEFT);
        }elseif(($dealId < 100000000) && ($dealId >= 1000000)){
            // 30位
            return str_pad($dealId,8,"0",STR_PAD_LEFT).'01'.str_pad($tplIdentifierId,2,"0",STR_PAD_LEFT).str_pad($userId,8,"0",STR_PAD_LEFT).str_pad($loadId,10,"0",STR_PAD_LEFT);
        }else{
            // 28位
            return str_pad($dealId,6,"0",STR_PAD_LEFT).'01'.str_pad($tplIdentifierId,2,"0",STR_PAD_LEFT).str_pad($userId,8,"0",STR_PAD_LEFT).str_pad($loadId,10,"0",STR_PAD_LEFT);
        }
    }

    /***
     * 签署时间戳，更新contract表状态
     * @param dealId, 标的ID
     * @param number, 合同编号
     * @param type
     */
    public function doSignTsaCallback($dealId,$number,$type=0,$projectId = 0){
        $logParams = "dealId:{$dealId},number:{$number},type:{$type},projectId:{$projectId} ";
        Logger::info(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,' 打戳回调合同服务 开始 ',$logParams)));
        // 智多新合同打戳回调
        if($type == ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT || $type == ContractServiceEnum::SOURCE_TYPE_RESERVATION){
            $response = self::signTsaCallback(intval($dealId), trim($number),intval($projectId),0,$type);
            if($response != true){
                throw new \Exception("智多新合同打戳回调失败 "."code: ".$response->errCode." msg:".$response->errMsg);
            }
            return true;
        }
        // 标的合同打戳回调
        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($dealId);
        if(!is_numeric($dealInfo['contract_tpl_type']) || ($type !=0)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail',$logParams)));
            return false;
        }
        if(is_numeric($dealInfo['contract_tpl_type'])){
            $response = self::signTsaCallback(intval($dealId), trim($number),intval($projectId),intval($type),ContractServiceEnum::SOURCE_TYPE_PH);
            if($response != true){
                throw new \Exception("网贷合同打戳回调失败 "."code: ".$response->errCode." msg:".$response->errMsg);
            }
            // 用于检查哪些标的没有成功打完戳
            // 打戳成功后，删除redis中的键值对
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis !== NULL){
                $redis->hDel('tsa_deal_ph_'.date('Y-m-d'),$dealId);
            }
        }
        return true;
    }


    /**
     * 智多新 合同编号 落库和查询用
     * @param integer  $loanId   智多新用户投资记录id loanId 受托用户
     * @param integer  $uniqueId
     * @return string 合同编号
     */
    public static function createDtNumber($loanId, $uniqueId)
    {
        // number 最多支持36位
        // 33位
        return  str_pad($loanId, 11, "0", STR_PAD_LEFT) . str_pad($uniqueId, 22, "0", STR_PAD_LEFT);
    }

    /**
     * 智多新 根据合同编号获取相应参数
     * @param $number
     * @return array ret
     *          integer  $loanId   智多新用户投资记录id loanId 受托用户
     *          integer  $uniqueId
     *          integer  $duotouLoanMappingContractId   duotouLoanMappingContract 主键id
     */
    public static function getInfoFromDtNumber($number)
    {
        $ret = array();
        if(strlen($number) != ContractEnum::LENGTH_P2P_DEAL_NUMBER_3){
            return $ret;
        }
        $ret['loanId'] = intval(substr($number, 0, 11));
        $ret['uniqueId'] = substr($number, 11, 22);
        $ret['duotouLoanMappingContractId'] = substr($number,-20);
        return $ret;
    }

    /**
     * 获取合同附件
     * @author <fanjingwen@ucfgroup.com>
     * @param  array $deal 标的信息[对应表字段]
     * @return array       [description]
     */
    public function getContractAttachmentByDealLoad($deal){
        if (empty($deal)) {
            return array();
        }
        $response_attachment = TplService::getContractAttachmentByDealId(intval($deal['id']),ContractServiceEnum::SOURCE_TYPE_PH);
        $cont_list = array();
        if ($response_attachment['status']) {
            $cont_json = $response_attachment['jsonData'];
            $cont_list = json_decode($cont_json, true);
        }
        return $cont_list;
    }

    /**
     * 根据投标ID获取合同信息
     *
     * @param int $load_id
     * @param DealModel $deal
     * @param int $user_id (可选)
     * @return array
     */
    public function getContractByDealLoad($load_id, $deal = false, $user_id = 0) {
        if (empty($load_id)) {
            return false;
        }
        if (empty($deal)) {
            return array();
        }
        $response = ContractService::getContractByLoadId(intval($deal['id']),intval($load_id),intval($user_id),ContractServiceEnum::SOURCE_TYPE_PH);
        if(empty($response)){
            return array();
        }
        return $response;
    }

}
