<?php
/**
 * @desc  掌众批量重新发起提现申请脚本
 * User: wangjiantong
 * Date: 2017/4/20 16:22
 */
require_once dirname(__FILE__).'/../app/init.php';


use core\service\UserCarryService;
use core\dao\DealModel;
use core\dao\UserCarryModel;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');


$idsStr = isset($argv[1]) ? $argv[1] : '';

if(empty($idsStr)){
    echo "未输入id!";
    exit();
}

$ids = explode(",",$idsStr);

$userCarryService = new UserCarryService();
$dealModel = new DealModel();
$dealLoanTypeModel = new DealLoanTypeModel();

foreach($ids as $id){
    $userCarry = UserCarryModel::instance()->find($id);
    if(empty($userCarry)){
        Logger::error($id.":未找到记录,无法重新发起提现!");
        echo $id.":未找到记录,无法重新发起提现!"."\n";
        continue;
    }

    //检查是否可重新申请提现
    $canRedo = $userCarryService->canRedoWithdraw($userCarry);
    if(empty($canRedo)){
        Logger::error($id.":检测是否可重新提现状态失败!");
        echo $id.":检测是否可重新提现状态失败!"."\n";
        continue;
    }

    // 复制原申请记录
    $userCarryData = $userCarry->getRow();
    $userCarryData['create_time'] = get_gmtime();
    $userCarryData['update_time'] = get_gmtime();
    $userCarryData['update_time_step1'] = get_gmtime();
    unset($userCarryData['id']);
    unset($userCarryData['desc']);
    unset($userCarryData['withdraw_status']);
    unset($userCarryData['withdraw_time']);
    unset($userCarryData['withdraw_msg']);

    // 审批状态
    $deal = $dealModel->find($userCarry['deal_id']);
    if (empty($deal)) {
        Logger::error($id.":未找到标的记录!");
        echo $id.":未找到标的记录!"."\n";
    }

    $loanTypeInfo = $dealLoanTypeModel->find($deal['type_id']);

    $userCarryData['status'] = 1; //进入财务审批

    // 保存
    $userCarryNew = new UserCarryModel();
    $userCarryNew->setRow($userCarryData);
    $rs = $userCarryNew->insert();
    if (!empty($rs)) {
        Logger::info("编号为:".$id."的提现申请重新发起成功!");
        echo "编号为:".$id."的提现申请重新发起成功!"."\n";
    } else {
        Logger::info("编号为:".$id."的提现申请重新发起失败!");
        echo "编号为:".$id."的提现申请重新发起失败!"."\n";
    }
}
