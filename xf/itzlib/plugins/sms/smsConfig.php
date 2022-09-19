<?php

/**
 * 短信接口统一配置文件
 */

return array(
		
		// 默认选择的短息通道
		'defaultGateway' => '1',
		
		// 短信网关类型
		'gatewayType' => array('emay', 'yyqd'),
		
		// 短信通道
		'gateway' => array(
				'1' => array(
						'type' => 'emay',
						'gatewayUrl' => 'http://voice.b2m.cn:8090/sdk/SDKService?wsdl',//'http://sdk4report.eucp.b2m.cn:8080/sdk/SDKService?wsdl',
						'serialNumber' => 'EUCP-EMY-VOC1-Z66H1',//'6SDK-EMY-6688-JIYRP',
						'password' => '22222222',//
						'sessionKey' => '33333333',
				)

				
		)
);

