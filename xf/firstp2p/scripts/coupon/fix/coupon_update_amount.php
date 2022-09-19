<?php
/**
 * coupon_update_amount.php
 *
 * 修复0.56系数需求时，后台编辑机构比例，相应金额没有变化的问题。机构返利月底白泽结算，三天前产生及时修复没用影响。
 *
 *
 * @date 2016-03-07
 * @author liangqiang <liangqiang@ucfgroup.com>
 */


require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\Logger;

set_time_limit(0);

class CouponUpdateAmount {

    public function run() {
        $log_info = array(__CLASS__, __FUNCTION__);
        //dealid参数传入
        $params = getopt("d:");
        $deal_id = isset($params['d']) && intval($params['d']) ? intval($params['d']) : 0;
        if (!empty($deal_id)) {
            $list = array($deal_id);
        } else { //线上错误数据的标
            $list = array('94164', '94974', '94976', '94984', '95197', '95368', '95441', '95442', '95442', '95445', '95477', '95479', '95492', '95504', '95516',
                          '95541', '95542', '95544', '95574', '95575', '95576', '95577', '95578', '95579', '95580', '95581', '95592', '95593', '95594', '95600',
                          '95601', '95602', '95603', '95604', '95604', '95605', '95606', '95607', '95610', '95611', '95612', '95613', '95614', '95615', '95616',
                          '95617', '95618', '95619', '95628', '95629', '95630', '95631', '95632', '95633', '95634', '95635', '95636', '95637', '95644', '95646',
                          '95647', '95648', '95649', '95650', '95651', '95652', '95653', '95654', '95655', '95665', '95666', '95668', '95669', '95670', '95671',
                          '95672', '95673', '95674', '95675', '95676', '95677', '95678', '95679', '95682', '95683', '95684', '95685', '95685', '95686', '95687',
                          '95688', '95689', '95690', '95691', '95692', '95693', '95694', '95695', '95696', '95697', '95698', '95699', '95700', '95701', '95712',
                          '95713', '95714', '95715', '95716', '95717', '95718', '95719', '95720', '95721', '95722', '95724', '95725', '95742', '95743', '95744',
                          '95745', '95746', '95747', '95748', '95749', '95276');
        }
        Logger::info(implode(" | ", array_merge($log_info, array('deal_ids:', json_encode($list)))));

        $coupon_log_model = new \core\dao\CouponLogModel();
        $coupon_log_service = new \core\service\CouponLogService();
        $fail_ids = array();
        $success_dealids = array();
        foreach ($list as $deal_id) {
            $logs = $coupon_log_model->findByDealId($deal_id);
            if (empty($logs)) {
                Logger::info(implode(" | ", array_merge($log_info, array($deal_id, 'empty deal list'))));
                continue;
            }
            foreach ($logs as $item) {
                $rs = $coupon_log_service->updateAmount($item['id']);
                Logger::info(implode(" | ", array_merge($log_info, array($deal_id, $item['id'], 'update', $rs))));
                if (empty($rs)) {
                    $fail_ids[$deal_id][] = $item['id'];
                } else {
                    $success_dealids[$deal_id] = isset($success_dealids[$deal_id]) ? $success_dealids[$deal_id] + 1 : 1;
                }
            }

        }
        Logger::info(implode(" | ", array_merge($log_info, array('done', 'fail:', json_encode($fail_ids)))));
        Logger::info(implode(" | ", array_merge($log_info, array('done', 'success:', json_encode(count($success_dealids))))));
    }

}


$service = new CouponUpdateAmount();
$service->run();
