<?php
/**
 * CheckJobsWorkerAction.class.php
 * 环境db及JobsWorker情况
 * @date 2016-01-28
 * @author 樊靖雯 <fanjignwen@ucfgroup.com>
 */

class CheckJobsWorkerAction extends CommonAction {

    public function index()
    {
        $list = array();
        for ($i = 0; $i < 29; ++$i) {
            $list[$i] = sprintf("%02d", $i + 1);
        }
        $this->assign('list', $list);
        // 调用展示页面
        $this->display();
    }

}

?>
