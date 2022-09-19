<?php
/**
 * 合同查看
 *
 * @date 2018年8月20日14:46:30
 */

namespace task\apis\account;

use libs\utils\Logger;
use core\enum\contract\ContractServiceEnum;
use task\lib\ApiAction;
use core\service\contract\ContractNewService;
use core\service\contract\ContractViewerService;
use core\service\contract\ContractUtilsService;

class Contshow extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $contract_id = intval($params['contract_id']);
        $deal_id = intval($params['deal_id']);
        $user_info = $params['user_info'];

        // 1 获取合同
        $cont = ContractViewerService::getOneFetchedContract($contract_id, $deal_id, ContractServiceEnum::SERVICE_TYPE_DEAL);
        // 2 防刷：校验此份合同是否属于当前用户
        if (!empty($user_info) && !ContractUtilsService::checkContractOwnership($cont, $user_info)) {
            return false;
        }
        $this->json_data = $cont;
    }

}
