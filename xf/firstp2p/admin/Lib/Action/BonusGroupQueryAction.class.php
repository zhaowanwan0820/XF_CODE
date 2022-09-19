<?php
/**
 *------------------------------------------------------------------
 * 分享红包客服自助查询平台
 *------------------------------------------------------------------
 * @auther wangshijie<wangshijie@ucfgroup.com>
 *------------------------------------------------------------------
 */

use core\service\UserService;
use core\dao\BonusModel;
use core\dao\BonusGroupModel;
use core\dao\UserModel;
use core\service\BonusService;

ini_set('display_errors', '1');
error_reporting(E_ERROR);
class BonusGroupQueryAction extends CommonAction {

    private $typeConfig = array(
        BonusService::TYPE_DEAL        => '投资红包',
        //BonusService::TYPE_BATCH       => '理财师红包',
        BonusService::TYPE_ACTIVITY    => '活动红包',
        BonusService::TYPE_XQL         => '流星雨红包',
    );

    public static $userList = array();

    /**
     * 新手双返红包列表页面
     */
    public function index() {

        $userService = new UserService();

        //查询条件
        $where = '1=1';

        $this->assign('taskList', MI('BonusTask')->field('id,name')->select());
        $this->assign('typeMap', $this->typeConfig);
        $bonus_type_id = isset($_GET['type']) ? intval(trim($_GET['type'])) : 1000;
        $this->assign('bonus_type_id', $bonus_type_id);
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $mobile = intval($_GET['mobile']);
        $user_id = intval($_GET['user_id']);
        $task_id = intval($_GET['task_id']);

        if ($bonus_type_id != 1000) {
            $where .= " AND bonus_type_id = $bonus_type_id";
        }

        if ($task_id) {
            $where .= " AND task_id = $task_id";
        }

        if ($timeStart) {
            $where .= " AND created_at >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND created_at <= '". strtotime($timeEnd) ."'";
        }

        $userInfo = '';
        if ($mobile) {
            $userInfo = UserModel::instance()->findByViaSlave("mobile = '$mobile'", 'id,user_name,real_name,mobile');
            $user_id = $userInfo['id'];
        }

        if ($user_id) {
            $userInfo = UserModel::instance()->findByViaSlave("id = '$user_id'", 'id,user_name,real_name,mobile');
            $where .= " AND user_id = " . $user_id;
        }

        if (!$mobile && !$user_id) {
            $this->display();
            return false;
        }

        $this->_list(MI('BonusGroup'), $where);
        $result = $this->get('list');
        if (empty($result)) {
            $this->display();
            return false;
        }

        $dataList = array();
        foreach ($result as $key => $item) {

            $data = array();

            $data['id']             = $item['id'];
            $data['real_name']      = userNameFormat($userInfo['real_name']);
            $data['user_name']      = $userInfo['user_name'];
            $data['mobile']         = adminMobileFormat($userInfo['mobile']);
            $data['money']          = $item['money'];
            $data['count']          = $item['count'];
            $data['create_time']    = date('Y-m-d H:i:s', $item['created_at']);
            $data['expire_time']    = date('Y-m-d H:i:s', $item['expired_at']);

            $dataList[] = $data;
        }

        $this->assign('list', $dataList);
        $this->display();
    }


    /**
     * 红包组领取详情信息
     */
    public function detail()
    {
        $group_id = intval($_REQUEST['group_id']);
        if (!$group_id) {
            $this->error("红包不存在！");
        }

        $groupInfo = BonusGroupModel::instance()->findByViaSlave("id=".$group_id, 'created_at');
        if (empty($groupInfo)) {
            $this->error("红包不存在！");
        }

        $dead_line = strtotime('-30 days');
        if ($groupInfo['created_at'] < $dead_line) {
            $this->error('只能查询最近30天的数据!');
        }

        $list = array();
        $this->_list(MI('Bonus'), "group_id=$group_id && status > 0");
        $result = $this->get('list');

        foreach ($result as $row) {

            $userInfo = UserModel::instance()->findByViaSlave("id=".intval($row['owner_uid']), 'id,real_name,mobile');
            if (empty($userInfo)) {
                continue;
            }

            $data = array();

            $data['id']             = $row['id'];
            $data['owner_uid']      = $row['owner_uid'];
            $data['user_name']      = userNameFormat($userInfo['real_name']);
            $data['mobile']         = adminMobileFormat($userInfo['mobile']);
            $data['money']          = $row['money'];
            $data['status']         = $row['status'] == 2 ? '已使用' : '未使用';
            $data['create_time']    = date('Y-m-d H:i:s', $row['created_at']);
            $data['expire_time']    = date('Y-m-d H:i:s', $row['expired_at']);

            $list[] = $data;
        }

        $this->assign('list', $list);
        $this->display();
    }
}
