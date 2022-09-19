<?php
/**
 * BonusModel class file.
 *
 * @author pengchagnlu@ucfgroup.com
 */

namespace core\dao;

/**
 * 红包配置
 *
 * @author pengchanglu@ucfgroup.com
 */
class BonusActivityModel extends BaseModel
{

    public static $type_arr = array(1 => '限投资使用');
    public static $load_limit_arr = array( 0 => '无');
    public static $status_arr = array( 0 => '无效', 1=>'有效');

    public function getByGroupId($group_id) {
        $condition = "`group_id` = ':group_id'";
        return $this->findBy($condition, '*', array(':group_id' => $group_id));
    }

}
