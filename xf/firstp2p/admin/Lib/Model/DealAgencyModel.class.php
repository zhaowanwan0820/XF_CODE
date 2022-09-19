<?php
/**
 * DealAgencyModel.class.php
 *
 * @date 2014-01-14 15:12
 * @author liangqiang@ucfgroup.com
 */

class DealAgencyModel extends CommonModel {

    protected $_validate = array(
        array('user_id', 'require', '关联会员ID必填！'),
        array('user_id', 'check_user_id', '关联会员不存在', 0, 'callback', 3),
    );


    protected function check_user_id() {
        $user_id = intval($_POST['user_id']);
        $user_info = get_user_info($user_id, true);
        if (empty($user_info) || $user_info['is_effect'] == 0 || $user_info['is_delete'] == 1) {
            return false;
        }
        return true;
    }

}
