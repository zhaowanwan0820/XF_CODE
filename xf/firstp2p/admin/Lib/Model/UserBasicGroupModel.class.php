<?php
/**
 * WeixinTemplateModel.class.php
 *
 * @date 2016-12-15
 * @author gengkuan <gengkuan@ucfgroup.com>
 */

class UserBasicGroupModel extends CommonModel{

    protected $_validate = array(
        array('rebate_effect_days', 'number', '活动持续时间是数字！', self::VALUE_VAILIDATE),
        array('refer_rebate_ratio', 'number', '返点比例是数字！', self::VALUE_VAILIDATE),
        array('remark', 'check_remark', '备注说明要小于512个字符', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('name', 'require', '政策名称必填！'),
        array('name', 'check_name', '政策名称要小于32个字符', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('name', 'check_name_unique', '政策名称必须唯一', 0, 'callback', 3),
    );
    /**
     * 校验备注说明
     */
    protected function check_remark() {
        $remark = $_REQUEST['remark'];
        return strlen($remark) < 512;
    }
    /**
     * 校验备注说明
     */
    protected function check_name() {
        $remark = $_REQUEST['name'];
        return strlen($remark) < 32;
    }
    /**
     * 校验备注说明
     */
    protected function check_name_unique() {
        $name = trim($_REQUEST['name']);
        $condition['name'] = $name;
        $id = trim($_REQUEST['id']);
        $exist_name = M(MODULE_NAME)->where($condition)->findAll();
        if (empty($exist_name)) {
            return true;
        } else if (empty($id)) { // 新增
            return false;
        } else { // 编辑
            foreach ($exist_name as $exist_item) {
                if ($exist_item['id'] != $id) {
                    return false;
                }
            }
            return true;
        }
    }
}