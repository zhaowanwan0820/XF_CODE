<?php

/**
 * @Class BlockCCFilter
 * 防暴力破解基础过滤组件
 *
 * @author  ThomasChan <chenjunhao@itouzi.com>
 * @link  http://confluence.itouzi.com/pages/viewpage.action?pageId=72056867
 * 
 */

class BlockCCFilter extends CFilter {

	public function preFilter($filterChain) {
		$config = require dirname( APP_DIR ) . '/itzlib/config/blockcc.php';


		/**
		* 针对 ip + action 的 cc
		*/
		$IpActionConf = array_keys($config['IpAction']);
		$url = FunctionUtil::getCurrentUrl($filterChain->controller);
		if (in_array($url, $IpActionConf)) {
			if ($url == '/newuser/rAjax/nreg') {
				$cc = BlockCC::getInstance()->getNew('IpAction')->SetAndCheck(['total']);
			} else {
				$cc = BlockCC::getInstance()->getNew('IpAction')->Check(['error']);
			}
			if (!$cc) {
				//header('Too Many Request', true, 429);
				header('Content-Type: application/json');
				echo json_encode([
					'info' => '您操作过于频繁，请稍后重试！',
					'code' => 429,
					'data' => []
				]);
				return false;
			}
		}

		/**
		* ...
		* 针对需求再次配置其他类型的防 CC 策略
		* ...
		*/

		return true;
	}

}
