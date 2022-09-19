<?php

use core\service\UserCouponLevelService;

class CouponGroupLevelAction extends CommonAction
{
    /**
     * 列表.
     */
    public function index()
    {
        $condition = array();
        $pageSize = 20;
        $pageNum = isset($_REQUEST['p']) ? intval($_REQUEST['p']) : 1;

        if (isset($_REQUEST['group_name'])) {
            $condition['group_name'] = trim($_REQUEST['group_name']);
        }
        if (isset($_REQUEST['service_status']) && $_REQUEST['service_status'] != -1) {
            $condition['service_status'] = intval($_REQUEST['service_status']);
        }
        if (isset($_REQUEST['group_is_effect']) && $_REQUEST['group_is_effect'] != -1) {
            $condition['group_is_effect'] = intval($_REQUEST['group_is_effect']);
        }
        if (isset($_REQUEST['level_name'])) {
            $condition['level_name'] = trim($_REQUEST['level_name']);
        }
        if (isset($_REQUEST['group_id'])) {
            $condition['group_id'] = intval($_REQUEST['group_id']);
        }
        if (isset($_REQUEST['level_id'])) {
            $condition['level_id'] = intval($_REQUEST['level_id']);
        }

        $userCouponLevelService = new UserCouponLevelService();
        $list = $userCouponLevelService->getGroupLevelListByCondition($condition, $pageSize, $pageNum);
        $count = $userCouponLevelService->getGroupLevelCountByCondition($condition);
        $this->form_index_list($list);
        $p = new Page($count, $pageSize);
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('nowPage', $p->nowPage);
        $this->assign('list', $list);
        $this->display();
    }

    public function export(){
        $condition = array();

        if (isset($_REQUEST['group_name'])) {
            $condition['group_name'] = trim($_REQUEST['group_name']);
        }
        if (isset($_REQUEST['service_status']) && $_REQUEST['service_status'] != -1) {
            $condition['service_status'] = intval($_REQUEST['service_status']);
        }
        if (isset($_REQUEST['group_is_effect']) && $_REQUEST['group_is_effect'] != -1) {
            $condition['group_is_effect'] = intval($_REQUEST['group_is_effect']);
        }
        if (isset($_REQUEST['level_name'])) {
            $condition['level_name'] = trim($_REQUEST['level_name']);
        }
        if (isset($_REQUEST['group_id'])) {
            $condition['group_id'] = intval($_REQUEST['group_id']);
        }
        if (isset($_REQUEST['level_id'])) {
            $condition['level_id'] = intval($_REQUEST['level_id']);
        }

        $userCouponLevelService = new UserCouponLevelService();
        $list = $userCouponLevelService->getGroupLevelListByCondition($condition);
        $this->form_index_list($list);
        $this->exportCSV($list);
    }

    protected function form_index_list(&$list)
    {
        foreach ($list as &$item) {
            $item['group_is_effect'] = 0 == $item['group_is_effect'] ? '无效' : '有效';
            $item['service_status'] = 0 == $item['service_status'] ? '无效' : '有效';
            $item['level_is_effect'] = 0 == $item['level_is_effect'] ? '无效' : '有效';
            $item['pack_ratio'] = 0 == $item['is_related'] ? '' : $item['pack_ratio'];
            $item['is_related'] = 0 == $item['is_related'] ? '否' : '是';
            $item['rule_status'] = 0 == $item['rule_status'] ? '异常' : '合理';
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['update_time'] = date('Y-m-d H:i:s', $item['update_time']);
        }
    }

    /**
    *下载csv文件
    */
    private function exportCSV($data,$header = false)
    {
        $header = !empty($header)? $header : array('编号','会员组id','会员组名称','服务标识','会员组状态','服务等级ID','服务等级','服务等级系数','服务等级状态','机构比例','打包比例上限','是否联动','规则校验','创建时间','更新时间');
        $content = implode(',', $header )."\n";
        foreach ($data as $value) {
            $content .= implode(',', array($value['id'],$value['group_id'],$value['group_name'],$value['service_status'],$value['group_is_effect'],
                $value['level_id'],$value['level_name'],$value['rebate_ratio'],$value['level_is_effect'],$value['agency_rebate_ratio'],
                $value['max_pack_ratio'],$value['is_related'],$value['rule_status'],$value['create_time'],$value['update_time'])) . "\n";
        }
        header("Content-Disposition: attachment; filename=CouponGroupLevel_".time().".csv");
        echo iconv('utf-8', 'gbk//ignore', $content);
    }

}
