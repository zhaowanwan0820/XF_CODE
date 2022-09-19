<?php
/**
 *-------------------------------------------------------
 * 投资券发送
 *-------------------------------------------------------
 * 2016-01-28 16:05:35
 *-------------------------------------------------------.
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\MsgBoxService;
use core\service\O2OService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use core\dao\UserMsgConfigModel;

/**
 * BonusBatchEvent
 * 投资券任务
 *
 * @uses AsyncEvent
 */
class DiscountEvent extends BaseEvent
{
    private $user_id = 0;
    private $mobile = '';
    private $type = 11;
    private $task = null;

    public $serial_no = 0;

    public function __construct($task)
    {
        $this->task = $task;
    }

    public function setSendUser($user_id = 0, $mobile = '')
    {
        $this->user_id = trim($user_id);
        $this->mobile = trim($mobile);
    }

    /**
     * 执行发送投资券.
     */
    public function execute()
    {
        if (empty($this->mobile)) { //获取用户的手机号
            $result  = \core\dao\UserModel::instance()->find($this->user_id, 'mobile, user_type, site_id', true);
            $this->mobile = $result['mobile'];
            $siteId = $result['site_id'];
        }
        if (empty($this->user_id)) { //获取用户的uid
            $result = \core\dao\UserModel::instance()->findBy("mobile='{$this->mobile}'", 'id, user_type, site_id', array(), true);
            $this->user_id = $result['id'];
            $siteId = $result['site_id'];
        }

        if ($this->mobile == '' && $this->user_id == '') {
            throw new \Exception("用户不存在。");
        }

        // if (substr($this->mobile, 0, 1) == '6' || $result['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
        //     return true;
        // }

        //调用O2O发送投资券
        $o2oService = new O2OService();
        $groupIds = explode(',', $this->task['discount_group_id']);
        foreach ($groupIds as $key => $groupId) {
            $result = $o2oService->acquireDiscount($this->user_id, $groupId, md5($this->user_id."|".$this->task['id']."|".$this->serial_no."|".$key));
            if (!$result) {
                $log = sprintf("uid=%s\tis_sms=%s\tgroup_id=%s\tresult=%s", $this->user_id, $this->task['is_sms'], $groupId, json_encode($result));
                Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH.'log/logger/bonus_task_'.$this->task['id'].date('_Y-m-d').'.log');
                return false;
            }
        }

        $sms_res = array();
        if ($this->task['is_sms'] == 1 && $this->task['sms_temp_id'] > 0 && $result['isAcquired'] == 0) {
            //$sms_res = \SiteApp::init()->sms->send($this->mobile, "{$this->task['money']},{$this->task['use_limit_day']}", $this->task['sms_temp_id'], 0);
            $msgConfig = UserMsgConfigModel::instance()->getSwitches($this->user_id, 'sms_switches');
            if (!isset($msgConfig[MsgBoxEnum::TYPE_DISCOUNT]) || $msgConfig[MsgBoxEnum::TYPE_DISCOUNT] == 1) {
                $smsTpls = array_flip($GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']);
                if (isset($smsTpls[$this->task['sms_temp_id']])) {
                    $digit = $result['type'] == 3 ? 3 : 2;
                    $params    = array(count($groupIds), number_format($this->task['money'], $digit), $this->task['use_limit_day']);
                    if ($siteId > 1) {
                        $siteTitle = \libs\utils\Site::getTitleById($siteId);
                        $siteTitle = $siteTitle ? "[$siteTitle]" : '';
                    } else {
                        $siteTitle = '';
                    }
                    array_unshift($params, $siteTitle);

                    $appSecret = $GLOBALS['sys_config']['SMS_SEND_CONFIG']['APP_SECRET'];
                    $sms_res = \NCFGroup\Common\Library\Sms\Sms::send(APP_NAME, $appSecret, $this->mobile, $smsTpls[$this->task['sms_temp_id']], $params);
                    //require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
                    //$msgcenter = new \Msgcenter();
                    //$msgcenter->setMsg($this->mobile, $this->user_id, $params, $smsTpls[$this->task['sms_temp_id']], '投资券奖励');
                    //$sms_res = $msgcenter->save();
                } else {
                    $sms_res = array('errMsg' => $this->task['sms_temp_id'].'模板不存在');
                }
            }
        }

        if ($this->task['is_sms'] == 2) {
            $msgConfig = UserMsgConfigModel::instance()->getSwitches($this->user_id, 'push_switches');
            if (isset($msgConfig[MsgBoxEnum::TYPE_DISCOUNT]) && $msgConfig[MsgBoxEnum::TYPE_DISCOUNT] == 0) {
                $msgbox = new MsgBoxService();
                if (in_array($result['type'], array(1, 2, 3))) {
                    $couponType = '返现券';
                    $unit = '元';
                    $digit = 2;
                    if ($result['type'] == 2) {
                        $couponType = '加息券';
                        $unit = '%';
                    } elseif ($result['type'] == 3) {
                        $couponType = '黄金券';
                        $unit = '克';
                        $digit = 3;
                        $this->task['money'] = $this->task['money'] / 10;
                    }
                    $content = sprintf("您收到了%s张%s共%s%s，有效期%s天，请尽快使用。", count($groupIds), $couponType, number_format($this->task['money'], $digit), $unit, $this->task['use_limit_day']);
                    $sms_res = $msgbox->create($this->user_id, MsgBoxEnum::TYPE_DISCOUNT, '获得优惠券', $content);
                }
            }
        }

        $log = sprintf("uid=%s\tis_sms=%s\tresult=%s\tsms_res=%s", $this->user_id, $this->task['is_sms'], json_encode($result), json_encode($sms_res));
        Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH.'log/logger/bonus_task_'.$this->task['id'].date('_Y-m-d').'.log');

        return true;
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
