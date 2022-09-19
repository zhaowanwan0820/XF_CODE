<?php

// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

vendor('phpexcel.PHPExcel');
use libs\utils\Logger;
use core\service\UserGroupService;
use core\service\UserCouponLevelService;

class UserGroupAction extends CommonAction
{
    /**
     * 政策组列表.
     */
    protected $basic_groups = array();

    public function __construct()
    {
        parent::__construct();
        $this->is_log_for_fields_detail = true;
        $this->basic_groups = M('UserBasicGroup')->getField('id,name');
        $userGroupService = new UserGroupService();
        $packRatio = $userGroupService->getPackRetioDatas();
        $maxPackRatio = $userGroupService->getMaxPackRetioDatas();
        $this->assign('basic_groups', $this->basic_groups);
        $this->assign('pack_ratio', $packRatio);
        $this->assign('max_pack_ratio', $maxPackRatio);
    }

    /**
     * 列表.
     */
    public function index()
    {
        $map = array();
        $name = isset($_REQUEST['name']) ? addslashes($_REQUEST['name']) : '';
        $basic_group_id = isset($_REQUEST['basic_group_id']) ? addslashes($_REQUEST['basic_group_id']) : '';
        if (isset($_REQUEST['service_status']) && '' != trim($_REQUEST['service_status']) && 'all' != trim($_REQUEST['service_status'])) {
            $map['service_status'] = intval($_REQUEST['service_status']);
        }
        if (isset($_REQUEST['is_effect']) && '' != trim($_REQUEST['is_effect']) && 'all' != trim($_REQUEST['is_effect'])) {
            $map['is_effect'] = intval($_REQUEST['is_effect']);
        }
        if (isset($_REQUEST['pack_ratio']) && $_REQUEST['pack_ratio'] >= 0) {
            $map['pack_ratio'] = $_REQUEST['pack_ratio'];
        }
        if (isset($_REQUEST['max_pack_ratio']) && $_REQUEST['max_pack_ratio'] >= 0) {
            $map['max_pack_ratio'] = $_REQUEST['max_pack_ratio'];
        }
        if ('' !== $name) {
            $map['name'] = array('like', "%{$name}%");
        }
        if ('' !== $basic_group_id) {
            $map['basic_group_id'] = intval($basic_group_id);
        }
        if (!empty($this->model)) {
            $this->_list($this->model, $map);
        }

        $this->display();
    }

    /**
     * 编辑显示.
     *
     * 用于单表的编辑页面的显示赋值，请求参数为：主键名=主键值
     */
    public function edit()
    {
        $id = isset($_REQUEST[$this->pk_name]) ? trim($_REQUEST[$this->pk_name]) : '';
        if (empty($id)) {
            $this->error(l('INVALID_OPERATION'));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        // 会员列表的链接
        $userListUrl = 'User/index';
        if (!empty($vo) && $vo['agency_user_id'] > 0) {
            $userService = new \core\service\UserService($vo['agency_user_id']);
            $userService->isEnterprise() && $userListUrl = 'Enterprise/index';
        }
        $this->assign('vo', $vo);
        $this->assign('userListUrl', $userListUrl);
        $this->display();
    }

    protected function form_index_list(&$list)
    {
        foreach ($list as &$item) {
            $item['agency_user_name'] = get_user_name($item['agency_user_id']);
            $item['basic_group_name'] = $this->basic_groups[$item['basic_group_id']];
            $item['is_related'] = 0 == $item['is_related'] ? '否' : '是';
            $item['service_status'] = 0 == $item['service_status'] ? '无效' : '有效';
            $item['is_effect'] = 0 == $item['is_effect'] ? '无效' : '有效';
        }
    }

    public function foreverdelete()
    {
        $ajax = intval($_REQUEST['ajax']);
        $id_list = parent::get_id_list();
        if (empty($id_list)) {
            $this->error(l('INVALID_OPERATION'), $ajax);
        }

        //检查组别是否有会员
        foreach ($id_list as $id) {
            $users = M('User')->where('group_id='.$id)->findAll();
            if (!empty($users)) {
                $this->error('该会员组别有会员存在，不能删除', $ajax);
            }
        }

        parent::foreverdelete();
    }

    /**
     * 导出所有记录.
     */
    public function export_csv()
    {
        $log_info = array(__CLASS__, __FUNCTION__);

        Logger::info(implode(' | ', array_merge($log_info, array('script start'))));
        $content = implode(',', array('编号', '会员组名称', '绑定用户', '服务标识', '邀请码前缀', '会员组状态', '机构/打包比例', '打包比例上限', '是否联动'))."\n";

        $list = $this->_list(D('UserGroup'), $map = '');
        if (empty($list)) {
            Logger::info(implode(' | ', array_merge($log_info, array('data empty'))));
        }
        $i = 0;
        foreach ($list as $item) {
            if (0 == $i) {
                Logger::info(implode(' | ', array_merge($log_info, array('export csv in', $content))));
            }
            $content .= implode(',', array($item['id'], $item['name'], strip_tags($item['agency_user_name']), $item['service_status'], $item['prefix'], $item['is_effect'], $item['pack_ratio'], $item['max_pack_ratio'], $item['is_related']))."\n";
            ++$i;
        }
        header('Content-Disposition: attachment; filename=user_group_'.date('Ymd_His').'.csv');
        // 环境不同导致转换过程出错，故忽略错误
        echo iconv('utf-8', 'gbk//ignore', $content);
        Logger::info(implode(' | ', array_merge($log_info, array('script end'))));
        return;
    }

    public function ajaxGetGroupInfo()
    {
        $return = array('status' => '0', 'message' => '该用户组不存在');
        $groupId = intval($_REQUEST['group_id']);
        if (0 == $groupId) {
            return ajax_return($return);
        }
        $groupInfo = M(MODULE_NAME)->where(array('id' => $groupId))->find();
        if (!$groupInfo) {
            return ajax_return($return);
        }

        $return['status'] = $groupId;
        $return['message'] = $groupInfo['name'];

        return ajax_return($return);
    }

    public function update()
    {
        $id = intval($_REQUEST['id']);
        $isRelated = intval($_REQUEST['is_related']);
        $maxPackRatio = bcadd($_REQUEST['max_pack_ratio'], 0, 5);
        $packRatio = bcadd($_REQUEST['pack_ratio'], 0, 5);
        $isEffect = intval($_REQUEST['is_effect']);
        $serviceStatus = intval($_REQUEST['service_status']);

        if($isEffect == 0){
           $userCouponLevelService = new UserCouponLevelService();
           $count = $userCouponLevelService->getUserInGroupCountByGroupId($id);
           if($count >0){
                $this->error('组内有用户，不允许置无效');
           }
        }

        if(bccomp($maxPackRatio, 5,5) >=0){
            $this->error('打包比例上限不能大于等于5');
        }

        if(bccomp($maxPackRatio, 0,5) == -1){
            $this->error('打包比例上限必须大于等于0');
        }

        if(bccomp($packRatio, 2,5) == 1){
            $this->error('打包系数不能大于2');
        }

        if(bccomp($packRatio, 0,5) == -1){
            $this->error('打包系数必须大于等于0');
        }

        if(bccomp($maxPackRatio, $packRatio,5) < 0){
            $this->error('打包比例上限必须大于等于打包系数');
        }
        $userCouponLevelService = new UserCouponLevelService();
        $result = $userCouponLevelService->checkGroupMatchLevels(array('id' => $id,'service_status'=>$serviceStatus,'is_effect' => $isEffect, 'pack_ratio' => $packRatio, 'is_related' => $isRelated, 'max_pack_ratio' => $maxPackRatio));
        if (!$result) {
            if (1 == $isRelated) {
                $this->error('个人系数必须小于等于打包系数');
            } else {
                $this->error('个人系数和机构系数之和必须小于等于打包系数上限');
            }
        }
        parent::update();
    }

    public function insert(){
        $maxPackRatio = bcadd($_REQUEST['max_pack_ratio'], 0, 5);
        $packRatio = bcadd($_REQUEST['pack_ratio'], 0, 5);
        if(bccomp($maxPackRatio, 5,5) >=0){
            $this->error('打包比例上限不能大于等于5');
        }

        if(bccomp($maxPackRatio, 0,5) == -1){
            $this->error('打包比例上限必须大于等于0');
        }

        if(bccomp($packRatio, 2,5) == 1){
            $this->error('打包系数不能大于2');
        }

        if(bccomp($packRatio, 0,5) == -1){
            $this->error('打包系数必须大于等于0');
        }

        if(bccomp($maxPackRatio, $packRatio,5) < 0){
            $this->error('打包比例上限必须大于等于打包系数');
        }
        parent::insert();
    }
}
