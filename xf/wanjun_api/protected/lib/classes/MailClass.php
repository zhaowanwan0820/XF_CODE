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
	public static $_config = Null;
	
	public function MailClass() {
		Yii::import ( "itzlib.plugins.mail.Mail" );
		// 载入配置
		self::$_config = Yii::app()->c->mail;
	}
	
	/**
	 * 发送邮件
	 * @param array $emailArr
	 * @param array $type
	 * @param array $title
	 * @param array $content
	 * @param array $attachment
	 * #param array $retryNum 失败重试次数
	 * @return boolean
	 */
	public function send($emailArr, $type, $title, $content, $attachment = array(), $retryNum = 1) {
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
		
		// 设置发送邮件内容
		$Mail->setSendData($emailArr, $title, $content, $attachment);
		
		// 执行发送请求
		for($i = 1; $i <= $retryNum; $i++) { 
			$sendFlag = $Mail->Send();
			if(!$sendFlag) {
				Yii::log(sprintf("###%s### trying %s mail result: %s[ %s ]", $type, $i, implode(',', $emailArr), $Mail->GetErrorMsg()), 'error');
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
	 * @param boolean $direct
	 * @param array $params
	 * @return boolean
	 */
	public function sendToUser($userId, $email, $title, $content, $attachment = array(), $sendType = 'system', $direct = false, $params = array()) {
		if($sendType == '') {
			$sendType = 'system';
		}
		
		if( empty($userId) || empty($title) || empty($content) || empty($email) || !$this->isEmail($email) ){
			return false;
		}
		
		if($direct) { // 直接发送
			$sendResult = $this->send($email, $sendType, $title, $content, $attachment);
			
			$UserEmailLog = new UserEmailLog();
			$UserEmailLog->type = $sendType;
			$UserEmailLog->user_id = $userId;
			$UserEmailLog->title = $title;
			$UserEmailLog->msg = $content;
			$UserEmailLog->email = $email;
			$UserEmailLog->addtime = time();
			$UserEmailLog->addip = FunctionUtil::ip_address();
			
			$queueData = array();
			$queueData['type'] = $sendType;
			$queueData['user_id'] = $userId;
			$queueData['title'] = $title;
			$queueData['content'] = $content;
			$queueData['email'] = $email;
			$queueData['send_address'] = $this->tmpSendAddress;
			$queueData['status'] = '1';
			
			if($sendResult == true) {
				$UserEmailLog->status = 1;
				$UserEmailLog->save();
				
				$queueData['send_status'] = '1';
				$this->addQueue($queueData);
				return true;
			} else {
				$UserEmailLog->attributes['status'] = 0;
				$UserEmailLog->save();
				
				$queueData['send_status'] = '2';
				$queueData['send_return'] = $this->tmpSendErrorMsg;
				$this->addQueue($queueData);
				return false;
			}
			
		} else {// 放入队列，有发送脚本发送
		
			$queueData = array();
			$queueData['type'] = $sendType;
			$queueData['user_id'] = $userId;
			$queueData['title'] = $title;
			$queueData['content'] = $content;
			$queueData['email'] = $email;
			$queueData['status'] = '0';
			$queueData['send_status'] = '0';
			
			return $this->addQueue($queueData);
		}
		
		return false;
	}
	
	public function addQueue($data) {
		$EmailQueueModel = new EmailQueue();
		foreach($data as $key => $value) {
			$EmailQueueModel->$key = $value;
		}
		
		$EmailQueueModel->addtime = time();
		$EmailQueueModel->addip = FunctionUtil::ip_address();
		
		if($EmailQueueModel->save() == false){
			return false;
		}else{
			return true;
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
		foreach ($queueRecords as $key => $record) {
			$queueData = array();
			
			$sendResult = $this->send($record->email, $record->type, $record->title, $record->content);
			
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
	 * @param string $address
	 * @return number
	 */
	public function isEmail($address) {
		return preg_match('/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/', $address);
	}

}
