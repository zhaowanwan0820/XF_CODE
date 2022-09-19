<?php

/**
 * Class BlockCC
 * 防暴力破解基础组件
 *
 * Usage:
 *
 * 1， 制定具体防爆策略后新增 class implements SuperBlockCC 或 extends BlockCCByRate 实现，在 BlockCC class constructor 中加入其映射；
 * 2， 在 itzlib/config/BlockCCConfig.php 中加入策略的配置；
 * 3， 在  /protected/class/BlockCCFilter.php preFilter 中加入对策略的过滤；
 * 4， 根据需要在业务逻辑中调用 BlockCC::getInstance()->getNew( A )->CheckCC( B ); 
 *     A/B 的取值参考 itzlib/config/BlockCCConfig.php 中的配置。
 *
 * 
 * Class BlockCC: 对外统一调用类
 * Interface SuperBlockCC: 防 CC 通用接口
 * 
 * Class BlockCCByRate: 根据速率限制基础类 {
 * 		Class BlockCCByIpAction: 根据 IP + Action 防 CC 具体实现
 *   	Class BlockCCByUserKey(原 Class PreventBruteForce): 根据 User + Key 防 CC 具体实现
 * }
 * 
 * 可根据不同需求再具体实现防 CC 的其他类，比如只针对 User 的防爆破
 * 
 * 
 * @link http://confluence.xxx.com/pages/viewpage.action?pageId=72056867
 * @author ThomasChan <chenjunhao@xxx.com>
 *
 */

/**
 *
 * @Class BlockCCByUserAction
 * 针对 ip + action 的具体实现
 *
 * 单位时间内 对 ip + action 的流速控制
 * 
 */
class BlockCCByIpAction extends BlockCCByRate {

	public $configAll = [];

	public function __beforeConstruct($config = null) {
		// Yii::log('called:BlockCCByIpAction->__beforeConstruct', 'info', 'BlockCCPlugins');
		$this->configAll = $config[FunctionUtil::getCurrentUrl()];
	}

	public function KeyGen($keySuffix = []) {
		// Yii::log('called:BlockCCByIpAction->KeyGen', 'info', 'BlockCCPlugins');
		$keyDefault = [
			FunctionUtil::ip_address(),
			FunctionUtil::getCurrentUrl(),
		];
		if (!$keySuffix) {
			throw new InvalidArgumentException("Class BlockCCByIpAction Method:CheckCC Argument:keySuffix:{$keySuffix} is invalid");
		}
		$this->config = $this->configAll[current($keySuffix)];
		$key = array_merge($keyDefault, $keySuffix);
		return implode('_', $key);
	}

	public function SetKey($key = '', $value = []) {
		parent::SetKey($key, $value);
		$this->SetLastTime($key);
	}

}
