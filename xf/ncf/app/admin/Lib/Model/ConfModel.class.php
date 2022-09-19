<?php
/**
 * ConfModel.class.php
 *
 * @date 2013-12-18 9:30
 * @author liangqiang@ucfgroup.com
 */

class ConfModel extends CommonModel {

    protected $_validate = array(
        array('name', 'check_name', '键名由大写字母,数字,下划线组成,且不能与现有的键名重复', 0, 'callback', 3),
        array('value_scope', 'check_value_scope', '请输入取值范围', 0, 'callback', 3),
        array('name', 'check_name', '该键名已存在', 0, 'callback', 3),
    );

    protected $_auto = array(
        array('group_id', 0),
        array('is_effect', 1),
        array('is_conf', 1),
        array('sort', 0),
    );

    protected function check_name() {
        $name = trim($_REQUEST['name']);
        $result = ereg("^[A-Z0-9_]+$", $name);
        if (empty($result)) {
            return false;
        }
        $condition['name'] = $name;
        $id = trim($_REQUEST['id']);
        if (isset($_REQUEST['site_id'])) {
            $condition['site_id'] = $_REQUEST['site_id'];
        }
        $exist_conf = M(MODULE_NAME)->where($condition)->findAll();
        if (empty($exist_conf)) {
            return true;
        } else if (empty($id)) { // 新增
            return false;
        } else { // 编辑
            foreach ($exist_conf as $exist_item) {
                if ($exist_item['id'] != $id) {
                    return false;
                }
            }
            return true;
        }
    }

    protected function check_value_scope() {
        $result = $_POST['input_type'] != 1 || !empty($_POST['value_scope']);
        return $result;
    }

}
