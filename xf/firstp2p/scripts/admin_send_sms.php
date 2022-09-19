<?php
/**
 * 每1分钟发送一次短信，量根据网关处理能力定
 * * * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php admin_send_sms.php
 */

set_time_limit(0);
ini_set('memory_limit', '4096M');

require_once(dirname(__FILE__) . '/../app/init.php');
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

FP::import("libs.common.dict");

use libs\utils\Logger;
use libs\utils\Alarm;

class AdminSendSms {

    const BATCH_SEND_LIMIT = 10000; //每分钟处理量
    const SEND_RETRY_TIMES = 1; //重试次数

    private $taskInfo = array();
    private $taskUserList = array();

    public function setTaskInfo() {
        $sql = 'SELECT * FROM firstp2p_sms_task WHERE task_status IN(5, 8) and ( expect_send_time = 0 or (expect_send_time > 0 and expect_send_time < '.time().') ) ORDER BY id ASC LIMIT 1';
        $result = $GLOBALS['msg_box_db']->getRow($sql);
        if (!empty($result)) {
            $this->taskInfo = (array) $result;
            Logger::info('处理短信任务:' . json_encode($this->taskInfo, JSON_UNESCAPED_UNICODE));
        }
    }

    public function getRedisKey() {
        return 'admin-sms-task-offset:' . $this->taskInfo['id'];
    }

    public function setTaskUserList() {
        if (empty($this->taskInfo)) {
            return true;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redisOffset = intval($redis->get($this->getRedisKey()));
        $mysqlOffset = intval($this->taskInfo['offset']);

        $taskId = intval($this->taskInfo['id']);
        $offset = max($redisOffset, $mysqlOffset);

        $sql  = "SELECT id, mobile_code, mobile, ext_content FROM firstp2p_sms_task_user ";
        $sql .= "WHERE sms_task_id = {$taskId} AND status = 0 AND send_time = 0 AND id > {$offset} ORDER BY id ASC LIMIT " . self::BATCH_SEND_LIMIT;
        $result = $GLOBALS ['msg_box_db']->getAll($sql);
        if (!empty($result)) {
            $this->taskUserList = (array) $result;
            $first = current($this->taskUserList);
            $last  = end($this->taskUserList);
            Logger::info("处理用户短信，id区间为[{$first['id']}, {$last['id']}]");
        }
    }

    public function modifyTaskInfo($data) {
        $updateFields = array();
        foreach ($data as $field => $value) {
            $updateFields[] = "{$field} = '{$value}'";
        }
        $updateFields = implode(', ', $updateFields);

        $sql = "UPDATE firstp2p_sms_task SET {$updateFields} WHERE id = " . $this->taskInfo['id'];
        $result = $GLOBALS ['msg_box_db']->query($sql);
        if (empty($result)) {
            Logger::error("更新短信任务失败, id" . $this->taskInfo['id']);
            throw new \Exception("更新短信任务失败");
        }
    }

    public function dealOneTaskUser($item) {
        $search = $replace = array();
        $extContent = json_decode($item['ext_content'], true);
        foreach ($extContent as $key => $val) {
            $search[]  = trim(sprintf('{%s}', $key));
            $replace[] = trim($val);
        }

        $content = str_replace($search, $replace, $this->taskInfo['content']);
        $result = $this->sendSms($item['mobile'], [$content], $id);
        return $result;
       // for ($time = 1; $time <= self::SEND_RETRY_TIMES; $time ++) {
       //     Logger::info(sprintf("发送数据:%s", json_encode($item, true)));
       //     if ($result) {
       //         $redis = \SiteApp::init()->dataCache->getRedisInstance();
       //         $redis->setex(self::getRedisKey(), 10 * 24 * 60 * 60, $item['id']); //放10天
       //         return true;
       //     }
 //           sleep(rand(1, 3));
       // }

 //       libs\utils\Alarm::push(sprintf("短信发送失败, 任务ID:%s, 短信ID:%s", $this->taskInfo['id'], $item['id']));
       // Logger::error("短信发送失败, 任务:" . json_encode($this->taskInfo, JSON_UNESCAPED_UNICODE) . ', 用户:' . json_decode($item, JSON_UNESCAPED_UNICODE));
       // return false;
    }

    public function dealBatchTaskUser($items) {
        $extent = json_decode(current($items)['ext_content'], true);
        if (empty($extent)) {
            $mobiles = [];
            $ids = [];
            $id = 0;
            foreach ($items as $item) {
                $id = $item['id'];
                $ids[] = $id;
                $mobiles[] = $item['mobile'];
            }
            $result = $this->sendSms($mobiles, [$this->taskInfo['content']], $id);
            if ($result) {
                $this->modifyTaskUser(1, $ids);
            }else {
                $this->modifyTaskUser(3, $ids);
            }

            return;
        }
        foreach ($items as $item) {
            $search = $replace = array();
            $extContent = json_decode($item['ext_content'], true);
            foreach ($extContent as $key => $val) {
                $search[] = trim(sprintf('{%s}', $key));
                $replace[] = trim($val);
            }

            $content = str_replace($search, $replace, $this->taskInfo['content']);
            $result = $this->sendSms($item['mobile'], [$content], $item['id']);
            if ($result) {
                $this->modifyTaskUser(1, [$item['id']]);
            }else {
                $this->modifyTaskUser(3, [$item['id']]);
            }
        }
    }

    public function modifyTaskUser($status, $ids) {
        if (empty($ids)) {
            return true;
        }

        $sendTime = time();
        $idStr = implode(', ', $ids);

        $sql = "UPDATE firstp2p_sms_task_user SET send_time = {$sendTime}, status = {$status} WHERE sms_task_id = {$this->taskInfo['id']} AND id IN({$idStr})";
        $result = $GLOBALS ['msg_box_db']->query($sql);
        if (empty($result)) {
            Logger::error("更新短信列表失败, 任务id:" . $this->taskInfo['id'] . ', ids:' . $idStr);
            throw new \Exception("更新短信列表失败");
        }
    }

    private function sendSms($mobile, $content, $id)
    {
        $appSecret = $GLOBALS['sys_config']['SMS_SEND_CONFIG']['APP_SECRET'];
        for ($time = 1; $time <= self::SEND_RETRY_TIMES; $time ++) {
            Logger::info(sprintf("admin send sms. sms send. task:".json_encode($this->taskInfo, JSON_UNESCAPED_UNICODE).", mobiles:{$mobile}, lastId:{$id}"));
            $result = \NCFGroup\Common\Library\Sms\Sms::send('p2pmarketing', $appSecret, $mobile, 'marketing', $content);
            if ($result['code'] === 0) {
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                $redis->setex(self::getRedisKey(), 10 * 24 * 60 * 60, $id); //放10天
                $this->taskInfo['offset'] = $id;
                return true;
            }
        }
        Logger::error("admin send sms failed. task:" . json_encode($this->taskInfo, JSON_UNESCAPED_UNICODE) . ", mobile:{$mobile}");
        return false;
    }

    public function setTaskFinish() {
        if (empty($this->taskInfo)) {
            return true;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redisOffset = intval($redis->get($this->getRedisKey()));
        $mysqlOffset = intval($this->taskInfo['offset']);

        $taskId = intval($this->taskInfo['id']);
        $offset = max($redisOffset, $mysqlOffset);

        $sql  = "SELECT count(*) AS count FROM firstp2p_sms_task_user WHERE sms_task_id = {$taskId} AND status = 0 AND send_time = 0 AND id > {$offset}";
        $result = $GLOBALS ['msg_box_db']->getAll($sql);
        if (empty($result)) {
            Logger::error("获取剩余短信条数失败, 任务id:" . $this->taskInfo['id']);
            throw new \Exception("获取剩余短信条数失败");
        }

        if (!$result[0]['count']) {
            $this->modifyTaskInfo(array('task_status' => 6, 'send_time' => time()));
        }
    }

    public function batchDealTaskUserList() {
        if (empty($this->taskUserList)) {
            $this->setTaskFinish();
            return true;
        }

        if ($this->taskInfo['task_status'] == 8) {
            $this->modifyTaskInfo(array('task_status' => 5));
        }

        $step = 100;
        for ($i=0; $i< count($this->taskUserList); $i=$i+$step) {
            $items = array_slice($this->taskUserList, $i, $step);
            $this->dealBatchTaskUser($items);
        }

        $this->setTaskFinish();
    }

    public function dealTaskUserList() {
        if (empty($this->taskUserList)) {
            $this->setTaskFinish();
            return true;
        }

        if ($this->taskInfo['task_status'] == 8) {
            $this->modifyTaskInfo(array('task_status' => 5));
        }

        $failIds = $succIds = array();
        foreach($this->taskUserList as $count => $item) {
            $result = $this->dealOneTaskUser($item);
            $result ? $succIds[] = $item['id'] : $failIds[] = $item['id'];
            if (($count + 1) % 1000 == 0) {
                $this->modifyTaskInfo(array('offset' => $item['id']));
                $this->modifyTaskUser(1, $succIds);
                $this->modifyTaskUser(3, $failIds);
            }
        }

        if (($count + 1) % 1000 != 0) {
            $this->modifyTaskInfo(array('offset' => $item['id']));
            $this->modifyTaskUser(1, $succIds);
            $this->modifyTaskUser(3, $failIds);
        }

        $this->taskInfo['offset'] = $item['id'];
        $this->setTaskFinish();
    }

    public function getLockKey() {
        return 'admin_send_sms_lock';
    }

    public function checkLock() {
        $cmd = "ps -ef | grep admin_send_sms | grep -v grep | grep -v " . getmypid();
        exec($cmd, $output, $return); //进程判断，单机可用
        if (!empty($output) || 1 !== $return) {
            Logger::info("上次没有执行完成, 跳过");
            exit(0);
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $lock = $redis->setnx($this->getLockKey(), 1);
        if (!$lock) {
            Logger::info("上次没有执行完成, 跳过");
            exit(0);
        }
    }

    private function _setMsgBoxDB() {
        $GLOBALS['msg_box_db'] = \libs\db\Db::getInstance('msg_box');
    }

    public function run($mode=0) {
        Logger::info("开始执行短信任务");
        //放析构就是不好使，不知道为啥
        register_shutdown_function(function() {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $redis->del($this->getLockKey());
        });

        $this->checkLock();
        $this->_setMsgBoxDB();
        $this->setTaskInfo();
        $this->setTaskUserList();
        if ($mode) {
            $this->batchDealTaskUserList();
        } else {
            $this->dealTaskUserList();
        }
    }

}

$mode = isset($argv[1]) ? $argv[1] : 0;
$obj = new AdminSendSms();
$obj->run($mode);
