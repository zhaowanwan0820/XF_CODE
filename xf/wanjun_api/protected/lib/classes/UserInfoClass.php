<?php
/*
 * 用户信息类
 */

class UserInfoClass
{

    /**
     * 获取用户信息
     **/
    public function getUser($user_id)
    {
        $UserInfoModel = new ItzPrivateUserinfo();
        $criteria = new CDbCriteria;
        $attributes = array(
            "user_id" => $user_id,
        );
        $UserInfoResult = $UserInfoModel->findByAttributes($attributes, $criteria);

        return $UserInfoResult;
    }

}