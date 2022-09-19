<?php
/**
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018/11/7
 * Time: 16:07
 */
namespace core\service\ifapush;

use libs\utils\Curl;
use core\service\user\UserService;

abstract class PushBase
{
    public $dbModel;

    abstract function collectData();

    public function saveData(){
        if(!$this->dbModel){
            throw new \Exception('dbModel uninitialized!');
        }

        $data = $this->collectData();
        $data['create_time'] = time();
        $data['update_time'] = time();
        if(empty($data)){
            throw new \Exception('collect data empty!');
        }

        return $this->dbModel->saveData($data);
    }

    //获取用userIDcard
    protected function getUserIdcard($userId){
        $userInfo = UserService::getUserById($userId,'user_type,country_code,real_name,id_type,mobile,email,create_time,idno');
        $userIdcard = $userInfo['idno'];
        if($userInfo['user_type'] == 1){
            $userCompInfo = UserService::getEnterpriseInfo($userId);
            $userIdcard = $userCompInfo['credentials_no'];
        }
        return $userIdcard;
    }
}