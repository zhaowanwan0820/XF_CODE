<?php

/**
 * DealFullCheckerEvent
 * 满标合同检测
 * @uses BaseCheckerEvent
 * @uses AsyncEvent
 * @package default
 * @author yangqing <yangqing@ucfgroup.com>
 */
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\event\BaseEvent;
use core\service\OpLogService;
use core\service\ContractService;

use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\DealContractModel;
use core\dao\OpStatusModel;
use core\dao\OpLogModel;

class DealFullCheckerEvent extends  BaseEvent
{
    private $_op_id;

    public function __construct(
        $op_id
    ) {
        $this->_op_id = $op_id;
    }

    public function execute() {
        $op_status = new OpStatusModel();
        $row = $op_status->find($this->_op_id);
        if($row){
            $oplog = new OpLogModel();
            $deal_id = $oplog->get_content_by_opname($row['op_name'], OpLogModel::OPNAME_DEAL_CONTRACT);
            $event_count = $oplog->get_count_by_opname($row['op_name']);

            $deal_load = new DealLoadModel();
            $load_count = $deal_load->getCountByID($deal_id);
            $load_count+=1;//任务总数比投资数量多一个
            if($event_count < $load_count){
                //补发合同
                $contract_service = new ContractService();
                $contract_service->contractReissueByOpLog($deal_id);
                return false;
            }else{
                $event_count = $oplog->get_count_by_opname($row['op_name'],1);
                //当任务数量达到投资数量
                if($event_count >= $load_count){
                    // 生成两条合同未签署的记录
                    $deal = DealModel::instance()->find($deal_id);
                    $deal_contract_model = new DealContractModel();
                    if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                        $deal['contract_version'] = 2;
                    }
                    $deal_contract_model->create($deal);
                    $ret = $op_status->update_status($this->_op_id,1);//标记所有任务执行完成
                    if($ret){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }
    public function alertMails()
    {
        return array('yangqing@ucfgroup.com');
    }
}
