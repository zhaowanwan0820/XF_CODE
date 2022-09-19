<?php
/**
 * 代签合同
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\ContractService;

set_time_limit(0);
ini_set('memory_limit', '2014M');

$deal_id = 9927;
$borrow_userid = 36254;
$agency_userid = 5526;

//借款人代签
echo "\n借款人开始签署";
$contract_service = new ContractService();
$borrow_res = $contract_service->userSignDealConts($deal_id, $borrow_userid);
echo "\n借款人签署",$borrow_res['result'] ? '成功' : '失败';

//担保公司代签
/* echo "\n担保公司开始签署";
$user_service = new UserService();
$agency_info = $user_service->getUserAgencyInfo($agency_userid);
$agency_res = $contract_service->agencySignDealConts($deal_id, $agency_userid, $agency_info['agency_info']);
echo "\n担保公司签署",$agency_res['result'] ? '成功' : '失败'; */

//更新签署时间
$sql = "UPDATE `firstp2p_agency_contract` SET `create_time` = '%s' WHERE `deal_id` = '%d' AND `user_id` = '%d'";
$borrow_time_res = $GLOBALS['db']->query(sprintf($sql, '1419077445', $deal_id, $borrow_userid));
echo "\n借款人签署时间更新",$borrow_time_res ? '成功' : '失败';
$agency_time_res = $GLOBALS['db']->query(sprintf($sql, '1419225213', $deal_id, $agency_userid));
echo "\n担保公司签署时间更新",$agency_time_res ? '成功' : '失败';
