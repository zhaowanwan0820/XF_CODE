<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\daos\MsgBoxDAO;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Ptp\services\PtpPushService;
use NCFGroup\Ptp\models\Firstp2pUserMsgConfig;
use NCFGroup\Protos\Ptp\RequestPushToSingle;
use NCFGroup\Protos\Ptp\RequestMsgBoxSend;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use NCFGroup\Common\Library\Logger;

class PtpMsgBoxService extends ServiceBase {

    const APPID_WANGXINLICAI = 1;

    /**
     * 创建消息
     */
    public function msgBoxSend(RequestMsgBoxSend $msg) {
        $userId = $msg->getUserId();
        $type = $msg->getType();
        $title = $msg->getTitle();
        $content = $msg->getContent();
        $userIds = $msg->getBatchUserIds();

        $msgBoxDao = new MsgBoxDAO();
        $response = new ResponseBase();

        // 消息中心升级
        if (strpos($content, '{') === 0) {
            $contentDetail = json_decode($content, true);
            $pushContent = $contentDetail['content'];
        } else {
            $pushContent = $content;
        }

        if ($userId) {
            $res = $msgBoxDao->create($userId, $type, $title, $content);
            if (!$res) {
                throw new \Exception('site msg create fail');
            }

            $res = $this->push($userId, $type, $title, $pushContent);
            if (!$res) {
                throw new \Exception('msg push failed');
            }
        } elseif (is_array($userIds)) {
            $res = $this->_batchMsgSend($msgBoxDao, $userIds, $type, $title, $content, $pushContent);
            $response->msg = $res;
        }
        $response->result = true;
        return $response;
    }

    private function _batchMsgSend(MsgBoxDAO $msgBoxDao, $userIds, $type, $title, $content, $pushContent) {
        $msg = array();
        $msg['success'] = $msg['create_fail'] = $msg['push_fail'] = array();
        foreach ($userIds as $uid) {
            try {
                $c_res = $msgBoxDao->create(intval($uid), $type, $title, $content);
            } catch (\Exception $e) {
                Logger::error(__CLASS__ .' '. __FUNCTION__ .' CreateMsgErr:'.$e->getMessage());
            }
            if ($c_res) {
                try {
                    $p_res = $this->push(intval($uid), $type, $title, $pushContent);
                } catch (\Exception $e) {
                    Logger::error(__CLASS__ .' '. __FUNCTION__ .' PushMsgErr:'.$e->getMessage());
                }
                if (!$p_res) {
                    $msg['push_fail'][] = $uid;
                } else {
                    $msg['success'][] = $uid;
                }
            } else {
                $msg['create_fail'][] = $uid;
            }
        }
        return $msg;
    }


    /**
     * 推送消息
     */
    public function push($userId, $type, $title, $content) {
        //是否需要推送
        if (!isset(MsgBoxEnum::$appType[$type])) {
            Logger::info(__CLASS__ .' '. __FUNCTION__ .' msg type not in push msg types, type:'. $type);
            return true;
        }

        $content = strip_tags($content);
        $params = array('type' => 'msg');

        //用户是否已关闭
        $field = 'pushSwitches';
        $userMsgConfig = Firstp2pUserMsgConfig::findFirst("userId='$userId'");
        $switches = !empty($userMsgConfig) ? json_decode($userMsgConfig->$field, true) : array();
        if (isset($switches[$type]) && $switches[$type] == 0) {
            Logger::info(__CLASS__ .' '. __FUNCTION__ . 'user msg config closed, switches:' . json_encode($switches) . '|type:'. $type);
            return true;
        }

        $msgBoxDao = new MsgBoxDAO();
        $badge = $msgBoxDao->getUnReadCount($userId);

        $pushService = new PtpPushService();
        $request = new RequestPushToSingle();
        $request->setAppId(self::APPID_WANGXINLICAI);
        $request->setAppUserId($userId);
        $request->setContent($content);
        $request->setBadge($badge);
        $request->setParams($params);
        $response = $pushService->toSingle($request);
        return $response->result;
    }
}
