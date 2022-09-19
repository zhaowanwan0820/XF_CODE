<?php
/**
 * 移动端推送Service
 */
namespace core\service;

use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestPushIsSigned;
use NCFGroup\Protos\Ptp\RequestPushToSingle;
use NCFGroup\Protos\Ptp\RequestPushToAll;

class PushService extends BaseService
{

    const APPID_WANGXINLICAI = 1;

    /**
     * 是否登记过移动设备
     */
    public function isSigned($userId)
    {
        //推送
        $request = new RequestPushIsSigned();
        $request->setAppId(self::APPID_WANGXINLICAI);
        $request->setAppUserId($userId);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPush',
            'method' => 'isSigned',
            'args' => $request
        ));

        return $response->result;
    }

    /**
     * 推送至单个用户
     */
    public function toSingle($userId, $content, $badge = 0, $params = array())
    {
        $userId = intval($userId);

        //用户是否登记移动设备
        if (!$this->isSigned($userId)) {
            Logger::info("PushServiceToSingle. no signed, args:".json_encode(func_get_args()));
            return true;
        }

        //推送
        $request = new RequestPushToSingle();
        $request->setAppId(self::APPID_WANGXINLICAI);
        $request->setAppUserId($userId);
        $request->setContent($content);
        $request->setBadge($badge);
        $request->setParams($params);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPush',
            'method' => 'toSingle',
            'args' => $request
        ));

        Logger::info("PushServiceToSingle. ret:{$response->result}, args:".json_encode(func_get_args()));

        return $response->result;
    }

    public function toBatch($userIdArray, $content, $params = array())
    {
    }

    public function toAll($title='', $content='', $params = array())
    {
        if (empty($content)) {
            return false;
        }
        $params = empty($params) ? array('type' => 'notice') : $params;
        $request = new RequestPushToAll();
        $request->setAppId(self::APPID_WANGXINLICAI);
        $request->setContent($content);
        $request->setTitle($title);
        $request->setParams($params);
        try {
            $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpPush',
                'method' => 'toAll',
                'args' => $request
            ));
        } catch (\Exception $e) {
            Logger::info('ToAllPushException:'.$e->getMessage());
        }


        Logger::info("PushServiceToAll. ret:{$response->result}, args:".json_encode(func_get_args()));
        return $response->result;
    }

}
