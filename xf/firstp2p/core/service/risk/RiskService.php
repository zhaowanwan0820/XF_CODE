<?php

namespace core\service\risk;

use NCFGroup\Common\Library\Risk\Risk;
use libs\utils\Site;
use core\service\UserService;

class RiskService
{

    // 初始状态
    const STATUS_INIT = 0;

    // 操作成功状态
    const STATUS_SUCCESS = 1;

    // 操作失败状态
    const STATUS_FAIL = 2;

    /**
     * 上报用户操作信息
     */
    public static function report($bizType, $status, array $extraData = array())
    {
        $params = self::getParams($bizType, $status, $extraData);
        return Risk::instance()->report($params);
    }

    /**
     * 校验用户是否是正常用户
     */
    public static function check($bizType, array $extraData = array())
    {
        if (!app_conf('HUOYAN_SWITCH')) {
            return true;
        }
        $params = self::getParams($bizType, self::STATUS_INIT, $extraData);
        return Risk::instance()->check($params);
    }

    private static function getParams($bizType, $status, array $extraData)
    {
        $userInfo = UserService::getLoginUser();
        $params = array(
            'mobile' => isset($userInfo['mobile']) ? $userInfo['mobile'] : '',
            'user_id' => isset($userInfo['id']) ? intval($userInfo['id']) : 0,
            'site' => intval(Site::getId()),
            'status' => $status,
            'biz_type' => $bizType,
        );

        if (isset($extraData['mobile'])) {
            $params['mobile'] = $extraData['mobile'];
            unset($extraData['mobile']);
        }

        if (isset($extraData['user_id'])) {
            $params['user_id'] = intval($extraData['user_id']);
            unset($extraData['user_id']);
        }
        $extraData['account_type'] = isset($userInfo['user_purpose']) ? $userInfo['user_purpose'] : '';
        $extraData['user_type'] = isset($userInfo['user_type']) ? $userInfo['user_type'] : '';
        $extraData['group_id'] = isset($userInfo['group_id']) ? $userInfo['group_id'] : '';
        $extraData['invite_code'] = isset($userInfo['invite_code']) ? $userInfo['invite_code'] : '';
        $params['biz_info'] = $extraData;

        return $params;
    }
}
