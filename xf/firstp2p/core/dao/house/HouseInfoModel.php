<?php
/**
 * HouseInfo
 * User: sunxuefeng
 * Date: 2017/9/28 0028
 * Time: 15:40
 */

namespace core\dao\house;

use core\dao\BaseModel;

class HouseInfoModel extends BaseModel
{
    /*
     * add user house information
     */
    public function addUserHouse($houseInfo)
    {
        foreach ($houseInfo as $key => $value) {
            if ($houseInfo[$key] !== NULL && $houseInfo[$key] !== '') {
                $this->$key = $this->escape($houseInfo[$key]);
            }
        }
        $this->create_time = time();
        $this->update_time = $this->create_time;
        $result = $this->insert();
        return $result ? $this->db->insert_id() : false;
    }
    /*
     * get house list by userId
     * default return value is array
     */
    public function getHouseListByUserId($userId)
    {
        $condition = 'user_id = '.intval($userId);
        $houseList = $this->findAll($condition, true);

        return $houseList;
    }

    // get houseInfo by house id
    public function getHouseById($id) {
        $id = intval($id);
        if ($id < 1) {
            return false;
        }
        $param = array(':id' => $id);
        $condition = " `id` = ':id'";
        $house = $this->findBy($condition, '*', $param);
        return !empty($house) ? $house : false;
    }

    public function saveHouse($houseInfo, $userId)
    {
        $houseInfo['update_time'] = time();
        $condition = "user_id = $userId";

        $result = $this->updateBy($houseInfo, $condition);
        return $result;
    }

    public function getHouseByOne($data)
    {
        foreach ($data as $key => $item) {
            $condition = $key.' = '.$item;
        }
        $result = $this->findBy($condition);
        return !empty($result) ? $result->getRow() : false;
    }
}
