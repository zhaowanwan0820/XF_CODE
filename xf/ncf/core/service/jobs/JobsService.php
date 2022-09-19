<?php
/**
 * JobsService class file.
 **/

namespace core\service\jobs;

use core\service\BaseService;
use core\dao\jobs\JobsModel;

/**
 * JobsService
 */
class JobsService extends BaseService {
    private $_dataModel = null;

    public function __construct() {
        $this->_dataModel = new JobsModel();
    }

    /**
     * @param integer $id 记录ID号
     * @param bool $model 是否返回model对象
     * @return array
     */
    public function load($id, $model = false) {
        $data = array();
        $_data = $this->_dataModel->find($id);
        if ($_data) {
            if ($model) {
                $data = $_data;
            }
            else {
                $data = $_data->getRow();
            }
        }
        return $data;
    }

    public function getModel($id) {
        return $this->load($id, true);
    }


    public function redo($id) {
        $job = $this->load($id);
        if (empty($job)) {
            throw new \Exception('记录不存在');
        }
        if ($job['status'] != 3 && $job['status'] != 1) {
            throw new \Exception('无法加入队列，请等待队列执行完成');
        }
        $_toUpdate['status'] = 0;
        $_toUpdate['begin_time'] = 0;
        $_toUpdate['finish_time'] = 0;
        $GLOBALS['db']->autoExecute('firstp2p_jobs', $_toUpdate, 'UPDATE', " id = '{$id}' AND status IN (1,3)");
        $st = $GLOBALS['db']->affected_rows();
        if ($st <= 0) {
            throw new \Exception('重新入队失败，请重新操作');
        }
    }
}
