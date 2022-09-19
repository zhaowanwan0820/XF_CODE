<?php
/**
 * UserModel.class.php
 * 
 * @date 2014-05-29
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class UserModel extends CommonModel{

    protected $_validate = array(
        array('group_id', 'require', '会员所属网站必填！'),
        array('coupon_level_id', 'require', '会员等级必填！'),
        array('coupon_level_valid_end', 'require', '会员等级必填！'),
    );

}
