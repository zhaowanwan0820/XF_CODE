<?php

use NCFGroup\Protos\Contract\RequestSaveContractTplIdentifier;
use NCFGroup\Protos\Contract\RequestGetContractTplIdentifierList;
use NCFGroup\Protos\Contract\RequestGetContractTplIdentifier;
use NCFGroup\Protos\Contract\RequestDeleteContractTplIdentifier;

use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;

class ContractTplIdentifierAction extends CommonAction
{
    /**
     * 获取合同模板标识列表
     */
    public function index()
    {
        $request = new RequestGetContractTplIdentifierList();
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\ContractTplIdentifier",
            'method' => "getTplIdentifierList",
            'args' => $request,
        ));

        $this->assign('list', $response->getList());
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        if (empty($_REQUEST['id'])) {
            $this->error('无效id！');
        }

        $id = intval($_REQUEST['id']);
        $request = new RequestGetContractTplIdentifier();
        $request->setId($id);
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\ContractTplIdentifier",
            'method' => "getTplIdentifierInfoById",
            'args' => $request,
        ));

        $this->assign('info', $response->getData());
        $sign_role_map = ContractTplIdentifierEnum::$sign_role_map;
        unset($sign_role_map[ContractTplIdentifierEnum::SIGN_ROLE_PLATFORM]);
        $this->assign('sign_role_map', $sign_role_map);
        $this->assign('service_type_map', ContractTplIdentifierEnum::$service_type_map);
        $this->assign('contract_type_map', ContractTplIdentifierEnum::$contract_type_map);
        $this->display();
    }

    /**
     * 新增
     */
    public function add()
    {
        $sign_role_map = ContractTplIdentifierEnum::$sign_role_map;
        unset($sign_role_map[ContractTplIdentifierEnum::SIGN_ROLE_PLATFORM]);
        $this->assign('sign_role_map', $sign_role_map);
        $this->assign('service_type_map', ContractTplIdentifierEnum::$service_type_map);
        $this->assign('contract_type_map', ContractTplIdentifierEnum::$contract_type_map);
        $this->display();
    }

    /**
     * 保存合同模板标识
     */
    public function save()
    {
        try {
            if(empty($_REQUEST['name'])) {
                throw new \Exception('模板标识不能为空');
            }

            $request = new RequestSaveContractTplIdentifier();
            $request->setId(intval($_REQUEST['id']));
            $request->setName($_REQUEST['name']);
            $request->setTitle($_REQUEST['title']);
            $request->setSignRole(array_reduce($_REQUEST['sign_role'], function ($carry, $item) { return $carry | $item; })); // 将签署方配置按位取或，以标识哪方需要签署
            $request->setContractSendNode(intval($_REQUEST['contract_send_node']));
            $request->setIsSeenWhenBid(intval($_REQUEST['is_seen_when_bid']));
            $request->setServiceType(intval($_REQUEST['service_type']));
            $request->setContractType(intval($_REQUEST['contract_type']));
            $request->setPlatformUserId(intval($_REQUEST['platform_user_id']));

            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\ContractTplIdentifier",
                'method' => "save",
                'args' => $request,
            ));

            if($response->getResult()){
                $this->assign('jumpUrl','/m.php?m=ContractTplIdentifier&a=index');
                $this->success('保存成功');
            }else{
                throw new \Exception('保存失败');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

//    /**
//     * 删除 废弃
//     */
//    public function delete()
//    {
//        try {
//            if(empty($_REQUEST['id'])) {
//                throw new \Exception('参数有误！');
//            }
//
//            $request = new RequestDeleteContractTplIdentifier();
//            $request->setId(intval($_REQUEST['id']));
//
//            $response = $this->getRpc('contractRpc')->callByObject(array(
//                'service' => "\NCFGroup\Contract\Services\ContractTplIdentifier",
//                'method' => "deleteTplIdentifierById",
//                'args' => $request,
//            ));
//
//            if($response->getResult()){
//                $this->assign('jumpUrl','/m.php?m=ContractTplIdentifier&a=index');
//                $this->success('删除成功');
//            }else{
//                throw new \Exception('删除失败');
//            }
//        } catch (\Exception $e) {
//            $this->error($e->getMessage());
//        }
//    }

}
