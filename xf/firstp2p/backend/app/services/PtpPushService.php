<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\RequestPushSignIn;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RequestPushSignOut;
use NCFGroup\Protos\Ptp\RequestPushIsSigned;
use NCFGroup\Protos\Ptp\RequestPushToSingle;
use NCFGroup\Protos\Ptp\RequestPushToAll;
use NCFGroup\Ptp\daos\PushDAO;
use NCFGroup\Ptp\Instrument\Event\PushEvent;
use NCFGroup\Task\Services\TaskService;
use NCFGroup\Ptp\services\PtpPushChannelService;
use NCFGroup\Common\Library\Logger;

/**
 * PushService
 * 推送相关service
 * @uses ServiceBase
 * @package default
 */
class PtpPushService extends ServiceBase
{

    //最大消息推送字数
    const MAX_MESSAGE_LENGTH = 100;

    const TYPE_ANDROID = 3;
    const TYPE_IOS = 4;

    /**
     * 设备签入
     * @param \NCFGroup\Protos\Ptp\RequestPushSignIn $request
     * @return type
     */
    public function signIn(RequestPushSignIn $request)
    {
        $appId = $request->getAppId();
        $appUserId = $request->getAppUserId();
        $baiduChannelId = $request->getBaiduChannelId();

        $data = array();
        $data['appVersion'] = $request->getAppVersion();
        $data['baiduUserId'] = $request->getBaiduUserId();
        $data['osType'] = $request->getOsType();
        $data['osVersion'] = $request->getOsVersion();
        $data['model'] = $request->getModel();
        $data['apnsToken'] = $request->getApnsToken();

        $result = PushDAO::addUser($appId, $appUserId, $baiduChannelId, $data);

        $response = new ResponseBase();
        $response->result = $result;
        return $response;
    }

    /**
     * 设备签出
     */
    public function signOut(RequestPushSignOut $request)
    {
        $appId = $request->getAppId();
        $appUserId = $request->getAppUserId();
        $baiduChannelId = $request->getBaiduChannelId();

        $result = PushDAO::deleteUser($appId, $appUserId, $baiduChannelId);

        $response = new ResponseBase();
        $response->result = $result;
        return $response;
    }

    /**
     * 用户是否签入
     */
    public function isSigned(RequestPushIsSigned $request)
    {
        $appId = $request->getAppId();
        $appUserId = $request->getAppUserId();
        $result = PushDAO::getStatusByUser($appId, $appUserId);

        $response = new ResponseBase();
        $response->result = $result;
        return $response;
    }

    /**
     * 推送单个用户
     */
    public function toSingle(RequestPushToSingle $request)
    {
        $appId = $request->getAppId();
        $userId = $request->getAppUserId();
        $content = $request->getContent();
        $badge = $request->getBadge();
        $params = $request->getParams();

        $channels = PushDAO::getUserChannels($appId, $userId);
        if (empty($channels)) {
            throw new \Exception('Channel is empty');
        }

        $content = $this->_cnSubStr($content, self::MAX_MESSAGE_LENGTH, '...');

        foreach ($channels as $item) {
            $pushEvent = new PushEvent($appId, $userId, $item['baiduChannelId'], $item['osType'], $content, $badge, $params);
            $taskService = new TaskService();
            $taskService->doBackground($pushEvent);
        }

        $response = new ResponseBase();
        $response->result = true;
        return $response;
    }

    /**
     * 推送All
     */
    public function toAll(RequestPushToAll $request)
    {
        $appId = $request->getAppId();
        $content = $request->getContent();
        $params = $request->getParams();

        $content = $this->_cnSubStr($content, self::MAX_MESSAGE_LENGTH, '...');
        $response = new ResponseBase();
        $response->result = true;
        try {
            $pushService = new PtpPushChannelService();
            $res['android'] = $pushService->toAll($appId, $pushService::OSTYPE_ANDROID, $content, $params);
            sleep(5);//广播频率限制
            $res['ios'] = $pushService->toAll($appId, $pushService::OSTYPE_IOS, $content, $params);
            if (false == ($res['android'] && $res['ios'])) {
                Logger::info("PushAllFailed.error:".json_encode($pushService->error));
                $response->result = false;
            }
            Logger::info("PushAllSuccessRes:".json_encode($res));
        } catch (\Exception $e) {
            Logger::info("PushAllException.error:".$e->getMessage());
            $response->result = false;
        }

        return $response;
    }


    /**
     * 中文截字方法
     */
    private function _cnSubStr($content, $length, $add)
    {
        if ($length && strlen($content) > $length) {
            $str = substr($content, 0, $length);
            $len = strlen($str);
            $hex = '';
            for ($i = $len - 1; $i >= 0; $i-=1) {
                $hex .= ' '.ord($str[$i]);
                $ch = ord($str[$i]);
                if(($ch & 128)==0) return substr($str,0,$i).$add;
                if(($ch & 192)==192) return substr($str,0,$i).$add;
            }
            return($str.$hex.$add);
        }
        return $content;
    }

}
