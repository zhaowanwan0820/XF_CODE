<?php
/**
 * 渠道 log
 *2013年11月14日16:01:40
 * @author 长路
 */

class DealChannelLogModel extends CommonModel {

    protected $_validate = array(
        array('channel_value', 'require', '渠道号必填！'),
        array('channel_value', 'number', '渠道号必须是数字！'),
        array('pay_factor', 'require', '返利系数必填！'),
        array('pay_factor', 'number', '返利系数必须是数字！'),
        array('channel_value', 'check_channel_value', '用户不存在', 0, 'callback', 3),
        array('pay_factor', 'check_pay_factor', '返利系数不正确', 0, 'callback', 3),
    );

    protected $_auto = array(
        array('channel_id', 'add_deal_channel_by_post', 3, 'function'),
        //array('pay_factor', 'add_deal_channel_factor_by_post', 3, 'function'), // 页面输入，不自动获取 -20131226
    );

    protected function check_channel_value() {
        $channel_value = intval($_POST['channel_value']);
        $advisor_info = get_user_info($channel_value, true);
        if (empty($advisor_info) || $advisor_info['is_effect'] == 0 || $advisor_info['is_delete'] == 1) {
            return false;
        }
        return true;
    }

    protected function check_pay_factor() {
        $pay_factor = trim($_REQUEST['pay_factor']);
        return (!empty($pay_factor) && is_numeric($pay_factor));
    }

}
