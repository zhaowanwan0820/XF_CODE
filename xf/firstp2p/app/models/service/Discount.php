<?php
/**
 * Discount class file.
 * @author wangyiming@ucfgroup.com
 **/

namespace app\models\service;

use app\models\dao\Deal;
use app\models\dao\DealLoad;
use app\models\dao\DepositInterestRecord;
use app\models\dao\MoneySnapshot;
use app\models\dao\User;
use app\models\dao\UserLog;

/**
 * 平台贴息相关类
 * @author wangyiming@ucfgroup.com
 **/
class Discount {

    /**
     * 贴息类型
     */
    public static $type_list = array('1' => '账户余额贴息', '2' => '投标贴息');

    /**
     * 确认状态
     */
    public static $status_list = array('0' => '未确认', '1' => '自动确认', '2' => '手工确认', '3' => '放弃确认');

	/**
	 * 保存用户当日余额
	 * @param $user_id int
	 * @param $money float
	 * @param $create_time int
	 * @return boolean
	 */
	public function saveMoneySnapshot($user_id, $money, $create_time) {
		$money_snapshot = new MoneySnapshot();
		$money_snapshot->user_id = $user_id;
		$money_snapshot->time = strtotime(date("Y-m-d")) - 3600*24 - 3600*8;
		$money_snapshot->money = $money;
		$money_snapshot->create_time = $create_time;

		if (!$money_snapshot->save()) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 获取制定日期所有用户余额快照
	 * @param $time int
	 * @return array
	 */
	public function getUserListByDate($time) {
		$money_snapshot = new MoneySnapshot();
		return $money_snapshot->findAllBySql("SELECT `user_id`, MIN(`money`) AS `money` FROM " . $money_snapshot->tableName() . " WHERE `time`='{$time}' GROUP BY `user_id`");
	}

	/**
	 * 获取当日制定用户资金记录中余额最小值
	 * @param $user_id int
	 * @param $time int
	 * @return float
	 */
	public function getMinMoneyByDate($user_id, $time) {
		$time_end = $time + 3600 * 24;
		$user_log = new UserLog();
		$result = $user_log->findBySql("SELECT `user_id`, MIN(`remaining_money`) AS `min` FROM " . $user_log->tableName() . " WHERE `user_id`='{$user_id}' AND is_delete = 0 AND `log_time`>'{$time}' AND `log_time`<'{$time_end}'");
		if ($result['user_id']) {
			return $result['min'];
		} else {
			return false;
		}
	}

	/**
	 * 保存贴息记录数据
	 * @param $user_id int
	 * @param $rate float
	 * @param $money float
	 * @param $interest float
	 * @param $type int 1-余额贴息 2-投资贴息
	 * @param $need_confirm boolean 是否需要后台确认
	 * @param $deal_loan_id int 投资id 0-余额贴息
	 * @return boolean
	 */
	public function saveDiscount($user_id, $rate, $money, $interest, $type, $deal_loan_id=0) {
		$discount_charge = new DepositInterestRecord();
		$discount_charge->user_id = $user_id;
		$discount_charge->time = strtotime(date("Y-m-d")) - 3600 * 24 ;
		$discount_charge->money = $money;
		$discount_charge->rate = $rate;
		$discount_charge->interest = $interest;
		$discount_charge->type = $type;
		$discount_charge->status = 0;
		$discount_charge->create_time = get_gmtime();
		$discount_charge->confirm_time = 0;
		$discount_charge->deal_loan_id = $deal_loan_id;

		if (!$discount_charge->save()) {
			return false;
		} else {
			return $discount_charge->id;
		}
	}

    /**
     * 确认贴息
     *
     * @param $ids int or array 贴息记录id, 支持单个id或id数组
     * @param $status 确认状态 0-未确认 1-自动确认 2-管理员确认 3-放弃确认 默认自动确认
     * @return boolean
     */
    public function pay($ids, $status = 1) {
        if (empty($ids)) {
            return false;
        }
        $ids = is_array($ids) ? $ids : array($ids);

        $user_platform = User::instance()->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));
        $admin_id = 0;
        if (defined("ADMIN_ROOT")) {
            $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
            $admin_id = !empty($adm_session) ? $adm_session['adm_id'] : 0;
        }
        $deposit_interest_record_dao = new DepositInterestRecord;
        $user_dao = new User;
        $deal_dao = new Deal;
        $deal_loan_dao = new DealLoad;

        foreach ($ids as $id) {
            // 获取贴息记录
            $item = $deposit_interest_record_dao->find($id);
            // 已经确认过的记录不处理
            if ($item->status != 0) {
                return false;
            }
            // 获取贴息会员记录
            $user = $user_dao->find($item->user_id);
            if (empty($user)) {
                return false;
            }

            // 更新贴息记录状态
            $item->status = $status;
            $item->confirm_time = get_gmtime();
            $item->admin_id = $admin_id;
            $item->save();

            // 日志
            if ($item->type == 1) {
                $note_user = "";
                $note_platform = "会员名称{$user->user_name} 贴息前账户余额{$item->money}元";
            } else {
                $deal_loan = $deal_loan_dao->find($item->deal_loan_id);
                $deal_info = $deal_dao->find($deal_loan->deal_id);
                $note_user = "编号:{$deal_info['id']} {$deal_info['name']} 投资记录ID:{$item->deal_loan_id}";
                $note_platform = "{$note_user} 会员名称{$user->user_name} 投标金额{$item->money}元";
            }

            // 资金转入
	     // TODO finance? 平台贴息 用户获得利息
            $user->changeMoney($item->interest, self::$type_list[$item->type], $note_user, $admin_id);
	      // TODO finance? 资金转入 平台扣减利息
            $user_platform->changeMoney(-$item->interest, self::$type_list[$item->type], $note_platform, $admin_id);
        }
    }
}
