<?php
/**
 * CouponLevelRebateModel.class.php
 *
 * @date 2014-05-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class CouponLevelRebateModel extends CommonModel {

    protected $_validate = array(
        array('prefix', 'require', '优惠码前缀必填！'),
        array('rebate_amount', 'require', '投资人返点金额必填！'),
        array('rebate_ratio', 'require', '投资人返点比例必填！'),
        array('referer_rebate_amount', 'require', '推荐人返点金额必填！'),
        array('referer_rebate_ratio', 'require', '推荐人返点比例必填！'),
        array('agency_rebate_amount', 'require', '机构返点金额必填！'),
        array('agency_rebate_ratio', 'require', '机构返点比例必填！'),
        array('fixed_days', 'number', '绑定天数必须是数字！', self::VALUE_VAILIDATE),
        array('rebate_amount', 'number', '投资人返点金额必须是数字！'),
        array('rebate_ratio', 'number', '投资人返点比例必须是数字！'),
        array('referer_rebate_amount', 'number', '推荐人返点金额必须是数字！'),
        array('referer_rebate_ratio', 'number', '推荐人返点比例必须是数字！'),
        array('agency_rebate_amount', 'number', '机构返点金额必须是数字！'),
        array('agency_rebate_ratio', 'number', '机构返点比例必须是数字！'),
        array('valid_begin', 'require', '有效期开始时间必填！'),
        array('valid_end', 'require', '有效期结束时间必填！'),
        array('remark', 'require', '备注说明必填！'),
        array('remark', 'check_remark', '备注说明要小于512个字符', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('prefix', 'check_prefix', '优惠码前缀必须由英文或数字组成,同一等级不能重复', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('valid_end', 'check_time', '有效期结束时间要晚于有效期开始时间', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
    );

    protected $_auto = array (
        array('prefix', 'trim', 3, 'function'),
        array('prefix', 'strtoupper', 3, 'function'),
        array('valid_begin','to_timespan',3,'function'),
        array('valid_end','to_timespan',3,'function'),
    );

    /**
     * 校验备注说明
     */
    protected function check_remark() {
        $remark = $_REQUEST['remark'];
        return strlen($remark) < 512;
    }

    /**
     * 校验前缀唯一性
     */
    protected function check_prefix() {
        $prefix = $_REQUEST['prefix'];
        $reg = "/^([0-9A-Za-z]){1,4}$/";
        if (!preg_match($reg, $prefix)) {
            return false;
        }
        $sql = sprintf("id<>'%d' and deal_id='%d' and level_id='%d' and prefix='%s'", intval($_REQUEST['id']), intval($_REQUEST['deal_id']), intval($_REQUEST['level_id']), strtoupper($_REQUEST['prefix']));
        $exist = M('CouponLevelRebate')->where($sql)->find();
        return empty($exist);
    }

    /**
     * 校验时间，开始小于结束
     */
    protected function check_time() {
        return strtotime($_POST['valid_begin']) < strtotime($_POST['valid_end']);
    }

}
