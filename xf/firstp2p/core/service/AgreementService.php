<?php
/**
 * 通用用户协议签署
 *
 * 1.type:自定义业务名称，(userid,type)唯一索引，以支持各业务的协议签署需求
 * 2.调用AgreementService::check()判断是否已签署协议，如未签署则跳转协议模板
 * 3.协议模板签署按钮ajax调用Action: agreement/agree?type=candy&token=xxx, 完成签署
 *
 * @date 2018-07-14
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\UserAgreementModel;

class AgreementService extends BaseService{

    /**
     * 合法的业务类型集合，防止刷库。新增业务时加入自定义业务名称到该数组
     * "candy"字段不可用，仅用于查询历史授信记录。
     */
    private static $type_list = array('candy_shop', 'candy');

    /**
     * 检查用户是否签署过协议
     *
     * @param user_id 用户ID
     * @param type 自定义业务名称
     * @return boolean
     */
    public static function check($user_id, $type) {
        if (empty($user_id) || empty($type) || !in_array($type, self::$type_list)) {
            return false;
        }
        $data= UserAgreementModel::instance()->getByUserId($user_id, $type);
        return !empty($data) && !empty($data['is_authorized']);
    }

    /**
     * 用户同意协议
     *
     * @param user_id 用户ID
     * @param type 自定义业务名称
     * @return boolean
     */
    public function agree($user_id, $type) {
        if (empty($user_id) || empty($type) || !in_array($type, self::$type_list)) {
            return false;
        }
        $data = UserAgreementModel::instance()->getByUserId($user_id, $type);
        if (!empty($data)) {
            return true;
        }
        $agreement = new UserAgreementModel();
        $agreement['user_id'] = $user_id;
        $agreement['type'] = $type;
        $agreement['is_authorized'] = 1;
        $agreement['create_time'] = get_gmtime();
        return $agreement->save();
    }
}
