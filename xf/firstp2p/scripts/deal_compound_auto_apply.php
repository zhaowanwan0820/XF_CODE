<?php
/**
 * 每日到期的利滚利项目动发起赎回申请
 */
require_once dirname(__FILE__).'/../app/init.php';
// 导入rpc配置
require_once(dirname(__FILE__). '/../libs/utils/PhalconRPCInject.php');
\libs\utils\PhalconRPCInject::init();
\FP::import("libs.utils.logger");

use core\service\DealCompoundService;
use core\dao\DealModel;
use core\dao\CompoundRedemptionApplyModel;
use core\dao\DealLoadModel;
use core\service\DealService;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);

set_time_limit(0);
ini_set('memory_limit', '1024M');

class DealCompoundAutoApply {
    public function run() {
        // 首先获取所有还款中的利滚利标
        $arr_deals = DealModel::instance()->findAll("`deal_type`='1' AND `deal_status`='4'", true, 'id');
        // 没有标的则直接返回true
        if (!$arr_deals) {
            return true;
        }

        $deal_service = new DealService();
        $deal_compound_service = new DealCompoundService();
        foreach ($arr_deals as $val) {
            $deal_id = $val['id'];
            // 获取单个标的最后申请时间，判断是否超过最后申请时间
            $last_apply_time = $deal_compound_service->getLastApplyDay($deal_id);
            if (get_gmtime() < $last_apply_time) {
                continue;
            }
            //$deal_compound_info = $deal_compound_service->getDealCompound($deal_id);
            //$repay_time = $last_apply_time + $deal_compound_info['redemption_period'] * 86400;

            // 找出该标的未提出申请的投资
            $arr_deal_load = DealLoadModel::instance()->findAllViaSlave("`deal_id`='{$deal_id}' AND `id` NOT IN (SELECT `deal_load_id` FROM " . CompoundRedemptionApplyModel::instance()->tableName() . " WHERE `deal_id`='{$deal_id}')", true, '*');

            foreach ($arr_deal_load as $item) {
                $deal_load_id = $item['id'];
                $user_id = $item['user_id'];
                // 自动发起申请
                $res = $deal_compound_service->redeem($deal_load_id, $user_id);
                if($res){
                    $digObject = new \core\service\DigService('redeem', array(
                        'id' => $user_id,
                        'cn' => $item['short_alias'],
                        'loadId' => $deal_load_id,
                        'money' => $item['money'],
                    ));
                    $prizelist = $digObject->getResult();
                    if (empty($prizelist)) {
                        //生成红包
                        $period_day = $deal_compound_service->getPeriodDay($deal_load_id);
                        if($period_day !== false && $period_day >= intval(app_conf('BONUS_LGL_SEND_LIMIT_DAYS'))){
                            $deal_service->makeBonus($item['deal_id'], $deal_load_id, $item['user_id'], $item['money']);
                        }
                    }
                }
            }
        }
    }
}

$obj = new DealCompoundAutoApply();
$obj->run();
