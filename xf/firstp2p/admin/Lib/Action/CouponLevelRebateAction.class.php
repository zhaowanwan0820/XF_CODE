<?php
/**
 * CouponLevelRebateAction.class.php
 *
 * 优惠码等级返利管理
 *
 * @date 2014-05-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class CouponLevelRebateAction extends CommonAction
{

    protected $is_log_for_fields_detail = true;

    /**
     * 用户等级列表
     */
    protected $levels = array();
    /**
     * 政策组列表
     */
    protected $basic_groups= array();

    public function __construct() {
        parent::__construct();
        $level_model = new \core\dao\CouponLevelModel();
        $this->levels = $level_model->getLevels(true);
        $this->assign('levels', $this->levels);
        $this->basic_groups = M('UserBasicGroup')->getField('id,name');
        $this->assign('basic_groups', $this->basic_groups);
    }

    /**
     * 列表
     */
    public function index()
    {
        $deal_id = intval($_REQUEST['deal_id']);
        $deal = false;
        if (!empty($deal_id)) {
            $deal = \core\dao\DealModel::instance()->find($deal_id, 'name');
        }
        $deal_name = empty($deal) ? '全局' : $deal['name'];
        $this->assign('deal_name', $deal_name);
        $condition = array();
        $groupName = isset($_REQUEST['group_name']) ? addslashes(trim($_REQUEST['group_name'])) : '';
        $basic_group_id= isset($_REQUEST['basic_group_id']) ? addslashes($_REQUEST['basic_group_id']) : '';
        if ($basic_group_id !== '') {
            $where["basic_group_id"] = intval($basic_group_id);
        }
        if ($groupName !== '') {
            $where["name"] = array('LIKE', "%{$groupName}%");
        }
        if(!empty($where)){
            $groupIds = M('UserGroup')->where( $where)->getField('id,name');
            $levelIds = M('CouponLevel')->where(array('group_id' => array('IN', array_keys($groupIds))))->getField('id,group_id');
            $condition['level_id'] = array('IN', array_keys($levelIds));
        }

        $condition['deal_id'] = 0;
        $this->assign("default_map", $condition);

        if (empty($_REQUEST ['_order'])) {
            $_REQUEST ['_order'] = "level_id`,`prefix";
            $_REQUEST ['_sort'] = 1;
        }
        parent::index();
    }

    /**
     * 导出所有记录
     */
    public function export_csv()
    {
        $content = implode(',', array('编号', '会员等级', '优惠码前缀', '绑定天数', '投资人返点金额', '投资人返点比例', '推荐人返点金额',
            '推荐人返点比例', '机构返点金额', '机构返点比例', '有效期开始时间', '有效期结束时间', '备注说明', '状态'))."\n";

        $_REQUEST['_order'] = 'level_id`,`prefix';
        $_REQUEST['_sort'] = 1;

        $list = $this->_list(D('CouponLevelRebate'), array('deal_id' => 0));

        foreach ($list as $item) {
            $content .= implode(',', array($item['id'], $item['group_level'], $item['prefix'], $item['fixed_days'], $item['rebate_amount'], $item['rebate_ratio'], $item['referer_rebate_amount'],
                $item['referer_rebate_ratio'], $item['agency_rebate_amount'], $item['agency_rebate_ratio'], to_date($item['valid_begin']), to_date($item['valid_end']),
                strip_tags($item['remark']), $item['is_effect'] ? '有效' : '无效'))."\n";
        }

        header('Content-Disposition: attachment; filename=coupon_level_rebate_'.date('Ymd_His').'.csv');
        echo iconv('utf-8', 'gbk//ignore', $content);
        return;
    }

    protected function form_index_list(&$list) {
        foreach ($list as &$item) {
            $level = $this->levels[$item['level_id']];
            $group_id= M('CouponLevel')->where("id = {$item['level_id']}")->getField('group_id');
            $basic_group_id= M('UserGroup')->where("id = {$group_id}")->getField('basic_group_id');
            $item['basic_group_name'] = $this->basic_groups[$basic_group_id];
            $item['group_level'] = $level['group_name'] . "-" . $level['level'];
            $item['remark'] = "<div style='width:100px;overflow:hidden;white-space:nowrap;' title='{$item['remark']}'>{$item['remark']}</div>";
            $item['opt_edit'] = "<a href='javascript:javascript:edit(" . $item['id'] . ");'>编辑</a>";
            if ($item['deal_id'] == 0) {
                $item['opt_del'] = "<a href='javascript:javascript:foreverdel(" . $item['id'] . ");'>彻底删除</a>";
            }
        }
    }

    /**
     * 删除校验
     * 该用户等级最后一条返利规则，但还存在用户属于该用户等级，不能删除
     * 支持批量删除
     */
    public function foreverdelete() {
        foreach ($this->get_id_list() as $id) {
            $condition[$this->pk_name] = $id;
            $rebate = $this->model->where($condition)->find();
            $condition = array('level_id' => $rebate['level_id']);
            $coupon_user = M('CouponUser')->where($condition)->findAll();
            $exists_rebates = $this->levels[$rebate['level_id']]['rebate'];
            if (count($exists_rebates) <= 1 && !empty($coupon_user)) {
                $this->error("这是该用户等级最后一条返利规则，但还存在用户属于该用户等级，不能删除。");
            }
        }
        parent::foreverdelete();
    }

    /**
     * 重置标所有的返利规则为全局返利规则
     */
    public function resetGlobal() {
        $deal_id = intval($_REQUEST['deal_id']);
        $ajax = intval($_REQUEST['ajax']);
        if (empty($deal_id)) {
            $this->error("参数错误");
        }
        $rebate_model = new \core\dao\CouponLevelRebateModel();
        $rs = $rebate_model->copyRebate($deal_id);
        if ($rs !== false) {
            save_log($deal_id . l("LOG_STATUS_1"), 1);
            $this->display_success(l("LOG_STATUS_1"), $ajax);
        } else {
            save_log($deal_id . l("LOG_STATUS_0"), 0);
            $this->error(l("LOG_STATUS_0"), $ajax);
        }
    }

    /**
     * 把规则全部置为有效/无效
     */
    public function effectAll() {
        $deal_id = intval($_REQUEST['deal_id']);
        $is_effect = intval($_REQUEST['is_effect']);
        if (empty($deal_id)) {
            $this->error("参数错误");
        }
        $condition = array('deal_id' => $deal_id);
        parent::set_effect_all($condition, $is_effect);
    }
    /**
     * 编辑优惠券
     */
    public function edit() {
        $id = intval($_REQUEST['id']);
        $level_id = $this->model->where("id = {$id}")->getField('level_id');
        $group_id= M('CouponLevel')->where("id = {$level_id}")->getField('group_id');
        $basic_group_id= M('UserGroup')->where("id = {$group_id}")->getField('basic_group_id');
        $basic_group_name = $this->basic_groups[$basic_group_id];
        $this->assign('basic_group_name', $basic_group_name);
        parent::edit();
    }


}
