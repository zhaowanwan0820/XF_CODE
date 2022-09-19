<?php

use core\dao\BonusDispatchConfigModel;
use core\dao\BonusAccountModel;
use core\dao\BonusTaskModel;
/**
 * 红包返利账户配置表
 */
class BonusAccountAction extends CommonAction {

    public function index() {
        parent::index();
    }

    public function add() {
        $this->assign('taskTypes', BonusAccountModel::$taskTypeMap);
        $this->display();
    }

    public function insert() {
        $this->dataCheck();
        parent::insert();
    }

    public function edit() {
        $this->assign('taskTypes', BonusAccountModel::$taskTypeMap);
        parent::edit();
    }

    public function update() {
        $this->dataCheck();
        parent::update();
    }

    public function delete() {
    }

    private function dataCheck() {
        $taskType = $_POST['task_type'] = intval($_POST['task_type']);
        $taskId = $_POST['task_id'] = intval($_POST['task_id']);
        $res = false;
        if ($taskType == BonusAccountModel::TYPE_TASK) {
            $msg = '红包任务';
            $res = BonusTaskModel::instance()->find($taskId);
        } else if ($taskType == BonusAccountModel::TYPE_RULE){
            $msg = '返利规则';
            $res = BonusDispatchConfigModel::instance()->find($taskId);
        }
        if (!$res) {
            return $this->error('请填写正确的'.$msg.'id');
        }
    }
}

?>
