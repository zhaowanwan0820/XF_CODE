<?php

/**
 *  批量更改用户状态历史记录 
 * @author liuzhenpeng
 */
class batchUserLogAction extends CommonAction{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!empty($_REQUEST['time_start'])) {
            $time_start = trim($_REQUEST['time_start']) . ' 00:00:00';
            $start_time = to_timespan($time_start);
            $map['finish_time'][] = array('egt', $start_time);
        }

        if (!empty($_REQUEST['time_end'])) {
            $time_end = trim($_REQUEST['time_end']) . ' 23:59:59';
            $end_time = to_timespan($time_end);
            $map['finish_time'][] = array('elt', $end_time);
        }

        //取列表数据
        $model = M ('BatchUserChange');

        $this->_list ($model, $map);
        $this->display ();
    }

    public function show()
    {
        $id = (int)trim($_REQUEST['id']);
        $id = ($id>0) ? $id : $this->error('参数错误');

        $model = M('BatchUserRes');
        $this->_list($model, array('batch_id' => $id));

        $this->display ();
    }
}


