<?php
/*
 * ChanetCpaClass
 */

class ChanetCpaClass  {

	protected static $_config;
	const ERROR_NOUSER = '用户不存在';
	const NO_VERIFY_REAL = '未实名认证';
	const NO_VERIFY_EMAIL = '未邮箱认证';
	const NO_VERIFY_PHONE = '未手机认证';

	function __construct() {
		if(empty(self::$_config)) {
			self::$_config  = include(APP_DIR .'/protected/config/chanetCpa.php');
		}
	}

	public function &getConfig() {
		return self::$_config;
	}
    
    /**
     * TODO:根据时间范围获取CPA记录
     * @param type $attributes
     * @param type $starttime
     * @param type $endtime
     * @param type $order
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getListByTime($attributes, $starttime,  $endtime = 0,  $order = '', $offset = 0,  $limit = 10) {
        $ItzChanetCpaModel = new ItzChanetCpa();
        $criteria = new CDbCriteria;
        $criteria->addBetweenCondition('addtime', $starttime, $endtime ? $endtime : time());
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        if(!empty($limit)) $criteria->limit = $limit;
		$criteria->index = 'user_id';

        $recordarr = $ItzChanetCpaModel->findALLByAttributes($attributes, $criteria);
        return $recordarr;
    }
	
	/**
	  * 根据时间查询有效用户
	  *
	  *
	  */
	public function checkByTime($starttime, $endtime = 0,  $order = '', $offset = 0,  $limit = 10) {
        $ItzChanetCpaModel = new ItzChanetCpa();
        $criteria = new CDbCriteria;
        $criteria->addBetweenCondition('addtime', $starttime, $endtime ? $endtime : time());
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        if(!empty($limit)) $criteria->limit = $limit;
		$criteria->index = 'user_id';

		$attributes = array();
        $recordarr = $ItzChanetCpaModel->findALLByAttributes($attributes, $criteria);
        return $recordarr;
    }

	/**
	 * 检查是否为有效用户
	 *@param int userId
	 *
	 */
    public function checkUserValidByUserId($userId) {
		$userRecord = User::model()->findByPk($userId);
        if(empty($userRecord)) {
			$result['status'] = false;
            $result['info'] = self::ERROR_NOUSER;
            return $result;
        }
		
        $userAttrs = $userRecord->getAttributes();
		return $this->checkUserValid($userAttrs);
	}
    
	/**
	 * 检查是否为有效用户
	 *@param int userId
	 *
	 */
    public function checkUserValid($userAttrs) {
        $result = array();
		$result['status'] = true;

		if(empty($userAttrs)) {
			$result['status'] = false;
            $result['info'] = self::ERROR_NOUSER;
            return $result;
        }
        
		// 实名认证
		if( isset(self::$_config['validRule']['realname']) && self::$_config['validRule']['realname']) {
			if( $result['status'] && $userAttrs['real_status'] == 1) {
				$result['status'] = true;
				$result['data']['realname'] = $userAttrs['realname'];
			} else {
				$result['status'] = false;
				$result['info'] = self::NO_VERIFY_REAL;
			}
		}
		// 邮箱认证
		if( isset(self::$_config['validRule']['email']) && self::$_config['validRule']['email']) {
			if( $result['status'] && $userAttrs['email_status'] == 1) {
				$result['status'] = true;
				$result['data']['email'] = $userAttrs['email'];
			} else {
				$result['status'] = false;
				$result['info'] = self::NO_VERIFY_EMAIL;
			}
		}
		// 手机认证
		if( isset(self::$_config['validRule']['phone']) && self::$_config['validRule']['phone']) {
			if( $result['status'] && $userAttrs['phone_status'] == 1) {
				$result['status'] = true;
				$result['data']['phone'] = $userAttrs['phone'];
			} else {
				$result['status'] = false;
				$result['info'] = self::NO_VERIFY_PHONE;
			}
		}

        return $result;
    }
	
	/**
	 * TODO:推送 
	 *
	 *
	 */
	public function push($id, $i = '') {
		$urlparamStr = '';
		$param = array();
		$param[] = 't='.self::$_config['push']['t'];
		$param[] = 'id='.$id;
		$param[] = 'i='.urlencode($i);
		$sign = md5(implode('&', $param).'&key='.self::$_config['signKey']);
		$param[] = 'sign='.$sign;
		
		$pushUrl = self::$_config['push']['url'].'?'.implode('&', $param);
		
		$pushFlag = false;
		$response = CurlUtil::get($pushUrl);
		
		if($response['content'] == '1') {
			$pushFlag = true;
		} else {
			$pushFlag = false;
		}

		return $pushFlag;
	}
    
}
