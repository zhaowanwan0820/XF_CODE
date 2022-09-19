<?php

//crontab: 0 6 * * *  cd /apps/product/nginx/htdocs/firstp2p/scripts && nohup /apps/product/php/bin/php discount_everyday.php

/**
 * 每日计算平台余额贴息
 */

require_once(dirname(__FILE__) . '/../app/init.php');

use app\models\service\Discount;
use app\models\dao\User;

class DepositInterestEveryday {
	public function run() {
		$discount = new Discount();

		//获取余额贴息利率
		$rate = app_conf('DEPOSIT_INTEREST_RATE');

		//贴息是否需要经过后台确认
		$is_auto = app_conf('IS_DEPOSIT_INTEREST_AUTO_PAID');

		//获取两天之前的所有用户余额
		$time0 = strtotime(date("Y-m-d")) - 3600 * 24 * 2 - 3600 * 8;
		$user_list = $discount->getUserListByDate($time0);
		$time1 = strtotime(date("Y-m-d")) - 3600 * 24 - 3600 * 8;

		foreach ($user_list as $val) {
			if ($val['money'] <=0 ) {
				continue;
			}
			$user_id = $val['user_id'];
			$min_userlog = $discount->getMinMoneyByDate($user_id, $time1);
			if ($min_userlog === false) {
				$principal = $val['money'];
			} else {
				$principal = $val['money'] < $min_userlog ? $val['money'] : $min_userlog ;
			}
			$interest = number_format($principal * $rate / 100 / 360, 2);

			//如果贴息未超过1分钱则不进行处理
			if ($interest >= 0.01) {
				$id = $discount->saveDiscount($user_id, $rate, $principal, $interest, 1);

				//如果不需后台确认，则直接将贴息增加到账户余额
				if ($is_auto) {
					$discount->pay($id, 1);
					/*
					$user_model = new User();
					$user = $user_model->find($user_id);
					$user->changeMoney($interest, "账户余额贴息", "");

					$user_consult = $user_model->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));
					$user_consult->changeMoney(-$interest, "账户余额贴息", "用户名称:{$user['name']} 贴息前账户余额" . number_format($user['money'], 2) . "元");
					*/
				}
			}
		}
	}
}

$switcher = app_conf("TURN_ON_DEPOSIT_INTEREST_REMAINING");
if ($switcher == true) {
	$die = new DepositInterestEveryday();
	$die->run();
}
