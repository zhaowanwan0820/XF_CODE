<?php
/**
 * Created by PhpStorm.
 * User: wangjiantong
 * Date: 2015/7/31
 * Time: 15:17
 */
namespace core\service;
use core\dao\UserCompanyModel;

/**
 * Class UserCompanyService
 * @package core\service
 */
class UserCompanyService extends BaseService {

    /**
     * 获取用户公司法人信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getCompanyLegalInfo($user_id) {
        if (empty($user_id)) {
            return false;
        }
        return UserCompanyModel::instance()->findByViaSlave("user_id = '$user_id'", 'legal_person');
    }

}