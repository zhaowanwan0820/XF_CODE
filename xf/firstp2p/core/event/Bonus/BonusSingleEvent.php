<?php
/**
 *-------------------------------------------------------
 * 按照用户组信息以及标签信息批量给用户发送红包
 *-------------------------------------------------------
 * 2014-12-29 17:05:35
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use core\service\BonusService;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\event\Bonus\BonusTaskEvent;
use libs\lock\LockFactory;
use core\service\MsgBoxService;
use core\service\bonus\BonusPush;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

/**
 * BonusBatchEvent
 * 分进程发送红包
 *
 * @uses AsyncEvent
 * @package default
 */
class BonusSingleEvent extends BaseEvent
{

    private $user_id = 0;
    private $mobile = '';
    private $type = 11;
    private $task = null;

    public $money = 0;
    public  $is_sign = false;
    public  $limit_start_time = 0;
    public  $limit_count = 7;
    public  $limit_money = 5;

    //private $lock_key = '';
    //private $lock_time = 86400;

    public function __construct($task) {
        $this->task = $task;
    }

    public function setSendUser($user_id = 0, $mobile = '') {
        $this->user_id = trim($user_id);
        $this->mobile = trim($mobile);
        //$this->lock_key = sprintf('task_bonus_single_event_%s_%s_%s', strval($this->task['id']), strval($user_id), strval($mobile));
    }

    /**
     * 执行发送红包
     */
    public function execute() {

        /*$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($this->lock_key, $this->lock_time)) {
            return false;
        }*/

        $bonus_model = new \core\dao\BonusModel();

        if ($this->mobile == '') {//查询数据库防止重复发送
            $result = $bonus_model->findBy("owner_uid='{$this->user_id}' && task_id={$this->task['id']}", 'id', array(), true);
        } else {
            $result = $bonus_model->findBy("mobile='{$this->mobile}' && task_id={$this->task['id']}", 'id', array(), true);
        }

        if (!empty($result)) {
            return true;
            //throw new \Exception("数据已经插入成功，请勿重复执行。");
        }

        $created_at = time();
        $expired_at = $created_at + $this->task['use_limit_day'] * 86400;
        if (empty($this->mobile)) { //获取用户的手机号
            $result  = \core\dao\UserModel::instance()->find($this->user_id, 'mobile, user_type', true);
            $this->mobile = $result['mobile'];
        }
        if (empty($this->user_id)) { //获取用户的uid
            $result = \core\dao\UserModel::instance()->findBy("mobile='{$this->mobile}'", 'id, user_type', array(), true);
            $this->user_id = $result['id'];
        }
        if ($this->mobile == '' && $this->user_id == '') {
            throw new \Exception("用户不存在。");
        }

        // if (substr($this->mobile, 0, 1) == '6' || $result['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
        //     return true;
        // }

        // if (!preg_match('/^1[0-9]{10}/', $this->mobile)) {
        //     $log = "手机号错误\tuid={$this->user_id}";
        //     Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH . 'log/logger/bonus_task_'. $this->task['id'].date('_Y-m-d'). '.log');
        //     return true;
        //     //throw new \Exception("手机号码格式错误。");
        // }

        if ($this->is_sign == true) {
            if (!$this->checkSend()) {
                $log = "该用户不符合发送资格\tid={$this->user_id}";
                Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH . 'log/logger/bonus_task_'. $this->task['id'].date('_Y-m-d'). '.log');
                return true;
            }
        }

        for($i = 0; $i < $this->task['times']; $i++) {
            $data = array( //单个红包数据
                'owner_uid'  => $this->user_id,
                'mobile'     => $this->mobile,
                'status'     => 1,
                'type'       => $this->type,
                'money'      => ($this->money != 0 ? $this->money : $this->task['money']),
                'task_id'    => $this->task['id'],
                'created_at' => $created_at,
                'expired_at' => $expired_at
            );
            $res = $bonus_model->single_bonus(0, 0, $this->user_id, $this->mobile, 1, $data['money'], $created_at, $expired_at, '', '', $this->type, 0, $this->task['id']);
            //$bonus_model->setRow($data);
            //$bonus_model->_isNew = true;
            //$res = $bonus_model->save();
        }

        $sms_res = array();
        if ($this->task['is_sms'] == 1 && $this->task['sms_temp_id'] > 0) {
            $sms_res = \SiteApp::init()->sms->send($this->mobile, "。", $this->task['sms_temp_id'], 0);
        }
        if ($this->task['is_sms'] == 2) {
            $msgbox = new MsgBoxService();
            $config = BonusPush::getConfig(BonusPush::GET_BONUS);
            $content = sprintf($config['content'], $this->task['times'], number_format($data['money'], 2), $this->task['use_limit_day']);
            $sms_res = $msgbox->create($this->user_id, MsgBoxEnum::TYPE_BONUS, $config['title'], $content);
        }
        $log = sprintf("uid=%s\tis_sms=%s\tresult=%s\tsms_res=%s", $this->user_id, $this->task['is_sms'], $res, json_encode($sms_res));
        Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH . 'log/logger/bonus_task_'. $this->task['id'].date('_Y-m-d'). '.log');
        return true;
    }

    public function checkSend() {
        if ($this->limit_start_time == 0) {
            $this->limit_start_time = strtotime(date('Y-m-d', strtotime('-7 days')));
        }
        $count_sql = sprintf('SELECT COUNT(id) FROM firstp2p_deal_load where user_id = %s && create_time >= %s', $this->user_id, ($this->limit_start_time - 28800));
        $count = intval(\core\dao\DealLoadModel::instance()->countBySql($count_sql, array(), true));
        if (bccomp($count, $this->limit_count) == 1) {
            return false;
        }

        $bonus_service = new BonusService();
        $money = $bonus_service->get_useable_money($this->user_id);
        if (bccomp($money['money'], $this->limit_money, 2) == 1) {
            return false;
        }
        return true;
    }

    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }
}
