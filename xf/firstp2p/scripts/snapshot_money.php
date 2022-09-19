<?php

//crontab: 0 0 * * *  cd /apps/product/nginx/htdocs/firstp2p/scripts && nohup /apps/product/php/bin/php snapshot_money.php

/**
 * 每日记录用户资金快照，以备余额贴息使用
 */

require_once(dirname(__FILE__) . '/../app/init.php');

use app\models\service\Discount;

class SnapShotMoney {
	public function run() {
		$arr_agency_uids = get_agency_user_ids();
		$agency_uids = implode(",", $arr_agency_uids);
		$user_money = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "user WHERE `id`!='" . app_conf('DEAL_CONSULT_FEE_USER_ID') . "' AND `id` NOT IN ({$agency_uids}) AND `is_effect`='1' AND `is_delete`='0'");

		$discount = new Discount();
		$time = get_gmtime();
		foreach ($user_money as $val) {
			$discount->saveMoneySnapshot($val['id'], $val['money'], $time);
		}
	}
}

$ssm = new SnapShotMoney();
$ssm->run();
