<?php
/**
 * 每日到期的利滚利还款计划定时还款
 */
require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");
use core\service\DealCompoundService;
use core\service\DealProjectService;
use core\dao\DealLoanRepayModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\JobsModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

class DealCompoundAutoRepay {

    public function run() {
        $jobs_model = new JobsModel();

        $today_repay_time = get_gmtime();
        //$sql = "SELECT `deal_id`, SUM(`money`) AS m FROM " . DealLoanRepayModel::instance()->tableName() . " WHERE `type` IN (8,9) AND `time`<='" . $today_repay_time . "' AND `time`!='0' AND `status`='0' GROUP BY `deal_id`";
        //$loan_repay_list = $GLOBALS['db']->get_slave()->getAll($sql);

        $sql = "SELECT `id` FROM " . DealModel::instance()->tableName() . " WHERE `deal_type` = '1' AND `deal_status` = '4'";
        $deal_list = $GLOBALS['db']->get_slave()->getAll($sql);

        foreach ($deal_list as $deal) {
            $sql = "SELECT `deal_id`, SUM(`money`) AS m FROM " . DealLoanRepayModel::instance()->tableName() . " WHERE `deal_id`='" . $deal['id'] . "' AND `type` IN (8,9) AND `time`<='" . $today_repay_time . "' AND `time`!='0' AND `status`='0'";
            $val = $GLOBALS['db']->get_slave()->getRow($sql);

            if (!$val['deal_id']) {
                continue;
            }

            $deal_id = $val['deal_id'];
            $money = $val['m'];
            $deal = DealModel::instance()->find($deal_id, '`id`,`user_id`,`is_during_repay`');
            if (!empty($deal) && $this->_check($deal, $money)) {
                $jobs_model->priority = 80;
                $rs = $jobs_model->addJob('\core\service\DealCompoundService::repayCompound', array('deal_id' => $deal_id, 'time' => $today_repay_time));
                if (empty($rs)) {
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "[{$deal_id}]添加任务失败")));
                } else {
                    $deal->is_during_repay = 1;
                    $rs = $deal->save();
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "[{$deal_id}]添加异步任务完毕", $rs)));
                }
            }
        }
    }

    /**
     * 检验标的状态及借款人余额是否足够还款
     */
    private function _check($deal, $money) {
        $log_info = array(__CLASS__, __FUNCTION__, APP, json_encode($deal->getRow()), $money);
        if (empty($deal) || empty($deal['id'])) {
            Logger::info(implode(" | ", array_merge($log_info, array('标的信息 不存在'))));
            return false;
        }
        if ($deal['is_during_repay'] == 1) {
            Logger::info(implode(" | ", array_merge($log_info, array('标的 状态为正在还款中'))));
            return false;
        }

        // 借款人信息
        $user = UserModel::instance()->find($deal['user_id'], 'id,money');
        $log_info[] = json_encode($user->getRow());
        if (empty($user)) {
            Logger::info(implode(" | ", array_merge($log_info, array('用户信息不存在'))));
            return false;
        }
        // 判断借款人余额是否足够还此项目
        if (bccomp($user['money'], $money, 2) != -1) {
            Logger::info(implode(" | ", array_merge($log_info, array('succ'))));
            return true;
        } else {
            Logger::info(implode(" | ", array_merge($log_info, array('fail', "剩余金额为 {$user['money']} 小于 {$money}"))));
            return false;
        }
    }
}

$obj = new DealCompoundAutoRepay();
$obj->run();
