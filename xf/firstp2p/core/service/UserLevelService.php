<?php
/**
 * 用户等级
 * @date 2014-03-20
 * @author caolong <caolong@ucfgroup.com>
 */

namespace core\service;

use core\dao\UserLevelModel;
/**
 * Class UserService
 * @package core\service
 */
class UserLevelService extends BaseService {

    /**
     * 获取用户信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getLevelInfo($id) {
        if (empty($id)) {
            return false;
        }
        return UserLevelModel::instance()->find($id);
    }
   
}
