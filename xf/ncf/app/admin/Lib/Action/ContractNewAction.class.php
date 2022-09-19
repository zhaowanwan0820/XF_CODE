<?php

use core\service\contract\ContractNewService;
use core\service\contract\ContractSignService;
use core\service\contract\ContractInvokerService;

use core\dao\deal\DealModel;

use core\enum\contract\ContractServiceEnum;

FP::import("libs.common.app");

class ContractNewAction extends CommonAction {
    /**
     * 提供获取合同服务标识的数据
     */
    private function getContractServiceInfo()
    {
        if ($_REQUEST['type'] == 1) {
            $service_id = intval($_REQUEST['projectId']);
            $service_type = ContractServiceEnum::SERVICE_TYPE_PROJECT;
        } else {
            $service_id = intval($_REQUEST['dealId']);
            $service_type = ContractServiceEnum::SERVICE_TYPE_DEAL;
        }
        if(empty($service_id)){
            $this->error("非法操作！！！");
        }

        return array($service_id, $service_type);
    }

    // 首页
    public function opencontract()
    {
        if(empty($_REQUEST['id'])){
            $this->error("非法操作！！！");
        }

        list($service_id, $service_type) = $this->getContractServiceInfo();
        $contract_invoker = new ContractInvokerService();
        $contract = $contract_invoker->getOneFetchedContract('viewer', intval($_REQUEST['id']), $service_id, $service_type);
        echo hide_message($contract['content']);
    }

    // 下载
    public function download()
    {
        if(empty($_REQUEST['id'])){
            $this->error("非法操作！！！");
        }

        list($service_id, $service_type) = $this->getContractServiceInfo();
        $contract_invoker = new ContractInvokerService();
        $contract = $contract_invoker->download('filer', intval($_REQUEST['id']), $service_id, $service_type);
    }

    /**
     * 下载时间戳的pdf文件
     */
    public function downloadtsa()
    {
        list($service_id, $service_type) = $this->getContractServiceInfo();
        $contract_invoker = new ContractInvokerService();
        $contract = $contract_invoker->downloadTsa('filer', intval($_REQUEST['id']), $service_id, $service_type);
        return $ret;
    }

    public function agreeAll()
    {
        $ajax = intval($_REQUEST['ajax']);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false ;
        if (!$id) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $type = intval($_REQUEST['type']);
        $dealInfo = DealModel::instance()->find($id);
        if (empty($dealInfo)) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $adm = es_session::get(md5(conf("AUTH_KEY")));
        if (!$adm) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $contractService = new ContractNewService();
        $res = $contractService->signDealContNew($id,$type,0);
        if (!$res) {
            $this->error (L("UPDATE_FAILED"), $ajax);
        }else{
           $this->success(L("UPDATE_SUCCESS"), $ajax);
        }
    }

}
?>
