<?php

/**
 * 用户自动注册类
 *
 * @ClassName: sendContract 
 * @Description: 通过姓名、手机号、邮箱自动注册
 * @param $user_name ,$user_email, $user_phone
 * @author Liwei 
 * @date Jul 29, 2013 1:12:38 PM  
 *
 */

require APP_ROOT_PATH.'system/libs/CryptRc4.php';
require APP_ROOT_PATH.'system/libs/user.php';

class autoRegister{
	private $str = '';		//组合字符串
	private $key = '';		// 密钥
	private $type = '';		//类型
	private $api_url = '';	//接口地址
	
	
	   
	public function __construct($data = NULL, $key = NULL, $type = NULL, $api_url = NULL) {
		
		$this->key = empty($key) ? '5oiR55qE5ZCN5a2X5piv77' : $key;
		$this->type = empty($type) ? 'MCRYPT_DES' : $type;
		$this->api_url = empty($api_url) ? $GLOBALS['sys_config']['AUTO_REGISTER_API_URL'] : $api_url;
		
		$this->creatStr($data);
	}

	/**
	 * 生成组合的字符串
	 *
	 * @Title: creatStr 
	 * @Description: 生成组合的字符串
	 * @param	$this->user_name;
	 * @param	$this->user_email;
	 * @param	$this->user_phone;
	 * @param	$this->key;
	 * @return set $this->str;  
	 * @author Liwei
	 * @throws 
	 *
	 */
   
	private function creatStr($arr){
		
		if(empty($arr)) return false;
		
		$arr['key'] = $this->key;
		
		$this->str = json_encode($arr);
	}
	
	/**
	 * 使用rc4加密
	 *
	 * @Title: rc4 
	 * @Description: rc4加密
	 * @param    
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	public function rc4Encode(){
		$rc4 = new CryptRc4($this->key);
		
		$str_en = $rc4->encrypt($this->str);
		
		return $str_en;
	}
	
	/**
	 * RC4 解密
	 *
	 * @Title: rc4Decode 
	 * @Description: 解密
	 * @param    
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	public function rc4Decode(){
		
		$rc4 = new CryptRC4($this->key);
		
		$str_de = $rc4->decrypt($this->str);
		
		return $str_de;
	}
	
	public function oAuthRegister(){
		
		$data = $this->rc4Encode();
		
		//接口返回数据
		$user_info = $this->curlDoGet($this->api_url, $data);	
		
		if (empty($user_info)) return false;
		
		$this->str = $user_info;
		
		//解密
		$decode_arr = $this->rc4Decode();
		
		//转数组
		$json_decode_arr = json_decode($decode_arr,true);
		
		$user_info_arr = $loc_user_info = array();
		if (is_array($json_decode_arr)){
			$user_info_arr = $json_decode_arr;
		}else{
			$user_info_arr = json_decode($json_decode_arr,true);
		}
		
		$loc_user_info = $this->saveAutoRegisterUserInfo($user_info_arr);
		
		return $loc_user_info;
	}
	
	/**
	 * 保存自动注册的用户到本地
	 *
	 * @Title: saveAutoRegisterUserInfo 
	 * @Description: 保存自动注册的用户到本地 
	 * @param  $user_info 用户信息
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	function saveAutoRegisterUserInfo($user_info){
		if(empty($user_info)) return false;
		
		$data = $res = array();
		$data['user_name'] = $user_info['user_login_name'];
        $data['email'] = $user_info['user_email'];
        $data['mobile'] = $user_info['user_name'];
        $data['oauth'] = 1;
		
		$res = save_user($data);
		
		//如果返回值错误则查询本地库里的记录
		if(is_array($res['data'])){
			$user_data = "";
			$user_data = $GLOBALS['db']->getRow("select id from ".DB_PREFIX."user where (user_name='".$data['user_name']."' or email = '".$data['email']."' or mobile = '".$data['mobile']."') and is_delete = 0");
			if($user_data['id']){
				$res['loc_user_id'] = $user_data['id'];
			}else{
				return false;
			}
		}else{
			$res['loc_user_id'] = $res['data'];
		}
		return array_merge($res,$user_info);		
	}	
	
	/**
	 * curl 请求
	 *
	 * @Title: curlDoGet 
	 * @Description: todo(这里用一句话描述这个方法的作用) 
	 * @param @param unknown_type $url
	 * @param @param unknown_type $data   
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	function curlDoGet($url, $data){
		$target = $url.'?data='.$data;
		$cu = curl_init();
		curl_setopt($cu, CURLOPT_URL, $target);
		curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($cu);
		curl_close($cu);
		return $ret;
	}
   
}

?>
