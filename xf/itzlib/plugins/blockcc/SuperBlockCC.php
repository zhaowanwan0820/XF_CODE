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
 * @interface SuperBlockCC 抽象类 对外提供 4 个方法
 * @method String  KeyGen( Array $pattern ) 生成 key， pattern 为 array 类型
 * @method Array   GetKey( String $key, String $field ) 获取指定 key 或 field 的值
 * @method Array   SetKey( String $key, Array $value ) 设置或自增 key 的 value
 * @method Boolean CheckCC( String $key ) 决定业务是否继续处理
 * @method Object  SetExpireTime( Int $time ) 设置过期时间，单位为 秒，如 20 表示 20 秒后过期
 */
interface SuperBlockCC {

	public function KeyGen($pattern = []);

	public function GetKey($key = '', $field = '');

	public function SetKey($key = '', $value = []);

	public function CheckCC($key = '');

	public function SetExpireTime($time = 0);
	
}
