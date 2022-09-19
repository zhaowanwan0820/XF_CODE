<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/11/23
 * Time: 下午6:42
 */

require_once dirname(__FILE__) . '/../app/init.php';

use core\service\DealProjectService;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$project_id = isset($argv[1]) ? intval($argv[1]) : 0;
if($project_id === 0){
    echo "未输入项目ID";
    exit();
}

$deal_pro_service = new DealProjectService();
$res = $deal_pro_service->updateProLoaned($project_id);

if($res){
    echo "success !";
}else{
    echo "fail !";
}
