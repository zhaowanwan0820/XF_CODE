<?php
class RealAuthStaticsUtil
{
	# memcache key 前缀
	const _pre = "realauth_record_responseTime_";

	# 统计样本数量
	const _count = 100;

	/**
	 *	description：统计 不同渠道的实名认证接口的正常响应时间
	 * 	params：lable => 渠道标识
	 *			time => 花费时长（秒）
	 *	统计时间 10 天 10*24*60*60 = 864000
	 *	最多统计_count条
	 */
	public static function record ($label, $time)
	{
		# time转成毫秒
		$time = (int)(round($time * 1000, 0));

		$key = self::_pre.$label;
		$array = array();
		$cached = Yii::app()->dcache->get($key);

		if( !$cached ){
			array_push( $array,$time );
        	$res = Yii::app()->dcache->set( $key,json_encode($array),864000 );
        }else{
        	$array = json_decode($cached,true);
        	if( count($array) < self::_count ){
        		array_push( $array,$time );
        		Yii::app()->dcache->set( $key,json_encode($array),864000 );
        	}else{
        		# 满统计样本数量了
        		Yii::app()->dcache->delete( $key );
        		$receiver = array('dinglingjie@xxx.com','genglequn@xxx.com');
        		self::sendEmailNotice( $label,$array );
        	}
        }
	}

	private static function sendEmailNotice( $label,$array ){
		$averageTime = array_sum( $array ) / self::_count;
		$averageTime = (int)(round($averageTime, 0));

		$emails = array('dinglingjie@xxx.com','genglequn@xxx.com');
        $title = "【重要邮件】接口响应时间统计--渠道方：$label";
        $content = "实名认证接口之渠道方：$label,近".self::_count."条成功数据的平均响应时间为：{$averageTime}毫秒";
        
        //发送邮件
        $MailClass = new MailClass();
        $result = $MailClass->send($emails, 'alarm', $title, $content);
	}
}