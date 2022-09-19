<?php
/**
 * @desc  对于存管标的投资如果5分钟内未收到回调则取消投资
 * User: jinhaidong
 * Date: 2017-4-19 13:34:22
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\P2pDepositoryService;
use core\service\P2pIdempotentService;
use core\dao\UserModel;
use core\dao\DealModel;
use NCFGroup\Common\Library\Idworker;


class SupervisionBonusRepair {

    public function run($orderId,$dealId,$payerId,$receiverId,$payAmount) {
        $payerInfo = UserModel::instance()->findViaSlave($payerId);
        $payerInfo->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;

        $receiverInfo = UserModel::instance()->findViaSlave($receiverId);
        $receiverInfo->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;

        $deal = DealModel::instance()->findViaSlave($dealId);

        $payerType = app_conf('NEW_BONUS_TITLE') . '充值';
        $payerNote = $receiverId ."使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$deal['name']}";
        $receiverType = '充值';
        $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$deal['name']}";

        $transferService = new core\service\TransferService();
        $tranRes = $transferService->transferByUser($payerInfo, $receiverInfo, $payAmount, $payerType, $payerNote, $receiverType, $receiverNote,$orderId);
        var_dump($tranRes);
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$orderId =      isset($argv[1]) ? $argv[1] : '';
$dealId =       isset($argv[2]) ? $argv[2] : '';
$payerId =      isset($argv[3]) ? $argv[3] : '';
$receiverId =   isset($argv[4]) ? $argv[4] : '';
$payAmount =    isset($argv[5]) ? $argv[5] : '';

$obj = new SupervisionBonusRepair();
$obj->run($orderId,$dealId,$payerId,$receiverId,$payAmount);
