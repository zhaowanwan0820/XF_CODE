<?php
/**
 * House User Information
 * User: sunxuefeng@ucfgroup.com
 * Date: 2017/10/14
 */

namespace core\dao\house;

use core\dao\BaseModel;

class HouseUserModel extends BaseModel
{
    // 通过user id 查询用户信息
    public function getUserByUserId($userId)
    {
        $condition = ' user_id = '.intval($userId);
        return $this->findBy($condition);
    }

    // 添加用户
    public function addUser($user)
    {
        foreach ($user as $key => $value) {
            if ($user[$key] !== NULL && $user[$key] !== '') {
                $this->$key = $this->escape($user[$key]);
            }
        }
        return $this->insert();
    }
}
