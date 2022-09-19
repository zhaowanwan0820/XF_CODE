<?php 
class UserUtils{


    /*
    * 设置登录cookie
    */
    public function setUserCookie($uid,$uc_id=-1){
        $ctime = $this->getCtime();        
        if($uc_id < 0)
        {
            setcookie( FunctionUtil::Key2Url("user_id_https",ConfUtil::get('Cookie.secret-key')), FunctionUtil::authcode($uid.",".time(),"ENCODE"), $ctime,'/', 'xxx.com', true );
        }
        else
        {
              setcookie( FunctionUtil::Key2Url("user_id_https",ConfUtil::get('Cookie.secret-key')), FunctionUtil::authcode($uid.",".time().",".$uc_id,"ENCODE"), $ctime, '/', 'xxx.com', true );
        }
    }
    private function getCtime(){
         if ( isset($_POST['cookietime']) && $_POST['cookietime'] > 0 ) 
         {
                return time() + $_POST['cookietime'] * 60;
         }else{
                return time() + 60 * 60;
         }
    }

    /*检验密码是否符合规则*/
    public function ValidPassword()
    {
        if (FunctionUtil::IsPwd($_POST["password"])) 
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    /*
    *验证手机号是否合法以及是否被注册
    *@param string $phone手机号
    *@return array array("code"=>0)
    *当code为0时可用当为2018时手机号被注册当为2012时手机号不和法
    */
    public function  ValidMobile($phone)
    {
        
        $result = array();
        if(FunctionUtil::IsMobile($phone))
        {
            if(UserService::getInstance()->PhoneCheck($phone))
            {
                return $result['code'] = 2018;
            }
            else
            {
                return $result['code'] = 0;
            }
        }
        else
        {
            $result['code'] = 2012;
        }
        return $result;
    }
    /*
    *生成10位以1开头的随即用户名
    *return int 随即用户名
    */
    public function getUserName()
    {
        return $this->generateUserName();   
    }
    private function generateUserName()
    {
        $user_name = mt_rand(1000000000,9999999999)."";
        $userService = new UserService();
        if ( UserService::getInstance()->NameCheck( $user_name)) 
        {
            return $this->generateUserName();
        }
        else
        {
            return $user_name;
        }
    }
}
