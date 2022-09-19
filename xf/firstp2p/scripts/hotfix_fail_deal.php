<?php
/**
 * 修复流标未成功的数据
 * @date 2015-03-13
 */
require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\service\DealProjectService;
use core\service\CouponService;

error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($deal_id <= 0) {
    exit('id错误');
}

$deal_dao = new DealModel();
$deal = $deal_dao->find($deal_id);
if (empty($deal)) {
    exit('id错误。');
}

$GLOBALS['db']->startTrans();
try {
    // 先修改订单状态
    $deal->deal_status = 3;
    $bad_time = $deal['start_time'] + $deal['enddate'] * 24 * 3600;
    if ($deal['bad_time'] != $bad_time) {
        $deal->bad_time = $bad_time;
    }
    if ($deal['is_send_bad_msg'] == 0) {
        $deal->is_send_bad_msg = 1;
    }
    if ($deal->save() === false) {
        throw new \Exception("fail deal error");
    }

    // 流标向用户返还金额
    $load_list = DealLoadModel::instance()->findAll("`is_repay`='0' AND `deal_id`='{$deal_id}' AND `from_deal_id`='0'");

    if ($load_list) {
        $user_dao = new UserModel();
        foreach ($load_list as $v) {
            $user_info = $user_dao->find($v['user_id']);
            if (!$user_info) {
                continue;
            }
            $note = '编号' . $deal['id'] . ' ' . $deal['name'] . '，单号' . $v['id'];
            $change_money = $user_info->changeMoney(- $v['money'], "取消投标", $note, 0, 0, 1);
            if(!$change_money){
                throw new \Exception("changeMoney error 用户id:".$v['user_id']);
            }

            //将投资记录设置为已还
            $deal_loan = DealLoadModel::instance()->find($v['id']);
            $deal_loan->is_repay = 1;
            $save_loan = $deal_loan->save();

            if(!$save_loan){
                throw new \Exception("save deal_loan error 用户id:".$v['user_id']);
            }
        }
    }

    $cont_service = new \core\service\ContractService();
    $cont_service->delContByDeal($deal_id);

    $deal_pro_service = new DealProjectService();
    if ($deal['project_id'] > 0) {
        $deal_pro_service->updateProBorrowed($deal['project_id']);
        $deal_pro_service->updateProLoaned($deal['project_id']);
    }

    $coupon = new CouponService();
    $coupon->updateLogStatusByDealId($deal_id, 2);

    $GLOBALS['db']->commit();
    echo '操作成功！';
    return true;
} catch (\Exception $e) {
    $GLOBALS['db']->rollback();
    echo '操作失败！',$e->getMessage();
    return false;
}

