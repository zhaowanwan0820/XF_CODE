<?php

namespace core\service\userProfile;
use libs\utils\Logger;
use core\dao\UserProfileModel;
use core\dao\UserModel;
use core\service\userProfile\UserProfileConfig;
use core\service\CouponBindService;


class UserProfile{

    private $startUserId;
    private $endUserId;

    //获取指标对应的类
    private function getIndexClass($key){
        $indexConf = UserProfileConfig::getFlushDataConf($key);
        $classes = array();
        foreach($indexConf as $key=>$className){
            $classFile = dirname(__FILE__).'/'.$className.".php";
            if(file_exists($classFile)) {
                require_once($classFile);
                $className = "\\core\\service\\userProfile\\".$className;
                $classes[$key] = new $className();
            }
        }
        return $classes;
    }

    /**
     *  全量刷新数据的入口
     */
    public function fullDataFlush($startUserId,$endUserId,$key='all'){
        $endUserId = empty($endUserId)?10000:$endUserId;
        $blackList = UserProfileConfig::getBlackList();
        $err = array();
        for( $userId=$startUserId; $userId<=$endUserId; $userId++ ){
            if(in_array($userId,$blackList)){
                $this->writeLog(sprintf("userId: %s in blackList ,do not need data flush ",$userId));
                continue;
            }
            $curData = array();
            $saveData = array();
            // 判断用户是否存在的
            $userInfo = $this->userBaseInfo($userId);
            if(empty($userInfo)){
                // 如过用户不存在，直接认为任务成功
                continue;
            }
            if( $this->hadInvested($userId)==0 ){
                $this->writeLog(sprintf("userId: %s did't invest ",$userId));
                $cps = new CouponBindService();
                $referUserInfo = $cps->getByUserId($userId);
                $saveData['cur_refere_user_id'] = $referUserInfo['refer_user_id'];
            }else{
                $classes = $this->getIndexClass($key);
                $saveData = $this->getData($classes,$userId,$curData);
            }
            $saveData['register_time'] = $userInfo['create_time'];
            $ret = $this->saveToDB($userId,$saveData);
            if($ret == false){
                $err[] = $userId;
            }
        }
        return $err;
    }


    public function sigleDataFlush($userId,$key = 'all'){
        $ret = $this->fullDataFlush($userId,$userId,$key);
        if(!empty($ret)){
            return false;
        }else{
            return true;
        }
    }
    /**
     *  获取各个profile 的属性信息
     */
    private function getData($classes,$userId,$curData){
        $data = array();
        foreach($classes as $k=>$v){
            $data = $v->process($userId);
            $curData = array_merge($curData,$data);
        }
        return $curData;
    }

    // 等着被继承
    public function process($userId){
        return false;
    }

    protected function userDataExist($userId){
        $user = UserProfileModel::instance()->getOneByUserId($userId);
        if(!empty($user)) return true;
        else return false;
    }

    protected function hadInvested($userId){
        $count = \core\dao\DealLoadModel::instance()->countByUserId($userId);
        if(!empty($count)){
           return $count;
        }
        return 0;
    }

    protected function userBaseInfo($userId){
        $info = UserModel::instance()->findBy("`id`='$userId' AND `is_delete`='0'","create_time");
        if(!empty($info)){
            $ret['create_time'] = $info['create_time'] + 28800;
            return $ret;
        }else{
            return array();
        }
    }

    private function saveToDB($userId,$data){
        try{
            $upm = new UserProfileModel();
            $exist = $this->userDataExist($userId);
            if(!$exist){
                $ret = $upm->addNewRecord($userId,$data);
                if($ret){
                    $this->writeLog(sprintf("userId: %s profile gen-success | %s ",$userId,json_encode($data)));
                    return true;
                }else{
                    $this->writeLog(sprintf("userId: %s profile gen-failed | %s ",$userId,json_encode($data)));
                    return false;
                }
            }else{
                //更新
                if(!empty($data)){
                    $ret = $upm->updateRecord($userId,$data);
                    // 没有抛出异常，就证明更新成功
                    $this->writeLog(sprintf("userId: %s profile update-success | %s ",$userId,json_encode($data)));
                    return true;
                }
                return true;
            }
        }catch(\Exception $e){
            $this->writeLog(sprintf("userId: %s profile update-failed | %s ",$userId,json_encode($data)));
            return false;
        }
    }

    public function writeLog( $str ){
        $str = sprintf("[%s]: %s \n",date("Y-m-d H:i:s"),$str);
        Logger::wLog($str, Logger::INFO, Logger::FILE, APP_ROOT_PATH . 'log/logger/user_profile_' . date('Ymd') . '.log');
    }

}
