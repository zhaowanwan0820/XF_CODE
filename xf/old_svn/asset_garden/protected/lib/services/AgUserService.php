<?php
/**
 * 资产花园用户
 */
class AgUserService extends ItzInstanceService
{

    public function getUserInfo($user_id){
        $result = ['data'=>[],'code'=>100,'info'=>'error'];
        if(!$user_id){
            $data['code'] = 1014;
            return $result;
        }
        $AgUserModel = AgUser::model()->findByPk($user_id);
        if(empty($AgUserModel)){
            $data['code'] = 1027;
            return $result;
        }
        $result['code'] = 0;
        $result['data']['is_set_pay_password'] = $AgUserModel->pay_password?1:0;
        $result['data']['real_name'] = $AgUserModel->real_name?$AgUserModel->real_name:$AgUserModel->name;
        $result['data']['phone'] = $AgUserModel->phone?substr_replace($AgUserModel->phone, '****', 3, 4):'';
        $result['data']['reg_time'] = $AgUserModel->reg_time;
        return $result;

    }
    /**
     * @return string
     */
    public function getNewUserName(){
        $user_name = strval(mt_rand(1000000000,9999999999));
        if(AgUserService::getInstance()->nameCheck($user_name)) {
            return $this->getNewUserName();
        }
        return $user_name;
    }

    /**
     * 校验用户名是否存在
     * @param $user_name
     * @return bool
     */
    public function nameCheck($user_name){
        $AgUserModel = AgUser::model()->findByAttributes(['name'=>$user_name]);
        if($AgUserModel){
            return $AgUserModel;
        }
        return false;
    }

    /**
     * 校验手机号是否存在
     * @param $phone
     * @return bool
     */
    public function phoneCheck($phone){

        $AgUserModel = AgUser::model()->findByAttributes(['phone'=>$phone]);
        if($AgUserModel){
            return $AgUserModel;
        }
        return false;
    }


    /**
     * 创建用户
     * @param $data
     * @return array
     */
    public function createUser($data){
        $result = ['data'=>[],'code'=>100,'info'=>'error'];
        try{
            Yii::app()->agdb->beginTransaction();

            $AgUserModel = new AgUser();
            foreach ($data as $key => $datum) {
                $AgUserModel->$key = $datum;
            }
            if($AgUserModel->save()==false){
                Yii::app()->agdb->rollback();
                $result['info'] = '保存用户信息失败';
                return $result;
            }
            $AgUserAccountModel = new AgUserAccount();
            $AgUserAccountModel->user_id = $AgUserModel->id;
            if($AgUserAccountModel->save()==false){
                Yii::app()->agdb->rollback();
                $result['info'] = '创建用户账户信息失败';
                return $result;
            }
            Yii::app()->agdb->commit();
            $result['data'] = $AgUserModel;
            $result['code'] = 0;
            return $result;

        }catch(Exception $ee){
            Yii::app()->agdb->rollback();
            return $result;
        }

    }

    /**
     * 获取用户所绑定的债权平台列表
     * @param $data
     * @return array
     */
    public function getUserBindPlantForm($data){
        $result = ['data'=>[],'code'=>100,'info'=>'error'];

        $platFormCondition = [];
        $whereArr = [];
        if(!isset($data['user_id']) || empty($data['user_id'])){
            $data['code'] = 1014;
            return $result;
        }
        $whereArr[] = "user_id = {$data['user_id']}";

        if(isset($data['authorization_status']) && in_array($data['authorization_status'],[0,1])){
            $whereArr[] = "authorization_status = {$data['authorization_status']}";
        }
        if(isset($data['agree_status']) && in_array($data['agree_status'],[0,1])){
            $whereArr[] = "agree_status = {$data['agree_status']}";
        }
        if(isset($data['platform_name'])){
            $platFormCondition['platform_name'] = $data['platform_name'];
        }
        $platformList = AgPlatformService::getInstance()->getPlatformListFromCache($platFormCondition);
        if(!$platformList){
            $data['code'] = 1015;
            return $result;
        }
        foreach ($platformList as $item) {
            $platformIdName[$item['id']] = $item['name'];
        }
        $whereArr[] = "platform_id in (".implode(',',array_keys($platformIdName)).")";

        $usePlatformList = Yii::app()->agdb->createCommand( "SELECT user_id,platform_id,platform_user_id,authorization_status,agree_status ,confirm_status FROM ag_user_platform WHERE ".implode(' AND ',$whereArr))->queryAll();
        if(empty($usePlatformList)){
            $result['code'] = 0;
            return $result;
        }
        foreach ($usePlatformList as &$item) {
            $item['platform_name'] = $platformIdName[$item['platform_id']];
        }
        $result['code'] = 0;
        $result['data'] = $usePlatformList;
        return $result;

    }

    /**
     * 用户绑定平台相关操作
     * @param $data
     * @return array
     */
    public function bindPlatform($data){
        $result = ['data'=>[],'code'=>100,'info'=>'error'];
        if(empty($data['platform_id'])){
            $data['code'] = 1010;
            return $result;
        }
        if(empty($data['user_id'])){
            $data['code'] = 1014;
            return $result;
        }

        $platformList = AgPlatformService::getInstance()->getPlatformListFromCache();
        if(empty($platformList)){
            $data['code'] = 1015;
            return $result;
        }
        if(!in_array($data['platform_id'],ArrayUtil::array_column($platformList,'id'))){
            $data['code'] = 1016;
            return $result;
        }

        $agPlatformUserModel = AgPlatformUser::model()->findByAttributes(['user_id'=>$data['user_id'],'platform_id'=>$data['platform_id']]);
        if(empty($agPlatformUserModel)){
            $agPlatformUserModel = new AgPlatformUser();
            $agPlatformUserModel->user_id = $data['user_id'];
            $agPlatformUserModel->platform_id = $data['platform_id'];
            $res = self::getDebtPlatFormUserInfo($data);
            if($res['code']){
                $result = $res;
                return $result;
            }
            $agPlatformUserModel->platform_user_id = $res['data']['user_id'];

        }
        
        if(isset($data['authorization_status'])){
            if(!empty($agPlatformUserModel->authorization_status)){
                $result['code'] = 1031;
                return $result;
            }
            $agPlatformUserModel->authorization_status = 1;
            $agPlatformUserModel->authorization_time = time();
        }

        if(isset($data['agree_status'])){
            if(!empty($agPlatformUserModel->agree_status)){
                $result['code'] = 1032;
                return $result;
            }
            $agPlatformUserModel->agree_status = 1;
            $agPlatformUserModel->agree_time = time();
        }

        if(isset($data['confirm_status']) && empty($agPlatformUserModel->confirm_status)){
            $agPlatformUserModel->confirm_status = 1;
        }

        if(isset($data['real_name']) && !empty($data['real_name'])){
            $agPlatformUserModel->real_name = $data['real_name'];
        }
        if(isset($data['id_no']) && !empty($data['id_no'])){
            $agPlatformUserModel->id_no = $data['id_no'];
        }
        if(isset($data['bank_card']) && !empty($data['bank_card'])){
            $agPlatformUserModel->bank_card = $data['bank_card'];
        }
        if($agPlatformUserModel->save()===false){
            $result['code'] = 1020;
            return $result;
        }
        $result['data'] = $agPlatformUserModel;
        $result['code'] = 0;
        return $result;

    }

    /**
     * 获取指定债权平台的用户信息
     * @param $data
     * @return array
     */
    public function getDebtPlatFormUserInfo($data){
        $result = ['data'=>[],'code'=>100,'info'=>'error'];
        if(empty($data['platform_id'])){
            $data['code'] = 1010;
            return $result;
        }
        switch ($data['platform_id']){
            case Yii::app()->c->itouzi['itouzi']['platform_id']:
                $result = self::getItzUserInfo($data);
                break;
            default;
                break;
        }
        return $result;
    }

    /**
     * 获取爱投资平台用户信息
     * @param $data
     * @return array
     */
    private function getItzUserInfo($data){
        $result = ['data'=>[],'code'=>100,'info'=>'error'];
        if(empty($data['id_no'])){
            $result['code'] = 1011;
            return $result;
        }
        if(empty($data['real_name'])){
            $result['code'] = 1012;
            return $result;
        }
        if(empty($data['bank_card'])){
            $result['code'] = 1013;
            return $result;
        }
        $itzUserInfo = Yii::app()->yiidb->createCommand('select user_id ,realname,xw_open from dw_user where card_id=:id_no ')->bindValue(':id_no',$data['id_no'])->queryRow();
        //爱投资特殊账号
        if($itzUserInfo['user_id'] == 161272){
            $result['code'] = 0;
            return $result;
        }
        if(empty($itzUserInfo)){
            $result['code'] = 1028;
            return $result;
        }
        if($itzUserInfo['xw_open']!=2){
            $result['code'] = 1033;
            return $result;
        }
        if($itzUserInfo['realname'] != $data['real_name']){
            $result['code'] = 1029;
            return $result;
        }
        $itzUserBankCard = Yii::app()->yiidb->createCommand('select user_id ,card_number from itz_safe_card where user_id=:user_id and card_number=:bank_card and status = 2')->bindValues([':user_id'=>$itzUserInfo['user_id'],'bank_card'=>$data['bank_card']])->queryRow();
        if(empty($itzUserBankCard)){
            $result['code'] = 1030;
            return $result;
        }
        $result['code'] = 0;
        $result['data'] = $itzUserInfo;
        return $result;

    }


}