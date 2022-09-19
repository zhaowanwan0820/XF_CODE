<?php
/**
 * UserPassportModel class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace core\dao;

/**
 * 用户信息
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class UserPassportModel extends BaseModel
{

    /**
     * 更新
     * @param type $id
     * @param type $uid
     * return bool
     */
    public function updateByIdAndUid($data,$id,$uid)
    {
        $condition = sprintf("`id` = '%d' and uid = '%d'",$this->escape($id),$this->escape($uid));
        return $this->updateAll($data,$condition);
    }

    /**
     * 获得用户护照信息
     * @param type $id
     * @param type $uid
     * return bool
     */
    public function getPassportInfo($userId)
    {
        $condition = sprintf("`uid` = '%d'",$this->escape($userId));
        return $this->findBy($condition);
    }


    /**
     * 判断用户护照信息是否存在
     * @param string $passportNo
     * @return bool
     */
    public function isPassportExists($passportNo)
    {
        $condition = sprintf("`passportid` = '%s' AND status != 2 ", $this->escape($passportNo));
        $result = $this->count($condition);
        return $result > 0;
    }


    /**
     * 检查护照表中的身份证号是否存在状态不为2（审核失败）的用户信息
     * @param string $idNo 通行证对应的身份证号码
     * @return bool
     */
    public function isPassportIdnoExists($idNo)
    {
        $condition = sprintf(" idno = '%s' AND `status` != 2 ", $this->escape($idNo));
        $result = $this->count($condition);
        return $result > 0;
    }
}
