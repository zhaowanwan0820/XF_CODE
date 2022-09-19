<?php
/**
 * AccountCheckAction class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
class AccountCheckAction extends CommonAction{

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        //定义条件
        $where = '1 = 1';
        $timeStart = trim($_REQUEST['time_start']);
        $timeEnd = trim($_REQUEST['time_end']);

        if ($timeStart) {
            $where .= " AND create_time >= '". to_timespan($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND create_time <= '". to_timespan($timeEnd) ."'";
        }

        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $where );
        }
        $this->assign('main_title', "余额对账列表");
        $this->display ();
    }

    public function edit() {

        // 获取要更新的记录
        $id = intval($_REQUEST['id']);
        $remark = trim($_REQUEST['remark']);

        $result = true;
        // 更新
        if ($id && $remark) {
            $result = $GLOBALS['db']->autoExecute(DB_PREFIX."account_check",array("remark"=>$remark),"UPDATE","id=".$id);
        }

        if ($result) {
            $this->success (l("UPDATE_SUCCESS"));
        } else {
            $this->error (l("UPDATE_FAILED"));
        }

    }
}
?>
