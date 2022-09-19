<?php
/**
 * @file WechatService.php
 * @author zlei
 * @date 2016/11/28
 *
 **/
class WechatService extends ItzInstanceService {

    public function __construct()
    {
        parent::__construct();
    }
	
    /*
     * 获取微信信息
    */
    public function getWeChatInfo(){
    	// 获取微信信息
    	$appid = ConfUtil::get("Wechat-service-app.id");
   		$appsecret = ConfUtil::get("Wechat-service-app.secret");
    	
        $redis_model = Yii::app()->newceleryqueue;
    	$token = $redis_model->get('wx_accesstoken');
    	$ticket = $redis_model->get('wx_accessticket');
    	if( empty($token) || empty($ticket) ) {
    		//获取token
    		$token = $this->curl('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret);
    		if(empty($token['access_token'])){
    			Yii::log("GetWeChatInfo Get WeChat Token is fail! info:".print_r($token,true),'error');
    		}
    		$token = $token['access_token'];
    		
    		//获取ticket
    		$ticket = $this->curl('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$token.'&type=jsapi');
    		if(empty($ticket['ticket'])){
    			Yii::log("GetWeChatInfo Get WeChat Ticket is fail! info:".print_r($ticket,true),'error');
    		}
    		$ticket = $ticket["ticket"];
    		
    		//存入redis
			$redis_model->serializer = false;
    		$res_redis = $redis_model->set('wx_accesstoken', $token, 660);
    		$redis_model->set('wx_accessticket', $ticket, 660);
    		if(!$res_redis){
    			Yii::log("GetWeChatInfo set WeChat Ticket to redis is fail !",'error');
    		}
    	}
    	/* $times = time();
    	// 注意 URL 一定要动态获取，不能 hardcode.
    	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    	$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    	$noncestr = 'hellbill';
    	$signature = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s",$ticket, $noncestr, $times, $url);
 		*/    	
    	return array(
    		'appid'=>$appid,
    		'appsecret'=>$appsecret,
    		'accesstoken'=>$token,
    		'ticket'=>$ticket,
    	);
    }


	/*
     * 获取ISEE微信信息
    */
    public function getIseeWeChatInfo(){
    	// 获取微信信息
    	$appid = ConfUtil::get("Wechat-isee-service-app.id");
   		$appsecret = ConfUtil::get("Wechat-isee-service-app.secret");
    	
        $redis_model = Yii::app()->newceleryqueue;
    	$token = $redis_model->get('isee_wx_accesstoken');
    	$ticket = $redis_model->get('isee_wx_accessticket');
    	if( empty($token) || empty($ticket) ) {
    		//获取token
    		$token = $this->curl('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret);
    		if(empty($token['access_token'])){
    			Yii::log("GetWeChatInfo Get ISEE WeChat Token is fail! info:".print_r($token,true),'error');
    		}
    		$token = $token['access_token'];
    		
    		//获取ticket
    		$ticket = $this->curl('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$token.'&type=jsapi');
    		if(empty($ticket['ticket'])){
    			Yii::log("GetWeChatInfo Get ISEE WeChat Ticket is fail! info:".print_r($ticket,true),'error');
    		}
    		$ticket = $ticket["ticket"];
    		
    		//存入redis
			$redis_model->serializer = false;
    		$res_redis = $redis_model->set('isee_wx_accesstoken', $token, 660);
    		$redis_model->set('isee_wx_accessticket', $ticket, 660);
    		if(!$res_redis){
    			Yii::log("GetWeChatInfo set ISEE WeChat Ticket to redis is fail !",'error');
    		}
    	}
    	/* $times = time();
    	// 注意 URL 一定要动态获取，不能 hardcode.
    	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    	$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    	$noncestr = 'hellbill';
    	$signature = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s",$ticket, $noncestr, $times, $url);
 		*/    	
    	return array(
    		'appid'=>$appid,
    		'appsecret'=>$appsecret,
    		'accesstoken'=>$token,
    		'ticket'=>$ticket,
    	);
    }
    /**
     * 获取微信模板信息
     * @param string $tid
     * @return Ambigous <multitype:, unknown>
     */
    public function getWxTemplateList($tid=''){
    	
    	$return = array(
    		'code'=>0,
    		'info'=>'',
    		'data'=>array()
    	);
    	
    	$info = $this->getWeChatInfo();

    	//GET方式
    	$url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=".$info['accesstoken'];
    	$list = $this->curl($url);
    	$t_info = array();
    	if($list){
    		if( isset($list['errcode']) && $list['errcode']>0 ){
    			$return['code'] = $list['errcode'];
    			$return['info'] = $list['errmsg'];
    			return $return;
    		}
    		foreach ($list['template_list'] as $k=>$val){
    			if($val['template_id'] == $tid){
    				$t_info = $val;
    				break;
    			}
    		}
    		if(empty($t_info)){
    			$return['code'] = 101;
    			$return['info'] = '获取数据失败！';
    			return $return;
    		}else{
    			$return['code'] = 0;
    			$return['info'] = '获取数据成功！';
    			$return['data'] = $t_info;
    		}
    	}
    	return $return;
    }
    
    
    /*
     * curl请求
    */
    public function curl($url, $curlPost=array()){
    	$ch = curl_init();//初始化curl
    	curl_setopt($ch,CURLOPT_URL, $url);//抓取指定网页
    	curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    	curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    	$data = curl_exec($ch);//运行curl
    	curl_close($ch);
    	return json_decode($data, true);
    }  
    
}