<?php
/**
 * 首尾标中奖页面
 *
 * @date 2014-10-14
 */

namespace web\controllers\event;

use web\controllers\BaseAction;

class Inclusive extends BaseAction {

   
    const MAX_INCLUSIVE_TYPE_LIST = 50; // 获取首尾活动时中奖名单最大数
    
    public function init() {

    }

    public function invoke() {

        // 首标
        $firstList = $this->rpc->local('CouponLogService\getExtraLogListByType',array(11,0,self::MAX_INCLUSIVE_TYPE_LIST));
        $firstList = $this->formateData($firstList);

        // 最后一笔
        $lastList = $this->rpc->local('CouponLogService\getExtraLogListByType',array(12,0,self::MAX_INCLUSIVE_TYPE_LIST));
        $lastList = $this->formateData($lastList);

        // 最大金额
        $maxAmountList = $this->rpc->local('CouponLogService\getExtraLogListByType',array(13,0,self::MAX_INCLUSIVE_TYPE_LIST));
        $maxAmountList = $this->formateData($maxAmountList);

        $user_id = !empty($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : '';
        if (!empty($user_id)) {
            $coupon = $this->rpc->local('CouponService\getOneUserCoupon', array($user_id));
            $coupon = $coupon['short_alias'];
        }

        $this->tpl->assign('coupon', $coupon);
        $this->tpl->assign('firstList',$firstList);
        $this->tpl->assign('lastList',$lastList);
        $this->tpl->assign('maxAmountList',$maxAmountList);
    }

    /**
     * 格式化数据
     * @param array $list
     * @return array
     */
    private function formateData($list){
        if (!is_array($list)){
            return array();
        }
        $type_text = array(
        	          11 => '一触即发',
                      12 => '一气呵成',
                      13 => '首屈一指',
                    );
        foreach ($list as $key => $v){
            $list[$key]['deal_name'] = msubstr($v['deal_name'],0,11); // 投标名称
            //投资人名称
            $list[$key]['consume_user_name'] = user_name_format($v['consume_user_name']);
            //投资人返点金额
            if (!empty($v['rebate_amount'])){
                $list[$key]['rebate_amount'] = format_price($v['rebate_amount']);
            }else{
                $list[$key]['rebate_amount'] = 0;
            }
            //投资人返点比例
            if (!empty($v['rebate_ratio'])){
                $list[$key]['rebate_ratio'] = format_rate_for_show($v['rebate_ratio']);
            }else{
                $list[$key]['rebate_ratio'] = 0;
            }
            $list[$key]['type_text'] = $type_text[$v['type']];
        }

        return $list;
    }

}
