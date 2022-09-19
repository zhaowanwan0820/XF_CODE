<?php
/**
 * 提供调用远程合同服务的方法
 */

namespace core\service\contract;

use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;

use core\service\deal\DealService;
use core\service\contract\ContractNewService;
use core\service\contract\ContractViewerService;
use core\service\contract\ContractService;
use core\service\contract\TplService;

use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractTplIdentifierEnum;
use libs\utils\Logger;

class ContractRemoterService{

    /**
     * 根据合同id 获取合同(合同记录 + 合同模板 + 合同标识)
     * @param int $deal_id
     * @param int $contract_id
     * @return array [contract_* 表中内容 + 合同title + 合同content]
     */
    static public function getContract($deal_id, $contract_id){
        try {
            // 获取 deal 信息
            $deal_info = (new DealService())->getDealInfo($deal_id);
            if(empty($deal_info)){
                throw new \Exception(sprintf('此标的不存在，标的 id：%d', $deal_id));
            }
            // 获取合同模板
            $response =  ContractService::getContractInfoByContractId(intval($deal_info->id),ContractServiceEnum::SERVICE_TYPE_DEAL,$contract_id, intval($deal_info->project_id),ContractServiceEnum::SOURCE_TYPE_PH);
            if (!empty($response)) {
                return  $response;
            } else {
                throw new \Exception(ContractService::getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，合同id：%d，标id：%d，失败原因：%s，file：%s, line:%s', $contract_id, $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据标的id 获取合同列表(模板-未渲染)
     * @param int $deal_id
     * @param boolean $is_seen_when_bid 标识是否只取用户投资时可见的合同
     * @return array [[id, title, content, contract_type]]
     */
    static public function getContractList($deal_id, $is_seen_when_bid = false){
        try {
            $deal_id = intval($deal_id);
            $deal_info = (new DealService())->getDealInfo($deal_id);
            if(empty($deal_info)){
                throw new \Exception(sprintf('此标的不存在，标的 id：%d', $deal_id));
            }
            $response =  TplService::getTplsByDealId($deal_id,time(),0,$deal_info['deal_type']);
            if (!empty($response)) {
                $res_tpls = array();
                foreach($response as $one_tpl) {
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
                throw new \Exception(TplService::getErrorMsg());
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同列表失败，标id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据标的id 获取合同列表
     * @param int $deal_load_id
     * @return array
     */
    static public function getContractListByDealLoadId($deal_load_id){
        try {
            $deal_load_id = intval($deal_load_id);
            $deal_load_info = DealLoadModel::instance()->findViaSlave($deal_load_id);
            if(empty($deal_load_info)){
                throw new \Exception('没有相关投资记录');
            }
            $deal_info = (new DealService())->getDealInfo($deal_load_info['deal_id']);
            if(empty($deal_info)){
                throw new \Exception('没有相关标的');
            }
            $contract_new_service = new ContractNewService();
            if ($contract_new_service->isAttachmentContract($deal_info['contract_tpl_type'])) {
                $cont_list = (new ContractService())->getContractAttachmentByDealLoad($deal_info);
                $is_attachment = true;
            } else {
                $is_attachment = false;

                $cont_list =  ContractService::getContractByLoadId(intval($deal_info->id),$deal_load_id,$deal_load_info['user_id'],ContractServiceEnum::SOURCE_TYPE_PH,true);
                if(empty($cont_list)){
                    throw new \Exception(ContractService::getErrorMsg());
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
    static private function getContractTypeSign($contract_type){
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
     * 根据模板id 获取合同模板
     * @param int $tpl_id(contract_template的主键)
     * @return array
     */
    static public function getContractTplById($tpl_id){
        try {
            $response =  TplService::getTplById($tpl_id);
            if (empty($response)) {
                throw new \Exception(sprintf('此模板不存在，模板 id：%d', $tpl_id));
            } else {
                return $response;
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同模板失败，合同模板id：%d，失败原因：%s，file：%s, line:%s', $tpl_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取标的下的所有合同
     * @param int $deal_id
     * @return array
     *    每个元素包含(id 合同id)+(number 合同编号)
     */
    static public function getDealAllContract($deal_id){
        try {
            $deal_id = intval($deal_id);
            $deal_info = (new DealService())->getDealInfo($deal_id);
            if(empty($deal_info)){
                throw new \Exception(sprintf('此项目不存在，标的 id：%d', $deal_id));
            }
            $pageSize = 1000;
            $result = array();
            $response =  ContractService::getContractIdNumByDealId($deal_id,1,1000,ContractServiceEnum::SOURCE_TYPE_PH);
            if(!empty($response)){
                $result = $response['list'];
                    if($response['count']> $pageSize){
                        $countInfo = $response['count'];
                        $count = $countInfo['num'];
                        $page = ceil($count/$pageSize);
                        for($i = 2; $i <= $page; $i++){
                            $pageResponse =  ContractService::getContractIdNumByDealId($deal_id,$i,1000,ContractServiceEnum::SOURCE_TYPE_PH);
                            if(!empty($pageResponse)){
                                if (!empty($pageResponse)) {
                                    $result = array_merge($result,$pageResponse['list']);
                                }else {
                                    throw new \Exception(ContractService::getErrorMsg());
                                }
                            }
                        }
                    }
                    return $result;
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('获取标的合同失败 ，标的 id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
        return array();
    }

    /**
     * 根据标的投资id 获取借款合同记录
     * (只会返回合同记录中tpl_identifier_id为1,16,31, 32, 33的合同记录)
     * @param int $deal_load_id
     * @return array [对应 contract_* 字段]
     */
    static public function getLoanContractByDealLoadId($deal_load_id){
        list(,$contract_list) = self::getContractListByDealLoadId($deal_load_id);
        $loan_contract = array();
        foreach ($contract_list as $contract) {
            if (self::isLoanContract($contract['tpl_indentifier_info']['name'])) {
                $loan_contract = $contract;
                break;
            }
        }
        return $loan_contract;
    }

    /**
     * 判断模板标识是否为借款合同类型
     * @param string $tpl_identifier_name
     * @return boolean
     */
    static private function isLoanContract($tpl_identifier_name){
        $position = stripos(trim($tpl_identifier_name),ContractTplIdentifierEnum::LOAN_CONT);
        if($position === 0){
            return true;
        }
        return false;
//        以前的判断逻辑
//        $loan_contract_tpl_identifier_id_arr = array(1, 16, 31, 32, 33);
//        return in_array($tpl_identifier_id, $loan_contract_tpl_identifier_id_arr);
    }
}
