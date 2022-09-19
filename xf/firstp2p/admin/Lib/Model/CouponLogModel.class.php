<?php
/**
 * CouponLogModel.class.php
 *
 * @date 2014-03-08 20:31
 * @author liangqiang@ucfgroup.com
 */

use core\service\CouponService;

class CouponLogModel extends CommonModel {

    protected $_validate = array(
        array('short_alias', 'require', '优惠码必填！'),
        array('refer_user_id', 'require', '推荐会员ID必填！', '', '', self::MODEL_UPDATE),
        array('agency_user_id', 'require', '机构会员ID必填！', '', '', self::MODEL_UPDATE),
        //array('referer_rebate_ratio_factor', 'require', '返利系数必填！', '', '', self::MODEL_UPDATE),
        array('refer_user_id', 'number', '推荐会员ID必须是数字！', self::VALUE_VAILIDATE),
        array('agency_user_id', 'number', '机构会员ID必须是数字！', self::VALUE_VAILIDATE),
        array('rebate_amount', 'number', '返点金额必须是数字！', self::VALUE_VAILIDATE),
        array('rebate_ratio', 'number', '返点比例必须是数字！', self::VALUE_VAILIDATE),
        array('referer_rebate_amount', 'number', '推荐人返点金额必须是数字！', self::VALUE_VAILIDATE),
        array('referer_rebate_ratio', 'number', '推荐人返点比例必须是数字！', self::VALUE_VAILIDATE),
        array('agency_rebate_amount', 'number', '机构返点金额必须是数字！', self::VALUE_VAILIDATE),
        array('agency_rebate_ratio', 'number', '机构返点比例必须是数字！', self::VALUE_VAILIDATE),
        array('short_alias', 'check_short_alias', '优惠码不正确，或者与推荐人及机构不一致', 0, 'callback', self::MODEL_BOTH),
        array('refer_user_id', 'check_refer_user_id', '推荐会员ID不正确', self::VALUE_VAILIDATE, 'callback',
              self::MODEL_BOTH),
        array('agency_user_id', 'check_agency_user_id', '机构会员ID不正确', self::VALUE_VAILIDATE, 'callback',
              self::MODEL_BOTH),
        //array('referer_rebate_ratio_factor', 'check_factor', '返利系数不正确', self::VALUE_VAILIDATE, 'callback',
        //      self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('refer_user_id', 'intval', 3, 'function'),
        array('agency_user_id', 'intval', 3, 'function'),
        array('short_alias', 'trim', 3, 'function'),
        array('short_alias', 'strtoupper', 3, 'function'),
    );

    /**
     * 校验优惠码及对应关系
     */
    protected function check_short_alias() {
        $short_alias = $_REQUEST['short_alias'];
        if (empty($short_alias)) {
            return false;
        }
        if ($short_alias == CouponService::SHORT_ALIAS_DEFAULT) {
            return true;
        }

        //如果只修改比例金额，则不校验推荐人机构及优惠码(因为优惠码可能会因为推荐人改组而变为无效)
        if (!empty($_REQUEST['id'])) {
            $data_origin = M('CouponLog')->where("id=" . intval($_REQUEST['id']))->find();
            if ($data_origin['refer_user_id'] == $_REQUEST['refer_user_id'] && $data_origin['agency_user_id'] == $_REQUEST['agency_user_id'] && $data_origin['short_alias'] == $short_alias) {
                return true;
            }
        }

        $coupon_service = new core\service\CouponService();
        $coupon = $coupon_service->checkCoupon($short_alias);
        if (empty($coupon)) {
            return false; //优惠码不正确
        }

        // 修改或者新增时填了ID需要校验
        if (!empty($_REQUEST['id']) || !empty($_REQUEST['refer_user_id']) || !empty($_REQUEST['agency_user_id'])) {
            return $coupon['refer_user_id'] == $_REQUEST['refer_user_id'] && $coupon['agency_user_id'] == $_REQUEST['agency_user_id']; //推荐人或机构对应关系需要一致
        } else {
            return true;
        }
    }

    /**
     * 校验推荐人ID，不填则以优惠码对应值为准
     */
    protected function check_refer_user_id() {
        $refer_user_id = intval($_REQUEST['refer_user_id']);
        if (!empty($refer_user_id)) {
            $user_info = get_user_info($refer_user_id, true);
            if (empty($user_info) || $user_info['is_effect'] == 0 || $user_info['is_delete'] == 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * 校验机构ID，不填则以优惠码对应值为准
     */
    protected function check_agency_user_id() {
        $agency_user_id = intval($_REQUEST['agency_user_id']);
        if (!empty($agency_user_id)) {
            $user_info = get_user_info($agency_user_id, true);
            if (empty($user_info) || $user_info['is_effect'] == 0 || $user_info['is_delete'] == 1) {
                return false;
            }
        }
        return true;
    }

//    protected function check_factor() {
//        $factor = trim($_REQUEST['referer_rebate_ratio_factor']);
//        //特殊优惠券处理  --增加特殊机构优惠券结算,需要有用户相关信息 20140507
//        /*$short_alias = $_REQUEST['short_alias'];
//        $coupon_service = new core\service\CouponService();
//        $couponPrefixChecked = $coupon_service->checkSpecialCoupon($short_alias);
//        if ($couponPrefixChecked) {
//            return true;
//        }*/
//        return (!empty($factor) && is_numeric($factor));
//    }

}
