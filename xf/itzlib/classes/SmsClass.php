<?php

/**
 * @file SmsClass.php
 * @author changqi
 * @description: 短消息处理类
 */
class SmsClass
{

    protected static $_sendTypeGatewayConf = Null;

    protected static $_contentSuffix = '【xxx.com】';

    protected static $SmsObj = Null;

    protected static $_config = Null;

    public $userRecord = null;

    private $sendType;

    private $_error = 'SendError: ';

    private $thisPointConfig = null;
    private $queueData = [];

    public function __construct()
    {
        Yii::import("itzlib.plugins.sms.Sms");
        self::$SmsObj = new Sms();

        $_configPath = dirname(dirname(__FILE__)) . '/config/sms.php';

        if (file_exists($_configPath)) {
            self::$_config = include($_configPath);
        }
    }

    /**
     * 调用发送短息接口
     * @param string $phone
     * @param string $content
     * @param string $gateway
     * @return array $sendResult
     */
    public function send($phone, $content, $gateway, $sendType = 'sendSms')
    {
        $sendResult = array();
        $initFlag = self::$SmsObj->initGateway($gateway);
        if (!$initFlag) {
            $sendResult['status'] = false;
            return $sendResult;
        }
        //筑望的通道 网关处强制签名，所以不需要再加后缀签名
        if ($gateway == 4) {
            $sendResult = self::$SmsObj->$sendType($phone, $content);
            //rubbish - 浙江筑望
        } elseif ($gateway == 3) {
            $sendResult = self::$SmsObj->sendSms($phone, $content . '【ITZ】');
            //亿美软通的所有签名都要在前面
        } elseif ($gateway == 1 || $gateway == 2) {
            if ($sendType == 'sendVoice') {
                $sendResult = self::$SmsObj->$sendType($phone, $content);
            } else {
                // TODO
                /*
                 * author cooler
                 * date 2014-12-22
                 * 去掉签名，由通道自己去加签名,新增对5 网关处理
                 * 对content 签名进行replace，前缀也去掉了
                */
                $content = str_replace("【ITZ】", "", $content);
                $sendResult = self::$SmsObj->$sendType($phone, $content);
            }
        } else {
            if ($sendType == 'sendVoice') {
                //$sendResult = self::$SmsObj->sendSms($phone, self::$_contentSuffix . $content);
                $sendResult = self::$SmsObj->$sendType($phone, $content);
            } else {
                $sendResult = self::$SmsObj->$sendType($phone, $content . self::$_contentSuffix);
            }
        }
        return $sendResult;
    }

    public function getBalance($gateway)
    {
        $initFlag = self::$SmsObj->initGateway($gateway);
        if (!$initFlag) {
            $sendResult['status'] = false;
            return $sendResult;
        }
        return self::$SmsObj->getBalance();
    }

    /**
     * [sendToUser 给用户发送短信]
     * @param  [type] $userId   [用户ID]
     * @param  [type] $phone    [手机号]
     * @param  [type] $content  [内容]
     * @param  string $sendType [触发点code]
     * @return [type]           [返回True或False]
     */
    public function sendToUser($userId, $phone, $content, $sendType = '', $delaySmsTime = null)
    {
        if (!$sendType) $sendType = 'system';
        $this->_error = __FUNCTION__ . ':' . print_r(func_get_args(), true);
        if (empty($userId) || empty($phone) || empty($content) || (!$this->isMobile($phone))) return $this->_writeLog();
        return $this->_Handle($userId, $phone, $content, $sendType, $delaySmsTime);
    }

    /**
     * [_Handle 短信处理方法]
     * @param  [type] $userId   [用户ID]
     * @param  [type] $phone    [手机号]
     * @param  [type] $content  [内容]
     * @param  string $sendType [触发点code]
     * @return [type]           [description]
     */
    private function _Handle($userId = '', $phone=0, $content='', $sendType = '', $delaySmsTime=0)
    {
        $this->sendType = $sendType = ($sendType == 'direct' || empty($sendType)) ? "direct" : $sendType;
        $this->userId = $userId;
        $this->phone = $phone;
        $this->_sendTypeSelect();
        if (!$this->_isAllow() or $this->_grayList()) {
            return false;
        }
		$this->_swtichGateway();
        $this->queueData['user_id'] = (String)$userId;
        $this->queueData['mobile'] = $this->phone;
        $this->queueData['content'] = $content;
        $this->queueData['createtime'] = time();
        $this->queueData['sync'] = '1';
        $this->queueData['callback'] = '';
        $this->queueData['hash_verify'] = '';
        $this->queueData['delaySmsTime'] = (int) $delaySmsTime;
        return $this->_allowSend();
    }

    /**
     * [_allowSend 允许发送调用的函数]
     * @return [type] [description]
     */
    private function _allowSend()
    {	Yii::log('sms send data '.print_r($this->queueData,true),'info',__CLASS__);
        return $this->addQueue($this->queueData);

    }

    /**
     * [_swtichGateway 选择网关]
     * @return [type] [description]
     */
    private function _swtichGateway()
    {
        if (empty($this->config)) return false;
        $this->queueData['mainGateway'] = isset($this->config['main']) ? $this->config['main'] : '';
        $this->queueData['viceGateway'] = isset($this->config['vice']) ? $this->config['vice'] : '';
        // var_dump($this->queueData);
    }

    /**
     * [_grayList 灰名单过滤]
     * @return [type] [description]
     */
    private function _grayList()
    {
        if (!$this->thisPointConfig) return false;
            $config = $this->_getDb()->createCommand()->select('params')->where('pointid = :pointid and type = :type', [':pointid' => $this->thisPointConfig['id'], ':type' => 0])->from('itz_trigger_template')->queryRow();
        if (empty($config)) {
            return $this->_writeLog();
        }
        $this->config = unserialize($config['params']);
        if (!$this->config['filter']) return $this->_writeLog();
        $blacklistservice = new BlackListService();
        $status = $blacklistservice->isInBlackList((String)$this->phone);
        if($status) {
            $this->_error .= " LINE:" . __LINE__ . " Error Reason: grayList Not Allow";
            $this->_writeLog();
        }
        return $status;
    }

    /**
     * [_isAllow 配置是否允许发送短信]
     * @return boolean [description]
     */
    private function _isAllow()
    {
        $this->thisPointConfig = $this->_getDb()->createCommand()->select('id, status')->where('code=:code', [':code' => $this->code])->from('itz_trigger_point')->queryRow();
        if (!$this->thisPointConfig || $this->thisPointConfig['status'] == 0) {
            $this->_error .= " LINE:" . __LINE__ . ' Error Reason：msg system Error';
            return $this->_writeLog();
        }
        return $this->_userNoticeConfigFilter();
    }

    /**
     * [_userNoticeConfigFilter 用户设置是否发送短信]
     * @return [type] [description]
     */
    private function _userNoticeConfigFilter()
    {
        if (!$this->userId) return true;
        $config = $this->userRecord['remind'] ? $this->userRecord['remind'] : current($this->_getDb()->createCommand()->select('remind')->where('user_id=:user_id', [':user_id' => $this->userId])->from('dw_user')->queryRow());
        if ($config == null) return true;
		$sendType = $this->sendType;
		$config = unserialize($config);
		$noticeConfig = Yii::app()->params['userNoticeConfig']['showNotice'];
		foreach ($noticeConfig as $k => $item) {
			if(in_array($sendType,$item['list'])){
				$sendType = $k ;
			}
		}
		if (array_key_exists($sendType, $config)) {
			if (array_key_exists('sms', $config[$sendType]) && $config[$sendType]['sms'] != 1) {
				$this->_error .= " LINE:" . __LINE__ . " Error Reason: User Not Allow";
				return $this->_writeLog();
			}
		}
		return true;
    }

    /**
     * [_sendTypeSelect 保证平滑上线]
     * @return [type] [description]
     */
    private function _sendTypeSelect()
    {
        if (is_array($this->sendType)) {
            $this->queueData['stype'] = $this->sendType['mtype'];
            $this->code = $this->sendType['mtype'];
        } else {
            $this->code = $this->queueData['stype'] = $this->sendType;
        }
        $this->sendType = $this->queueData['stype'];
        return true;
    }

    public function sendUserVoice($userId, $phone, $content, $type, $sendType = 'sendVoice')
    {
        if ($sendType == '') {
            $sendType = 'system';
        }
        /*
        if(empty($userId) || empty($phone) || empty($content))
        {
            return false;
        }
        (
        */
        $gateway = $this->switchGateway($sendType);
        $sendResult = $this->send($phone, $content, $gateway, 'sendVoice');
        $ArrayResult = explode(':', $sendResult['statusCode']);
        $UserSmsLogModel = new UserSmsLog();
        $UserSmsLogModel->type = $type;
        $UserSmsLogModel->user_id = $userId;
        $UserSmsLogModel->mobile = $phone;
        $UserSmsLogModel->content = $content;
        $UserSmsLogModel->gateway = $gateway;

        $UserSmsLogModel->addtime = time();
        $UserSmsLogModel->addip = FunctionUtil::ip_address();
        $UserSmsLogModel->remark = '';
        if ($sendResult['statusCode'] == 0) {

            if ($sendResult['statusCode'] == 0) {
                //插入log记录表
                $UserSmsLogModel->ret = $sendResult['statusCode'];
                $UserSmsLogModel->status = 1;
                $UserSmsLogModel->save();
                return true;
            }
        } else {
            $UserSmsLogModel->ret = $sendResult['statusCode'];
            $UserSmsLogModel->status = 0;
            $UserSmsLogModel->save();
            return $sendResult['statusCode'];
        }

    }

    /**
     * 群发短信
     * @param string $phone
     * @param string $content
     * @param string $sendType
     * @param string $direct
     * @param array $params
     * @return boolean
     */
    public function sendGroup($phone, $content, $sendType = '', $direct = false, $params = array())
    {
        if ($sendType == '') {
            $sendType = 'system';
        }
        $userId = '';
        if (empty($phone) || empty($content)) {
            return false;
        }
        if (is_array($phone)) {
            foreach ($phone as $value) {
                $this->_Handle($userId, $value, $content, $sendType);
            }
            return true;
        }
        return $this->_Handle($userId, $phone, $content, $sendType);
    }

    /**
     * 加入到短息发送队列
     * @param array $data
     * @return booleanclear
     */
    public function addQueue($data)
    {
    	//开发模式挡板 发送到张爱芳手机上 15801298262
    	/* if (Yii::app()->viewRenderer->globalVal['developMode']) {
    		$data['mobile'] = '15801298262';
    		$data['content'] .= '测试环境发出';
    	} */
        if($data['delaySmsTime'] > time() - 10000) {
            $flag = (new DelaySendMessageQueueService())->addQueue($data);
            $this->_error .= " LINE:" . __LINE__ . " Error Reason: Delay SMS Insert Into Redis Queue Error";
            return $flag?:$this->_writeLog("error");
        
        }
        //插入redis，
        //2017-12-26  新增market_yimei  亿美活动  by liujiashu
        $gateway_list = array('iSee_yimei', 'itz_qxhl', 'isee_qxhl', 'itz_hl95', 'market_yimei');
        if(in_array($data['mainGateway'], $gateway_list) || in_array($data['viceGateway'], $gateway_list)){
        	$flag = (new RedisQueueService())->enQueue('new_sms', $data);
            $this->_error .= " LINE:" . __LINE__ . " Error Reason: New SMS Insert Into Redis Queue Error";
            return $flag?:$this->_writeLog("error");
    	}else {
    		$flag = (new RedisQueueService())->enQueue('sms', $data);
            $this->_error .= " LINE:" . __LINE__ . " Error Reason: Delay SMS Insert Into Redis Queue Error";
            return $flag?:$this->_writeLog("error");
        }
    }

    /**
     * 更新短息发送队列中的消息数据
     * @param string $queueId
     * @param array $data
     * @return boolean
     */
    public function updateQueue($queueId, $data)
    { //更新redis
        $SmsQueueModel = new SmsQueue();
        $result = $SmsQueueModel->findByAttributes(array('id' => $queueId));
        if ($result == null) {
            return false;
        } else {
            foreach ($data as $key => $value) {
                if ($key != 'id') {
                    $result->$key = $value;
                }
            }
            if ($result->save() == false) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 处理短信发送请求
     * @param number $usleep 每条发送休眠时间
     * @param number $limit 每次处理条数
     * @param array $hasType 查询包含的请求类型
     * @param array $hasnoType 查询不包含的请求类型
     */
    public function dealQueue($usleep = 500000, $limit = 100, $hasType = array(), $hasnoType = array())
    { //删除redis
        $SmsQueueModel = new SmsQueue();
        // 设置查询条件
        $criteria = new CDbCriteria;
        if (!empty($hasType)) {
            $criteria->addInCondition('type', $hasType, 'AND');
        }
        if (!empty($hasnoType)) {
            $criteria->addNotInCondition('type', $hasnoType, 'AND');
        }
        // 设置查询条数
        $criteria->limit = $limit;
        // 设置查询结果数组的索引
        $criteria->index = 'id';
        $attributes = array(
            'status' => '0',
        );
        $queueRecords = $SmsQueueModel->findAllByAttributes($attributes, $criteria);

        $recordIdArr = array_keys($queueRecords);

        // 更新状态为处理中
        $SmsQueueModel->updateByPk($recordIdArr, array('status' => '2'));

        $queueData = array();
        foreach ($queueRecords as $key => $record) {
            $queueData = array();
            // 发送短信
            $sendResult = $this->send($record->mobile, $record->content, $record->gateway);

            if ($sendResult['status'] == true) {
                $queueData['send_status'] = '1';
                $queueData['ret'] = $sendResult['statusCode'];
            } else {
                $queueData['send_status'] = '2';
                $queueData['ret'] = $sendResult['statusCode'];
            }
            $queueData['status'] = '1';
            // 更新状态及发送结果
            $SmsQueueModel->updateByPk($record->id, $queueData);

            // 插入短信发送日志表
            $UserSmsLogModel = new UserSmsLog();
            $UserSmsLogModel->type = $record->type;
            $UserSmsLogModel->user_id = $record->user_id;
            $UserSmsLogModel->mobile = $record->mobile;
            $UserSmsLogModel->content = $record->content;
            $UserSmsLogModel->gateway = $record->gateway;
            $UserSmsLogModel->addtime = time();
            $UserSmsLogModel->addip = FunctionUtil::ip_address();
            $UserSmsLogModel->ret = $sendResult['statusCode'];
            $UserSmsLogModel->remark = '';
            if ($sendResult['status'] == true) {
                $UserSmsLogModel->status = 1;
                $UserSmsLogModel->save();
            } else {
                $UserSmsLogModel->status = 0;
                $UserSmsLogModel->save();
            }

            unset($queueRecords[$key]);
            // 休眠 $usleep 微妙
            usleep($usleep);
        }

        unset($recordIdArr);
    }

    /**
     * 选择短信网关
     * @param string $sendType
     */
    public function switchGateway($sendType = 'default')
    {

        $tmpTypeName = $sendType;
        if ($tmpTypeName == '' || !isset(self::$_config['switchGatewayByContentType'][$tmpTypeName])) {
            $tmpTypeName = 'default';
        }

        return self::$_config['switchGatewayByContentType'][$tmpTypeName];
    }

    private function _getDb()
    {
        if (isset(Yii::app()->dwdb)) {
            return Yii::app()->dwdb;
        }
        return Yii::app()->db;
    }
    private function _writeLog($type = 'info') {
        Yii::log($this->_error, $type, __CLASS__);
        return false;
    }

    /**
     * 判断是否是手机号
     * @param string $phone
     * @return boolean
     */
    public function isMobile($phone)
    {
        $flag = preg_match('/^1[\d]{10}$/', $phone) || preg_match('/^0[\d]{10,11}$/', $phone);
        if(!$flag)
            $this->_error .= " LINE:" . __LINE__ . " Error Reason: the Phone is not Correct";
        return  $flag; 
    }

}
