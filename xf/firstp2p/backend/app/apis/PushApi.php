<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Protos\Ptp\RequestPushToSingle;
use NCFGroup\Ptp\services\PtpPushService;

class PushApi
{
    private $logger;
    private $di;
    private $pushService;

    private function init()
    {
        $this->di = getDI();
        $this->logger = $this->di->get('logger');

        $this->pushService = new PtpPushService();
    }

    public function batchPush()
    {
        $this->init();
        $params = $this->di->get('requestBody');

        foreach ($params as $param) {
            try {
                $this->doPush($param['userId'], $param['content']);
            } catch (\Exception $e) {
                $this->logger->error("PuahApi push error:{$e->getMessage()}, userId:{$param['userId']}, content:{$param['content']}");
            }
        }

        return array(
            'errorCode' => 0,
            'errorMsg' => '',
            'data' => array()
        );
    }

    /**
     * @param $userId
     * @param $content
     * @param int $appId 1为网信理财
     * @param int $badge 角标
     * @param array $params
     * @return mixed
     */
    private function doPush($userId, $content, $appId = 1, $badge = 1, $params = array('type' => 'home'))
    {
        $request = new RequestPushToSingle();
        $request->setAppId($appId);
        $request->setAppUserId(intval($userId));
        $request->setContent($content);
        $request->setBadge($badge);
        $request->setParams($params);
        return $this->pushService->toSingle($request);
    }
}
