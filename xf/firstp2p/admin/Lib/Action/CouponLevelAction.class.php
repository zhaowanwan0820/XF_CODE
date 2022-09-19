<?php
/**
 * CouponLevelAction.php
 *
 * 优惠码等级规则管理
 *
 * @date 2014-05-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

use \core\dao\CouponLevelModel;

class CouponLevelAction extends CommonAction {

    protected $is_log_for_fields_detail = true;

    /**
     * 用户组列表
     */
    protected $groups = array();

    public function __construct() {
        parent::__construct();
        //用户组列表
        $groups = M('UserGroup')->findAll();
        foreach ($groups as $item) {
            $this->groups[$item['id']] = $item;
        }
        $this->assign('groups', $this->groups);
    }

    /**
     * 列表
     */
    public function index()
    {
        $condition = array();

        $groupName = isset($_REQUEST['group_name']) ? addslashes(trim($_REQUEST['group_name'])) : '';
        if ($groupName !== '') {
            $groups = M('UserGroup')->where("name LIKE '%{$groupName}%'")->getField('id,name');
            $condition['group_id'] = array('IN', array_keys($groups));
        }

        //按会员组、level名称排序
        if (empty($_REQUEST ['_order'])) {
            $_REQUEST ['_order'] = "group_id`,`level";
            $_REQUEST ['_sort'] = 1;
        }

        $list = $this->_list(D('CouponLevel'), $condition);

        $this->display();
    }

    protected function form_index_list(&$list) {
        $level_model = new CouponLevelModel();
        $level_list = $level_model->getLevels(true);
        foreach ($list as &$item) {
            $item['group'] = $this->groups[$item['group_id']]['name'];
            //多个优惠码前缀|分隔
            $item['rebate_prefix_str'] = implode("|", $level_list[$item['id']]['rebate_prefix']);
            $item['opt_add_rebate'] = "<a href='" . u("CouponLevelRebate/add", array('level_id' => $item['id'])) . "' target='_blank'>添加优惠码</a>";
        }
    }

    /**
     * 导出所有记录
     */
    public function export_csv()
    {
        $content = implode(',', array('编号', '会员所属网站', '会员等级', '投资交易额(元)', '有效期(天)', '优惠码前缀'))."\n";

        $list = $this->_list(D('CouponLevel'), $map='');

        foreach ($list as $item) {
            $content .= implode(',', array($item['id'], $item['group'], $item['level'], $item['money'], $item['valid_days'], $item['rebate_prefix_str']))."\n";
        }

        header('Content-Disposition: attachment; filename=coupon_level_'.date('Ymd_His').'.csv');
        // 环境不同导致转换过程出错，故忽略错误
        echo iconv('utf-8', 'gbk//ignore', $content);
        return;
    }

    /**
     * 删除校验
     * 存在用户属于该用户等级，存在返利规则属于该用户等级，不能删除
     * 支持批量删除
     */
    public function foreverdelete() {
        //用户等级下有用户则不能删除
        $condition = array('coupon_level_id' => array('in', $this->get_id_list()));
        $coupon_user = M('User')->where($condition)->findAll();
        if (!empty($coupon_user)) {
            $this->error("存在用户属于该用户等级，不能删除");
        }
        //用户等级下有返利规则则不能删除
        $coupon_leve_rebate = M('CouponLevelRebate')->where($condition)->findAll();
        if (!empty($coupon_leve_rebate)) {
            $this->error("存在返利规则属于该用户等级，不能删除");
        }
        parent::foreverdelete();
    }

    /**
     * 会员组选择后的用户等级二级联动下拉框取值
     */
    public function get_level_select() {
        $group_id = trim($_REQUEST["group_id"]);
        if (empty($group_id)) {
            exit;
        }
        $level_model = new CouponLevelModel();
        $level_list = $level_model->getLevels(true, $group_id);
        foreach ($level_list as $level) {
            if (!empty($level['rebate'])) { // 要求用户等级下有返利规则
                $select[] = array("id" => $level['id'], "level" => $level['level']);
            }
        }
        if (empty($select)) {
            $select[] = array("id" => '', "level" => '');
        }
        echo json_encode($select);
        exit;
    }

    /**
     * 全量更新用户等级
     */
    public function update_user_coupon_level() {
        $coupon_level_service = new \core\service\CouponLevelService();
        $result = $coupon_level_service->updateUserLevel();
        //更新变化数量，包括有效时间更新的情况
        $result['update'] = count($result['update']);
        //保持不动数量
        $result['keep'] = count($result['keep']);
        echo json_encode($result);
    }

}
