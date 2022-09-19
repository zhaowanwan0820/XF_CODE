<?php
/**
 * 提供调用远程合同服务的方法
 */

namespace core\service\contract;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\DealLoadModel;
use core\dao\ContractModel;
use core\dao\darkmoon\DarkmoonDealModel;

use core\service\ContractNewService;

use core\service\contract\ContractViewerService;
use core\service\contract\ContractUtilsService;

use NCFGroup\Protos\Contract\RequestGetContractInfoByContractId;
use NCFGroup\Protos\Contract\RequestGetTplsByDealId;
use NCFGroup\Protos\Contract\RequestGetTplById;
use NCFGroup\Protos\Contract\RequestGetContractByProjectId;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Protos\Contract\RequestGetContractByLoadId;
use NCFGroup\Protos\Contract\RequestGetContractTplIdentifier;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;

use libs\utils\Logger;

class ContractRemoterService
{

    /**
     * 根据合同id 获取合同
     * @param int $deal_id
     * @param int $contract_id
     * @return array [contract_* 表中内容 + 合同title + 合同content]
     */
    static public function getContract($deal_id, $contract_id)
    {
        try {
            // 获取 deal 信息
            $deal_info = DealModel::instance()->findViaSlave($deal_id);
            if(empty($deal_info)){
                throw new \Exception(sprintf('此标的不存在，标的 id：%d', $deal_id));
            }

            // 获取合同模板
            $request = new RequestGetContractInfoByContractId();
            $request->setServiceId(intval($deal_info->id));
            $request->setServiceType(ContractServiceEnum::SERVICE_TYPE_DEAL);
            $request->setContractId($contract_id);
            $request->setSourceType(intval($deal_info->deal_type));
            $request->setOther(json_encode(array('project_id' => intval($deal_info->project_id))));
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractInfoByContractId", $request);

            if (0 == $response->getErrorCode()) {
                return $contract_info = $response->getData();
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，合同id：%d，标id：%d，失败原因：%s，file：%s, line:%s', $contract_id, $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据合同id 获取合同
     * @param int $deal_id
     * @param int $contract_id
     * @return array [contract_* 表中内容 + 合同title + 合同content]
     */
    static public function getGoldContract($deal_id, $contract_id)
    {
        try {

            // 获取合同模板
            $request = new RequestGetContractInfoByContractId();
            $request->setServiceId($deal_id);
            $request->setServiceType(ContractServiceEnum::SERVICE_TYPE_GOLD_DEAL);
            $request->setContractId($contract_id);
            $request->setSourceType(100);
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractInfoByContractId", $request);

            if (0 == $response->getErrorCode()) {
                return $contract_info = $response->getData();
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，合同id：%d，标id：%d，失败原因：%s，file：%s, line:%s', $contract_id, $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据标的id 获取合同列表
     * @param int $deal_id
     * @param boolean $is_seen_when_bid 标识是否只取用户投资时可见的合同
     * @return array [[id, title, content, contract_type]]
     */
    static public function getContractList($deal_id, $is_seen_when_bid = false)
    {
        try {
            $deal_id = intval($deal_id);
            $deal_info = DealModel::instance()->findViaSlave($deal_id);
            if(empty($deal_info)){
                throw new \Exception(sprintf('此标的不存在，标的 id：%d', $deal_id));
            }

            // 获取合同模板 list
            $request = new RequestGetTplsByDealId();
            $request->setDealId($deal_id);
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
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
                            'contract_type' => self::getContractTypeSign($one_tpl['tpl_identifier_info']['contractType'])
                        );
                    }
                }
                return $res_tpls;
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同列表失败，标id：%d，用户id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $user_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据标的id 获取合同列表
     * @param int $deal_load_id
     * @return array
     */
    static public function getContractListByDealLoadId($deal_load_id)
    {
        try {
            $deal_load_id = intval($deal_load_id);
            $deal_load_info = DealLoadModel::instance()->findViaSlave($deal_load_id);
            $deal_info = DealModel::instance()->findViaSlave($deal_load_info['deal_id']);
            if(empty($deal_load_info) || empty($deal_info)){
                throw new \Exception('没有相关投资记录');
            }

            $contract_new_service = new ContractNewService();
            if ($contract_new_service->isAttachmentContract($deal_info['contract_tpl_type'])) {
                $cont_list = ContractModel::instance()->getContractAttachmentByDealLoad($deal_info);
                $is_attachment = true;
            } else {
                $is_attachment = false;
                $request = new RequestGetContractByLoadId();
                $request->setDealId(intval($deal_info['id']));
                $request->setLoadId(intval($deal_load_id));
                $request->setUserId(intval($deal_load_info['user_id']));
                $request->setSourceType(intval($deal_info['deal_type']));
                $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract","getContractByLoadId",$request);
                if($response->getErrorCode() == 0){
                    $cont_list = $response->getList();
                } else {
                    throw new \Exception($response->getErrorMsg());
                }
            }

            return array($is_attachment, $cont_list);
        } catch (\Exception $e) {
            Logger::error(sprintf('获取单笔投资的合同列表失败，投资id：%d，失败原因：%s，file：%s, line:%s', $deal_load_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取合同类型的 boolean 标识，以供判断时，通过对应 key 是否为 true 来确定合同类型
     * @param int $contract_type 合同类型 对应 contract_tpl_identifier 表
     * @return array [is_*** => boolean]
     */
    static private function getContractTypeSign($contract_type)
    {
        $contract_type_info = array(
            'is_loan_contract' => false,
            'is_warrant_contract' => false,
            'is_lender_contract' => false,
            'is_buyback_contract' => false,
        );
        switch ($contract_type) {
            case ContractTplIdentifierEnum::CONTRACT_TYPE_LOAN: // 借款合同
                $contract_type_info['is_loan_contract'] = true;
                break;
            case ContractTplIdentifierEnum::CONTRACT_TYPE_WARRANT: // 担保合同
                $contract_type_info['is_warrant_contract'] = true;
                break;
            case ContractTplIdentifierEnum::CONTRACT_TYPE_LENDER_PROTOCAL: // 出借人平台服务协议
                $contract_type_info['is_lender_contract'] = true;
                break;
            case ContractTplIdentifierEnum::CONTRACT_TYPE_BUYBACK_NOTIFICATION: // 资产收益权回购通知
                $contract_type_info['is_buyback_contract'] = true;
                break;
        }

        return $contract_type_info;
    }

    /**
     * 获取项目合同
     * @param int $project_id
     * @param int $contract_id
     * @return array ['合同库中-contract表字段' + '合同标题-title' + '合同内容-content' + '首标信息-deal_info']
     */
    static public function getProjectContract($project_id, $contract_id)
    {
        try {
            $project_id = intval($project_id);
            $deal_info = DealProjectModel::instance()->getFirstDealByProjectId(intval($project_id)); // 获取 deal 信息
            if(empty($deal_info)){
                throw new \Exception(sprintf('此标的不存在，标的 id：%d', $deal_id));
            }

            // 获取合同模板
            $request = new RequestGetContractInfoByContractId();
            $request->setServiceId(intval($deal_info->project_id));
            $request->setServiceType(ContractServiceEnum::SERVICE_TYPE_PROJECT);
            $request->setContractId($contract_id);
            $request->setSourceType(intval($deal_info->deal_type));
            $request->setOther(json_encode(array('deal_id' => intval($deal_info->id))));
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractInfoByContractId", $request);

            if (0 == $response->getErrorCode()) {
                $contract_info = $response->getData();
                $contract_info['deal_info'] = $deal_info;
                return $contract_info;
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，合同id：%d，项目id：%d，失败原因：%s，file：%s, line:%s', $contract_id, $project_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据模板id 获取合同模板
     * @param int $tpl_id
     * @return array
     */
    static public function getContractTplById($tpl_id)
    {
        try {
            $request = new RequestGetTplById();
            $request->setId(intval($tpl_id));
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Tpl", "getTplById", $request);

            if (empty($response->list['data'])) {
                throw new \Exception(sprintf('此模板不存在，模板 id：%d', $tpl_id));
            } else {
                return $response->list['data'];
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同模板失败，合同模板id：%d，失败原因：%s，file：%s, line:%s', $tpl_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取项目下的所有合同
     * @param int $project_id
     * @return array
     */
    static public function getProjectAllContract($project_id)
    {
        try {
            $project_id = intval($project_id);
            $project_info = DealProjectModel::instance()->findViaSlave($project_id, 'deal_type');
            if(empty($project_info)){
                throw new \Exception(sprintf('此项目不存在，项目 id：%d', $project_id));
            }

            // 获取合同模板
            $request = new RequestGetContractByProjectId();
            $request->setProjectId($project_id);
            $request->setSourceType(intval($project_info['deal_type']));
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractByProjectId", $request);

            if (0 == $response->getErrorCode()) {
                return $response->getList();
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取项目合同失败 ，项目id：%d，失败原因：%s，file：%s, line:%s', $project_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取标的下的所有合同
     * @param int $deal_id
     * @return array
     */
    static public function getDealAllContract($deal_id)
    {
        try {
            $deal_id = intval($deal_id);
            $deal_info = DealModel::instance()->findViaSlave($deal_id, 'deal_type');
            if(empty($deal_info)){
                throw new \Exception(sprintf('此项目不存在，标的 id：%d', $deal_id));
            }
            $pageSize = 1000;
            $result = array();

            // 获取合同模板
            $request = new RequestGetContractByDealId();
            $request->setDealId($deal_id);
            $request->setSourceType(intval($deal_info['deal_type']));
            $request->setPageNo(1);
            $request->setPageSize($pageSize);
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractIdNumByDealId", $request);

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
                            $pageRequest->setSourceType(intval($deal_info['deal_type']));
                            $pageRequest->setPageNo($i);
                            $pageRequest->setPageSize($pageSize);
                            $pageResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractIdNumByDealId", $pageRequest);
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

        } catch (\Exception $e) {
            Logger::error(sprintf('获取标的合同失败 ，标的 id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取黄金标的下的所有合同
     * @param int $deal_id
     * @return array
     */
    static public function getGoldDealAllContract($deal_id)
    {
        try {
            $deal_id = intval($deal_id);

            // 获取合同模板
            $request = new RequestGetContractByDealId();
            $request->setDealId($deal_id);
            $request->setSourceType(100);
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractByDealId", $request);

            if (0 == $response->getErrorCode()) {
                return $response->getList();
            } else {
                throw new \Exception($response->getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取标的合同失败 ，标的 id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取暗月标的下的所有合同
     * @param int $deal_id
     * @return array
     */
    static public function getDarkmoonDealAllContract($deal_id)
    {
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
            $response = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractIdNumByDealId", $request);

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
                            $pageResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Contract", "getContractIdNumByDealId", $pageRequest);
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



    /**
     * 根据标的投资id 获取借款合同记录
     * @param int $deal_load_id
     * @return array [对应 contract_* 字段]
     */
    static public function getLoanContractByDealLoadId($deal_load_id)
    {
        list(,$contract_list) = self::getContractListByDealLoadId($deal_load_id);
        $loan_contract = array();
        foreach ($contract_list as $contract) {
            if (self::isLoanContract($contract['tpl_identifier_id'])) {
                $loan_contract = $contract;
                break;
            }
        }
        return $loan_contract;
    }

    /**
     * 判断模板标识是否为借款合同类型
     * @param int $tpl_identifier_id
     * @return boolean
     */
    static private function isLoanContract($tpl_identifier_id)
    {
        $loan_contract_tpl_identifier_id_arr = array(1, 16);

        return in_array($tpl_identifier_id, $loan_contract_tpl_identifier_id_arr);
    }
}
