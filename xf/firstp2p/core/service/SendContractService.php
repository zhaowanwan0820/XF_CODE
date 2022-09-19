<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/11/18
 * Time: 上午21:30
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\JobsModel;

use core\service\DealService;
use core\service\ContractNewService;
use core\service\UserService;
use core\service\DealLoadService;
use core\service\DealLoanTypeService;
use core\service\contract\ContractUtilsService;
use core\dao\DealContractModel;
use core\dao\DealLoadModel;
use core\dao\DealProjectModel;
use NCFGroup\Protos\Contract\RequestGetDealCId;
use NCFGroup\Protos\Contract\RequestSendContract;
use NCFGroup\Protos\Contract\RequestSendProjectContract;
use NCFGroup\Protos\Contract\RequestGetContractByApproveNumber;
use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;
use NCFGroup\Common\Library\ApiService;

//use libs\utils\PhalconRPCInject;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\utils\Rpc;
use libs\utils\Logger;
use libs\utils\Monitor;
use core\service\GoldService;
/**
 * 合同服务 SendContractService
 */
class SendContractService extends BaseService {

    /**
     * @param integer $dealId 标的ID
     * @param integer $loadId 投资ID
     * @param boolean $isFull 是否满标
     * @return boolean $status 是否成功
     */
    public function send($dealId,$loadId,$isFull=false,$createTime = 0){
        $logsParam = "dealId:{$dealId},loadId:{$loadId},isFull:{$isFull},createTime:{$createTime}";
        $rpc = new Rpc('contractRpc');
        $dealService = new DealService();
        $dealLoadService = new DealLoadService();
        $loanInfo = DealLoadModel::instance()->find($loadId, 'user_id');
        $deal = $dealService->getDeal($dealId);
        $dealInfo = $deal->getRow();
        if(empty($dealInfo)){
            return true;
        }
        //代签相关
        $project = DealProjectModel::instance()->find($deal['project_id']);
        if(empty($dealInfo)){
            return true;
        }
        $autoSign = 0;
        if(empty($project)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'失败原因:Project is null!')));
            throw new \Exception('Send Contract:Project is null!');
        }

        //借款人代签标记
        if($project['entrust_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;
        }

        //担保机构代签标记
        if($project['entrust_agency_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_AGENCY;
        }

        //资产端代签标记
        if($project['entrust_advisory_sign']){
            $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_ADVISORY;
        }

        $isDealZX = $dealService->isDealEx($deal['deal_type']);

        $contractRequest = new RequestGetDealCId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(0);
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);
        if($response && ($response->errorCode != 0)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'getDealCid','RPC contract_service is fail!')));
            throw new \Exception('RPC contract_service is fail!');
        }else{
            if(count($response->data) === 0){
                \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'getDealCid','RPC response Data is null!')));
                throw new \Exception('RPC response Data is null!');
            }
        }

        $createTime = $createTime == 0?time():$createTime;
        $sendRequest = new RequestSendContract();
        $sendRequest->setDealId(intval($dealId));
        $sendRequest->setProjectId(intval($deal['project_id']));
        $sendRequest->setAdvisoryAgencyId(intval($deal['advisory_id']));
        $sendRequest->setGuaranteeAgencyId(intval($deal['agency_id']));
        $sendRequest->setEntrustAgencyId(intval($deal['entrust_agency_id']));
        $sendRequest->setCanalAgencyId(intval($deal['canal_agency_id']));
        $sendRequest->setBorrowUserId(intval($deal['user_id']));
        $sendRequest->setDealLoadId(intval($loadId));
        $sendRequest->setIsZX($isDealZX);
        $sendRequest->setDealType(0);
        $sendRequest->setSourceType($deal['deal_type']);
        $sendRequest->setIsFull($isFull);
        $sendRequest->setLenderUserId(intval($loanInfo['user_id']));
        $sendRequest->setSourceType($deal['deal_type']);
        $sendRequest->setCreateTime($createTime);
        $sendRequest->setAutoSign($autoSign);
        //重试次数3次，超时时间5秒
        $sendResponse = $rpc->go("\NCFGroup\Contract\Services\SendContract","send",$sendRequest,3,5);
        //如果接口请求超时,抛出异常
        if(empty($sendResponse)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'send','RPC超时')));
            return false;
        }
        if($sendResponse->errorCode > 0){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'send','RPC调用结果错误码不为0')));
            return false;
        }else{
            if($isFull){
                // 借款人代签署，则检查是否存在临时合同记录，
                // 如有并且签署，则转移到正式合同中
                if($project['entrust_sign']){
                    $tmpContractRequest = new RequestGetContractByApproveNumber();
                    $tmpContractRequest->setApproveNumber($dealInfo['approve_number']);
                    $tmpContractResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\ContractBeforeBorrow", "getContractByApproveNumber", $tmpContractRequest);
                    if ($tmpContractResponse['errCode'] == 0 ) {
                        if($tmpContractResponse['data']['borrowerSignTime'] <= 0){
                            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'临时合同记录未签署')));
                            return false;
                        }
                        // 转移临时合同数据到正式合同
                        $jobsModel = new JobsModel();
                        $function = "\core\service\SendContractService::transferBeforeBorrowContract";
                        $params = array(
                            'dealId' => $dealId,
                            'createTime' => time(),
                        );
                        $jobsModel->priority = JobsModel::PRIORITY_CONTRACT;
                        if($jobsModel->addJob($function, $params)) {
                            Logger::info(sprintf('转移临时合同数据到正式合同任务添加成功，参数：%s，file：%s, line:%s', json_encode($params), __FILE__, __LINE__));
                        }else{
                            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'转移临时合同数据到正式合同任务添加失败',$params)));
                            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                            return false;
                        }
                    }
                }

                $dealContractModel = new DealContractModel();
                if(!$dealContractModel->createNew($deal)){
                    \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                    Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logsParam,'dealContractModel-createNew-fai')));
                    return false;
                }

            }
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_SUCCESS');
            return true;
        }
        return false;
    }

    /**
     * @param integer $dealId 标的ID
     * @param integer $createTime
     */
    public function transferBeforeBorrowContract($dealId, $createTime){
        $rpc = new Rpc('contractRpc');
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        $dealInfo = $deal->getRow();
        $logs = 'dealId:' . $dealId . ' approve_number:' .  $dealInfo['approve_number'];
        Logger::info(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,$logs)));
        if(empty($dealInfo)){
            return true;
        }
        //代签相关
        $project = DealProjectModel::instance()->find($deal['project_id']);
        if(empty($dealInfo)){
            return true;
        }
        $autoSign = 0;
        if(empty($project)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,'失败原因:Project is null!')));
            throw new \Exception('Send Contract:Project is null!');
        }

        //借款人代签标记
        if($project['entrust_sign'] == 0){
            return true;
        }
        $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;

        //如果借款人代签，则获取临时合同记录
        $tmpContractRequest = new RequestGetContractByApproveNumber();
        $tmpContractRequest->setApproveNumber($dealInfo['approve_number']);
        $tmpContractResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\ContractBeforeBorrow", "getContractByApproveNumber", $tmpContractRequest);
        if ($tmpContractResponse['errCode'] != 0 ) {
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'参数:'.$logs,'临时合同记录不存在 errMsg:'. $tmpContractResponse['errMsg'])));
            throw new \Exception('临时合同记录不存在 errMsg:'. $tmpContractResponse['errMsg']);
        }
        if($tmpContractResponse['data']['borrowerSignTime'] <= 0){
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'参数:'.$logs,'临时合同记录未签署')));
            throw new \Exception('临时合同记录未签署');
        }

        //合同模板分类
        $isDealZX = $dealService->isDealEx($deal['deal_type']);
        $contractRequest = new RequestGetDealCId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(0);
        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);
        if($response && ($response->errorCode != 0)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"getDealCid",'RPC调用结果错误码不为0')));
            throw new \Exception('RPC contract_service is fail!');
        }else{
            if(count($response->data) === 0){
                \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"getDealCid",'RPC response Data is null!')));
                throw new \Exception('RPC response Data is null!');
            }
        }

        $sendRequest = new RequestSendContract();
        $sendRequest->setDealId(intval($dealId));
        $sendRequest->setTmpContractId(intval($tmpContractResponse['data']['id']));
        $sendRequest->setProjectId(intval($deal['project_id']));
        $sendRequest->setAdvisoryAgencyId(intval($deal['advisory_id']));
        $sendRequest->setGuaranteeAgencyId(intval($deal['agency_id']));
        $sendRequest->setEntrustAgencyId(intval($deal['entrust_agency_id']));
        $sendRequest->setCanalAgencyId(intval($deal['canal_agency_id']));
        $sendRequest->setBorrowUserId(intval($deal['user_id']));
        $sendRequest->setDealLoadId(intval(0));
        $sendRequest->setIsZX($isDealZX);
        $sendRequest->setDealType(0);
        $sendRequest->setSourceType($deal['deal_type']);
        $sendRequest->setIsFull(true);
        $sendRequest->setLenderUserId(intval(0));
        $sendRequest->setCreateTime($createTime);
        $sendRequest->setSourceType($deal['deal_type']);
        $sendRequest->setAutoSign($autoSign);
        //重试次数3次，超时时间5秒
        $sendResponse = $rpc->go("\NCFGroup\Contract\Services\SendContract","transferBeforeBorrowContract",$sendRequest,3,5);
        //如果接口请求超时,抛出异常
        if(empty($sendResponse)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"transferBeforeBorrowContract",'RPC请求超时')));
            return false;
        }
        if($sendResponse->errorCode > 0){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"transferBeforeBorrowContract",'RPC调用结果错误码不为0')));
            return false;
        }

        Logger::info(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'success',$logs)));
        return true;
    }

    /**
     * 检测标的是否生成deal Contract记录,并对委托自动签署并漏签的合同进行补签
     * @param integer $dealId 标的ID
     * @return boolean $status 是否成功
     */
    public function fullCheck($dealId){
        $dealContractModel = new DealContractModel();
        $deal = DealModel::instance()->find($dealId);
        if(empty($deal)){
            throw new \Exception('标的不存在 dealId:'.$dealId);
        }
        if($dealContractModel->getDealContractSignInfo(intval($dealId)) == 0){
            if(!$dealContractModel->createNew($deal)){
                throw new \Exception('RPC response Data is null!');
            }
        }
        $project = DealProjectModel::instance()->find($deal['project_id']);
        if(empty($project)){
            throw new \Exception('项目不存在 projectId:'.$deal['project_id']);
        }
        // 因为entrust_sign、entrust_agency_sign和entrust_advisory_sign都为1，所以就不会走满标后的签署逻辑，就不会生成发送合同邮件和站内信
        // 所以在此满标之后，生成好所有签署成功的合通之后，做一个补充发送合同邮件和站内信。
        $signInfo = (new ContractNewService())->getContSignNum($dealId,0,0); //是否全部签署
        if($project['entrust_advisory_sign']==1 && $project['entrust_agency_sign']==1
            && $project['entrust_sign']==1 && $signInfo['is_sign_all'] == true){
                $jb = new JobsModel();
                $func = '\core\service\ContractNewService::sendMsg';
                $params = array('dealId' => $dealId );
                $jb->priority = JobsModel::SEND_CONTRACT_MSG;
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
     * @return boolean $status 是否成功
     */
    public function sendGoldConstract($borrowId,$dealId,$userId,$loadId,$isFull=false,$createTime = 0){
        $logs = "borrowId:{$borrowId},dealId:{$dealId},userId:{$userId},loadId:{$loadId},isFull:{$isFull},createTime:{$createTime}";
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetDealCId();
        $contractRequest->setDealId(intval($dealId));
        $contractRequest->setType(2);
        $contractRequest->setSourceType(100);
        $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);
        if($response && ($response->errorCode != 0)){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"getDealCid",'RPC调用结果错误码不为0')));
            throw new \Exception('RPC contract_service is fail!');
        }else{
            if(count($response->data) === 0){
                \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"getDealCid",'RPC response Data is null!')));
                throw new \Exception('RPC response Data is null!');
            }
        }
        $contractService = new ContractNewService();

        $createTime = $createTime == 0?time():$createTime;
        $sendRequest = new RequestSendContract();
        $sendRequest->setDealId(intval($dealId));;
        $sendRequest->setBorrowUserId(intval($borrowId));
        $sendRequest->setDealLoadId(intval($loadId));
        $sendRequest->setDealType(2);//goldtype
        $sendRequest->setSourceType(100);
        $sendRequest->setIsFull($isFull);
        $sendRequest->setLenderUserId(intval($userId));
        $sendRequest->setCreateTime($createTime);
        $sendResponse = $rpc->go("\NCFGroup\Contract\Services\SendContract","send",$sendRequest);
        if($sendResponse->errorCode > 0){
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
            Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"send",'RPC调用结果错误码不为0')));
            return false;
        }else{
            if($isFull){
                $goldService = new GoldService();
                if($goldService->createNew($dealId)){
                    \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                    Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"goldService createNew 失败")));
                    return false;
                }
                if(!$contractService->signGoldDealContNew($dealId,1,0,0,true)){
                    Logger::error(implode('| ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"goldService signGoldDealContNew 代签失败")));
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                }
            }
            \libs\utils\Monitor::add('CS_SEND_CONTRACT_SUCCESS');
            return true;
       }
    }
    /**
     * 生成项目合同
     * @param integer $project_id
     * @return boolean $status 是否成功
     */
    public function sendProjectContract($projectId)
    {
        $logs = "projectId:{$projectId}";
        try {
            $projectId = intval($projectId);
            $dealService = new DealService();
            $contractService = new ContractNewService();
            $userService = new UserService();
            $dealModel = new DealModel();
            $project = DealProjectModel::instance()->find($projectId);

            $autoSign = 0;

            if(empty($project)){
                throw new \Exception('project is empty!');
            }

            //借款人代签标记
            if($project['entrust_sign']){
                $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;
            }

            //担保机构代签标记
            if($project['entrust_agency_sign']){
                $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_AGENCY;
            }

            //资产端代签标记
            if($project['entrust_advisory_sign']){
                $autoSign += ContractTplIdentifierEnum::SIGN_ROLE_ADVISORY;
            }

            $deal = $dealModel->getFirstDealByProId($projectId,array(DealModel::$DEAL_STATUS['full']));

            if(empty($deal)){
                throw new \Exception('deal is empty!');
            }

            $rpc = new Rpc('contractRpc');

            $isDealZX = $dealService->isDealEx($deal['deal_type']);

            $contractRequest = new RequestGetDealCId();
            $contractRequest->setDealId(intval($deal['id']));
            $contractRequest->setType(0);
            $contractRequest->setSourceType($deal['deal_type']);
            $response = $rpc->go("\NCFGroup\Contract\Services\Category","getDealCid",$contractRequest);

            if($response && ($response->errorCode != 0)){
                \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"getDealCid",'RPC调用结果错误码不为0')));
                throw new \Exception('RPC contract_service is fail!');
            }else{
                if(count($response->data) === 0){
                    \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                    Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"getDealCid",'RPC response Data is null!')));
                    throw new \Exception('RPC response Data is null!');
                }
            }

            $createTime = time();
            $sendRequest = new RequestSendProjectContract();
            $sendRequest->setProjectId($projectId);
            $sendRequest->setDealId(intval($deal['id']));
            $sendRequest->setAdvisoryAgencyId(intval($deal['advisory_id']));
            $sendRequest->setGuaranteeAgencyId(intval($deal['agency_id']));
            $sendRequest->setEntrustAgencyId(intval($deal['entrust_agency_id']));
            $sendRequest->setCanalAgencyId(intval($deal['canal_agency_id']));
            $sendRequest->setBorrowUserId(intval($deal['user_id']));
            $sendRequest->setLenderUserId(0);
            $sendRequest->setSourceType($deal['deal_type']);
            $sendRequest->setCreateTime($createTime);
            $sendRequest->setAutoSign($autoSign);

            $sendResponse = $rpc->go("\NCFGroup\Contract\Services\SendContract","projectSend",$sendRequest);

            if(empty($sendResponse)||($sendResponse->errorCode > 0)){
                \libs\utils\Monitor::add('CS_SEND_CONTRACT_FAIL');
                Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logs,"projectSend",'RPC response Data is null!')));
                throw new \Exception('RPC contract_service is fail!');
            }


            //委托签署相关

            if($project['entrust_sign'] == 1){
                if(!$contractService->signProjectCont($projectId,1,0,0,true)){
                    Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL',sprintf('代签项目合同失败。项目 id：%d,role: 1', $projectId))));
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                }
            }
            if($project['entrust_agency_sign'] == 1){
                if(!$contractService->signProjectCont($projectId,2,0,0,true)){
                    Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL',sprintf('代签项目合同失败。项目 id：%d,role: 2', $projectId))));
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                }
            }
            if($project['entrust_advisory_sign'] == 1) {
                if(!$contractService->signProjectCont($projectId, 3, 0, 0, true)){
                    Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL',sprintf('代签项目合同失败。项目 id：%d,role: 3', $projectId))));
                    \libs\utils\Monitor::add('CS_CONTRACT_SIGN_FAIL');
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('生成项目合同失败：%s；项目 id：%d', $e->getMessage(), $projectId));
            return false;
        }
    }

    /**
     * 生成智多新(根据时间区分合同分类类型)合同记录
     * @param array requestData          智多新                                                    |   随心约
     *           int dealId              实际上存的是智多新用户投资记录id loanId 受托用户           |   预约id
     *           int borrowUserId        实际上存的是智多新用户 受托用户                            |   0
     *           int projectId           实际上是智多新项目id                                       |   ContractServiceEnum::RESERVATION_PROJECT_ID
     *           int dealLoadId          实际上存的是智多新用户投资记录id redemptionLoanId 转让用户 |   0
     *           int type                1:智多鑫(contract_category_deal_record中的type)            |   ContractServiceEnum::TYPE_RESERVATION_SUPER
     *           int lenderUserId        实际上存的是智多新用户 转让用户                            |   userId
     *           int sourceType          101:智多新                                                 |   ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER
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
        try{
            if(empty($requestData['dealId'])){
                throw new \Exception("生成合同记录失败 dealId为null");
            }
            $response = ApiService::rpc("contract", "sendContract/sendDtContract", array('requestData' => $requestData));
            // 获取合同id
            $contractId = 0;
            if(!$response){
                $number = ContractService::createDtNumber($requestData['dealId'],$requestData['uniqueId']);
                $contract = ContractService::getContractByNumber($requestData['dealId'],$number,$requestData['sourceType']);
                if(empty($contract)){
                    throw new \Exception("生成合同记录失败");
                }
                if($contract[0]['user_id'] != $requestData['lenderUserId']){
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
            $jobsModel->priority = JobsModel::CONTRACT_JOBS_TSA_DT;
            // 考虑到主从延迟，延时1分钟之后再执行
            if($jobsModel->addJob($function, $params, get_gmtime()+60)) {
                Logger::info(sprintf('智多新合同打戳jobs添加成功，参数：%s，file：%s, line:%s', $logParams, __FILE__, __LINE__));
            }else{
                throw new \Exception('智多新合同打戳jobs添加失败');
            }
            return true;
        }catch(\Exception $e){
            Logger::error(implode(' | ', array(__FILE__,__FUNCTION__,__LINE__,'CS_SEND_CONTRACT_FAIL','参数:'.$logParams,$e->getMessage())));
            Monitor::add('CS_SEND_CONTRACT_FAIL');
            throw $e;
        }
    }
}
