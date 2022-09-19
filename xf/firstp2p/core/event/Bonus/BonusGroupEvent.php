<?php
/**
 *-------------------------------------------------------
 * 按照用户组信息以及标签信息批量给用户发送红包
 *-------------------------------------------------------
 * 2015-04-27 10:57:32
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use NCFGroup\Task\Events\AsyncEvent;
use core\service\BonusService;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\event\Bonus\BonusTaskEvent;
use libs\lock\LockFactory;
use core\service\MsgBoxService;
use core\service\bonus\BonusPush;

/**
 * BonusBatchEvent
 * 分进程发送红包
 *
 * @uses AsyncEvent
 * @package default
 */
class BonusGroupEvent extends BaseEvent
{

    private $user_id = 0;
    private $mobile = '';
    private $type = 11;
    private $task = null;

    //private $lock_key = '';
    //private $lock_time = 86400;

    public function __construct($task) {
        $this->task = $task;
    }

    public function setSendUser($user_id = 0, $mobile = '') {
        $this->user_id = trim($user_id);
        $mobile = strval(trim($mobile));
        if (empty($this->user_id)) { //获取用户的uid
            $result = \core\dao\UserModel::instance()->findBy("mobile='{$mobile}'", 'id', array(), true);
            $this->user_id = $result['id'];
        }
        //$this->lock_key = sprintf('task_bonus_group_event_%s_%s_%s', strval($this->task['id']), strval($user_id), strval($mobile));
    }

    /**
     * 执行发送红包
     */
    public function execute() {

        /*$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($this->lock_key, $this->lock_time)) {
            return false;
        }*/

        if (empty($this->user_id)) {
            throw new \Exception("用户ID为空。");
        }

        //if (\core\dao\UserModel::instance()->isEnterpriseUser($this->user_id)) {
            //return true;
        //}

        $bonus_group_model = new \core\dao\BonusGroupModel();
        $result = $bonus_group_model->findBy("user_id='{$this->user_id}' AND task_id=".$this->task['id'], 'id', array(), true);
        if (!empty($result)) {
            return true;
            //throw new \Exception("任务已经执行，请勿重复执行。");
        }

        $created_at = time();
        $expired_at = $created_at + $this->task['send_limit_day'] * 86400;

        for($i = 0; $i < $this->task['times']; $i++) {
            $data = array( //红包组数据
                'user_id'       => $this->user_id,
                'bonus_type_id' => 1,
                'count'          => $this->task['count'],
                'money'         => $this->task['money'],
                'created_at'    => $created_at,
                'expired_at'    => $expired_at,
                'task_id'       => $this->task['id']
            );
            $bonus_group_model->setRow($data);
            $bonus_group_model->_isNew = true;
            $res = $bonus_group_model->save();
        }

        $sms_res = array();
        if ($this->task['is_sms'] == 1 && $this->task['sms_temp_id'] > 0) {
            $sms_res = \SiteApp::init()->sms->send($this->mobile, "。", $this->task['sms_temp_id'], 0);
        }
        if ($this->task['is_sms'] == 2) {
            $msgbox = new MsgBoxService();
            $config = BonusPush::getConfig(BonusPush::GET_GROUP);
            $content = sprintf($config['content'], $this->task['count'], number_format($this->task['money'], 2), $this->task['send_limit_day']);
            $sms_res = $msgbox->create($this->user_id, MsgBoxEnum::TYPE_BONUS, $config['title'], $content);
        }
        $log = sprintf("uid=%s\tis_sms=%s\tresult=%s\tsms_res=%s", $this->user_id, $this->task['is_sms'], $res, json_encode($sms_res));
        Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH . 'log/logger/bonus_task_'. $this->task['id'].date('_Y-m-d'). '.log');
        return true;
    }

    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }
}
