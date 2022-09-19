<?php
/**
 * CouponSpecialModel.class.php
 *
 * @date 2014-05-31
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class CouponSpecialModel extends CommonModel {

    protected $_validate = array(
        array('short_alias', 'require', '优惠码必填！'),
        array('rebate_amount', 'require', '返点金额必填！'),
        array('rebate_ratio', 'require', '返点比例必填！'),
        array('referer_rebate_amount', 'require', '推荐人返点金额必填！'),
        array('referer_rebate_ratio', 'require', '推荐人返点比例必填！'),
        array('agency_rebate_amount', 'require', '机构返点金额必填！'),
        array('agency_rebate_ratio', 'require', '机构返点比例必填！'),
        array('refer_user_id', 'number', '推荐人会员ID必须是数字！', self::VALUE_VAILIDATE),
        array('fixed_days', 'number', '绑定天数必须是数字！', self::VALUE_VAILIDATE),
        array('rebate_amount', 'number', '返点金额必须是数字！'),
        array('rebate_ratio', 'number', '返点比例必须是数字！'),
        array('referer_rebate_amount', 'number', '推荐人返点金额必须是数字！'),
        array('referer_rebate_ratio', 'number', '推荐人返点比例必须是数字！'),
        array('agency_rebate_amount', 'number', '机构返点金额必须是数字！'),
        array('agency_rebate_ratio', 'number', '机构返点比例必须是数字！'),
        array('valid_begin', 'require', '有效期开始时间必填！'),
        array('valid_end', 'require', '有效期结束时间必填！'),
        array('remark', 'require', '备注说明必填！'),
        array('remark', 'check_remark', '备注说明要小于512个字符', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('short_alias', 'check_short_alias', '优惠码已经存在', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('refer_user_id', 'check_refer_user_id', '推荐人会员ID无效', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('valid_end', 'check_time', '有效期结束时间要晚于有效期开始时间', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('short_alias', 'trim', 3, 'function'),
        array('short_alias', 'strtoupper', 3, 'function'),
        array('valid_begin', 'to_timespan', 3, 'function'),
        array('valid_end', 'to_timespan', 3, 'function'),
    );

    /**
     * 校验备注说明
     */
    protected function check_remark() {
        $remark = $_REQUEST['remark'];
        return strlen($remark) < 512;
    }

    /**
     * 校验优惠码唯一
     */
    protected function check_short_alias() {
        // 不允许输入i,I和o,O
        if (stripos($_REQUEST['short_alias'],'i') != false || stripos($_REQUEST['short_alias'],'o') != false){
            return false;
        }
        $sql = sprintf("id<>'%d' and deal_id='%d' and short_alias='%s'", intval($_REQUEST['id']), intval($_REQUEST['deal_id']), strtoupper(trim($_REQUEST['short_alias'])));
        $exist = M('CouponSpecial')->where($sql)->find();
        return empty($exist);
    }

    /**
     * 校验推荐会员ID
     */
    protected function check_refer_user_id() {
        $user_id = intval($_POST['refer_user_id']);
        if(empty($user_id)){
            return true;
        }
        $user_info = get_user_info($user_id, true);
        if (empty($user_info) || $user_info['is_effect'] == 0 || $user_info['is_delete'] == 1) {
            return false;
        }
        return true;
    }

    /**
     * 校验时间，开始小于结束
     */
    protected function check_time() {
        return strtotime($_POST['valid_begin']) < strtotime($_POST['valid_end']);
    }
}
