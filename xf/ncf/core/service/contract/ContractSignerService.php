<?php
/**
 * 合同打戳
 */

namespace core\service\contract;

// service
use core\service\contract\ContractRemoterService;

// dao
use core\dao\contract\ContractFilesWithNumModel;
use core\dao\jobs\JobsModel;
use core\dao\project\DealProjectModel;

use core\enum\JobsEnum;
use core\enum\contract\ContractServiceEnum;

// libs
use libs\utils\Logger;
use libs\utils\Alarm;

class ContractSignerService
{
    /**
     * 根据服务 id 进行合同的异步签署(批量)
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param int $service_type 服务类型 1:标的；2:项目；3:黄金标的
     * @return boolean
     */
    static public function signAllContractByServiceId($service_id, $service_type = 1)
    {
        try {
            if ($service_id <= 0) {
                throw new \Exception(sprintf('服务id有误，参数：%s', $service_id));
            }
            $service_id = intval($service_id);
            $service_type = intval($service_type);

            switch ($service_type) {
                case ContractServiceEnum::SERVICE_TYPE_DEAL:
                    $list = ContractRemoterService::getDealAllContract($service_id);
                    break;
               default:
                    throw new \Exception(sprintf('服务类型有误，参数：%s', $service_type));
            }

            // 监控进入gm合同数量
            $monitor_num = count($list);
            $succ = 0;
            $error_contract_ids = array();

            if(empty($list)){
                Alarm::push('tsacheck', 'tsacheck ph盖戳合同列表为空!', 'deal_id:'.$service_id);
            }

            foreach($list as $one){
                if (self::signOneContract($one['id'], $one['number'], $service_id, $service_type)) {
                    ++$succ;
                } else {
                    $error_contract_ids[] = $one['id'];
                }
            }

            if($monitor_num != $succ){
                $alert_data = array(
                    'service_id' => $service_id,
                    'service_type' => $service_type,
                    'needTsaCount' => $monitor_num,
                    'realTsaCount' => $succ,
                    'errorContractId' => $error_contract_ids,
                );
                Alarm::push('tsacheck', 'tsacheck ph 时间戳入队报警', json_encode($alert_data));
                Logger::error(sprintf('合同批量盖戳数量和合同记录数量不统一，访问参数：%s，file：%s, line:%s', json_encode(func_get_args()), __FILE__, __LINE__));
            }
            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('合同批量盖戳失败，访问参数：%s，失败原因：%s，file：%s, line:%s', json_encode(func_get_args()), $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 根据服务 id && 合同 id 进行合同的异步签署
     * @param int $contract_id
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param int $service_type 服务类型 1:标的；2:项目 101:智多新
     * @return boolean
     */
    static public function signOneContractByServiceId($contract_id, $service_id, $service_type = 1)
    {
        try {
            if ($contract_id <= 0 || $service_id <= 0) {
                throw new \Exception('参数有误');
            }

            $contract_id = intval($contract_id);
            $service_id = intval($service_id);
            $service_type = intval($service_type);
            switch ($service_type) {
                case ContractServiceEnum::SERVICE_TYPE_DEAL:
                    $contract = ContractRemoterService::getContract($service_id, $contract_id);
                    break;
                case ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT:
                    $contract =  ContractService::getContractByCid(intval($service_id),$contract_id,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $contract = $contract[0];
                    break;
                case ContractServiceEnum::SOURCE_TYPE_RESERVATION:
                    $contract =  ContractService::getContractByCid(intval($service_id),$contract_id,ContractServiceEnum::SOURCE_TYPE_RESERVATION);
                    $contract = $contract[0];
                    break;
               default:
                    throw new \Exception(sprintf('服务类型有误，参数：%s', $service_type));
            }
            if(empty($contract)){
                throw new \Exception('找不到合同记录');
            }
            return self::signOneContract($contract['id'], $contract['number'], $service_id, $service_type);
        } catch (\Exception $e) {
            Logger::error(sprintf('合同盖戳失败，访问参数：%s，失败原因：%s，file：%s, line:%s', json_encode(func_get_args()), $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 针对单个合同进行异步签署
     * @param int $contract_id
     * @param string $contract_num
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param int $service_type 服务类型 1:标的；2:项目 3:黄金 4:暗月 101:智多新 102:随心约
     * @return boolean
     */
    static private function signOneContract($contract_id, $contract_num, $service_id, $service_type = 1)
    {
        try {
            $service_id = intval($service_id);
            $service_type = intval($service_type);
            // 除了智多新的合同路径，其他的source_type为0
            $source_type = ($service_type != ContractServiceEnum::SERVICE_TYPE_DEAL) ? $service_type : 0;
            $contract_files = ContractFilesWithNumModel::instance()->getAllByContractNum($contract_num,$contract_id,$source_type);
            if (empty($contract_files)) { // 如果不存在记录，就插入新的
                $res_file_insert = ContractFilesWithNumModel::instance()->addNewRecord($contract_id, $contract_num, ContractFilesWithNumModel::FDFS_DEFAULT, ContractFilesWithNumModel::FDFS_DEFAULT, $service_id,null,$source_type);
                if (false === $res_file_insert) {
                    throw new \Exception('合同文件记录插入失败！');
                } else {
                    Logger::info(sprintf('合同文件插入成功，合同id：%d，合同num：%s，服务id：%d，服务类型：%d，file：%s, line:%s', $contract_id, $contract_num, $service_id, $service_type, __FILE__, __LINE__));
                }
            } elseif ($contract_files[0]['status'] == ContractFilesWithNumModel::TSA_STATUS_DONE) { // 已签署
                Logger::info(sprintf('合同已签署，合同id：%d，合同num：%s，服务id：%d，服务类型：%d，file：%s, line:%s', $contract_id, $contract_num, $service_id, $service_type, __FILE__, __LINE__));
                return true;
            }
            // 随鑫约 的打戳方法 另外的 jobsEnum
            // 添加签署异步任务
            $jobs_model = new JobsModel();
            $function = "\core\service\contract\ContractSignService::signOneContract";
            // 标的合同
            if ($service_type == ContractServiceEnum::SERVICE_TYPE_DEAL) {
               $params = array(
                    'contract_id' => $contract_id,
                    'async' => false,
                    'deal_id' => $service_id,
                );
                $jobs_model->priority = JobsEnum::CONTRACT_TSA;
            } elseif ($service_type == ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT) {
                $params = array(
                    'contract_id' => $contract_id,
                    'async' => false,
                    'deal_id' => $service_id,
                    'type' => $service_type,
                );
                $jobs_model->priority = JobsEnum::CONTRACT_TSA_DT;
            }elseif ($service_type == ContractServiceEnum::SOURCE_TYPE_RESERVATION) {
                $params = array(
                    'contract_id' => $contract_id,
                    'async' => false,
                    'deal_id' => $service_id,
                    'type' => $service_type,
                );
                $jobs_model->priority = JobsEnum::CONTRACT_JOBS_TSA_RESERVATION;
            }else {
                throw new \Exception(sprintf('服务类型有误，参数：%s', $service_type));
            }


            if ($jobs_model->addJob($function, $params)) {
                Logger::info(sprintf('异步签署任务添加成功，参数：%s，file：%s, line:%s', json_encode($params), __FILE__, __LINE__));
                return true;
            } else {
                throw new \Exception('签署 jobs 添加失败！');
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('合同签署失败，合同id：%d，合同num：%s，服务id：%d，服务类型：%d，失败原因：%s，file：%s, line:%s', $contract_id, $contract_num, $service_id, $service_type, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 检查某个网贷标的合同打戳情况
     * @param int $dealId
     * @return array $ret
    */
    public static function checkTsaWithDealId($dealId){
        $contracts = ContractInvokerService::getDealAllContract('remoter',$dealId);
        if(empty($contracts)){
            return array();
        }
        $ret = array('dealId'=>$dealId);
        $ret['contractNum'] = count($contracts);
        $ret['failInfo'] = array();
        $count = 0;
        foreach ($contracts as $one) {
            $tsaInfo = ContractFilesWithNumModel::instance()->getAllByContractNum($one['number'],$one['id']);
            if(!empty($tsaInfo[0]['group_id']) && !empty($tsaInfo[0]['path'])){
                $count ++;
            }else{
                $ret['failInfo'][] = array('id'=>$one['id'],'number'=>$one['number'],'group'=>$tsaInfo[0]['group_id'],'path'=>$tsaInfo[0]['path']);
            }
        }

        $ret['tsaNum'] = $count;
        if(empty($ret['failInfo'])){
            $ret['hasfail'] = 0;
        }else{
            $ret['hasfail'] = 1;
        }
        return $ret;
    }
}
