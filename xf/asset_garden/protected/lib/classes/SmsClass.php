<?php
/**
 * @file SmsClass.php
 * @author changqi
 * @description: 短消息处理类
 */

class SmsClass {
	
	protected static $_sendTypeGatewayConf = Null;
	
	protected static $_contentSuffix = '【xxx.com】';
	
	protected static $SmsObj = Null;
	
	public function __construct() {
		Yii::import ( "itzlib.plugins.sms.Sms" );
		self::$SmsObj = new Sms();
		
		self::$_sendTypeGatewayConf = Yii::app()->c->sms['switchGatewayByContentType'];
		
	}
	
	/**
	 * 调用发送短息接口
	 * @param string $phone
	 * @param string $content
	 * @param string $gateway
	 * @return array $sendResult
	 */
	public function send($phone, $content, $gateway) {
		$sendResult = array();
		$initFlag = self::$SmsObj->initGateway($gateway);
		if(!$initFlag) {
			$sendResult['status'] = false;
			return $sendResult;
		}
		
		$sendResult = self::$SmsObj->sendSms($phone, $content.self::$_contentSuffix);
		return $sendResult;
	}
	
	/**
	 * 给用户发送短信
	 * @param string $userId
	 * @param string $phone
	 * @param string $content
	 * @param string $sendType
	 * @param string $direct
	 * @param array $params
	 * @return boolean
	 */
	public function sendToUser($userId, $phone, $content, $sendType = '', $direct = false, $params = array()) {
		if($sendType == '') {
			$sendType = 'system';
		}
		
		if( empty($userId) || empty($phone) || empty($content) || !$this->isMobile($phone) ){
			return false;
		}
		// 选择短信网关
		$gateway = $this->switchGateway($sendType);
		
		if($direct) { // 直接发送
			$sendResult = $this->send($phone, $content, $gateway);
			
			$UserSmsLogModel = new UserSmsLog();
			$UserSmsLogModel->type = $sendType;
			$UserSmsLogModel->user_id = $userId;
			$UserSmsLogModel->mobile = $phone;
			$UserSmsLogModel->content = $content;
			$UserSmsLogModel->gateway = $gateway;
			$UserSmsLogModel->addtime = time();
			$UserSmsLogModel->addip = FunctionUtil::ip_address();
			$UserSmsLogModel->remark = '';
			
			$queueData = array();
			$queueData['type'] = $sendType;
			$queueData['user_id'] = $userId;
			$queueData['mobile'] = $phone;
			$queueData['content'] = $content;
			$queueData['gateway'] = $gateway;
			$queueData['status'] = '1';
			
			if($sendResult['status'] == true) {
				$UserSmsLogModel->status = 1;
				$UserSmsLogModel->save();
				
				$queueData['send_status'] = '1';
				$queueData['ret'] = $sendResult['statusCode'];
				$this->addQueue($queueData);
				return true;
			} else {
				$UserSmsLogModel->status = 0;
				$UserSmsLogModel->save();
				
				$queueData['send_status'] = '2';
				$queueData['ret'] = $sendResult['statusCode'];
				$this->addQueue($queueData);
				return false;
			}
			
		} else {// 放入队列，有发送脚本发送

			$queueData = array();
			$queueData['type'] = $sendType;
			$queueData['user_id'] = $userId;
			$queueData['mobile'] = $phone;
			$queueData['content'] = $content;
			$queueData['gateway'] = $gateway;
			$queueData['status'] = '0';
			$queueData['send_status'] = '0';
			
			return $this->addQueue($queueData);
		}
		return false;
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
	public function sendGroup($phone, $content, $sendType = '', $direct = false, $params = array()) {
		if($sendType == '') {
			$sendType = 'system';
		}
		
		if( empty($phone) || empty($content) ){
			return false;
		}
		// 选择短信网关
		$gateway = $this->switchGateway($sendType);
		
		$queueData = array();
		$queueData['type'] = $sendType;
		$queueData['user_id'] = '0';
		if(is_array($phone)) {
			$queueData['mobile'] = implode(',', $phone);
		} else {
			$queueData['mobile'] = $phone;
		}
		$queueData['content'] = $content;
		$queueData['gateway'] = $gateway;
		$queueData['status'] = '0';
		$queueData['send_status'] = '0';
			
		return $this->addQueue($queueData);
	}
	
	/**
	 * 加入到短息发送队列
	 * @param array $data
	 * @return boolean
	 */
	public function addQueue($data) {
		$SmsQueueModel = new SmsQueue();
		foreach($data as $key => $value) {
			$SmsQueueModel->$key = $value;
		}
		
		$SmsQueueModel->addtime = time();
		$SmsQueueModel->addip = FunctionUtil::ip_address();
		
		if($SmsQueueModel->save() == false){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * 更新短息发送队列中的消息数据
	 * @param string $queueId
	 * @param array $data
	 * @return boolean
	 */
	public function updateQueue($queueId, $data) {
		$SmsQueueModel = new SmsQueue();
		$result = $SmsQueueModel->findByAttributes( array('id'=> $queueId) );
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
	 * 处理短信发送请求
	 * @param number $usleep 每条发送休眠时间
	 * @param number $limit 每次处理条数
	 * @param array $hasType 查询包含的请求类型
	 * @param array $hasnoType 查询不包含的请求类型
	 */
	public function dealQueue($usleep = 500000, $limit = 100, $hasType = array(), $hasnoType = array()) {
		$SmsQueueModel = new SmsQueue();
		// 设置查询条件
		$criteria = new CDbCriteria;
		if(!empty($hasType)) {
			$criteria->addInCondition('type', $hasType, 'AND');
		}
		if(!empty($hasnoType)) {
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
		$SmsQueueModel->updateByPk($recordIdArr, array('status' => '2') );
		
		$queueData = array();
		foreach($queueRecords as $key => $record) {
			$queueData = array();
			// 发送短信
			$sendResult = $this->send($record->mobile, $record->content, $record->gateway);
			
			if($sendResult['status'] == true) {
				$queueData['send_status'] = '1';
				$queueData['ret'] = $sendResult['statusCode'];
			} else {
				$queueData['send_status'] = '2';
				$queueData['ret'] = $sendResult['statusCode'];
			}
			$queueData['status'] = '1';
			// 更新状态及发送结果
			$SmsQueueModel->updateByPk($record->id, $queueData );
			
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
			if($sendResult['status'] == true) {
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
	public function switchGateway($sendType = 'default') {
		$tmpTypeName = $sendType;
		if($tmpTypeName == '' || !isset(self::$_sendTypeGatewayConf[$tmpTypeName])) {
			$tmpTypeName = 'default';
		}
		
		return self::$_sendTypeGatewayConf[$tmpTypeName];
	}
	
	/**
	 * 判断是否是手机号
	 * @param string $phone
	 * @return boolean
	 */
	public function isMobile($phone) {
		return preg_match('/^1[3458][\d]{9}$/', $phone)
				|| preg_match('/^0[\d]{10,11}$/', $phone);
	}

}
