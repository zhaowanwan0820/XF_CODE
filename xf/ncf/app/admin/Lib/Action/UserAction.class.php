<?php
/**
 * user桥接, 个人会员列表
 */

use core\enum\UserEnum;
use core\enum\EnterpriseEnum;
use core\service\user\UserService;

class UserAction extends WxBridgeAction {


    /**
     * ajax根据用户id获取用户信息
     * @author zhanglei5@ucfgroup.com
     */
    public function getAjaxUser() {
        $return = array("status" => 0, "message" => "");
        $userId = intval($_REQUEST['id']);
        if ($userId == 0) {
            return ajax_return($return);
        }

        $user = UserService::getUserById($userId);
        if (!$user) {
            return ajax_return($return);
        }
        $return['status'] = 1;
        $return['user'] = $user;

        // JIRA#3260 企业账户二期 - 用户类型
        if (UserEnum::USER_TYPE_NORMAL == $return['user']['user_type']) {
            $return['user']['user_type_name'] = UserEnum::USER_TYPE_NORMAL_NAME;
            // 获取带有url超链的姓名string
            $return['user']['name'] = get_user_url($return['user'], UserEnum::TABLE_FIELD_REAL_NAME);
        } elseif (UserEnum::USER_TYPE_ENTERPRISE == $return['user']['user_type']) {
            $return['user']['user_type_name'] = UserEnum::USER_TYPE_ENTERPRISE_NAME;
            // 获取企业名称
            $enterpriseInfo = UserService::getEnterpriseInfo($userId);
            // 获取带有url超链的姓名string
            $return['user']['company_name'] = $enterpriseInfo['company_name'];
            $return['user']['name'] = get_user_url($return['user'], EnterpriseEnum::TABLE_FIELD_COMPANY_NAME);
        }
        return ajax_return($return);
    }
}
