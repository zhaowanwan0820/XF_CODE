<?php
/**
 * firstp2p网站- 存管用户添加授权 回调接口
 *
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionAccountService;

class MemberAuthorizationCreateNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['userId'] = isset($requestData['userId']) ? intval($requestData['userId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['grantList'] = isset($requestData['grantList']) ? addslashes($requestData['grantList']) : '';
        $params['grantAmountList'] = isset($requestData['grantAmountList']) ? addslashes($requestData['grantAmountList']) : '';
        $params['grantTimeList'] = isset($requestData['grantTimeList']) ? addslashes($requestData['grantTimeList']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionAccountService();
        return $supervisionObj->memberAuthorizationCreateNotify($params);
    }
}
