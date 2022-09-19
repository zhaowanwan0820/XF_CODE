<?php

/**
 * UserPassportService class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 * */

namespace core\service;

use core\dao\UserPassportModel;

/**
 * Class UserPassport
 * @package core\service
 */
class UserPassportService extends BaseService {

    /**
     * undocumented function
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     * */
    public function getPassport($user_id) {
        return UserPassportModel::instance()->find($user_id);
    }

    /**
     * 更新
     * @param type $id
     * @param type $uid
     * return bool
     */
    public function updateByIdAndUid($data, $id, $uid) {
        return UserPassportModel::instance()->updateByIdAndUid($data, $id, $uid);
    }

    /**
     * 插入新记录
     * @param type $data
     * @return type
     */
    public function addInfo($data) {
        $userPassport = UserPassportModel::instance();
        $userPassport->setRow($data);
        return $userPassport->insert();
    }

    /**
     * 获得passport用户信息
     * @param type $userId
     * @return type
     */
    public function getPassportInfo($userId) {
        return UserPassportModel::instance()->getPassportInfo($userId);
    }


    /**
     * 检查用户护照信息是否存在
     * @param string $passportNo 护照号码
     * @return bool
     */
    public function isPassportExists($passportNo)
    {
        return UserPassportModel::instance()->isPassportExists($passportNo);
    }

    /**
     * 检查护照表中的身份证号是否存在状态不为2（审核失败的用户）
     * @param string $idNo 通行证对应的身份证号码
     * @return bool
     */
    public function isPassportIdnoExists($idNo)
    {
        return UserPassportModel::instance()->isPassportIdnoExists($idNo);
    }
}
