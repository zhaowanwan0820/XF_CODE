<?php
/**
 * 通用用户授权协议
 *
 * @date 2018-07-14
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

class UserAgreementModel extends BaseModel {

    /**
     * 根据用户ID及业务名称获取协议签署记录
     *
     * @param user_id 用户ID
     * @param type 自定义业务名称
     * @return boolean
     */
    public function getByUserId($user_id, $type) {
        $condition = sprintf("`user_id` = '%d' and `type` = '%s'", intval($user_id),  $this->escape($type));
        return $this->findBy($condition);
    }

}
