<?php
/**
 * 合同中心-借款列表
 *
 * @date 2018年8月20日14:46:30
 */

namespace task\apis\account;

use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\contract\ContractNewService;

class Contract extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        // 参数
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $user_id = intval($params['userId']);
        $role = intval($params['role']);
        $page_num = intval($params['pageNum']) <= 0 ? 1 : intval($params['pageNum']); // 第p页
        $isP2p = (bool) $params['isP2p'];
        $page_size = intval($params['pageSize']) <= 0 ? 10 : intval($params['pageSize']);
        // 借款列表
        $contract_new_service = new ContractNewService();
        $result = $contract_new_service->getContDealList($user_id, $page_num, $page_size, $role, $isP2p, true);
        // 普惠把p2p所有的"收益"改为"利息"
        foreach($result['list'] as $key => $value){
            $result['list'][$key]['loantype_name'] = p2pTextFilter($value['loantype_name'],$value['deal_type']);
        }
        $this->json_data = $result;
    }

}
