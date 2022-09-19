<?php

//crontab: 0 6 * * *  cd /apps/product/nginx/htdocs/firstp2p/scripts && nohup /apps/product/php/bin/php discount_deal_loan.php

/**
 * 每日计算平台余额贴息
 */

require_once(dirname(__FILE__) . '/../app/init.php');

use app\models\service\Discount;
use app\models\dao\User;
use app\models\dao\Deal;
use app\models\dao\DealLoad;

class DepositInterestDealLoan {
	public function run() {
		$discount = new Discount();
		$deal_loan_model = new DealLoad();
		$time_today = strtotime(date("Y-m-d")) - 3600 * 8;
		$time_start = $time_today - 3600 * 24;
		$agency_uids = get_agency_user_ids();

		//贴息是否需要经过后台确认
		$is_auto = app_conf('IS_LOAN_DEPOSIT_INTEREST_AUTO_PAID');

		$deal_model = new Deal();
		// 筛选条件：不为母表 并且 (标的状态为等待确认（预约投标）、投标中、满标 或 本日内放款或流标)
		$deal_list = $deal_model->findAll("`parent_id`!='0' AND `is_delete`='0' AND (`deal_status` IN (0,1,2) OR `repay_start_time`>='{$time_today}' OR (`deal_status`='3' AND `update_time`>='{$time_today}'))");
		foreach ($deal_list as $deal) {
			$rate = $deal['income_fee_rate'];
			$deal_loan = $deal_loan_model->getDealLoanList($deal['id']);

			foreach ($deal_loan as $val) {
				if ($val['create_time'] > $time_start) {
					continue;
				}

				$user_id = $val['user_id'];
				if ($user_id == app_conf('DEAL_CONSULT_FEE_USER_ID') || in_array($user_id, $agency_uids)) {
					continue;
				}

				$principal = $val['money'];
				$interest = number_format($principal * $rate / 100 / 360, 2);

				//如果贴息未超过1分钱则不进行处理
				if ($interest >= 0.01) {
					$id = $discount->saveDiscount($user_id, $rate, $principal, $interest, 2, $val['id']);

					//如果不需后台确认，则直接将贴息增加到账户余额
					if ($is_auto) {
						$discount->pay($id, 1);
						/*
						$user_model = new User();
						$user = $user_model->find($user_id);
						$user->changeMoney($interest, "投资贴息", "编号:{$val['deal_id']} {$val['name']} 投资记录ID{$val['id']}");

						$user_consult = $user_model->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));
						$user_consult->changeMoney(-$interest, "投资贴息", "编号{$val['deal_id']} {$val['name']} 投资记录ID{$val['id']} 用户ID{$user_id} 投资金额{$principal}元");
						*/
					}
				}
			}
		}
	}
}

$switcher = app_conf("TURN_ON_DEPOSIT_INTEREST_DEAL_LOAN");
if ($switcher == true) {
	$didl = new DepositInterestDealLoan();
	$didl->run();
}

