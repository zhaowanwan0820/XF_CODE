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
 * @Class BlockCCByUserSpecKey
 * 针对 user + 特定 key 的限制
 * 原 Class PreventBruteForce
 *
 * $key 由 固定字符串A + user_id 组成
 * 配置文件中的 key 就是 固定字符串A
 * 
 */
class BlockCCByUserKey extends BlockCCByRate {

	/**
	 * 重写 KeyGen 的原因是 user + key 防 cc 的 配置文件中 的 key 是 $pattern 中的首个元素   ，  即 以  字符串为前缀的
	 * @param array $pattern key 的组成，有 字符串、 user_id 等
	 */
	public function KeyGen($pattern = []) {
		// Yii::log('called:BlockCCByUserKey->KeyGen', 'info', 'BlockCCPlugins');
		if (!$pattern) {
			throw new InvalidArgumentException("Class BlockCCByUserKey Method:KeyGen Argument:pattern:{$pattern} is invalid");
		}
		$keyPrefix = current($pattern);
		$key = implode('_', $pattern);
		$this->config = $this->config[$keyPrefix];
		return $key;
	}

	public function ShallResetKeyField($key, $lastTime, $expireEndTime, $time, $value) {
		$a = parent::ShallResetKeyField($key, $lastTime, $expireEndTime, $time, $value);
		$this->SetLastTime($key);
		// Yii::log('called:BlockCCByUserKey->ShallResetKeyField'.print_r($a, true), 'info', 'BlockCCPlugins');
		return $a;
	}

}
