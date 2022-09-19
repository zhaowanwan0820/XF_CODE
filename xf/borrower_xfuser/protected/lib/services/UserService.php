<?php
/**
 * @file UserService.php
 * @date 2016/1/12
 * 
 **/

class UserService extends  ItzInstanceService {

    private $_userClass ;
    public function __construct(  )
    {
        $this->_userClass = new UserClass( );
        parent::__construct();
    }

    /**
     * [createAccount 创建资金帐户]
     * @param  [array] $params
     * @return [boolean]
     */
    public function createAccount($params)
    {

        //创建资金账号
        $account_info = array(
            "user_id" => $params['user_id'],
        );

        $accountInfo = BaseCrudService::getInstance()->add("Account", $account_info);
        if ($accountInfo == false) { //写账户失败
            Yii::log("FUNC: UserService->createAccount, Error When Reg Insert To account table: " . print_r($account_info, true), "error", 'UserServersError');
            return false;
        }
        return true;
    }

    function UpdateYimaUserInfo($data = array()){
        //yima user数据量很少，不和user表一一对应，直接更新会报错
        $result = BaseCrudService::getInstance()->get("YimaUserInfo","",0,1,"",array(
            "user_id" => $data["user_id"]
        ));
        if(!empty($result)){
            return BaseCrudService::getInstance()->update("YimaUserInfo",array(
                "addtime" => time(), "user_id" => $data["user_id"]
            ),"user_id");
        }
    }
    function UpdateLtCpaInfo($data = array()){
        //yima user数据量很少，不和user表一一对应，直接更新会报错
        $result = BaseCrudService::getInstance()->get("LinktechCpa","",0,1,"",array(
            "user_id" => $data["user_id"]
        ));
        if(!empty($result)){
            return BaseCrudService::getInstance()->update("LinktechCpa",array(
                "addtime" => time(), "user_id" => $data["user_id"]
            ),"user_id");
        }
    }

    /**
     * 生成随机的6位数字加字母密码
     * @return string
     */
    public function passwordRandStr(){

        $str = range('a', 'z');
        // 去除大写的O，以防止与0混淆 
        unset($str[array_search('O', $str)]);
        $arr = array_merge(range(0, 9), $str);
        shuffle($arr);
        $invitecode = '';
        $arr_len = count($arr);
        for ($i = 0; $i < 6; $i++) {
            $rand = mt_rand(0, $arr_len - 1);
            $invitecode .= $arr[$rand];
        }
        return $invitecode ;
    }
    /**
     * [addUser 新增用户表]
     * @param [array] $params
     */
    public function addUser( $params ) {
        $return = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        if(empty($params)){
            $return['code']=1;
            return $return;
        }
        $UserModel = new User();
        foreach ( $params as $key => $value ) {
            $UserModel->$key = $value;
        }
        if ( $UserModel->save() == false ) {
            $return['code'] = 1;
            $return['info'] = "insert user error: ".print_r($UserModel->getErrors(), true);
            Yii:log( $return['info'] ,"error" ,'UserServersError');
        } else {
            $return['data'] = $UserModel->getAttributes();
        }
        return $return;
    }

    /**
     * [addUser 新增用户详情表]
     * @param [array] $params
     */
    public function addUserInfo( $params ) {
        $return = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        if(empty($params)){
            $return['code']=1;
            return $return;
        }
        $UserinfoModel = new Userinfo();
        foreach ( $params as $key => $value ) {
            $UserinfoModel->$key = $value;
        }

        if ( $UserinfoModel->save() == false ) {
            $return['code'] = 1;
            $return['info'] = "insert userinfo error: ".print_r($UserinfoModel->getErrors(), true);
            Yii:log( $return['info'] ,"error" ,'UserServersError');
        } else {
            $return['data'] = $UserinfoModel->getAttributes();
        }
        return $return;
    }



    /**
     * 根据指定字段获取用户id
     * @param string $field
     * @param string $value
     * @return array
     */
    public function getUserIds($field, $value){
        $returnResult = array(
            'code'=>'','info'=>'','data'=>array()
        );
        if ($field == '' || !isset($field) || $value == '' || !isset($value)) {
            $returnResult['code'] = 4000;
            $returnResult['info'] = '必传值不能为空！';
            return $returnResult;
        }
        $userList = User::model()->findAll($field . '=:value', array(':value' => $value));
        if (count($userList) == 0) {
            $returnResult['code'] = 4001;
            $returnResult['info'] = '没有符合条件的用户！';
            return $returnResult;
        }
        $uid = array();
        foreach ($userList as $key => $value) {
            $uid[] = $value->user_id;
        }
        $returnResult['code'] = 0;
        $returnResult['data'] = $uid;
        $returnResult['info'] = '获取用户ID成功！';
        return $returnResult;
    }
    /*
     * 判断手机号是否存在
     * */
    function PhoneCheck($phone){
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = "phone = :phone and phone_status = 1";
        $CDbCriteria->params[":phone"] = $phone;
        $result = $this->_userClass->getUserByAttr(array(),$CDbCriteria);
        if (empty($result)) return false;
        else return true;
    }
    /**
     * 根据后台用户id后台获取用户信息
     * @param int $user_id
     * @return array
     */
    public function getItzUserInfoByUid($user_id=0){
    	$returnResult = array(
    			'code'=>'','info'=>'','data'=>array()
    	);
    	$user_id = intval($user_id);
    	if (empty($user_id)) {
    		$returnResult['code'] = 4000;
    		$returnResult['info'] = '必传值不能为空！';
    		return $returnResult;
    	}
    	
    	$userList = ItzUser::model()->find(' id=:value', array(':value' => $user_id));
    	 
    	if (count($userList) == 0) {
    		$returnResult['code'] = 4001;
    		$returnResult['info'] = '没有符合条件的用户！';
    		return $returnResult;
    	}
    	$info = array();
    	$info['user_id'] = $userList->id;
    	$info['username'] = $userList->username;
    	$info['email'] = $userList->email;
    	$info['phone'] = $userList->phone;
    	
    	$returnResult['code'] = 0;
    	$returnResult['data'] = $info;
    	$returnResult['info'] = '获取用户信息成功！';
    	return $returnResult;
    }

    /**
     * 判断用户名是否被注册
     */
    function NameCheck($username){
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = "username = :username";
        $CDbCriteria->params[":username"] = $username;
        $result = $this->_userClass->getUserByAttr(array(),$CDbCriteria);
        if (empty($result)) return false;
        else return true;
    }


    /**
     * 验证手机号
     * @param $mobile_phone
     * @return bool
     */
    function is_mobile_phone ($mobile_phone){
        $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
        if(preg_match($chars, $mobile_phone)) {
            return true;
        }
        return false;
    }
}


