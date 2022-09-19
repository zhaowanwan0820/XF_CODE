<?php
/**
 * @desc 独立脚本，修复交易所的担保和咨询机构的签署记录
 * user: duxuefeng
 * date: 2018年03月13日
 */
error_reporting(E_ALL ^ E_NOTICE);//显示除去 E_NOTICE 之外的所有错误信息
ini_set("display_errors", 1);
set_time_limit(0);
ini_set('memory_limit', '2048M');
require_once dirname(__FILE__).'/../../app/init.php';

use core\service\UserService;
use core\dao\DealAgencyModel;
use core\dao\DealModel;
use libs\utils\Logger;
use \libs\Db\Db;

// 0 读输入参数
if(empty($argv[1])){
    echo "请输入dealId\n";
    exit(0);
}
// 参数使用逗号分隔
$dealIds = explode(",", $argv[1]);
if(!is_array($dealIds)){
    echo "dealId请用半角逗号分隔,argv:{$argv[1]}\n";
    exit(0);
}
$selectSql = " SELECT * FROM `firstp2p_deal_contract` WHERE `deal_id` = '%d' ";
$user_service = new UserService();
foreach($dealIds as $dealId){
    // 1 找到对应的deal_contract记录，foreach获取其中的deal_id匹配，user_id  = 0 的记录，获取其中的agency_id和sign_time
    if(!is_numeric($dealId)){
        echo "dealId不为int dealId:{$dealId}\n";
        continue;
    }
    $sql = sprintf($selectSql, $dealId);
    $dealContracts = Db::getInstance('firstp2p')->getAll($sql);
    $deal = DealModel::instance()->find($dealId);
    $updateData = array();
    if(!$dealContracts){
        Logger::error(implode(" | ", array(__FILE__,__LINE__, "获取deal_contract失败，dealId:".$dealId)));
        echo implode(" | ", array(__FILE__,__LINE__, "获取deal_contract失败，dealId:".$dealId)) . "\n";
        continue;
    }
    foreach($dealContracts as $dealContract){
        //1.1 判断用户是否为担保，咨询，借款人
        $is_agency = $deal['agency_id'] == $dealContract['agency_id'] ? 1 : 0;
        $is_advisory = $deal['advisory_id'] == $dealContract['agency_id'] ? 1 : 0;;
        $is_borrow = $deal['user_id'] == $dealContract['user_id'] ? 1 : 0;;

        // if($dealAgency['type'] == DealAgencyModel::TYPE_GUARANTEE){
        if($is_agency == 1){
            $updateData['agency_id'] = $dealContract['agency_id'];
            $updateData['agency_sign_time'] = $dealContract['sign_time'];
        }
        // if($dealAgency['type'] == DealAgencyModel::TYPE_CONSULT){
        if($is_advisory == 1){
            $updateData['advisory_id'] = $dealContract['agency_id'];
            $updateData['advisory_sign_time'] = $dealContract['sign_time'];
        }
        if($is_borrow == 1){
            $updateData['borrow_user_id'] = $dealContract['user_id'];
            $updateData['borrower_sign_time'] = $dealContract['sign_time'];
        }

    }
    if(empty($updateData) || count($updateData) != 6){
        Logger::error(implode(" | ", array(__FILE__,__LINE__, "contract更新数据为空或者更新数据错误，dealId:".$dealId)));
        echo implode(" | ", array(__FILE__,__LINE__, "contract更新数据为空或者更新数据错误，dealId:".$dealId)) . "\n";
        continue;
    }

    // 2 找到对应的合同库中的contract表
    // 2.1 根据dealId和tpl_identifier_id获取合同分表
    // 2.2 update 该条记录的agency_id,agency_sign_time,advisory_id,advisory_sign_time,borrow_user_id和borrower_sign_time
    $contractName = sprintf("contract_%d", ($dealId%128));
    $updateSql = "UPDATE `{$contractName}` SET `agency_id` = '{$updateData['agency_id']}', `agency_sign_time` = '{$updateData['agency_sign_time']}', `advisory_id` = '{$updateData['advisory_id']}', `advisory_sign_time` = '{$updateData['advisory_sign_time']}', `borrow_user_id` = '{$updateData['borrow_user_id']}', `borrower_sign_time` = '{$updateData['borrower_sign_time']}'  WHERE`deal_id` = '{$dealId}' AND `tpl_identifier_id` = '27'; ";
    echo $updateSql . "\n";
}





