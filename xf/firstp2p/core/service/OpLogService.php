<?php
/**
 * OpLogService.php
 * @date 2015-01-14
 * @author <yangqing@ucfgroup.com>
 */

namespace core\service;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\SendContractEvent;
use core\event\BaseCheckerEvent;
use core\dao\OpLogModel;
use core\dao\OpStatusModel;
/**
 * Class OpLogService
 * @package core\service
 */
class OpLogService extends BaseService {


    /**
     * send_contract
     * 发送合同生成任务
     * @param mixed $deal_id 项目ID
     * @param mixed $load_id 投资ID
     * @param mixed $is_full 是否满标
     * @access public
     * @return int op_status_id
     */
    public function send_contract($deal_id, $load_id, $is_full){
        $model = new OpLogModel();
        $ret = $model->insert_deal_contract($deal_id, $load_id);
        if($is_full){
            $op_log = $model->findBy("op_content = '0' AND op_name = 'DEAL_SEND_CONTRACT_".$deal_id."'","update_time");
        }else{
            $op_log = $model->findBy("op_content = '".$load_id."'","update_time");
        }


        $update_time = isset($op_log['update_time'])?$op_log['update_time']:false;
        if($ret !== false){
            $status = new OpStatusModel();
            $op_status_id = $status->insert_status_log($model->get_opname_by_content($deal_id, OpLogModel::OPNAME_DEAL_CONTRACT),OpLogModel::OPNAME_DEAL_CONTRACT,$deal_id);
            $event = new SendContractEvent($deal_id,$load_id, $is_full, $ret, $update_time);
            $obj = new GTaskService();
            if(!$obj->doBackground($event, 10)){
                $model->update_status($ret,-1);
                throw new \Exception('任务注册失败');
                return false;
            }else{
                return $op_status_id;
            }
        }else{
            throw new \Exception('日志注册失败');
            return false;
        }
    }

    /**
     * addChecker
     * 注册check机
     * @param BaseCheckerEvent $event 要添加的check机
     * @param float $max_try 失败尝试次数
     * @access public
     * @return boolean
     */
    public function addChecker($event, $max_try=10 ){
        $obj = new GTaskService();
        if(!$obj->doBackground($event, $max_try)){
            return false;
        }else{
            return true;
        }
    }
}
