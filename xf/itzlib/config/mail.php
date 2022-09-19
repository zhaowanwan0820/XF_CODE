<?php
/******************************
 * $File: mail.php
 * $Description: mail
 * $Author: changqi 
 * $Time:2014-01-16
 * $Update:None 
 * $UpdateDate:None 
******************************/


return array(
		
		// 默认邮件发送网关
		'defaultGateway' => '3',
		
		// 邮件通道
		'gateway' => array(
				'1' => array(
						'host' => 'smtp.exmail.qq.com',
						'username' => 'report-auto@xxx.com',
						'password' => 'af^9c1#9b24e@3',
						'address' => 'report-auto@xxx.com',
						'fromName' => 'ITZ',
				),
				'2' => array(
						'host' => 'smtpcloud.sohu.com',
						'username' => 'postmaster@triggerIP.noreply.xxx.com',
						'password' => 'DeTdFbh30UWoNpFq',
						'address' => 'service@noreply.xxx.com',
						'fromName' => 'ITZ',
				),
				'3' => array(
						'host' => 'smtpcloud.sohu.com',
						'username' => 'postmaster@triggerIP.noreply.xxx.com',
						'password' => 'DeTdFbh30UWoNpFq',
						'address' => 'service@noreply.xxx.com',
						'fromName' => 'ITZ',
				),
		),
		
		// 根据邮件选择邮件发送网关的配置
		'switchGatewayByDomain' => array(
				'xxx.com' => array('1'),
				'qq.com' => array('2'),
		),
		
		// 根据要发送的邮件类型选择邮件发送网关的配置
		'switchGatewayByContentType' => array(
		),
		
		// send failed retry number
		'FailedRetryNumberByType' => array(
			'reg' => 3,
		),
		
);
