<?php

/*
 * 用户登录验证
 */
class UserIdentity extends CUserIdentity {

    protected $status;
    public $errorCounters;

    const ERROR_NO = 0; //验证成功
    const ERROR_USER = 1; //用户不存在
    const ERROR_PWD = 2; //密码不正确
    const ERROR_USERTYPE = 3; // 用户类不匹配    	
    /**
     * @登录注册重构@20160903@chenjunhao@账户的两个锁定级别与锁定时间
     */
    const LOCK_LEVEL_1 = 5; // 一级锁定次数
    const LOCK_LEVEL_1_TIME = 10800; // 一级锁定时长 3小时
    const LOCK_LEVEL_2 = 10; // 二级锁定次数
    const LOCK_LEVEL_2_TIME = -1; // 二级锁定时长 不限/永久
    const ERROR_LOCK_0 = 2097;
    const ERROR_LOCK_2 = 2098;
    const ERROR_LOCK_1 = 2099;
	/*
     * 验证登录
     * is_guar 是否是企业后台登录
     * is_union 是否是联盟登录
     * is_oauth 是否是第三方登录
     * */
	public function authenticate($is_guar=false, $is_union=false, $is_oauth=false) {
	    //检查用户名 邮箱 电话是否存在
        if ($is_guar) {
            $guar = new GuarantorNewClass();
            $user = $guar->getByPhone($this->username);
        } else if ($is_union) {
            $user = UnionService::getInstance()->getUser($this->username);
        } else {
            $UserClass = new UserClass();
            $user = $UserClass->checkUserValid($this->username);
        }
        if (!$user) {//用户不存在
            $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            $this->status = self::ERROR_USER;
            return false;
        }
        // blackList @ThomasChan
        try {
            $blackList = include '/tmp/blackList.php';
            if (in_array($user->user_id, $blackList)) {
                Yii::log('SpecialUser_Login_Trace user_id:'.$user->user_id.' and:'.$this->password, 'info', 'SpecialUser');
            }
        } catch (Exception $ee) {
            Yii::log('SpecialUser_Login_Trace :'.print_r($ee->getMessage(),true), 'error', 'SpecialUser');
        }

        //第三方登录可能没有设置密码 需要支持登录
        if ($is_oauth) {
            Yii::app()->user->setState('_user', $user->attributes);
            $this->errorCode = self::ERROR_NONE;
            $this->status = 0;
            return true;
        }
        $user = $this->verifyPassword($user, $is_guar, $is_union);
        Yii::app()->user->setState('_user', $user->attributes);
        if ($is_union == true) {
            Yii::app()->user->setState('_is_union', true);
        }
        if ($is_guar || $is_union) { // 如果是担保公司，就不去锁定了
            return $this->status === 0 ? true : false;
        }
        $userStatus = $this->verifyUserStatus();
        if ($userStatus['code'] === 0) {
            return true;
        }
        $this->errorCode = $userStatus['code'];
        if ($userStatus['code'] === self::ERROR_LOCK_0) {
            $this->errorCounters = $userStatus['data'];
        }
        return false;
    }

    private function verifyPassword($user, $is_guar, $is_union) {
        $pwdUtil = PasswordUtils::getInstance();
        // @登录注册重构@20160902@chenjunhao@密码加盐改用password_hash,逐步替换以前的md5
        // 如果密码是之前的 md5, 密码正确时，更新密码为加盐后的
        if (strlen($user->password) === 32 &&
            $user->password == md5($this->password)
        ) {
            if (!$is_guar && !$is_union) { // 如果是主站用户
                $saltPassword = $pwdUtil->addSalt($this->password);
                $return = UserService::getInstance()->saveUser([
                    'user_id' => $user->user_id,
                    'password' => $saltPassword,
                ]);
                if ($return['code'] === 0) {
                    $user->password = $return['data']['password'];
                }
            }
            $this->errorCode = self::ERROR_NONE;
            $this->status = 0;
        } else if ($pwdUtil->passwordValidate($this->password, $user->password)) {
            $this->errorCode = self::ERROR_NONE;
            $this->status = 0;
        } else {
            $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            $this->status = self::ERROR_PWD;
        }
        return $user;
	}

    public function getStatus() {
        return $this->status;
    }

    /**
     * [getLoginLockInfo 登录注册重构@20160902@chenjunhao@登录时判定帐号是否是锁定状态]
     */
    public function getLoginLockInfo() {
        $UserClass = new UserClass();
        $user = $UserClass->checkUserValid($this->username);
        $key = 'lock_loginpwd_'.$user['user_id'];
        $errorRecordEncode = Yii::app()->rcache->get($key);
        return $this->verifyLockInfo($errorRecordEncode);
    }

    /**
     * [verifyLockInfo 登录注册重构@20160902@chenjunhao@登录时判定帐号是否是锁定状态]
     * @param  [type] $counter [redis 中的错误记录]
     * @return [object]
     */
    private function verifyLockInfo($counter) {
        if ($counter) {
            $errorRecord = json_decode($counter);
            $countErrorRecord = count($errorRecord);
            if ($countErrorRecord >= self::LOCK_LEVEL_2 &&
                (self::LOCK_LEVEL_2_TIME === -1 ||
                time() <= (
                    $errorRecord[self::LOCK_LEVEL_2 - 1] + self::LOCK_LEVEL_2_TIME
                ))
            ) {
                return [
                    'code' => self::ERROR_LOCK_2,
                    'info' => Yii::app()->c->apicodeconfig[self::ERROR_LOCK_2],
                ];
            } else if ($countErrorRecord === self::LOCK_LEVEL_1 &&
                (self::LOCK_LEVEL_1_TIME === -1 ||
                time() <= (
                    $errorRecord[self::LOCK_LEVEL_1 - 1] + self::LOCK_LEVEL_1_TIME
                ))
            ) {
                return [
                    'code' => self::ERROR_LOCK_1,
                    'info' => Yii::app()->c->apicodeconfig[self::ERROR_LOCK_1],
                ];
            }
        } else {
            $errorRecord = [];
        }
        return [
            'data' => $errorRecord,
            'code' => 0
        ];
    }

    /**
     * [shouldVerifyValicode 登录注册重构@20160902@chenjunhao
     * @登录时判定是否需要输入图形验证码以及验证图形验证码的正确性]
     * @param  [type] $valicode [用户输入的图形验证码]
     */
    public function shouldVerifyValicode($valicode) {
        $ipActionError = BlockCC::getInstance()->getNew('IpAction')->Get(['error']);
        if ($ipActionError['day'] < 100) {
            if (!Yii::app()->session->get('loginLockCounter')
                || Yii::app()->session->get('loginLockCounter') < 2) {
                $lockNum = $this->getLoginLockInfo();
                if ($lockNum['code'] === 0 && count($lockNum['data']) < 2) {
                    return [ 'code' => 0 ];
                }
            }
        }
        if (!$valicode) {
            return [
                'code' => 2055,
                'info' => '请填写验证码'
            ];
        }
        $captchaCheck  = new CaptchaCheck();
        $result = $captchaCheck->ValidCaptcha($valicode, true);//验证并销毁验证码
        if ($result['code'] !== 0) {
            if ($result['code'] == 2105) {
                $err = '图形验证码超时，请重新输入';
            } else if ($result['code'] == 2106) {
                $err = '图形验证码不正确，请重新输入';
            }
            unset(Yii::app()->session['valicode']);
            return [
                'code' => $result['code'],
                'info' => $err
            ];
        }
        return [ 'code' => 0 ];
    }

    /**
     * [verifyUserStatus 登录注册重构@20160902@chenjunhao
     * @验证用户状态是否可以登录]
     */
    protected function verifyUserStatus() {
        $user = Yii::app()->user->getState("_user");
        $key = 'lock_loginpwd_'.$user['user_id'];
        $errorRecordEncode = Yii::app()->rcache->get($key);
        $errorCounter = $this->verifyLockInfo($errorRecordEncode);
        if ($errorCounter['code'] !== 0) {
            return $errorCounter;
        }
        $errorRecord = $errorCounter['data'];
        if ($this->status === 0) {
            Yii::app()->rcache->del($key);
            Yii::app()->session['loginLockCounter'] = 0;
            return [ 'code' => 0 ];
        }
        $newErrorRecord = array_merge($errorRecord, [ time() ]);
        $value = json_encode($newErrorRecord);
        // cRedisCache bug, expire 0 时无法设置永不过期，先设为 20 年过期
        Yii::app()->rcache->set($key, $value, 20 * 365 * 86400);
        $loginLockCounterNum = (int)Yii::app()->session->get('loginLockCounter') + 1;
        Yii::app()->session['loginLockCounter'] = $loginLockCounterNum;
        $countNewErrorRecord = count($newErrorRecord);
        $errorCounter = $this->verifyLockInfo($value);
        if ($errorCounter['code'] !== 0) {
            return $errorCounter;
        }
        if ($countNewErrorRecord > self::LOCK_LEVEL_1) {
            $errorRemainNum = self::LOCK_LEVEL_2 - $countNewErrorRecord;
        } else {
            $errorRemainNum = self::LOCK_LEVEL_1 - $countNewErrorRecord;
        }
        return [
            'code' => self::ERROR_LOCK_0,
            'data' => $errorRemainNum,
        ];
    }

//    public function verifyPhoneAuthStatus($phone){
//		$return= [
//			'code'=>0,
//			'info'=>'',
//		];
//		$now = time();
//		$phoneAuthKey = $this->getRegLoginCacheKey($phone);
//		$authDate = Yii::app()->rcache->get($phoneAuthKey);
//		if($authDate){
//			if($authDate['num']>=self::LOCK_LEVEL_2){
//				$return['code'] = 7890;
//				$return['info'] = '该方式已被永久锁定，请使用其他方式操作';
//				return $return;
//			}
//			if($authDate['time']){
//				if($now < $authDate['time']){
//					$return['code'] = 4567;
//					$return['info'] = '账号锁定中，剩余'.gmstrftime('%H:%M:%S',($authDate['time']-$now)).'秒';
//					return $return;
//				}
//			}
//		}
//		return $return;
//	}
//
//	public function setPhoneAuthDate($phone){
//    	$times = self::LOCK_LEVEL_1;
//		$now = time();
//		$phoneAuthKey = $this->getRegLoginCacheKey($phone);
//		$authDate = Yii::app()->rcache->get($phoneAuthKey);
//		if($authDate){
//			if($authDate['num']>=self::LOCK_LEVEL_2){
//				return Yii::app()->rcache->set($phoneAuthKey,$authDate,86400*365);
//			} elseif ($authDate['num'] == self::LOCK_LEVEL_1-1){
//				$authDate['time'] = $now+3600*3;
//			}
//			if($authDate['num']>=self::LOCK_LEVEL_1){
//				$times = self::LOCK_LEVEL_2;
//			}
//		}
//		$authDate['num']+=1;
//		Yii::app()->rcache->set($phoneAuthKey,$authDate,86400);
//		$surplus = $times-$authDate['num'];
//		$return['info'] = '剩余'.$surplus.'次';
//		$return['code'] = 1234;
//		$return['data'] = $surplus;
//		return $return;
//	}
//
//
//	public function delPhoneAuthDate($phone){
//		$phoneAuthKey = $this->getRegLoginCacheKey($phone);
//		$res = Yii::app()->rcache->del($phoneAuthKey);
//		return true;
//	}
//
//
//	private function getRegLoginCacheKey($phone){
//		return 'reg_login_auth_key_'.$phone;
//	}

}
