<?php
/**
 * firstp2p网站-企业用户开户-异步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionAccountService;

class EnterpriseRegisterNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['userId'] = isset($requestData['userId']) ? intval($requestData['userId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionAccountService();
        return $supervisionObj->registerNotify($params);
    }
}
