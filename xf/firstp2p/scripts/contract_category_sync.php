<?php
/**
 * 同步合同服务分类表给P2P,为信贷提供视图
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\ContractCategoryTmpModel;
use core\service\ContractNewService;

set_time_limit(0);

$contractService = new ContractNewService();
$tmpModel = new ContractCategoryTmpModel();

$categorys = $contractService->getContractCategorys();
// 也把删除状态的合同分类同步
$deleteCategorys = $contractService->getContractCategorys(1);
$categorys = array_merge($categorys,$deleteCategorys);
if($categorys && (count($categorys) > 0)){
    foreach($categorys as $category){
        $tmpModel = new ContractCategoryTmpModel();
        if($tmpModel->find($category['id'])){
            $tmpModel->updateCategory($category);
        }else{
            $tmpModel->insertCategory($category);
        }
    }
}
