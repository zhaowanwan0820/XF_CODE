<?php

namespace core\service\contract;

use libs\utils\Logger;
use libs\utils\Monitor;
use libs\db\Db;

use core\dao\jobs\JobsModel;
use core\dao\contract\DealContractModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\dao\project\DealProjectModel;

use core\service\BaseService;
use core\service\deal\DealService;
use core\service\contract\CategoryService;
use core\service\contract\ContractBeforeBorrowService;
use core\service\contract\ContractService;
use core\service\contract\ContractNewService;

use core\enum\JobsEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractCategoryEnum;
use core\enum\contract\ContractTplIdentifierEnum;

class SendContractService extends BaseService {

    private static $funcMap = array(
        /**
         * 生成合同记录
         * @param array requestData
         *               advisoryAgencyId
         *               guaranteeAgencyId
         *               borrowUserId
         *               dealId
         *               projectId
         *               dealLoadId
         *               type
         *               lenderUserId
         *               entrustAgencyId
         *               canalAgencyId
         *               isZX
         *               isFull
         *               sourceType
         *               createTime
         *               autoSign
         * @return array
         */
        'send' => array('requestData'),
        /**
         * 生成项目合同记录
         * 专享相关，暂时注释掉
         * @param array requestData
         *              borrowUserId
         *              lenderUserId
         *              guaranteeAgencyId
         *              advisoryAgencyId
         *              entrustAgencyId
         *              canalAgencyId
         *              projectId
         *              sourceType
         *              createTime
         *              dealId
         *              autoSign
         * @return array
         */
 //       'projectSend' => array('requestData'),
        /**
         * 将前置合同记录(临时表)转移到正式合同表中
         * @param array requestData
         *              borrowUserId
         *              tmpContractId
         *              dealId
         *              type
         *              sourceType
         *              createTime
         *              autoSign
         * @return array
         */
        'transferBeforeBorrowContract' => array('requestData'),
        /**
         * 生成智多新(根据时间区分合同分类类型)合同记录
         * @param array requestData
         *           int dealId              实际上存的是智多新用户投资记录id loanId 受托用户
         *           int borrowUserId        实际上存的是智多新用户 转让用户
         *           int projectId           实际上是智多新项目id
         *           int dealLoadId          实际上存的是智多新用户投资记录id redemptionLoanId 转让用户
         *           int type                1:智多鑫(contract_category_deal_record中的type)
         *           int lenderUserId        实际上存的是智多新用户 受托用户
         *           int sourceType          101:智多新
         *           int createTime          用户加入智多鑫的时间或者转让发生时间
         *        string tplPrefix           合同标识tag 用于区分是签署哪份合同
         * @return array result
         *            int errorCode
         *            int errorMsg
         *          array data
         */
        'sendDtContract' => array('requestData'),
   );

    private static $defaultFunc = array(
    );

    private static $defaultParamValue = array(
        'type' =>  ContractServiceEnum::TYPE_P2P,
        'sourceType' => ContractServiceEnum::SOURCE_TYPE_PH,
        'isDelete' => ContractCategoryEnum::CATEGORY_IS_DLETE_NO,
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
        return self::rpc('contract', 'sendContract/'.$name, $args, false, 5);
    }

    /**
     * 检测标的是否生成deal Contract记录,并对委托自动签署并漏签的合同进行补签
     * @param integer $dealId 标的ID
     * @return boolean $status 是否成功
     */
    public function fullCheck($dealId, $createTime = 0){
        $createTime = empty($createTime) ? time() : $createTime;
        $logParams = "dealId:{$dealId},,isFull: true,createTime:{$createTime}";
        //代签相关
        $deal = (new DealService())->getDealInfo($dealId);
        $project = DealProjectModel::instance()->find($deal['project_id']);
        $dealContractModel = new DealContractModel();
        if(empty($deal) || empty($project)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因: deal or project is null!')));
            throw new \Exception('Send Contract full check: deal project is null!');
        }
        //借款人代签标记
        $autoSign = 0;
        if($project['entrust_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;
            $tmpContractResponse = ContractBeforeBorrowService::getContractByApproveNumber($deal['approve_number']);
            if(!empty($tmpContractResponse) && ($tmpContractResponse['borrowerSignTime'] <= 0)){
                Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(sprintf('临时合同记录未签署，参数：%s，file：%s, line:%s | %s', json_encode($logParams), __FILE__, __LINE__,'CS_SEND_CONTRACT_FAIL'));
                throw new \Exception(sprintf('临时合同记录未签署，参数：%s，file：%s, line:%s', json_encode($logParams), __FILE__, __LINE__));
            }
        }
        //如果未签署，则补签，并且将前置合同也补发
        if($dealContractModel->getDealContractSignInfo(intval($dealId)) == 0){
            $db = Db::getInstance('firstp2p');
            try{
                $db->startTrans();
                // 借款人代签署，则检查是否存在临时合同记录，
                // 如有并且签署，则转移到正式合同中
                if($project['entrust_sign'] &&  !empty($tmpContractResponse)){
                     // 转移临时合同数据到正式合同
                     $jobsModel = new JobsModel();
                     $function = "\core\service\contract\SendContractService::transferBeforeBorrowContractJob";
                     $params = array(
                         'dealId' => $dealId,
                         'createTime' => $createTime,
                     );
                     $jobsModel->priority = JobsEnum::PRIORITY_CONTRACT;
                     if($jobsModel->addJob($function, $params)) {
                         Logger::info(sprintf('转移临时合同数据到正式合同jobs添加成功，参数：%s，file：%s, line:%s', $logParams, __FILE__, __LINE__));
                     }else{
                         Logger::error(sprintf('转移临时合同数据到正式合同jobs添加失败，参数：%s，file：%s, line:%s | %s', $logParams , __FILE__, __LINE__,'CS_SEND_CONTRACT_FAIL'));
                         Monitor::add('CS_SEND_CONTRACT_FAIL');
                         throw new \Exception('转移临时合同数据到正式合同jobs添加失败');
                     }
                }
                $dealContractModel = new DealContractModel();
                if(!$dealContractModel->createNew($deal)){
                    Monitor::add('CS_SEND_CONTRACT_FAIL');
                    Logger::error(sprintf('dealContractModel-createNew-fail，参数：%s，file：%s, line:%s | %s', $dealId, __FILE__, __LINE__,'CS_SEND_CONTRACT_FAIL'));
                    throw new \Exception(sprintf('dealContractModel-createNew-fail，参数：%s，file：%s, line:%s', $dealId, __FILE__, __LINE__));
                }
                $db->commit();
            }catch(\Exception $e){
                $db->rollback();
                Logger::error(sprintf('dealContractModel-createNew-fail，参数：%s，file：%s, line:%s errMsg:%s | %s',$dealId, __FILE__, __LINE__,$e->getMessage(),'CS_SEND_CONTRACT_FAIL'));
                throw $e;
            }
        }
        // 因为entrust_sign、entrust_agency_sign和entrust_advisory_sign都为1，所以就不会走满标后的签署逻辑，就不会生成发送合同邮件和站内信
        // 所以在此满标之后，生成好所有签署成功的合同之后，做一个补充发送合同邮件和站内信。
        $signInfo = (new ContractNewService())->getContSignNum($dealId,0,0); //是否全部签署
        if($project['entrust_agency_sign']==1 && $project['entrust_advisory_sign']==1
            && $project['entrust_sign']==1 && $signInfo['is_sign_all'] == true){
            $jb = new JobsModel();
            $func = '\core\service\contract\ContractNewService::sendMsg';
            $params = array('dealId' => $dealId );
            $jb->priority = JobsEnum::SEND_CONTRACT_MSG;
            $res = $jb->addJob($func,$params,false,1);
            if(!$res){
                throw new \Exception('add insert jobs fail');
            }
        }
       return true;
    }

    /**
     * @param integer $dealId 标的ID
     * @param integer $loadId 投资ID
     * @param boolean $isFull 是否满标
     * @param int $createTime 生成合同时间
     * @return boolean $status 是否成功
     */
    public function sendContract($dealId,$loadId,$isFull=false,$createTime = 0){
        $logParams = "dealId:{$dealId},loadId:{$loadId},isFull:{$isFull},createTime:{$createTime}";
        $dealService = new DealService();
        $deal = $dealService->getDealInfo($dealId);
        $dealInfo = $deal->getRow();
        $loanInfo = DealLoadModel::instance()->find($loadId, 'user_id');;
        if(empty($dealInfo)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因:deal is null!')));
            throw new \Exception('Send Contract:deal is null!');
        }
        //代签相关
        $project = DealProjectModel::instance()->find($deal['project_id']);
        $autoSign = 0;
        if(empty($project)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因:Project is null!')));
            throw new \Exception('Send Contract:Project is null!');
        }

        //借款人代签标记
        if($project['entrust_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;
            if($isFull === true){
                $tmpContractResponse = ContractBeforeBorrowService::getContractByApproveNumber($dealInfo['approve_number']);
                if(!empty($tmpContractResponse) && ($tmpContractResponse['borrowerSignTime'] <= 0)){
                    Monitor::add('CS_SEND_CONTRACT_FAIL');
                    Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因:临时合同记录未签署')));
                    throw new \Exception(sprintf('临时合同记录未签署，参数：%s，file：%s, line:%s', json_encode($logParams), __FILE__, __LINE__));
                }
            }
        }
        //担保机构代签标记
        if($project['entrust_agency_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_AGENCY;
        }
        //资产端代签标记
        if($project['entrust_advisory_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_ADVISORY;
        }
        $isDealZX = false;

        $categoryResponse = CategoryService::getDealCId(intval($dealId),ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
        if(empty($categoryResponse)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因:调用getDealCId接口失败')));
            throw new \Exception('RPC contract_service CategoryService getDealCId is fail! errmsg:' . CategoryService::getErrorMsg());
        }
        $createTime = $createTime == 0?time():$createTime;

        $sendRequest = array();
        $sendRequest['dealId'] = intval($dealId);
        $sendRequest['projectId'] = intval($deal['project_id']);
        $sendRequest['advisoryAgencyId'] = intval($deal['advisory_id']);
        $sendRequest['guaranteeAgencyId'] = intval($deal['agency_id']);
        $sendRequest['entrustAgencyId'] = intval($deal['entrust_agency_id']);
        $sendRequest['canalAgencyId'] = intval($deal['canal_agency_id']);
        $sendRequest['borrowUserId'] = intval($deal['user_id']);
        $sendRequest['dealLoadId'] = intval($loadId);
        $sendRequest['isZX'] = $isDealZX;
        $sendRequest['type'] = ContractServiceEnum::TYPE_P2P;
        $sendRequest['sourceType'] = $deal['deal_type'];
        $sendRequest['isFull'] = $isFull;
        $sendRequest['lenderUserId'] = intval($loanInfo['user_id']);
        $sendRequest['sourceType'] = ContractServiceEnum::SOURCE_TYPE_PH;
        $sendRequest['createTime'] = $createTime;
        $sendRequest['autoSign'] = $autoSign;

        $sendResponse = self::send($sendRequest);

        //是否签署成功
        if(empty($sendResponse)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因:调用生成合同接口失败')));
            throw new \Exception(sprintf('生成合同失败 ，参数：%s，file：%s, line:%s', $logParams, __FILE__, __LINE__));
        }

        //是否满标
        if($isFull){
            $db = Db::getInstance('firstp2p');
            try{
                $db->startTrans();
                // 借款人代签署，则检查是否存在临时合同记录，
                // 如有并且签署，则转移到正式合同中
                if($project['entrust_sign'] &&  !empty($tmpContractResponse)){
                     // 转移临时合同数据到正式合同
                     $jobsModel = new JobsModel();
                     $function = "\core\service\contract\SendContractService::transferBeforeBorrowContractJob";
                     $params = array(
                         'dealId' => $dealId,
                         'createTime' => $createTime,
                     );
                     $jobsModel->priority = JobsEnum::PRIORITY_CONTRACT;
                     if($jobsModel->addJob($function, $params)) {
                         Logger::info(sprintf('转移临时合同数据到正式合同jobs添加成功，参数：%s，file：%s, line:%s', $logParams, __FILE__, __LINE__));
                     }else{
                         Logger::error(sprintf('转移临时合同数据到正式合同jobs添加失败，参数：%s，file：%s, line:%s', $logParams , __FILE__, __LINE__));
                         Monitor::add('CS_SEND_CONTRACT_FAIL');
                         throw new \Exception('转移临时合同数据到正式合同jobs添加失败');
                     }
                }
                $dealContractModel = new DealContractModel();
                if(!$dealContractModel->createNew($deal)){
                    Monitor::add('CS_SEND_CONTRACT_FAIL');
                    Logger::error(sprintf('dealContractModel-createNew-fail，参数：%s，file：%s, line:%s', $dealId, __FILE__, __LINE__));
                    throw new \Exception(sprintf('dealContractModel-createNew-fail，参数：%s，file：%s, line:%s', $dealId, __FILE__, __LINE__));
                }
                $db->commit();
            }catch(\Exception $e){
                $db->rollback();
                Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'失败原因:'.$e->getMessage())));
                return false;
            }
        }
        Monitor::add('CS_SEND_CONTRACT_SUCCESS');
        return true;
    }

    /**
     * @param integer $dealId 标的ID
     * @param integer $createTime
     */
    public function transferBeforeBorrowContractJob($dealId,$createTime){
        $dealService = new DealService();
        $deal = $dealService->getDealInfo($dealId);
        $dealInfo = $deal->getRow();
        $logs = 'dealId:' . $dealId . ' approve_number:' .  $dealInfo['approve_number'] . ' create_time:' . $createTime ;
        Logger::info(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,$logs)));

        $project = DealProjectModel::instance()->find($dealInfo['project_id']);
        if(empty($deal) || empty($project)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,'deal or project is null!')));
            throw new \Exception('Send Contract transferBeforeBorrowContractJob : deal or project is null!');
        }
        //代签相关
        $autoSign = 0;
        //借款人代签标记
        if($project['entrust_sign'] == 0){
            return true;
        }
        $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;

        //如果借款人代签，则获取临时合同记录
        $tmpContractResponse = ContractBeforeBorrowService::getContractByApproveNumber($dealInfo['approve_number']);
        if(empty($tmpContractResponse)){
            throw new \Exception('临时合同记录不存在 errMsg:'. ContractBeforeBorrowService::getErrorMsg());
        }
        if($tmpContractResponse['borrowerSignTime'] <= 0){
            throw new \Exception('临时合同记录未签署');
        }

        //合同模板分类
        $categoryResponse = CategoryService::getDealCId(intval($dealId),ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
        if(empty($categoryResponse)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,'CategoryService getDealCId is fail')));
            throw new \Exception('RPC contract_service CategoryService getDealCId is fail! errmsg:' . CategoryService::getErrorMsg());
        }


        $createTime = empty($createTime) ? time() : $createTime;
        $sendRequest = array();
        $sendRequest['tmpContractId'] = intval($tmpContractResponse['id']);
        $sendRequest['dealId'] = intval($dealId);
        $sendRequest['type'] = ContractServiceEnum::TYPE_P2P;
        $sendRequest['sourceType'] = ContractServiceEnum::SOURCE_TYPE_PH;
        $sendRequest['createTime'] = $createTime;
        $sendRequest['autoSign'] = $autoSign;
        $sendRequest['borrowUserId'] = intval($deal['user_id']);

        $sendResponse = self::transferBeforeBorrowContract($sendRequest);
        //如果接口请求超时,抛出异常
        if(empty($sendResponse)){
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,'transferBeforeBorrowContract failed')));
            throw new \Exception(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail',$logs)));
        }
        Logger::info(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'success',$logs)));
        return true;
    }

    /**
     * 生成智多新(根据时间区分合同分类类型)合同记录
     * @param array requestData          智多新                                                    |   随心约
     *           int dealId              实际上存的是智多新用户投资记录id loanId 受托用户           |   预约id
     *           int borrowUserId        实际上存的是智多新用户 受托用户                            |   0
     *           int projectId           实际上是智多新项目id                                       |   ContractServiceEnum::RESERVATION_PROJECT_ID
     *           int dealLoadId          实际上存的是智多新用户投资记录id redemptionLoanId 转让用户 |   0
     *           int type                1:智多鑫(contract_category_deal_record中的type)            |   ContractServiceEnum::TYPE_RESERVATION
     *           int lenderUserId        实际上存的是智多新用户 转让用户                            |   userId
     *           int sourceType          101:智多新                                                 |   ContractServiceEnum::SOURCE_TYPE_RESERVATION
     *           int createTime          用户加入智多鑫的时间或者转让发生时间                       |   预约时间
     *        string tplPrefix           合同标识tag 用于区分是签署哪份合同                         |   ContractTplIdentifierEnum::TPL_RESERVATION_CONTRACT
     *        string uniqueId            智多新 分表编号 + duotou_loan_mapping_contract_x的主键id   |   0
     *                                   (tplPrefix 投资顾问：ContractTplIdentifierEnum::DTB_CONT
     *                                    债权转让：ContractTplIdentifierEnum::DTB_TRANSFER )
     * @return array result
     * @throws \Exception
     */
    public function sendDtContractJob($requestData){
        $logParams = json_encode($requestData);
        if(empty($requestData['dealId'])){
            throw new \Exception("生成合同记录失败 dealId为null");
        }
        $response = self::sendDtContract($requestData);
        // 获取合同id
        $contractId = 0;
        if(!$response){
            $number = ContractService::createDtNumber($requestData['dealId'],$requestData['uniqueId']);
            $contract = ContractService::getContractByNumber($requestData['dealId'],$number,$requestData['sourceType']);
            if(empty($contract)){
                Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'sendDtContract 生成合同记录失败')));
                throw new \Exception("生成合同记录失败");
            }
            if($contract[0]['user_id'] != $requestData['lenderUserId']){
                Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,'getContractByNumber 生成合同记录有问题')));
                throw new \Exception("生成合同记录有问题");
            }
            $contractId = $contract[0]['id'];
        }else{
            $contractId = $response['result'];
        }

        if(empty($contractId)){
            throw new \Exception("生成合同记录id");
        }

        // 打戳
        $jobsModel = new JobsModel();
        $function = "\core\service\contract\ContractSignerService::signOneContractByServiceId";
        $params = array(
            'contract_id' => $contractId,
            'deal_id' => $requestData['dealId'],
            'service_type' => $requestData['sourceType'],
        );
        $jobsModel->priority = JobsEnum::CONTRACT_JOBS_TSA_DT;
        $logsType = $requestData['sourceType'] == ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT ? '智多新' : '随心约';
        // 考虑到主从延迟，延时1分钟之后再执行
        if($jobsModel->addJob($function, $params, get_gmtime()+60)) {
            Logger::info(sprintf($logsType.'合同打戳jobs添加成功，参数：%s，file：%s, line:%s', $logParams, __FILE__, __LINE__));
        }else{
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,$logsType.'合同打戳jobs添加失败')));
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            throw new \Exception($logsType.'合同打戳jobs添加失败');
        }
        return true;
    }

}
