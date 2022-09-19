<?php

/**
 * @file RemindService.php
 * @date 2013/10/25
 * 提醒类
 **/
class NewRemindService extends ItzInstanceService
{


    protected $expire1 = 3600;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;
    protected static $_getTriggerTemplate = null;
    protected static $_getSystemConfig = null;

    private static $_info = 'SendError: ';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 给用户发送提醒
     * @param array $data
     * @param string $sendMessage
     * @param string $sendEmail
     * @param string $sendPhone
     * @return boolean
     */
    public static function SendToUser($data, $sendMessage = true, $sendEmail = true, $sendPhone = true)
    {
        self::$_info = 'SendError: ';
        self::$_info .= __FUNCTION__ . " " . print_r(func_get_args(), true);
        if (empty($data) || !isset($data['receive_user'])) {
            self::$_info .= ' LINE:' . __LINE__ . ' send data is empty or receive_user is empty';
            return self::_writeLog();
        }
        list($mtype, $TriggerPointReocrd) = self::_getTriggerPointReocrd($data);
        #是否存在该触发点
        if (empty($TriggerPointReocrd)) {
            self::$_info .= ' LINE:' . __LINE__ . ' Trigger Point not exists';
            return self::_writeLog();
        }
        // 获取邮件、短信开关信息
        $systemConfig = self::_getSystemConfig();

        $UserClass = new UserClass();
        $userRecord = $UserClass->getAdminUser($data['receive_user']);
        if($userRecord) {
            $temp = [];
            $temp['phone'] = $userRecord->phone;
            $temp['email'] = $userRecord->email;
            $temp['remind'] = "a:0:{}";
            $userRecord = $temp;
        } else {
            $userRecord = (Object) [];
            $userRecord->remind = "a:0:{}";
            $userRecord->email = '';
            $userRecord->phone = '';
            $userRecord = (Array) $userRecord;
        }
        #获取短信模板
        list($smsTrigger, $messageTrigger, $emailTrigger) = self::_getTriggerTemplate($mtype);
           
        $email = isset($data['email']) ? $data['email'] : $userRecord['email'];
        $phone = isset($data['phone']) ? $data['phone'] : $userRecord['phone'];
        $delaySmsTime = isset($data['delaySmsTime']) ? $data['delaySmsTime'] : null;
        $delayEmailTime = isset($data['delayEmailTime']) ? $data['delayEmailTime'] : null;
        $flag = 0;
        if (!empty($messageTrigger) && $sendMessage) {
            $sendResult = $messageResult = self::_sendMessage($data, $messageTrigger, $mtype, $userRecord);
            $flag++;
        }

        if ((!empty($emailTrigger) && $systemConfig['con_emailsend'] == '1' && $sendEmail) && isset($email)) {
            $sendResult = $mailResult = self::_sendEmail($data, $emailTrigger, $email, $mtype, $userRecord, $delayEmailTime);
            $flag++;

        }
        if ((!empty($smsTrigger) && $systemConfig['con_smssend'] == '1' && $sendPhone) && isset($phone)) {
            $sendResult = $smsResult = self::_sendSms($data, $smsTrigger, $userRecord, $phone, $mtype, $delaySmsTime);
            $flag++;

        };
        if($flag > 1) {
            return true;
        } else {
            return $sendResult;
        }
    }

    /**
     * [getMtype 获取触发点]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private static function getMtype($data)
    {
        if (isset($data['type']) && !isset($data['mtype'])) return $data['type'];
        return $data['mtype'];
    }

    /**
     * [_getTriggerPointReocrd 获取触发点相关信息]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private static function _getTriggerPointReocrd($data)
    {
        $mtype = self::getMtype($data);
        // 判断提醒类型 yes or no
        $TriggerPoint = new ItzTriggerPoint();
        $TriggerPointReocrd = $TriggerPoint->findByAttributes(array('code' => $mtype));
        return array($mtype, $TriggerPointReocrd);
    }

    /**
     * [_sendMessage 发送站内信]
     * @param  [type] $data           [description]
     * @param  [type] $messageTrigger [description]
     * @param  [type] $mtype          [description]
     * @param  [type] $userRecord     [description]
     * @return [type]                 [description]
     */
    private static function _sendMessage($data, $messageTrigger, $mtype, $userRecord)
    {
        $MessageClass = new MessageClass();
        $MessageClass->userRecord = $userRecord;
        //发送站内信
        $contents = $messageTrigger['header'] . $messageTrigger['content'] . $messageTrigger['footer'];
        $matches = array();
        preg_match_all("/%[A-Za-z0-9_]+%/", $contents, $matches);
        foreach ($matches[0] as $key => $value) {
            $dkey = str_replace("%", "", $value);
            if (array_key_exists($dkey, $data['data'])) {
                $contents = str_replace($value, $data['data'][$dkey], $contents);
            }
        }
        $title = $messageTrigger['title'];
        $matches = array();
        preg_match_all("/%[A-Za-z0-9_]+%/", $title, $matches);
        foreach ($matches[0] as $key => $value) {
            $dkey = str_replace("%", "", $value);
            if (array_key_exists($dkey, $data['data'])) {
                $title = str_replace($value, $data['data'][$dkey], $title);
            }
        }
        $messageResult = $MessageClass->send(
            $data['receive_user'],
            $data['sent_user'],
            //$messageTrigger['title'],
            $title,
            $contents,
            $mtype, '0'
        );
        return $messageResult;
    }

    /**
     * [_sendEmail 发送邮件]
     * @param  [type] $data           [description]
     * @param  [type] $emailTrigger   [触发点模板]
     * @param  [type] $email          [description]
     * @param  [type] $mtype          [触发点code]
     * @param  [type] $userRecord     [description]
     * @param  [type] $delayEmailTime [description]
     * @return [type]                 [description]
     */
    private static function _sendEmail($data, $emailTrigger, $email, $mtype, $userRecord, $delayEmailTime = null)
    {
        $MailClass = new MailClass();
        $MailClass->userRecord = $userRecord;
        $contents = $emailTrigger['header'] . $emailTrigger['content'] . $emailTrigger['footer'];
        $matches = array();
        preg_match_all("/%[A-Za-z0-9_]+%/", $contents, $matches);
        foreach ($matches[0] as $key => $value) {
            $dkey = str_replace("%", "", $value);
            if (array_key_exists($dkey, $data['data'])) {
                $contents = str_replace($value, $data['data'][$dkey], $contents);
            }
        }
        $title = $emailTrigger['title'];
        $matches = array();
        preg_match_all("/%[A-Za-z0-9_]+%/", $title, $matches);
        foreach ($matches[0] as $key => $value) {
            $dkey = str_replace("%", "", $value);
            if (array_key_exists($dkey, $data['data'])) {
                $title = str_replace($value, $data['data'][$dkey], $title);
            }
        }
        $mailResult = $MailClass->sendToUser(
            $data['receive_user'],
            $email,
            $title,
            $contents,
            (!empty($data['attachment'])) ? $data['attachment'] : array(),
            $mtype,
            $delayEmailTime
        );
        return $mailResult;
    }

    /**
     * [_sendSms 发送短信]
     * @param  [type] $data         [description]
     * @param  [type] $smsTrigger   [description]
     * @param  [type] $userRecord   [description]
     * @param  [type] $phone        [description]
     * @param  [type] $mtype        [description]
     * @param  [type] $delaySmsTime [description]
     * @return [type]               [description]
     */
    private static function _sendSms($data, $smsTrigger, $userRecord, $phone, $mtype, $delaySmsTime = null)
    {
        $SmsClass = new SmsClass();
        $contents = $smsTrigger['header'] . $smsTrigger['content'] . $smsTrigger['footer'];
        $matches = array();
        preg_match_all("/%[A-Za-z0-9_]+%/", $contents, $matches);
        foreach ($matches[0] as $key => $value) {
            $dkey = str_replace("%", "", $value);
            if (array_key_exists($dkey, $data['data'])) {
                $contents = str_replace($value, $data['data'][$dkey], $contents);
            }
        }
        $SmsClass->userRecord = $userRecord;
        $smsResult = $SmsClass->sendToUser(
            $data['receive_user'],
            $phone,
            $contents,
            $mtype,
            $delaySmsTime
        );
        return $smsResult;
    }

    /**
     * [_getTriggerTemplate 获取触发点模板]
     * @param  [type] $mtype [description]
     * @return [type]        [description]
     */
    private static function _getTriggerTemplate($mtype)
    {
    	
    	$triggerResult = Yii::app()->dwdb->createCommand()->select('t1.code,t2.type,t2.title,t2.header,t2.content,t2.footer')->where("t1.status=1 and t1.code=:code and t1.id=t2.pointid", [':code' => $mtype])->from("itz_trigger_point t1,itz_trigger_template t2")->queryAll();
    	$smsTrigger = array();
    	$messageTrigger = array();
    	$emailTrigger = array();
    	foreach ($triggerResult as $key => $value) {
    		if ($value['type'] == 0) {
    			$smsTrigger = $value;
    		}
    		if ($value['type'] == 1) {
    			$messageTrigger = $value;
    	
    		}
    		if ($value['type'] == 2) {
    			$emailTrigger = $value;
    		}
    	};
    	self::$_getTriggerTemplate = array($smsTrigger, $messageTrigger, $emailTrigger);

       return self::$_getTriggerTemplate;
    }

    /**
     * [_getSystemConfig 获取系统配置]
     * @return [type] [description]
     */
    private static function _getSystemConfig()
    {
        if(!self::$_getSystemConfig) {
            $SystemModel = new System();
            $systemRecord = $SystemModel->findAllByAttributes(array('nid' => array('con_emailsend', 'con_smssend')));
            $systemConfig = array();
            foreach ($systemRecord as $record) {
                $systemConfig[$record->nid] = $record->value;
            }
            self::$_getSystemConfig = $systemConfig;
        }

        return self::$_getSystemConfig;
    }
    /**
     * [_writeLog 写入Log]
     * @param  string $type [description]
     * @return [type]       [description]
     */
    private static function _writeLog($type = 'info')
    {
        Yii::log(self::$_info, $type, __CLASS__);
        return false;

    }
    /**
     * [beginBatchInterface 开启批量提交]
     * @return [type] [description]
     */
    public function beginBatchInterface() {
        return Yii::app()->dwdb->beginTransaction();

    }
    /**
     * [commitBatchInterface 提交本次批量消息]
     * @return [type] [description]
     */
    public function commitBatchInterface() {
        return Yii::app()->dwdb->commit();

    }
    /**
     * [rollbackBatchInterface 回滚本次消息]
     * @return [type] [description]
     */
    public function rollbackBatchInterface() {
        return Yii::app()->dwdb->rollback();

    }

}

?>