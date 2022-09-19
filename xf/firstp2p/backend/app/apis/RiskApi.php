<?php
/**
 * 风控数据同步接口
 */

namespace NCFGroup\Ptp\Apis;

use core\service\UserService;
use libs\utils\Logger;
use libs\utils\Monitor;

class RiskApi
{
    //每次请求支持的最大userid数量
    const MAX_USERIDS_COUNT = 100;

    private $params = [];

    //接口ip限制，只允许特定ip访问
    private $whiteIps = [
        '172.21.30.21',
        '172.21.30.22',
        '172.21.29.21',
        '172.21.29.22',
    ];

    private function init()
    {
        Monitor::add('RISK_REQUEST_BACKEND_API');

        $di = getDI();
        $this->params = $di->get('requestBody');

        $clientIp = get_real_ip();
        if (app_conf("ENV_FLAG") == 'online' && !in_array($clientIp, $this->whiteIps)) {
            Logger::info("RiskApi. ip {$clientIp}受限");
            throw new \Exception("ip受限");
        }
    }

    public function getUserInfo()
    {
        $result = array('errorCode' => 0, 'errorMsg' => '');
        try{
            $this->init();
        }catch (\Exception $ex){
            return array('errorCode' => -1, 'errorMsg' => $ex->getMessage(), 'data' => array());
        }

        $userids = [];
        foreach ($this->params['userids'] as $id) {
            $userids[] = intval($id);
        }

        if (empty($userids)) {
            return array('errorCode' => -1, 'errorMsg' => '缺少参数userids', 'data' => array());
        }

        Logger::info('Risk_GetUserInfo. userids:' . implode(',', $userids) . ',count:' . count($userids));

        if (count($userids) > self::MAX_USERIDS_COUNT) {
            return array('errorCode' => -1, 'errorMsg' => 'userids数量超过上限'.self::MAX_USERIDS_COUNT, 'data' => array());
        }

        $userService = new UserService();
        $userInfo = $userService->getUserInfoByIds($userids, 'id,mobile,idno,id_type,invite_code,create_time');
        Logger::info('Risk_GetUserInfo result. count:' . count($userInfo));
        Monitor::add('RISK_REQUEST_USERINFO_COUNT', count($userInfo));

        $result['data'] = $userInfo;
        return $result;
    }

}
