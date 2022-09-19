<?php
/**
 * CouponLevelModel.php
 *
 * @date 2014-05-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class CouponLevelModel extends CommonModel {

    protected $_validate = array(
        array('group_id', 'require', '用户组必填！'),
        array('level', 'require', '用户等级必填！'),
        array('money', 'require', '投资交易额必填！'),
        array('valid_days', 'require', '有效期必填！'),
        array('group_id', 'require', '用户组必填！'),
        array('money', 'number', '投资交易额必须是数字！'),
        array('valid_days', 'number', '有效期必须是数字！'),
        array('level', 'check_level', '用户等级已经存在', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        //array('level', '', '用户等级已经存在！', self::VALUE_VAILIDATE, 'unique', self::MODEL_BOTH), //update有bug
        array('money', 'check_money', '投资交易额要大于等于0', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('valid_days', 'check_valid_days', '有效期要大于0', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
    );

    /**
     * 会员组和用户等级 唯一
     */
    protected function check_level() {
        $sql = sprintf("id<>'%d' and group_id='%d' and level='%s'", intval($_REQUEST['id']), intval($_REQUEST['group_id']), $_REQUEST['level']);
        $exist = M('CouponLevel')->where($sql)->find();
        return empty($exist);
    }

    /**
     * 校验投机交易额
     */
    protected function check_money() {
        $money = intval($_REQUEST['money']);
        return $money >= 0;
    }

    /**
     * 校验有效期天数
     */
    protected function check_valid_days() {
        $valid_days = floatval($_REQUEST['valid_days']);
        return $valid_days > 0;
    }

}
