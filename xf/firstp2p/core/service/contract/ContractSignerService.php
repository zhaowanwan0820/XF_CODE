<?php
/**
 * 合同打戳
 */

namespace core\service\contract;

// service
use core\service\contract\ContractRemoterService;

// dao
use core\dao\ContractFilesWithNumModel;
use core\dao\JobsModel;
use core\dao\DealProjectModel;
use core\dao\darkmoon\DarkmoonDealModel;

// protos
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

// gtask
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Common\Library\ApiService;
use core\event\ContractSignEvent;

use core\service\darkmoon\ContractService AS DarkmoonContractService;
use core\service\ContractService;
// libs
use libs\utils\Logger;

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
                case ContractServiceEnum::SERVICE_TYPE_PROJECT:
                    $list = ContractRemoterService::getProjectAllContract($service_id);
                    break;
                case ContractServiceEnum::SERVICE_TYPE_GOLD_DEAL:
                    $list = ContractRemoterService::getGoldDealAllContract($service_id);
                    break;
                case ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL:
                    $list = ContractRemoterService::getDarkmoonDealAllContract($service_id);
                    break;
                default:
                    throw new \Exception(sprintf('服务类型有误，参数：%s', $service_type));
            }

            // 监控进入gm合同数量
            $monitor_num = count($list);
            $succ = 0;
            $error_contract_ids = array();

            if(empty($list)){
                \libs\utils\Alarm::push('tsacheck', 'tsacheck 盖戳合同列表为空!', 'deal_id:'.$service_id);
            }

            foreach($list as $one){
                if (self::signOneContract($one['id'], $one['number'], $service_id, $service_type)) {
                    ++$succ;
                    // 暗月项目，最后一个打戳jobs时，添加一个检查打戳状态，并且更新暗月标的为打戳完成
                    if(($service_type == ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL) && ($monitor_num === $succ)){
                        // 添加签署异步任务
                        $jobs_model = new JobsModel();
                        $function = "\core\service\darkmoon\ContractService::updateDealAfterCheckTsa";
                        $params = array(
                            'dealId' => $service_id,
                        );
                        $jobs_model->priority = 176;
                        if($jobs_model->addJob($function, $params)) {
                            Logger::info(sprintf('异步检查签署任务添加成功，参数：%s，file：%s, line:%s', json_encode($params), __FILE__, __LINE__));
                        }else{
                            throw new \Exception(sprintf('异步签署任务添加失败，参数：%s，file：%s, line:%s', json_encode($params), __FILE__, __LINE__));
                        }
                    }
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
                \libs\utils\Alarm::push('tsacheck', 'tsacheck 时间戳入队报警', json_encode($alert_data));
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
     * @param int $service_type 服务类型 1:标的；2:项目 4:线下交易所  103:随心约
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
                case ContractServiceEnum::SERVICE_TYPE_PROJECT:
                    $contract = ContractRemoterService::getProjectContract($service_id, $contract_id);
                    break;
                case ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL:
                    $cs = new DarkmoonContractService();
                    $contract = $cs->getContract($contract_id,$service_id);
                    break;
                case ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER:
                    $contract =  ContractService::getContractByCid(intval($service_id),$contract_id,ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER);
                    $contract = isset($contract[0]) ? $contract[0] : array();
                    break;
                default:
                    throw new \Exception(sprintf('服务类型有误，参数：%s', $service_type));
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
     * @param int $service_type 服务类型 1:标的；2:项目 3:黄金 4:暗月
     * @return boolean
     */
    static private function signOneContract($contract_id, $contract_num, $service_id, $service_type = 1)
    {
        try {
            $service_id = intval($service_id);
            $service_type = intval($service_type);
            // 除了随心约合同，其他的source_type为0
            $source_type = ($service_type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER) ? $service_type : 0;
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

            // 上线过渡，此处将标的盖戳服务逐步迁到 jobs
            if (ContractServiceEnum::SERVICE_TYPE_DEAL == $service_type && 0 != $service_id%10) {
                $gtask = new GTaskService();
                $event = new ContractSignEvent($contract_id, $service_id);
                return $gtask->doBackground($event, 20, Task::PRIORITY_NORMAL, null, 'domq_cpu');
            } else {
                // 添加签署异步任务
                $jobs_model = new JobsModel();
                $function = "\core\service\ContractSignService::signOneContract";
                $jobs_model->priority = JobsModel::CONTRACT_TSA;
                if (ContractServiceEnum::SERVICE_TYPE_PROJECT == $service_type) { // 签署项目合同
                    $deal_info = DealProjectModel::instance()->getFirstDealByProjectId($service_id);
                    $params = array(
                        'contract_id' => $contract_id,
                        'async' => false,
                        'deal_id' => $deal_info['id'],
                        'type' => 1,
                        'project_id' => $service_id,
                    );
                }elseif(ContractServiceEnum::SERVICE_TYPE_GOLD_DEAL == $service_type){
                    $params = array(
                        'contract_id' => $contract_id,
                        'async' => false,
                        'deal_id' => $service_id,
                        'type' => 2,
                    );
                }elseif(ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL == $service_type){
                    $params = array(
                        'contract_id' => $contract_id,
                        'async' => false,
                        'deal_id' => $service_id,
                        'type' => ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL,
                    );
                }elseif(ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER == $service_type){
                    $params = array(
                        'contract_id' => $contract_id,
                        'async' => false,
                        'deal_id' => $service_id,
                        'type' => ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER,
                    );
                    $jobs_model->priority = JobsModel::CONTRACT_JOBS_TSA_RESERVATION;
                } else {
                    $params = array(
                        'contract_id' => $contract_id,
                        'async' => false,
                        'deal_id' => $service_id,
                    );
                }

                if ($jobs_model->addJob($function, $params)) {
                    Logger::info(sprintf('异步签署任务添加成功，参数：%s，file：%s, line:%s', json_encode($params), __FILE__, __LINE__));
                    return true;
                } else {
                    throw new \Exception('签署 jobs 添加失败！');
                }
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('合同签署失败，合同id：%d，合同num：%s，服务id：%d，服务类型：%d，失败原因：%s，file：%s, line:%s', $contract_id, $contract_num, $service_id, $service_type, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }
}
