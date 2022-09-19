<?php
/**
 * 特斯拉活动页面
 *
 * @date 2014-09-14
 */

namespace web\controllers\event;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\CouponService;

class Promotion extends BaseAction {

    public function init() {

        $this->form = new Form();
        $this->form->rules = array(
                'cn' => array('filter' => 'string'),
                'ref_code'=>array("filter"=>'string'),
        );  
        $this->form->validate();
    }   

    public function invoke() {

        $data = $this->form->data;
        $ref_code = trim($data['ref_code']); 
        $cn = trim($data['cn']);
        $link_coupon = $ref_code?$ref_code:$cn;

        $validGroups = array(
            11,12,13,14,16,24,25,26,27        
        );

        //baize 统计 pcon参数
        $pcon = '';

        $coupon = $this->rpc->local('CouponService\checkCoupon', array($link_coupon));
        //判断有效性
        if(isset($coupon['group_id']) && in_array($coupon['group_id'], $validGroups)){
            $pcon = sprintf("&_method=%s&salias=%s", $_SERVER['REQUEST_METHOD'], $link_coupon);
        }

        $this->tpl->assign('ref_code', $link_coupon);
        $this->tpl->assign('pcon', $pcon);
    }

}
