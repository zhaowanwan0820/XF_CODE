<?php
/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
 /*
 * 用户登录验证
 */

class DUserIdentity extends CUserIdentity {
    protected $status;
    private $_id;
        
    const ERROR_NO = 0; //验证成功
    const ERROR_USER = 1; //用户不存在
    const ERROR_PWD = 2; //密码不正确
    const ERROR_USERTYPE = 3; // 用户类不匹配
	//验证登录
	public function authenticate() {
        $user = Yii::app()->db->createCommand()
            ->select('id,username, password,addtime,phone,email,status,realname,user_type')
            ->from('itz_user')
            ->where('username=:username', array(':username'=>$this->username))
            ->queryRow();
        //用户名不存在
        if(empty($user)){
            $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            $this->status = self::ERROR_USER;
            return false;
        }
        //密码错误
        if(md5(md5(trim($this->password))) !== $user['password']){
            $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            $this->status = self::ERROR_PWD;
            return false;
        }
        //用户类型错误
        if($user['status'] != 1){
            $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            $this->status = self::ERROR_USERTYPE;
            return false;
        }
        $this->errorCode = self::ERROR_NONE;
        $this->status = 0;
        $this->_id = $user['id']; //id仍然存旧的
        unset($user['password']);
        Yii::app()->user->setState('_user',$user);//新的用户信息
        return true;
	}

    public function getStatus() {
        return $this->status;
    }
    
    //必须返回id，不能返回usrName
    public function getId()
    {
        return $this->_id;
    }
}
