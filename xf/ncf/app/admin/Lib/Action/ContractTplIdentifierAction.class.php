<?php

use core\service\contract\ContractTplIdentifierService;

use core\enum\contract\ContractTplIdentifierEnum;

class ContractTplIdentifierAction extends CommonAction
{
    /**
     * 获取合同模板标识列表
     */
    public function index()
    {
        $response = ContractTplIdentifierService::getTplIdentifierList();
        $this->assign('list', $response);
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
        $response =  ContractTplIdentifierService:: getTplIdentifierInfoById($id);
        $this->assign('info', $response);
        $this->assign('sign_role_map', ContractTplIdentifierEnum::$sign_role_map);
        $this->assign('service_type_map', ContractTplIdentifierEnum::$service_type_map);
        $this->assign('contract_type_map', ContractTplIdentifierEnum::$contract_type_map);
        $this->display();
    }

    /**
     * 新增
     */
    public function add()
    {
        $this->assign('sign_role_map', ContractTplIdentifierEnum::$sign_role_map);
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

            $sign_role = array_reduce($_REQUEST['sign_role'], function ($carry, $item) { return $carry | $item; }); // 将签署方配置按位取或，以标识哪方需要签署
            $response = ContractTplIdentifierService :: save(
                $_REQUEST['name'],
                $_REQUEST['title'],
                $sign_role,
                intval($_REQUEST['contract_send_node']),
                intval($_REQUEST['is_seen_when_bid']),
                intval($_REQUEST['service_type']),
                intval($_REQUEST['contract_type']),
                intval($_REQUEST['id'])
            );
            if($response){
                $this->assign('jumpUrl','/m.php?m=ContractTplIdentifier&a=index');
                $this->success('保存成功');
            }else{
                throw new \Exception('保存失败');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }


}
