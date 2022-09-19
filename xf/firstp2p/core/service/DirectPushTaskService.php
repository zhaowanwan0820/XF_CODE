<?php
/**
 * 直推工具
 * @date 12 五月, 2016
 */

namespace core\service;

use libs\utils\Logger;
use core\dao\DirectPushTaskModel;
use core\service\directPush\DptUserStrategy;
use core\service\directPush\DptDealStrategy;
use core\service\directPush\DptInviteStrategy;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use core\event\Bonus\CouponEvent;
use libs\utils\Curl;

class DirectPushTaskService extends BaseService
{

    /**
     * 参数密钥
     */
    const SIGN_KEY = '!@$#P2P*%#2';

    /**
     * 添加任务接口
     */
    const BAIZE_API_TASK_ADD    = 'http://baize.corp.ncfgroup.com/api/taskadd';

    /**
     * 任务状态接口
     */
    const BAIZE_API_TASK_STAT   = 'http://baize.corp.ncfgroup.com/api/taskstatus';

    /**
     * 任务结果接口
     */
    const BAIZE_API_TASK_RESULT = 'http://baize.corp.ncfgroup.com/api/taskresult';

    /**
     * 字段
     */
    public static $dbFields = array(
        'id', 'name', 'send_way', 'type', 'conditions', 'status', 'scope_type', 'scope_ids', 'msg_type', 'coupon_ids',
        'msg_params', 'start_time', 'is_continuous', 'continuous_params', 'create_time', 'update_time', 'queue_id'
    );

    /**
     * createTask
     *
     * @param array $data
     * @access public
     * @return void
     */
    public function createTask(array $data, $submitUid = 0)
    {
        $time = time();
        $service = $this->getStrategy($data['type']);
        $sendTimes = $service->generateTimes($data);
        $GLOBALS['db']->startTrans();
        try {
            foreach ($sendTimes as $item) {
                $data['name'] = $item['name'];
                $data['start_time'] = $item['start_time'];
                $data['conditions'] = $service->buildConditonString($data);
                $insert = array();
                foreach(self::$dbFields as $field) {
                    if ($data[$field] === null || $data[$field] === '') {
                        continue;
                    }
                    $insert[$field] = $data[$field];
                }
                $taskId = DirectPushTaskModel::instance()->add($insert);
                $param = array(
                   'service_type' => 3,
                   'service_id'   => $taskId,
                   'standby_1'    => $insert['name'],
                   'standby_2'    => $insert['start_time'],
                   'status'       => 1,
                   'submit_uid'   => $submitUid,
                   'create_time'  => $time,
                   'update_time'  => $time,
                );
                $result = \core\dao\ServiceAuditModel::instance()->add($param);
                if ($result == false) {
                    throw new \Exception("任务插入失败");
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }

    /**
     * updateTask
     *
     * @param array $data
     * @access public
     * @return void
     */
    public function updateTask(array $data)
    {
        $service = $this->getStrategy($data['type']);
        $data['conditions'] = $service->buildConditonString($data);
        $update = array();
        foreach(self::$dbFields as $field) {
            if ($data[$field] === null || $data[$field] === '') {
                continue;
            }
            $update[$field] = $data[$field];
        }
        return DirectPushTaskModel::instance()->modify($update);

    }

    /**
     * enqueueToBaize
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    public function enqueueToBaize($data)
    {
        $params = $this->getStrategy($data['type'])->getParams($data);
        $params['client'] = 'P2P';
        $params['time'] = time();
        $url = self::BAIZE_API_TASK_ADD.'?'.http_build_query($params)."&sign=".$this->signature($params);
        $result = Curl::get($url);
        Logger::info('直推任务 添加任务'." | $url | $result");
        return json_decode($result, true);
    }

    /**
     * getResultFromBaize
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    public function getResultFromBaize($id)
    {
        $params = array('spark_id' => $id);
        $params['client'] = 'P2P';
        $params['time'] = time();
        $url = self::BAIZE_API_TASK_RESULT.'?'.http_build_query($params)."&sign=".$this->signature($params);
        $result = Curl::get($url);
        Logger::info('直推任务 获取结果'." | $url | ".substr($result, 0, 100));
        return json_decode($result, true);
    }

    /**
     * getStatusFromBaize
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    public function getStatusFromBaize($id)
    {
        $params = array('spark_id' => intval($id));
        $params['client'] = 'P2P';
        $params['time'] = time();
        $url = self::BAIZE_API_TASK_STAT.'?'.http_build_query($params)."&sign=".$this->signature($params);
        $result = Curl::get($url);
        Logger::info('直推任务 查询状态'." | $url | $result");
        return json_decode($result, true);
    }

    /**
     * getParams
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    public function getStrategy($type)
    {
        $service = null;
        switch ($type) {
            case 20:
                $service = new DptUserStrategy();
                break;
            case 21:
                $service = new DptDealStrategy();
                break;
            case 22:
                $service = new DptInviteStrategy();
                break;
        }
        return $service;
    }

    /**
     * getTaskById
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    public function getTaskById($id)
    {
        return DirectPushTaskModel::instance()->find($id);
    }

    /**
     * signature
     *
     * @param mixed $params
     * @param mixed $key
     * @access public
     * @return string
     */
    public function signature($params, $key = self::SIGN_KEY)
    {
        unset($params['sign']);
        ksort($params);
        reset($params);
        return md5(urldecode(http_build_query($params)).$key);
    }

    /**
     * addTasks
     *
     * @access public
     * @return void
     */
    public function addTasks()
    {
        $result = DirectPushTaskModel::instance()->findAllViaSlave(sprintf('status = 0 && queue_id = 0 && start_time < %s', get_gmtime() + 3600), true, '*');
        foreach ($result as $row) {
            $spark = $this->enqueueToBaize($row);
            if ($spark['status'] != 0 || $spark['data']['spark_id'] <= 0) {
                continue;
            }
            DirectPushTaskModel::instance()->updateBy(array('queue_id' => $spark['data']['spark_id']), "id={$row['id']}");
        }

        return true;
    }

    /**
     * runTasks
     *
     * @access public
     * @return void
     */
    public function runTasks()
    {
        $result = DirectPushTaskModel::instance()->findAllViaSlave('status = 0 && queue_id > 0 && is_effect = 1 && start_time < '.get_gmtime(), true, '*');
        $gtask = new GTaskService();
        $event = new CouponEvent();
        $serialNo = $sendNumber = 0;
        $directPushTaskModel = new DirectPushTaskModel();
        foreach ($result as $row) {
            $data = $this->getResultFromBaize($row['queue_id']);
            if ($data['status'] == 0) {
                $res = $directPushTaskModel->updateStatus($row['id'], 1, array('status' => 0));
                //$res = DirectPushTaskModel::instance()->updateBy(array('status' => 1), "id={$row['id']}");
                if (!$res) {
                    continue;
                }
                foreach($data['data']['spark_result'] as $uid) {
                    $event            = new CouponEvent();
                    $event->userId    = $uid;
                    $event->couponIds = $row['coupon_ids'];
                    $event->msgType   = $row['msg_type'];
                    $event->msgParams = $row['msg_params'];
                    $event->serialNo  = ++$serialNo;
                    $event->taskId    = $row['id'];

                    $result = $gtask->doBackground($event, 1, TASK::PRIORITY_NORMAL, null, 'domq_bonus');
                    if ($result) {
                      $sendNumber++; // 实际放入到队列的个数
                    }
                }
                $directPushTaskModel->updateStatus($row['id'], 2, array('status' => 1));
                //DirectPushTaskModel::instance()->updateBy(array('status' => 2), "id={$row['id']}");
                $this->noticeMail($row, $sendNumber);
            } else {
                Logger::info('直推任务异常'.var_export($row, true).var_export($result, true));
                continue; //有异常任务忽略，继续执行，保证正常任务不会被阻断
            }
            break;
        }

        return $sendNumber;
    }

    /**
     * noticeMail
     *
     * @param mixed $task
     * @param int $sendNumber
     * @access public
     * @return void
     */
    public function noticeMail($task, $sendNumber = 0) {

        $sendList = explode(',', \core\dao\BonusConfModel::get('DIRECT_PUSH_TASK_MAIL_LIST'));
        if (empty($sendList)) {
            return true;
        }
        parse_str($task['conditions'], $conditions);
        $summary = array();
        $subject = "【直推任务】" . $task['name'];

        $body = '<ul style="font-size:px;color:#1f497d;font-weight:bold;">';
        $body .= '<b style="color:red;">发送信息如下：</b>';
        $body .= "<div><b>任务ID: {$task['id']}</b></div>";
        if ($task['send_way'] == 1) {
            $body .= "<div><b>类型: 按名单发送</b></div>";
        } elseif ($task['type'] == 2) {
            $body .= "<div><b>类型: 按条件发送</b></div>";
            $body .= "<div><b>类型: 按条件发送</b></div>";
            $body .= "<div><b>条件: ".$this->getStrategy($task['type'])->buildInfo($conditions)."</b></div>";
        }
        $body .= "<div><b>成功放入队列个数: {$sendNumber}个</b></div>";
        $body .= '</ul>';

        $msgcenter = new \Msgcenter();
        $msgcenter->setMsg(implode(',', $sendList), 0, $body, false, $subject);
        $msgcenter->save();
    }
}
