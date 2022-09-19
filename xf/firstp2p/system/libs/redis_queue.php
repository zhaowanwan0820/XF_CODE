<?php
	require dirname(__FILE__).'/../common.php';
	require APP_ROOT_PATH.'/app/Lib/common.php';
	require_once dirname(__FILE__)."/../utils/es_sms.php";
 	
	
	/**
	 * @discription	redis短信接口
	 * @author 		caolong
	 * @date		2013-10-24
	 * @modify		2013-10-30
	 * @log			suc 	成功 
	 * 				input	写入
	 * 				war		第一个队列处理失败
	 * 				err		第二个队列处理是啊比
	 * 				rep		重复发短信一次
	 */  
	class RedisQueue{
		private $link;			
		private $first 		= 'hash_first_';		//redis hash前缀key
		private $second		= 'hash_second_';		//处理失败的第二个 备用key
		private $prefix		= 'queue_key_';
		private $uniqKey	= 'queue_';				//生成队列唯一key
		private $expireTime = 172800;				//设置key失效时间1天
		private $config 	= array('host'=>'10.18.6.50','port'=>'6379');	//redis配置
		private $allowkey	= array('content','phone');
		private $logDir 	= '/home/wwwlogs/';
		
		function __construct(){
			$this->getConnection();			
		}
		
		/**
		 *  redis连接
		 */
		private function getConnection() {
			if($this->link === null) {
				try {
					$this->link = new Redis();
					$this->link->connect($this->config['host'],$this->config['port']);
				} catch (Exception $e) {
					$this->logWrite($e->getMessage()."\n",'redisErr');
				}
				
			}			
		}
		
		/**
		 *  写入队列
		 * @param string $redisKey
		 * @param unknown $data
		 * @return boolean
		 */
		public function writeRdis($data = array(),$key = 'first',$flag = false) {
			if(!empty($data)) {
					if($this->checkData($data))
						return false;	 
					$queue = $this->prefix.$key;
					$uniqidKey = uniqid($this->uniqKey);
					$return = $this->link->lPush($queue,$uniqidKey);
					if(!empty($return)) {
						$hKey = $this->$key.$uniqidKey;
						$this->link->hMset($hKey,$data);
						//设置 key缓存失效时间
						$this->link->expire($hKey,$this->expireTime);
						//记录日志
						$logStr = $this->dealArr($data);
						$extension = $flag == true ? 'xxput' : 'input';
						$this->logWrite($logStr,$extension);
					}else
						return false;			
			}else
				return false; 

			return true;			
		}
		
		/**
		 * 处理队列
		 * @param string $flag
		 */
		public function handleReids($key) {
			if(!empty($key)) {	
				while($result = $this->link->brPop($this->prefix.$key,3600)) {
					if(!empty($result[1])) {
						$hKey = $this->$key.$result[1];
						$r = $this->link->hMget($hKey,$this->allowkey);
						if(!empty($r)) {
							//发送短信
							$return = $this->sendMsg($r);
							$logStr = $this->dealArr($r);
							if($return) {
								//删除 key下的域值
								$this->link->del($hKey);
								$status = 'suc';
							}else{	//发送失败处理
								$status = $key ==  'first'   ? 'war' : 'err';
								$this->writeRdis($r,'second',true);
							}
							$this->logWrite($logStr,$status);
						}
						sleep(5);
					}
				}
			}
		}
		
		
		/**
		 * 处理发送短信
		 * @param unknown $data
		 */
		public function sendMsg($data = array()) {
			if(!empty($data)) {
				$sms = new sms_sender();
				$sms_content = str_replace(array(" "," "), '', $data['content']);
				$result = $sms->sendSMS($data['phone'],$sms_content);
				if($result['status'] == 1)
					return true;
				else{ //补救 发送一次
					$r = $sms->sendSMS($data['phone'],$sms_content);
					$logStr = $this->dealArr($data);
					$this->logWrite($logStr,'rep');
					if($r['status'] == 1)
						return true;
				}
			}
			return false;
		}
		
		/**
		 * 日志数组处理
		 * @param unknown $arr
		 * @param string $flag
		 * @return string|boolean
		 */
		public function dealArr($arr = array(),$flag = false) {
			$logStr = '';
			if(!empty($arr)) {
				$values = array_values($arr);
				if($flag) {
					$logStr .= http_build_query($arr);
				}else {
					foreach((array)$values as $key=>$val )
						$logStr.= $val." ";
				} 
				return $logStr.date('Y-m-d H:i:s')."  \n";
			}else 
				return false;
		}
		
		/**
		 * 写日志
		 * @param string $content
		 * @param string $type
		 */
		public function logWrite($content = '',$type = 'err') {
			$fileName = date('Ymd');
			$file = $this->checkDir().$fileName.'_'.$type.'.log';
			file_put_contents($file,$content,FILE_APPEND);
		}
		
		
		/**
		 * 检查目录
		 * @return string
		 */
		public function checkDir() {
			$year 	= date('Y');
			$month 	= date('m');
			$dirName= $this->logDir.$year.'/'.$month.'/';
			$new = explode('/', $dirName);
			foreach((array)$new as $key=>$val) {
				$dir.=$val.'/';
				if(!file_exists($dir)) {
					@mkdir($dir,0777);
				}
			}
			return $dirName; 
		}
		
		//验证 入队列数据  内容 手机号
		public function checkData( $data = array(),$flag = false) {
			if($flag) return false; 
			foreach((array)$data as $key=>$val) {
				if($key == 'content') {
					if(trim($val) != '') 
						return false;
					else
						return true;
				}
				if($key == 'phone') {
					$exp = '/^13[0-9]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|18[012356789]{1}[0-9]{8}$|14[57]{1}[0-9]$/';
					if(preg_match($exp,$val))
						return false;
					else 
						return true;
				}
			}
		}
	}
	
	
	
	/**
		使用demo
		$queue = new RedisQueue();
		$data = array('content'=>' php版- ikoDotA - 博客园','phone'=>'18618199758');
		
		//写入队列   return bool true 成功  false 失败
		$queue->writeRdis($data);
		
		//脚本处理 跑数据
		/dev/script/guard.sh
		
	 */
	

	