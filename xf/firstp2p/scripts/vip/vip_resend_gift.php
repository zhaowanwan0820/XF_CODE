<?php
/**
  * vip礼包补发
  * 1.查询vipAccount表vip账户
  * 2.根据vip等级调用发礼包方法
  */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\vip\VipAccountModel;
use core\dao\vip\VipGiftLogModel;
use core\dao\vip\VipLogModel;
use core\service\vip\VipService;
use libs\utils\Logger;
use libs\utils\PaymentApi;

$vipSql = 'SELECT id, user_id, service_grade FROM firstp2p_vip_account WHERE service_grade>=1 order by id asc';
$vipdb = \libs\db\Db::getInstance('vip','master','utf8',1);
$result = $vipdb->query($vipSql);
$vipService = new VipService();
PaymentApi::log('vip礼包补发:sql:' . $vipSql);

$total=0;
while($result && ($data = $vipdb->fetchRow($result))) {
    try {
        if (!$vipService->checkMainSite($data['user_id'])) {
            PaymentApi::log('vip礼包检查非主站用户' . $data['user_id'].'|vipInfo|'.json_encode($data,JSON_UNESCAPED_UNICODE));
            continue;
        }
        $countSql = 'SELECT count(*) FROM firstp2p_vip_gift_log WHERE user_id='.$data['user_id'].' AND award_type=3';
        $count = VipGiftLogModel::instance()->countBySql($countSql);
        if ($count != $data['service_grade']) {
            $total++;
            $logSql = "SELECT id FROM firstp2p_vip_log WHERE user_id = {$data['user_id']} ORDER BY id DESC LIMIT 1";
            $vipPointLog = VipLogModel::instance()->findBySql($logSql);
            PaymentApi::log('vip礼包检查补发logId:' . $vipPointLog['id']."|userId|".$data['user_id']."|serviceGrade|".$data['service_grade']);
            $vipService->addUpgradeGiftLogAndSendGift($data['user_id'], $data['service_grade'], $vipPointLog['id']);
        } else {
            continue;
        }
    } catch (\Exception $e) {
        PaymentApi::log('vip礼包检查补发异常' .$e->getMessage());
        continue;
    }
}
PaymentApi::log('vip礼包检查结束total:'.$total);

