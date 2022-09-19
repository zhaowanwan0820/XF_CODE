<?php

/**
 * 预约相关
 *
 * @author sunxuefeng@ucfgroup.com
 */

use core\service\booking\BookService;

class BookingSessionAction extends CommonAction {

    public static $SESSIONS_STATUS = array(
        BookService::SESSION_STATUS_NORMAL => '可预约',
        BookService::SESSION_STATUS_FULL => '已约满',
        BookService::SESSION_STATUS_EXPIRED => '已过期',
    );

    public function index($deal_type = 0) {
        $name = $this->getActionName();
        $model = DI($name);

        // 不展示无效场次
        $sessions = $model->where('status!='.BookService::SESSION_STATUS_INVALID)->order("`id` DESC")->findAll();
        foreach($sessions as &$session) {
            $session['start_time'] = date('Y-m-d H:i:s', $session['start_time']);
            $session['end_time'] = date('Y-m-d H:i:s', $session['end_time']);
            $session['status'] = self::$SESSIONS_STATUS[$session['status']];
            $session['city'] = BookService::$CITYS[$session['city']];
        }

        $this->assign('list', $sessions);
        $this->display ('index');
    }

    /**
     * 删除场次
     */
    public function delete() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if ($id) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );

            $list = M(MODULE_NAME)->where($condition)->save(array('status' => BookService::SESSION_STATUS_INVALID));
            if ($list!==false) {
                save_log(l("DELETE_SUCCESS"),1);
                $this->success (l("DELETE_SUCCESS"),$ajax);
            } else {
                save_log(l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 添加场次
     */
    public function add() {

        $citys = BookService::$CITYS;
        $this->assign('citys', $citys);

        $this->display('add');
    }

    /**
     * 增加场次 ajax
     */
    public function insert()
    {
        if (!isset(BookService::$CITYS[$_POST['citys_name']])) {
            $this->error('invalid city id');
        }

        if (intval($_POST['limit_count']) < 1) {
            $this->error('limit count error');
        }

        if (!strtotime($_POST['start_time']) || !strtotime($_POST['end_time'])) {
            $this->error('error format of time');
        }

        if (!trim($_POST['remark'])) {
            $this->error('not found mark');
        }

        $currentTime = time();
        $data = array(
            'city' => $_POST['citys_name'],
            'limit_count' => intval($_POST['limit_count']),
            'start_time' => strtotime($_POST['start_time']),
            'end_time' => strtotime($_POST['end_time']),
            'status' => BookService::SESSION_STATUS_NORMAL,
            'remark' => $_POST['remark'],
            'created_at' => $currentTime,
            'modify_at' => $currentTime,
        );

        $m = M(MODULE_NAME);
        //开始验证有效性
        $res = $m->add($data);
        if (!$res) {
            $this->error('add error');
        }

        $this->redirect(u(MODULE_NAME."/index"));
    }

    /**
     * 编辑场次
     */
    public function edit() {
        $id = intval($_REQUEST ['id']);

        $session = M('BookingSession')->where(array('id' =>$id))->find();
        $session['start_time'] = date('Y-m-d H:i:s', $session['start_time']);
        $session['end_time'] = date('Y-m-d H:i:s', $session['end_time']);

        $citys = BookService::$CITYS;

        $this->assign('citys', $citys);
        $this->assign('session', $session);
        $this->display('edit');
    }

    public function save() {
        if (!isset($_REQUEST['id'])) {
            $this->error('id is empty');
        }
        if (!isset(BookService::$CITYS[$_POST['citys_name']])) {
            $this->error('invalid city id');
        }

        if (intval($_POST['limit_count']) < 1) {
            $this->error('limit count error');
        }

        if (!strtotime($_POST['start_time']) || !strtotime($_POST['end_time'])) {
            $this->error('error format of time');
        }

        if (!trim($_POST['remark'])) {
            $this->error('not found mark');
        }

        $currentTime = time();
        $data = array(
            'city' => $_POST['citys_name'],
            'limit_count' => intval($_POST['limit_count']),
            'start_time' => strtotime($_POST['start_time']),
            'end_time' => strtotime($_POST['end_time']),
            'status' => BookService::SESSION_STATUS_NORMAL,
            'remark' => $_POST['remark'],
            'created_at' => $currentTime,
            'modify_at' => $currentTime,
        );

        $res = M(MODULE_NAME)->where("id = {$_REQUEST['id']}")->save($data);
        if (!$res) {
            $this->error('fail to save');
        }

        $this->redirect(u(MODULE_NAME."/index"));
    }
}
