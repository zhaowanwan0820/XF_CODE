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
 * @Class BlockCCByRate
 * 针对速率的具体实现的策略
 *
 * 主要实现：单位时间内对速率的限制
 *
 * key 由 字符串组成
 * value 统一为 {
 * 		min 自然分钟的速率上限 整数
 * 		hour 自然小时的上限 整数
 * 		day 自然天的 整数
 * 		last_t 最后一次时间 毫秒
 * 		expire_end_t 过期时间点 毫秒
 * }
 * 
 */
class BlockCCByRate implements SuperBlockCC {

	/**
	 * redis 实例
	 * @var Object
	 */
	protected $redis;

	/**
	 * 过期时间 时间段
	 * @var Int 秒
	 */
	public $expire_time;

	/**
	 * 过期时间点 时间戳
	 * @var Int 毫秒
	 */
	public $expire_end_time;

	/**
	 * 是否使用的是默认过期时间
	 * 如果使用的是默认过期时间，则每个自然分钟都会重新计算次数、小时、天同理
	 * 如果提供了过期时间，则达到规则限制后，下一个自然分钟时次数不会重新计算，直到过期
	 * @var boolean
	 */
	protected $auto_expire_time = true;

	/**
	 * 递增数量
	 * timeReg 是为了  判断 上次更新时间是否跟本次是同一时刻，
	 * 见 179 行的判断条件
	 * @var array
	 */
	public $incr = [
		'min' => [
			'timeReg' => 'Y-m-d H:i',
			'value' => 1,
		],
		'hour' => [
			'timeReg' => 'Y-m-d H',
			'value' => 1,
		],
		'day' => [
			'timeReg' => 'Y-m-d',
			'value' => 1,
		],
	];

	/**
	 * 配置
	 * @var Object
	 */
	public $config;

	/**
	 * 一般这个函数用来确定配置文件中本次应使用的配置，
	 * 也可在 KeyGen 中再确定
	 * @param  array $config
	 */
	public function __beforeConstruct($config) {
		// Yii::log('called:BlockCCByRate->__beforeConstruct', 'info', 'BlockCCPlugins');
		$this->config = $config;
	}

	/**
	 * 构造函数
	 * @param array $config
	 */
	public function __construct($config = null) {
		// Yii::log('called:BlockCCByRate->__construct', 'info', 'BlockCCPlugins');
		if (!$config) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:__construct Argument:config:{$config} is invalid");
		}
		$this->__beforeConstruct($config);
		$this->redis = RedisService::getInstance();
		$this->expire_time = strtotime('+1 days midnight') - 1 - time();
		$this->expire_end_time = strtotime('+1 days midnight') - 1 . '000';
	}

	public function KeyGen($key = []) {
		// Yii::log('called:BlockCCByRate->KeyGen', 'info', 'BlockCCPlugins');
		if (!$key) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:KeyGen Argument:key:{$key} is invalid");
		}
		return implode('_', $key);
	}

	public function GetKey($key = '', $field = '') {
		// Yii::log('called:BlockCCByRate->GetKey', 'info', 'BlockCCPlugins');
		if (!$key) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:GetKey Argument:key:{$key} is invalid");
		}
		if (!$field) {
			return $this->redis->hGetAll($key);
		} else {
			return $this->redis->hGet($key, $field);
		}
	}

	public function SetExpireTime($time = 0) {
		// Yii::log('called:BlockCCByRate->SetExpireTime', 'info', 'BlockCCPlugins');
		if ( (int) $time > 0 ) {
			$this->auto_expire_time = false;
			$this->expire_time = $time;
			$this->expire_end_time = time() + $time . '000';
		}
	}

	public function SetKey($key = '', $value = []) {
		// Yii::log('called:BlockCCByRate->SetKey', 'info', 'BlockCCPlugins');
		if (!$key) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:SetKey Argument:key:{$key} is invalid");
		}
		$m = $this->redis->hIncrBy($key, 'min', $this->incr['min']['value']);
		$h = $this->redis->hIncrBy($key, 'hour', $this->incr['hour']['value']);
		$d = $this->redis->hIncrBy($key, 'day', $this->incr['day']['value']);
		Yii::log('called:BlockCCByRate->SetKey, key:'.$key.', min:'.$m.', hour:'.$h.', day:'.$d, 'info', 'BlockCCPlugins');
	}

	public function clearKey($key = '') {
		// Yii::log('called:BlockCCByRate->SetKey', 'info', 'BlockCCPlugins');
		if (!$key) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:SetKey Argument:key:{$key} is invalid");
		}
		$r = $this->redis->del($key);
        if($r==0){
            $r = $this->redis->del($key);
        }
		Yii::log('called:BlockCCByRate->clearKey, key:'.$key.' result:'.$r, 'BlockCCPlugins');
	}

	public function SetLastTime($key = '') {
		// Yii::log('called:BlockCCByRate->SetLastTime', 'info', 'BlockCCPlugins');
		if (!$key) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:SetLastTime Argument:key:{$key} is invalid");
		}
		$mstime = FunctionUtil::getMillisecond();
		$this->redis->hSet($key, 'last_t', $mstime);
	}

	public function CheckCC($key = '') {
		// Yii::log('called:BlockCCByRate->CheckCC', 'info', 'BlockCCPlugins');
		if (!$key) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:CheckCC Argument:key:{$key} is invalid");
		}
		if (!$this->config) {
			throw new InvalidArgumentException("Class BlockCCByRate Method:CheckCC Argument:config:{$this->config} is invalid");
		}
		
		$time = time();
		$mstime = FunctionUtil::getMillisecond();
		$value = $this->GetKey($key);
		Yii::log('called:BlockCCByRate->CheckCC value:'.print_r($value, true), 'info', 'BlockCCPlugins');
		if (!$value['min']) {
			$value['min'] = 0;
		}
		if (!$value['hour']) {
			$value['hour'] = 0;
		}
		if (!$value['day']) {
			$value['day'] = 0;
		}
		if (!$value['last_t']) {
			$value['last_t'] = $mstime;
		}
		if (!$value['expire_end_t']) {
			$value['expire_end_t'] = $this->expire_end_time;
		}

		// 因为是毫秒所以要 / 1000
		$lastTime = floor($value['last_t']/1000);
		$expireEndTime = floor($value['expire_end_t']/1000);
		
		$value = $this->ShallResetKeyField($key, $lastTime, $expireEndTime, $time, $value);

		$cc = $this->ShallBlock($key, $value);
		// die(var_dump($cc));
		if (!$cc) {
			$this->WhenBlocked($key, $value);
		}
		return $cc;
	}

	/**
	 * 用来确定min/hour/day的counter 是否该清零
	 * @param string $key
	 * @param int $lastTime
	 * @param int $expireEndTime
	 * @param int $time
	 * @param int $v_min
	 * @param int $v_hour
	 * @param int $v_day
	 */
	public function ShallResetKeyField($key, $lastTime, $expireEndTime, $time, $afterIncr) {
		// Yii::log('called:BlockCCByRate->ShallResetKeyField', 'info', 'BlockCCPlugins');
		array_walk($this->incr, function($_field, $_key) use ($key, $lastTime, $time, $expireEndTime, &$afterIncr) {
			// 如果 本次跟上次 不是同一个 [分/时/天]  或者  过期结束时间点 小于 当前时间
			if (date($_field['timeReg'], $lastTime) != date($_field['timeReg'], $time) || $expireEndTime < $time ) {
				// 如果是自动设置的过期时间
				//        或者
				//     是手动设置的过期时间 并且 没有超过规则限制
				// 就清零
				// Yii::log('是不同分钟', 'info', 'BlockCCPlugins');
				if ($this->auto_expire_time || (!$this->auto_expire_time && $afterIncr[$_key] < $this->config[$_key])) {
					// Yii::log('ShallResetKeyField setted key '.$_key.' to 0', 'info', 'BlockCCPlugins');
					$this->redis->hSet($key, $_key, 0);
					$afterIncr[$_key] = 0;
				}
			}
		});
		// 如果是第一次请求，就设置一下过期时间和过期结束时间点
		// if ($afterIncr['min'] == 1 && $afterIncr['hour'] == 1 && $afterIncr['day'] == 1) {
			$this->redis->setExpireTime($key, $this->expire_time);
			$this->redis->hSet($key, 'expire_end_t', $this->expire_end_time);
			$afterIncr['expire_end_t'] = $this->expire_end_time;
		// }
		return $afterIncr;
	}

	/**
	 * 判断是否应该 block 本次请求
	 * @param string $key
	 * @param array $value
	 */
	public function ShallBlock($key, $value) {
		// Yii::log('called:BlockCCByRate->ShallBlock', 'info', 'BlockCCPlugins');
		$status = true;
		array_walk(array_keys($this->incr), function($field) use ($value, &$status) {
			// 如果配置不是无限制(-1)  并且 已经 超过限制
			if ($this->config[$field] != -1 && $value[$field] > $this->config[$field]) {
				$status = false;
			}
		});
		return $status;
	}

	/**
	 * 当请求被 block 后可能要做的事：
	 * 比如重置过期时间
	 * @param string $key
	 * @param array $value
	 */
	public function WhenBlocked($key, $value) {
		Yii::log('called:BlockCCByRate->WhenBlocked '.print_r($value, true), 'info', 'BlockCCPlugins');
		array_walk(array_keys($this->incr), function($field) use ($key, $value) {
			// 第一次达到限制
			// 并且
			// 手动设置的过期时间
			// 就
			// 重置过期时间为设置的时间
			if (($value[$field]-1) == $this->config[$field] && !$this->auto_expire_time) {
				$this->redis->setExpireTime($key, $this->expire_time);
				$this->redis->hSet($key, 'expire_end_t', $this->expire_end_time);
			}
		});
	}

}
