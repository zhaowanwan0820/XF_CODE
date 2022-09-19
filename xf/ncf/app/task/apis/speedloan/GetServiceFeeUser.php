<?php
/**
 * 获取速贷服务费账户配置
 */
namespace task\apis\speedloan;

use task\lib\ApiAction;

class GetServiceFeeUser extends ApiAction {
    public function invoke()
    {
        $response['msg'] = '';
        $response['status'] = 0;
        try {
            $serviceFeeUserId = app_conf('SPEED_LOAN_SERVICE_FEE_UID');
            if (empty($serviceFeeUserId))
            {
                throw new \Exception('速贷网贷还款服务费账号未设置');
            }
            $response = ['status' => 0, 'msg' => '成功', 'data' => $serviceFeeUserId];
        } catch (\Exception $e) {
            $response = ['status' => -1, 'msg' => $e->getMessage()];
        }
        $this->json_data = $response;
    }
}
