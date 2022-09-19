<?php
set_time_limit(0);
ini_set('memory_limit','1024M');

require_once dirname(__FILE__)."/../app/init.php";
use core\service\mq\MqService;
use core\dao\JobsModel;

/**
 * 业财系统数据上报
 */
if (!isset($argv[1])) {
    exit("请指定执行的命令参数\n 1 放款 \n 2 还款 \n 3 提前还款 \n 4 部分提前还款");
}
if (!isset($argv[2])) {
    exit("请正确传递上报参数");
}

$type = intval($argv[1]);
$id = intval($argv[2]);
$mqService = new MqService();
switch ($type) {
    case 1 :
        $param['dealId'] = $id; //标的id 7148
        $mqService->loan($param);//放款
        break;
    case 2 :
        $param['repayId'] = $id;//2 9096
        $mqService->repay($param);//还款
        break;
    case 3 :
        $param['prepayId'] = $id;
        $mqService->prepay($param);//提前还款
        break;
    case 4 :
        $param['prepayId'] = $id;
        $mqService->partPrepay($param);//部分提前还款
        break;
    default :
        echo '参数错误！';
        break;
}