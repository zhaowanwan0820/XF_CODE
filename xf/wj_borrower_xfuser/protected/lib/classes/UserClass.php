<?php
/*
 * 用户信息类
 */

class UserClass  {
        
    /**
     * 获取用户信息
     **/
    public function getUser($user_id){
        $UserModel = new User();
        $criteria = new CDbCriteria; 
        $attributes = array(
          "user_id"    =>   $user_id,   
        );
        $UserResult =$UserModel->findByAttributes($attributes,$criteria);
        return $UserResult;
     }
     public function getAdminUser($user_id) {
        $UserModel = new \iauth\models\User();
        $criteria = new CDbCriteria; 
        $attributes = array(
          "id"    =>   $user_id,   
        );
        $UserResult =$UserModel->findByAttributes($attributes,$criteria);
        return $UserResult;
     }

     /**
     * 根据ucenter_uid获取用户信息
     **/
    public function getUserByUid($uid){
        $UserModel = new User();
        $criteria = new CDbCriteria; 
        $attributes = array(
          "ucenter_uid"    =>   $uid,   
        );
        $UserResult =$UserModel->findByAttributes($attributes,$criteria);
        return $UserResult;
     }
     
    /**
     * 获取用户信息
     **/
    public function getUserByAttr($attributes= array(),$CDbCriteria=null){
        $UserModel = new User();
        $criteria = new CDbCriteria; 
        $UserResult =$UserModel->findByAttributes($attributes,$CDbCriteria);
        return $UserResult;
     }

     /**
     * 通过username获取用户信息
     **/
    public function getByUserName($username){
        $UserModel = new User();
        $criteria = new CDbCriteria;
        $attributes = array(
          "username"    =>   $username,
        );
        $UserResult =$UserModel->findByAttributes($attributes,$criteria);
        return $UserResult;
     }

    /**
     * 判断邮箱，手机号，用户名是否存在
     */
     public function checkUserValid($value){
        $criteria = new CDbCriteria;
        $criteria->condition = " (username = :username or (email = :email and email_status = 1) or (phone = :phone and phone_status = 1 ))";
        $criteria->params[':username'] = $value;
        $criteria->params[':email'] = $value;
        $criteria->params[':phone'] = $value;
        $users = User::model()->findAll($criteria);
        foreach($users as $userInfo){
          // 修改1：判断status=0是为了不让 ‘非正常’状态的用户登录，
          //        当时的case是：注册一个用户A，username是x，
          //            将用户x注销掉，再用username x注册一个用户B，
          //            此时若不判断status=0而是在之后的代码进行判断的话那么这个新注册的用户B是登录不上的
          // 修改2：此处于 2015、4、15日 ‘应’ 增加判断条件 status=2（冻结用户） 但是由于涉及到找回密码的代码，所以取消增加判断，@chenjunhao
            if($userInfo->status == '0'){
                return $userInfo;
            }
        }
        return false;
     }
    /**
     * 获取UserInfo表的数据
     */
    public function getUserInfo($user_id){
        $UserInfoModer = new Userinfo();
        $criteria = new CDbCriteria;
        $criteria->condition = "user_id ='".$user_id."'";
        $UserInfoResult = $UserInfoModer->find($criteria);
        return $UserInfoResult;
    }

    public function getListByUserIds($userids) {
        $UserModel = new User();
        $criteria = new CDbCriteria;
        $attributes = array(
          "user_id"    =>   $userids,
        );
        $userResult = $UserModel->findAllByAttributes($attributes, array( 'index' => 'user_id' ));
        return $userResult;
    }

    /**
      * 更新登录密码
      *
      *
      */
    public function updatePassword($user_id, $password) {
        if(empty($user_id) || empty($password)) {
            return false;
        }
        return User::model()->updateByPk($user_id, array( 'password' => self::encryptPassword($password) ));
    }

    /**
      * 更新支付密码
      *
      *
      */
    public function updatePayPassword($user_id, $password) {
        if(empty($user_id) || empty($password)) {
            return false;
        }
        return User::model()->updateByPk($user_id, array( 'paypassword' => self::encryptPassword($password) ));
    }

    public function encryptPassword($input) {
        return md5($input);
    }
    
    public function comparePassword($inputpassword, $userpassword) {
        if(self::encryptPassword($inputpassword) != $userpassword ) {
            return false;
        } else {
            return true;
        }
    }

}
