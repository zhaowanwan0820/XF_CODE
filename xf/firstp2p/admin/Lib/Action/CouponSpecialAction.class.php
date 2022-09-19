<?php
/**
 * CouponSpecialAction.php
 *
 * 特殊优惠券管理
 * 
 * @date 2014-05-31
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class CouponSpecialAction extends CommonAction {

    protected $is_log_for_fields_detail = true;

    public function index() {
        $deal_id = intval($_REQUEST['deal_id']);
        $deal = false;
        if (!empty($deal_id)) {
            $deal = \core\dao\DealModel::instance()->find($deal_id, 'name');
        }
        $deal_name = empty($deal) ? '全局' : $deal['name'];
        $this->assign('deal_name', $deal_name);

        $condition['deal_id'] = 0;
        $this->assign("default_map", $condition);
        parent::index();
    }

    protected function form_index_list(&$list) {
        foreach ($list as &$item) {
            $item['refer_user_name'] = get_user_name($item['refer_user_id']);
            $item['remark'] = "<div style='width:100px;overflow:hidden;white-space:nowrap;' title='{$item['remark']}'>{$item['remark']}</div>";

            $item['opt_edit'] = "<a href='javascript:javascript:edit(" . $item['id'] . ");'>编辑</a>";
            if ($item['deal_id'] == 0) {
                $item['opt_del'] = "<a href='javascript:javascript:foreverdel(" . $item['id'] . ");'>彻底删除</a>";
            }
        }
    }

    /**
     * 重置标所有的特殊优惠码返利规则为全局特殊优惠码返利规则
     */
    public function resetGlobal() {
        $deal_id = intval($_REQUEST['deal_id']);
        $ajax = intval($_REQUEST['ajax']);
        if (empty($deal_id)) {
            $this->error("参数错误");
        }
        $rebate_model = new \core\dao\CouponSpecialModel();
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

}