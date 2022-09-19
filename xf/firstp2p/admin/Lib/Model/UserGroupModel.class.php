<?php
/**
 * UserGroupModel.class.php
 *
 * @date 2013-12-03 17:50
 * @author liangqiang@ucfgroup.com
 */

class UserGroupModel extends CommonModel {

    protected $_validate = array(
        array('channel_pay_factor', 'number', '渠道返利系数必须是数字！' ),
        array('channel_pay_factor', 'check_factor', '渠道返利系数须介于0.0001到9999.9999之间，最多四位小数！', 0, 'callback', 3),
    );

    protected function check_factor(){
        $factor = $_REQUEST['channel_pay_factor'];
        if(!is_numeric($factor) || !preg_match('/^\d{1,4}(\.\d{1,4})?$/', $factor)){
            return false;
        }
        return $factor >= 0.0001 && $factor <= 9999.9999;
    }


}
