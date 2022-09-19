<?php

namespace core\service\risk;

use NCFGroup\Common\Library\Risk\Risk;
use libs\utils\Site;

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
        return true;
        //$params = self::getParams($bizType, $status, $extraData);
        //return Risk::instance()->report($params);
    }

    /**
     * 校验用户是否是正常用户
     */
    public static function check($bizType, array $extraData = array())
    {
        return true;
        //$params = self::getParams($bizType, self::STATUS_INIT, $extraData);
        //return Risk::instance()->check($params);
    }

    private static function getParams($bizType, $status, array $extraData)
    {
        $params = array(
            'mobile' => isset($extraData['mobile']) ? $extraData['mobile'] : '',
            'user_id' => isset($extraData['user_id']) ? intval($extraData['user_id']) : 0,
            'site' => intval(Site::getId()),
            'status' => $status,
            'biz_type' => $bizType,
        );
        unset($extraData['mobile']);
        unset($extraData['user_id']);
        $params['biz_info'] = $extraData;
        return $params;
    }
}

