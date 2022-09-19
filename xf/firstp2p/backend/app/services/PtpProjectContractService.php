<?php

namespace NCFGroup\Ptp\services;

use core\dao\DealSiteModel;
use core\service\ContractService;
use core\service\ContractNewService;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestProjectContract;
use NCFGroup\Protos\Ptp\ResponseProjectContract;

require_once APP_ROOT_PATH . "/openapi/lib/functions.php";

/**
 * ProjectContractService
 * 项目合同相关
 * @uses ServiceBase
 * @package default
 */
class PtpProjectContractService extends ServiceBase {

    /**
     * 获取已投项目，合同内容
     */
    public function getContract(RequestProjectContract $request){
        $id = $request->getId();
        $userId = $request->getUserId();
        $userName = $request->getUserName();
        $projectId = $request->getProjectId();
        $contractNewService = new ContractNewService();
        $contract = $contractNewService->showContract($id,0,$projectId,1);
        $response = new ResponseProjectContract();
        // 获取不到内容，但是content会返回
        if (empty($contract) || empty($contract['content'])){
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }

        $contract['content'] = hide_message($contract['content']);
        $response->setContent((string) $contract['content']);
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;

    }
}
