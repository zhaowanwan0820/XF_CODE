<?php
/**
 * @file MailClass.php
 * @author changqi
 * @description: 邮件处理类
 */

class MailClass {
	// 当前发送邮件的邮件地址
	public  $tmpSendAddress = '';
	public  $tmpSendErrorMsg = '';
	protected static $_config = Null;

	const EMAIL_TOP = '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>EDM</title>
</head>
<body style="margin:0; padding:0;">
<div id="itz-eamil-wrapper">
<div style="margin:0; padding:0; width:90%; max-width:1200px; color:#666; font:normal 12px/24px \'SimSun\'; margin:0 auto;-webkit-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.15); -moz-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.15); -o-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.15); box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.15); margin-top:70px; ">
<div style="margin:0; padding:0;height:77px; width:100%; overflow:hidden; background:#fafafa; border-top:2px solid #be0614; border-bottom:1px solid #e2e2e2;">
<a href="https://www.xxx.com" target="_blank" style="margin:0; padding:0; text-decoration:none;"><img src="https://www.xxx.com/static_res/img/originImg/logo.png" style="margin:0; padding:0;float:left; display:inline; margin-left:20px; border:0;" width="152" height="30" title="ITZ" alt="ITZ" /></a>
<div style="margin:0; padding:0;float:right; padding-right:20px; width:175px; text-align: right;">
<div style=" margin:0; padding:0; height:12px; line-height:14px; margin-top:18px; text-align:right;">
<a href="https://www.xxx.com" target="_blank" style=" margin:0; padding:0; font-size:12px; color:#666; margin-right:13px; text-decoration:none;"/>ITZ首页</a>
<a href="https://www.xxx.com/help/index?idx=0" target="_blank" style=" margin:0; padding:0; font-size:12px; color:#666; text-decoration:none;"/>帮助中心</a>
</div>
<div style=" margin:0; padding:0;color:#be0614; margin-top:14px;">
<strong style=" margin:0; padding:0; font:normal 16px/18px \'Microsoft Yahei\'; ">电话：</strong><strong style=" font:normal 18px/18px \'Arial\'; color:#be0614; text-align:right; ">400-0088-100</strong>
</div>
</div>
</div>
<div style=" margin:0; padding:0; font-size:14px; line-height:24px; padding:35px 18px; background:#fff;  border-bottom:1px solid #e2e2e2;  word-wrap: break-word;">';

	const EMAIL_BOTTOM = '</div>
<div style=" margin:0; padding:0; height:115px; background:#fafafa; padding:20px 18px; overflow:hidden;">
<dl style=" margin:0; padding:0; float:left; color:#999; width:303px; border-right:1px solid #f0f0f0; overflow:hidden;">
<dt style=" margin:0; padding:0; font-size:14px; width:100%; float:left; line-height:16px; margin-bottom:10px;">ITZ微信公众号</dt>
<dd style=" margin:0; padding:0; font-size:12px; width:100%; float:left; margin-left:0; padding:0;">
<div style=" margin:0; padding:0; float:left; margin-right:10px;">
<img src="https://www.xxx.com/static_res/img/originImg/icon_qcode_big.jpg" width="100" height="100" alt="ITZ官方微信" title="ITZ官方微信" />
</div>
<p style=" margin:0; padding:0;  margin-top:48px; line-height:18px;">
扫描二维码<br/>
关注ITZ官方微信
</p>
</dd>
</dl>
<dl style=" margin:0; padding:0; float:left; display:inline; color:#999; width:283px; padding-left:20px; border-left:1px solid #ffffff; overflow:hidden;">
<dt style=" margin:0; padding:0; font-size:14px; width:100%; float:left; line-height:16px; margin-bottom:20px; font-weight:bold;">关注我们</dt>
<dd style=" margin:0; padding:0; line-height:16px; width:100%; float:left; margin-left:0; padding:0;">
<a href="https://bbs.xxx.com" target="_blank" style=" margin:0; padding:0; height:16px; text-decoration:none; color:#999; display:block; margin-bottom:15px;"><span style=" margin:0; padding:0; float:left;">爱亲论坛：bbs.xxx.com</span></a>
<a href="http://e.weibo.com/com" target="_blank" style=" margin:0; padding:0; height:16px; text-decoration:none; color:#999; display:block; margin-bottom:15px;"><span style=" margin:0; padding:0;float:left;">关注ITZ官方微博</span></a>
<span style=" margin:0; padding:0;height:16px; text-decoration:none; color:#999; display:block;"><span style=" margin:0; padding:0; float:left;">ITZ手机版:<a href="http://m.xxx.com" target="_blank" style=" margin:0; padding:0; height:16px; text-decoration:none; color:#999; margin-bottom:15px;">m.xxx.com</a></span></span>
</dd>
</dl>
</div>
<div style="margin:0; padding:0; margin-top:10px; color:#b4b4b4; text-align:center; padding-bottom:50px;">
为了您能够正常收到来自ITZ会员邮件，请将 <a href="mailto:service@noreply.xxx.com" style="margin:0; padding:0;text-decoration:underline; color:#b4b4b4;">service@noreply.xxx.com</a>添加进您的通讯录。<br/>
不希望在接受此类邮件？ <a href="#" target="_blank" style="margin:0; padding:0;text-decoration:none; color:#2687d9;">立即退订</a>
</div>
</div>
</div>
</body>
</html>';
	private $userId;
	private $sendType;
	private $_error = 'SendError: ';
	public $userRecord = null;

	public function MailClass() {
		Yii::import ( "itzlib.plugins.mail.Mail" );
		// 载入配置
		$_configPath = dirname(dirname (__FILE__)).'/config/mail.php';
		if(file_exists($_configPath)) {
			self::$_config = include($_configPath);
		}
	}

	/**
	 * 发送邮件
	 * @param array $emailArr
	 * @param array $type
	 * @param array $title
	 * @param array $content
	 * @param array $attachment
	 * #param array $retryNum 失败重试次数
	 * @param bool $header 是否加公共头尾
	 * @return boolean
	 */
	public function send($emailArr, $type, $title, $content, $attachment = array(), $retryNum = 1, $header = true) {
	    Yii::log(__FUNCTION__." ".print_r(func_get_args(),true),"debug");
		if(empty(self::$_config)) {// 如果配置不存在，终止发送
			return false;
		}
		if(!is_array($emailArr)) {
			$emailArr = array($emailArr);
		}

		// 设置发送失败重试次数
		if(isset(self::$_config['FailedRetryNumberByType'][$type])) {
			$retryNum = intval(self::$_config['FailedRetryNumberByType'][$type]);
		}
		if($retryNum <= 0) $retryNum = 1;

		$Mail = new Mail();
		// 选择邮件服务网关
		$gateway = $this->switchGateway($emailArr, $type);
		// 设置邮件服务网关及账号
		$gatewayData = array();
		$gatewayData['host'] = self::$_config['gateway'][$gateway]['host'];
		$gatewayData['username'] = self::$_config['gateway'][$gateway]['username'];
		$gatewayData['password'] = self::$_config['gateway'][$gateway]['password'];
		if(!empty(self::$_config['gateway'][$gateway]['address'])) {
			$gatewayData['from'] = self::$_config['gateway'][$gateway]['address'];
		}
		if(!empty(self::$_config['gateway'][$gateway]['fromName'])) {
			$gatewayData['fromName'] = self::$_config['gateway'][$gateway]['fromName'];
		}
		$Mail->setGateway($gatewayData);

		if(!empty($attachment) && !is_array($attachment)) {
			$attachment = array($attachment);
		}
		if($header) {
		    //内容拼接头尾
		    $content = self::EMAIL_TOP . $content . self::EMAIL_BOTTOM;
		}

		// 设置发送邮件内容
		$Mail->setSendData($emailArr, $title, $content, $attachment);

		// 执行发送请求
		for($i = 1; $i <= $retryNum; $i++) {
			$sendFlag = $Mail->Send();
			if(!$sendFlag) {
				Yii::log(sprintf("###%s### trying %s mail result: %s[ %s ]", $type, $i, implode(',', $emailArr), $Mail->GetErrorMsg()), 'error');
			}else{
			    break;
			}

		}

		if(!$sendFlag) {
			// 获取发送错误信息
			$this->tmpSendErrorMsg = $Mail->GetErrorMsg();
		}

		// 获取发送邮件的服务账号
		$this->tmpSendAddress = $Mail->getFrom();

		return $sendFlag;
	}

	/**
	 * 给用户发送邮件
	 * @param string $userId
	 * @param string $title
	 * @param string $content
	 * @param string $email
	 * @param array $attachment
	 * @param array $sendType
	 * @param boolean $delayEmailTime
	 * @param array $params
	 * @return boolean
	 */
	public function sendToUser($userId = '', $email='', $title='', $content='', $attachment = array(), $sendType = 'system', $delayEmailTime = false, $params = array(), $header = true, $ctype = '', $sync = 1, $callback = '') {
		$this->_error .= __FUNCTION__ . " " . print_r(func_get_args(), true);
		if($sendType == '') {
			$sendType = 'system';
		}
		if( empty($userId) || empty($title) || empty($content) || empty($email) || !$this->isEmail($email) ){
			$this->_error .= " LINE:" . __LINE__ . " params is empty or validate email is FALSE";
			return $this->_writeLog();
		}
		if(is_array($sendType)) $sendType = $sendType['type'];
		$this->userId = $userId;
		$this->sendType = $sendType;
		if(!$this->_isAllow()) {
			return false;
		}
		$queueData = array();
		$queueData['user_id'] = (String) $userId;
		$queueData['title'] = $title;
		$queueData['content'] = $content;
		$queueData['email'] = $email;
		$queueData['email_mod'] = $params['email_mod'] ? $params['email_mod'] : 1;
		$queueData['stype'] = $sendType;
		$queueData['sync'] = $sync;
		$queueData['callback'] = $callback;
		$queueData['ctype'] = $ctype;
		$queueData['createtime'] = time();
		$queueData['hash_verify'] = '';
		if($delayEmailTime > time() - 100000) {
			$queueData['delayEmailTime'] = (int) $delayEmailTime;
		}
		return $this->addQueue($queueData);
	}
	/**
	 * [_isAllow 配置是否允许发送短信]
	 * @return boolean [description]
	 */
	private function _isAllow() {
		$this->thisPointConfig = $this->_getDb()->createCommand()->select('id, status')->where('code=:code', [':code' => $this->sendType])->from('itz_trigger_point')->queryRow();
		if(!$this->thisPointConfig || $this->thisPointConfig['status'] == 0) {
			$this->_error .= ' LINE: ' . __LINE__ . 'Has been deactivated or Exists';
			return $this->_writeLog();
		}
		return $this->_userNoticeConfigFilter();
	}
	/**
	 * [_userNoticeConfigFilter 用户设置是否发送短信]
	 * @return [type] [description]
	 */
	private function _userNoticeConfigFilter() {
		if(!$this->userId) return true;
		$config = $this->userRecord['remind'] ? $this->userRecord['remind'] : current($this->_getDb()->createCommand()->select('remind')->where('user_id=:user_id', [':user_id' => $this->userId])->from('dw_user')->queryRow());
		//提现申请到账默认不发；
		if($config == null){
			if($this->sendType == 'withdApply')
				return false;
			else
				return true;
		}
		$config = unserialize($config);
		$sendType = $this->sendType;
		$noticeConfig = Yii::app()->params['userNoticeConfig']['showNotice'];
		//统一将触发点code替换为触发点组code；
		foreach ($noticeConfig as $k => $item) {
			if(in_array($sendType,$item['list'])){
				$sendType = $k ;
			}
		}
		//提现申请到账默认不发
		if(!array_key_exists($sendType, $config) && $this->sendType == 'withdApply') return false;

		if(array_key_exists($sendType, $config)) {
			if(array_key_exists('email', $config[$sendType]) && $config[$sendType]['email'] != 1) {
				$this->_error .= " LINE:" . __LINE__ . " Error Reason: User Not Allow";
				return $this->_writeLog();
			}
		}
		return true;
	}
	/**
     * 给内部用户发送邮件
     * @param string $userId
     * @param string $title
     * @param string $content
     * @param string $email
     * @param array $attachment
     * @param array $sendType
     * @param boolean $direct
     * @param array $params
     * @return boolean
     */
    public function sendToUserInternal($userId, $email, $title, $content, $attachment = array(), $sendType = 'system', $direct = false, $params = array(), $header = true) {
        Yii::log(__FUNCTION__." ".print_r(func_get_args(),true),"debug");
        if($sendType == '') {
            $sendType = 'system';
        }

        if( empty($userId) || empty($title) || empty($content) || empty($email) || !$this->isEmail($email) ){
            Yii::log(__FUNCTION__." LINE:".__LINE__." params is empty or validate email is FALSE","error");
            return false;
        }


        $sendResult = $this->send($email, $sendType, $title, $content, $attachment, 1, $header);
        if($sendResult == true) {
            return true;
        }
        return false;
    }
    /**
	 * 加入到邮件发送队列
	 * @param array $data
	 * @return booleanclear
	 */
	public function addQueue($data) {
		Yii::log('email send data '.print_r($data,true),'info',__CLASS__);
		//开发模式挡板 发送到qa@xxx.com上
		/* if (Yii::app()->viewRenderer->globalVal['developMode']) {
			$data['email'] = 'qa@xxx.com';
			$data['title'] .= '测试环境发出';
		} */
		if(isset($data['delayEmailTime'])) {
            return (new DelaySendMessageQueueService())->addQueue($data, 2);
        }
        if($data['email_mod'] == 2){
			return (new RedisQueueService())->enQueue('new_email', $data);
        }else{
        	return (new RedisQueueService())->enQueue('email', $data);
        }
	}

	public function updateQueue($data) {
		$EmailQueueModel = new EmailQueue();
		$result = $EmailQueueModel->findByAttributes( array('id'=> $queueId) );
		if($result== null){
			return false;
		}else{
			foreach($data as $key => $value){
				if($key != 'id'){
					$result->$key = $value;
				}
			}
			if($result->save() == false){
				return false;
			}else{
				return true;
			}
		}
	}

	/**
	 * 处理邮件发送请求
	 */
	public function dealQueue($usleep = 500000, $limit = 100, $hasType = array(), $hasnoType = array()) {
		$EmailQueueModel = new EmailQueue();
		$criteria = new CDbCriteria;
		if(!empty($hasType)) {
			$criteria->addInCondition('type', $hasType, 'AND');
		}
		if(!empty($hasnoType)) {
			$criteria->addNotInCondition('type', $hasType, 'AND');
		}
		$criteria->limit = $limit;
		$criteria->index = 'id';
		$attributes = array(
				'status' => '0',
		);
		$queueRecords = $EmailQueueModel->findAllByAttributes($attributes, $criteria);

		$recordIdArr = array_keys($queueRecords);

		// 更新状态为处理中
		$EmailQueueModel->updateByPk($recordIdArr, array('status' => '2') );

		$queueData = array();
		$_attachment = NULL;
		foreach ($queueRecords as $key => $record) {
			$queueData = array();
			if(!empty($record->attachment)) {
				$_attachment = unserialize($record->attachment);
			}

			$sendResult = $this->send($record->email, $record->type, $record->title, $record->content, $_attachment);

			$queueData['send_address'] = $this->tmpSendAddress;
			$queueData['status'] = '1';
			if($sendResult == true) {
				$queueData['send_status'] = '1';
			} else {
				$queueData['send_status'] = '2';
				$queueData['send_return'] = $this->tmpSendErrorMsg;
			}

			// 更新状态及发送结果
			$EmailQueueModel->updateByPk($record->id, $queueData );

			// 插入UserEmailLog
			$UserEmailLog = new UserEmailLog();
			$UserEmailLog->type = $record->type;
			$UserEmailLog->user_id = $record->user_id;
			$UserEmailLog->title = $record->title;
			$UserEmailLog->msg = $record->content;
			$UserEmailLog->email = $record->email;
			$UserEmailLog->addtime = time();
			$UserEmailLog->addip = FunctionUtil::ip_address();
			if($sendResult == true) {
				$UserEmailLog->status = '1';
			} else {
				$UserEmailLog->status = '0';
			}
			$UserEmailLog->save();



			unset($queueRecords[$key]);
			// 休眠 $usleep 微妙
			usleep($usleep);
		}

		unset($recordIdArr);
	}

	/**
	 * 根据发送邮件类型选择发送通道
	 * @param string $type
	 */
	public function switchGateway($emailArr, $type = 'default') {
		$gateway = '';

		if(empty($emailArr)) {
			return $gateway;
		}

		if( !in_array($type, self::$_config['switchGatewayByContentType']) ) {

			if(is_array($emailArr[0])) {
				$email = $emailArr[0][0];
			} else {
				$email = $emailArr[0];
			}

			$serviceDomainArr = array_keys(self::$_config['switchGatewayByDomain']);

			foreach($serviceDomainArr as $domain) {
				if(stristr($email, $domain)) {
					$randkey = array_rand(self::$_config['switchGatewayByDomain'][$domain]);
					$gateway = self::$_config['switchGatewayByDomain'][$domain][$randkey];
					break;
				}
			}

			if(empty($gateway)) {
				$gateway = self::$_config['defaultGateway'];
			}

			return $gateway;

		} else {
			$key = array_rand(self::$_config['switchGatewayByContentType']);
			$gateway = self::$_config['switchGatewayByContentType'][$key];
			return $gateway;
		}
	}

	/**
	 * 判断是否符合email的地址格式
	 * @param string or array $address
	 * @return number
	 */
	public function isEmail($address) {
		$flag = 0;
		if(is_array($address)){
			foreach ($address as $key => $value) {
				if(preg_match('/^([\.a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/', $value)){
					$flag = 1;
					break;
				}
			}
		}else{
			if(preg_match('/^([\.a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/', $address)){
				$flag = 1;
			}
		}
		return $flag;
	}
	private function _getDb() {
		if(isset(Yii::app()->dwdb)) {
			return Yii::app()->dwdb;
		}
		return Yii::app()->db;
	}

	private function _writeLog($type = 'info')
	{
		Yii::log($this->_error, $type, __CLASS__);
		return false;
	}


	/**
	 * 有解发送邮件通知
	 * @param $emails
	 * @param $title
	 * @param $content
	 * @return mixed
	 */
	public function yjSend($emails, $title, $content) {
		$return_result['code'] = 0;
		if(empty($emails) || empty($title) || empty($content)){
			Yii::log("yjSend email error, params error ", "error", __CLASS__);
			$return_result['code'] = 1001;
			return $return_result;
		}
		//发送
		$email_url = "https://api.youjiemall.com/hh/email.send";
		$remind = array();
		$remind['email'] = $emails;
		$remind['title'] = $title;
		$remind['content'] = $content;
		$result = CurlService::getInstance()->yjRequest($email_url, json_encode($remind), 'post');
		if($result == false || $result['data']['code'] != 0){
			Yii::log("yjSend email error, yjRequest return:".print_r($result,true), "error", __CLASS__);
			$return_result['code'] = 1005;
			return $return_result;
		}
		return $return_result;
	}
}
