<?php

/**
 * Class BlockCC
 * 防暴力破解基础组件
 *
 * Usage:
 *
 * 1， 制定具体防爆策略后新增 class implements SuperBlockCC 或 extends BlockCCByRate 实现，在 BlockCC class constructor 中加入其映射；
 * 2， 在 itzlib/config/BlockCCConfig.php 中加入策略的配置；
 * 3， 在 /protected/class/BlockCCFilter.php preFilter 中加入对策略的过滤；
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
 *
 */

/**
 *
 * @Class BlockCC
 * 统一对外接口
 */
class BlockCC {

	/**
	 * 运行实例
	 * @var Object
	 */
	public static $instance;

	/**
	 * 实例化类
	 * @var Object
	 */
	public $cc;

    public static function getInstance() {
        if (is_null(self::$instance) ){
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
	 * 实例函数
	 * @param String $type 配置文件里的策略名称
	 * 策略配置全部放在 itzlib/config/BlockCCConfig.php 文件中，以策略名为维度
	 * @link itzlib/config/BlockCCConfig.php see details
	 */
	public function getNew($type = '') {
		if (!$type) {
			throw new InvalidArgumentException("Class BlockCC __constructor must have initialization Argument:type");
		}
		$GlobalConfig = require dirname( __DIR__ ) . '/config/blockcc.php';
		$ClassConfig = $GlobalConfig[$type];
		$ClassName = isset($ClassConfig['ClassName']) ? $ClassConfig['ClassName'] : 'BlockCCBy' . $type;
		$this->cc = new $ClassName($ClassConfig);
		return $this;
	}

	/**
	 * 获取 key 的值
	 * @param string $pattern
	 * @param string $field
	 * @return Object|null
	 */
	public function Get($pattern = [], $field = '') {
		try {
			$key = $this->cc->KeyGen($pattern);
			$result = $this->cc->GetKey($key, $field);
			Yii::log('called:BlockCC->Get, key:'.$key.', value:'.print_r($result, true), 'info', 'BlockCCPlugins');
			return $result;
		} catch (Exception $ee) {
			Yii::log('Class BlockCC Get Exception:' . print_r($ee->getMessage(), true), 'error', 'BlockCCPlugins');
		}
		return [];
	}

	/**
	 * 设置 key 的值
	 * @param string $pattern
	 * @param array  $value
	 * @return null
	 */
	public function Set($pattern = [], $value = []) {
		try {
			$key = $this->cc->KeyGen($pattern);
			$this->cc->SetKey($key, $value);
			Yii::log('called:BlockCC->Set, key:'.$key, 'info', 'BlockCCPlugins');
		} catch (Exception $ee) {
			Yii::log('Class BlockCC Set Exception:' . print_r($ee->getMessage(), true), 'error', 'BlockCCPlugins');
		}
		return true;
	}

	/**
	 * 设置 key 首次达到限制规则后多长时间后过期
	 * @param integer $time
	 * @return Object
	 */
	public function SetExpireTime($time = 0) {
		$this->cc->SetExpireTime($time);
		return $this;
	}

	/**
	 * 检查是否符合限制规则
	 * @param string $pattern
	 * @return Boolean
	 */
	public function Check($pattern = []) {
		try {
			$key = $this->cc->KeyGen($pattern);
			$result = $this->cc->CheckCC($key);
			Yii::log('called:BlockCC->Check, key:'.$key.', value:'.$result, 'info', 'BlockCCPlugins');
			return $result;
		} catch (Exception $ee) {
			Yii::log('Class BlockCC Check Exception:' . print_r($ee->getMessage(), true), 'error', 'BlockCCPlugins');
		}
		return true;
	}

	/**
	 * 设置 value 并检查是否符合限制规则
	 * @param array $pattern
	 * @return Boolean
	 */
	public function SetAndCheck($pattern = []) {
		try {
			$key = $this->cc->KeyGen($pattern);
			Yii::log('called:BlockCC->SetAndCheck, key:'.$key, 'info', 'BlockCCPlugins');
			$this->cc->SetKey($key);
			$continue = $this->cc->CheckCC($key);
			if (!$continue) {
				Yii::log("Class BlockCC:: key:{$key} blocked", 'info', 'BlockCCPlugins');
				return false;
			}
		} catch (Exception $ee) {
			Yii::log('Class BlockCC SetAndCheck Exception:' . print_r($ee->getMessage(), true), 'error', 'BlockCCPlugins');
		}
		return true;
	}

	/**
	 * 清除 SetAndCheck 的规则
	 * @param array $pattern
	 * @return Boolean
	 */
	public function clear($pattern = []) {
		try {
			$key = $this->cc->KeyGen($pattern);
			Yii::log('called:BlockCC->clear, key:'.$key, 'info', 'BlockCCPlugins');
			$this->cc->clearKey($key);
		} catch (Exception $ee) {
			Yii::log('Class BlockCC clear Exception:' . print_r($ee->getMessage(), true), 'error', 'BlockCCPlugins');
		}
		return true;
	}
}
