<?php
/**
 * UserCompanyModel.php
 *
 * @date 2014-03-18
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

/**
 * 用户公司信息
 *
 * Class UserCompanyModel
 * @package core\dao
 */
class UserCompanyModel extends BaseModel {

    /**
     * 根据用户ID获取用户公司信息
     *
     * @param $user_id 用户ID
     * @return bool|\libs\db\Model
     */
    public function findByUserId($user_id, $fields = '*') {
        $user_id = intval($user_id);
        if (empty($user_id)) {
            return false;
        }

        $condition = "is_effect = 1 and is_delete = 0 and user_id = '%d'";
        $condition = sprintf($condition, $this->escape($user_id));
        return $this->findByViaSlave($condition, $fields);
    }

}
