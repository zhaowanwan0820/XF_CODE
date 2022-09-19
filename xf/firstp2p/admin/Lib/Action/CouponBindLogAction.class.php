<?php
/**
 * CouponBindAction.class.php.
 *
 * 理财师邀请码修改日志管理
 *
 * @date 2017-11-10
 *
 * @author gengkuan <gengkuan@ucfgroup.com>
 */
use core\service\UserService;
use core\service\CouponBindService;

class CouponBindLogAction extends CommonAction
{
    /**
     * 用户 邀请码列表.
     */
    protected function form_index_list(&$list)
    {
        $userIDArr = array();
        $userServ = new UserService();
        foreach ($list as $k => $v) {
            if (!empty($v['user_id'])) {
                $userIDArr[] = $v['user_id'];
            }
            if (!empty($v['new_refer_user_id'])) {
                $userIDArr[] = $v['new_refer_user_id'];
            }
        }
        $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
        foreach ($list as &$item) {
            $item['user_name'] = $this->_get_user_link($item['user_id'], $listOfBorrower[$item['user_id']]['real_name']);
            $item['new_refer_real_name'] = $this->_get_user_link($item['new_refer_user_id'], $listOfBorrower[$item['new_refer_user_id']]['real_name']);
            $item['user_num'] = $this->_get_user_link($item['user_id'], numTo32($item['user_id']));
            $item['user_id'] = $this->_get_user_link($item['user_id'], $item['user_id']);
            $item['new_refer_user_id'] = $this->_get_user_link($item['new_refer_user_id'], $item['new_refer_user_id']);
        }
    }

    /**
     * user_id转用户名称并加链接.
     *
     * @param $user_id
     * @param $text
     *
     * @return string
     */
    private function _get_user_link($user_id, $text)
    {
        return "<a href='".u('User/index', array('user_id' => $user_id))."' target='_blank'>".$text.'</a>';
    }

    public function index($type = 1)
    {
        // 列表过滤器，生成查询Map对象
        $map = $this->_search();
        $operationTime = trim($_REQUEST['begin']);
        $operationTimeEnd = trim($_REQUEST['end']);
        if ($operationTime) {
            $map['create_time'] = array('between', to_timespan($operationTime).','.to_timespan($operationTimeEnd));
        }
        if (!empty($_REQUEST['user_num'])) {
            $map['user_id'] = de32Tonum($_REQUEST['user_num']);
            unset($map['user_num']);
        }
        if (!empty($_REQUEST['operator'])) {
            $admin_id = M('Admin')->where("adm_name='".$_REQUEST['operator']."'")->getField('id');
            if (!empty($admin_id)) {
                $map['admin_id'] = $admin_id;
            } else {
                $map['admin_id'] = '-1';
            }
        }
        if(!empty($_REQUEST['short_alias'])){
            $map['new_short_alias'] = trim($_REQUEST['short_alias']);
        }

        $map['type'] = $type;
        $this->model = MI(MODULE_NAME);
        if (!empty($this->model)) {
            $this->_list($this->model, $map);
        }
        $this->assign("type", $type);
        if (CouponBindService::TYPE_INVITE == $type) {
            $this->display('inviteIndex');
        } else {
            $this->display();
        }

        return;
    }

    //邀请人操作日志列表
    public function inviteIndex()
    {
        $this->index(CouponBindService::TYPE_INVITE);
    }

    //导出数据
    public function export_csv()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $map = array();
        $operationTime = trim($_REQUEST['begin']);
        $operationTimeEnd = trim($_REQUEST['end']);
        if ($operationTime) {
            $map[] = 'create_time between '.to_timespan($operationTime).' and '.to_timespan($operationTimeEnd);
        }
        if (!empty($_REQUEST['user_num'])) {
            $map[] = 'user_id  = '.intval(de32Tonum($_REQUEST['user_num']));
        }
        if (!empty($_REQUEST['operator'])) {
            $admin_id = M('Admin')->where("adm_name='".$_REQUEST['operator']."'")->getField('id');
            if (!empty($admin_id)) {
                $map[] = 'admin_id  = '.$admin_id;
            } else {
                $map[] = 'admin_id  = -1';
            }
        }
        if (!empty($_REQUEST['user_id'])) {
            $map[] = 'user_id  = '.intval($_REQUEST['user_id']);
        }
        
        $type = intval($_REQUEST['type']);
        $map[] = 'type  = '.$type;

        $where_str = $map ? ' where '.implode(' AND ', $map) : '';
        $sql = 'SELECT * from firstp2p_coupon_bind_log '.$where_str.' ORDER BY id DESC';
        $res = $GLOBALS['db']->get_slave()->query($sql);
        if (false === $res) {
            $this->error('搜索列表为空');
        }
        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportCouponBindLog',
                'analyze' => $sql,
            )
        );
        $datatime = date('YmdHis', time());
        $file_name = 'couponbindlog'.$datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        if ($type == CouponBindService::TYPE_INVITE) {
            $head = array('投资人ID', '投资人会员姓名', '投资人会员编号', '当前推荐人ID', '当前推荐人姓名',
                '当前推荐人邀请码', '操作人', '更新时间', );
        } else {
            $head = array('投资人ID', '投资人会员姓名', '投资人会员编号', '当前服务人ID', '当前服务人姓名',
                '当前服务人邀请码', '操作人', '更新时间', );
        }

        foreach ($head as &$item) {
            $item = iconv('utf-8', 'gbk//IGNORE', $item);
        }
        $userServ = new UserService();
        fputcsv($fp, $head);
        while ($val = $GLOBALS['db']->fetchRow($res)) {
            $create_time = to_date($val['create_time']);
            $admin_name = get_admin_name($val['admin_id']);
            $user_num = numTo32($val['user_id']);
            $user_name = $userServ->getUserInfoListByID(array($val['user_id']))[$val['user_id']]['real_name'];
            $new_refer_user_name = $userServ->getUserInfoListByID(array($val['new_refer_user_id']))[$val['new_refer_user_id']]['real_name'];
            ++$count;
            if (0 == $count % $limit) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $arr = array(
                $val['user_id'],
                $user_name,
                $user_num,
                $val['new_refer_user_id'],
                $new_refer_user_name,
                $val['new_short_alias'],
                $admin_name,
                $create_time,
            );
            foreach ($arr as &$item) {
                $item = iconv('utf-8', 'gbk//IGNORE', $item);
            }
            fputcsv($fp, $arr);
        }
        exit;
    }
}
