<?php
/**
 * 合同中心-
 * 根据dealLoadId获取合同信息
 *
 * @date 2018年8月20日14:46:30
 */

namespace task\apis\account;

use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\contract\ContractInvokerService;

class ContractByDealLoadId extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        // 参数
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $deal_load_id = intval($params['dealLoadId']);

        $contract = ContractInvokerService::getLoanContractByDealLoadId('remoter', $deal_load_id);
        $this->json_data = $contract;
    }

}
