<?php
namespace NCFGroup\Ptp\services;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use libs\utils\PaymentApi;
use libs\utils\Logger;
//use NCFGroup\Ptp\models\Firstp2pUserMsgConfig;
//use NCFGroup\Ptp\models\Firstp2pUser;
use libs\sms\SmsServer;
use NCFGroup\Common\Library\Sms\Sms;

/**
 * CommonService
 * @uses ServiceBase
 * @package default
 */
class PtpCommonService extends ServiceBase {
    /**
     * 发送短信
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function sendSmsFund(SimpleRequestBase $request) {
        $par = $request->getParamArray();
        $userId = $par['userId'];
        $mobile = $par['mobile'];
        $smsKey = $par['smsKey'];
        $params = $par['params'];
        $params = isset($params) ? json_decode(urldecode($params)) : array();
        $mark = isset($par['mark']) ? $par['mark'] : '';
        if (empty($userId) || empty($mobile) || empty($smsKey)) {
            throw new \Exception("传入参数不正确！");
        }
        //用户短信开关判断
        $type = $par['type'];
        if (!empty($type)) {
            //$userMsgConfig = Firstp2pUserMsgConfig::findFirst("userId='$userId'");
            $switches = \core\dao\UserMsgConfigModel::instance()->getSwitches($userId, 'sms_switches');
            if (isset($switches[$type]) && $switches[$type] == 0) {
                Logger::info(__CLASS__ .' '. __FUNCTION__ . 'user sms config closed, switches:' . json_encode($switches) . '|type:'. $type);
                return true;
            }
        }

        if ($par['source'] == 'marketing') {
            //$userInfo = Firstp2pUser::findFirst("id='$userId'");
            //if (empty($userInfo) || $userInfo->isEffect == 0 || $userInfo->isDelete == 1) {
            $userInfo = (new \core\service\UserService())->getUserViaSlave($userId);
            if (empty($userInfo) || $userInfo['is_effect'] == 0 || $userInfo['is_delete'] == 1) {
                Logger::info(__CLASS__ .' '. __FUNCTION__ . 'invalid user:' . json_encode($userInfo) . '|params:'. json_encode($par));
                return true;
            }
            // if ($userInfo->siteId > 1) {
            //     $siteTitle = \libs\utils\Site::getTitleById($userInfo->siteId);
            //     $siteTitle = $siteTitle ? "[$siteTitle]" : '';
            // } else {
            //     $siteTitle = '';
            // }
            // array_unshift($params, $siteTitle);
            // \NCFGroup\Common\Library\Sms::instance()->send($mobile, $smsKey, $params);
            SmsServer::instance()->send($mobile, $smsKey, $params, null, $userInfo['site_id']);
        } else {
            SmsServer::instance()->send($mobile, $smsKey, $params, $userId);
        }

        // 记录日志
        $apiLog = $par;
        $apiLog['time'] = date('Y-m-d H:i:s');
        $apiLog['ip'] = get_real_ip();
        PaymentApi::log("API_SMS_LOG:".json_encode($apiLog), Logger::INFO);

        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }

    public function getCSRFToken(SimpleRequestBase $req)
    {
        $tokenKey = round(microtime(true) * 1000);
        $token = mktoken($tokenKey);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->set('TOKEN_' . $tokenKey, $token, 'ex', 600);
        return ['tokenKey' => $tokenKey, 'token' => $token];
    }

    /**
     * 获取理财配置
     * @param SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase
     */
    public function getConfig(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        // 配置类型(0:系统配置1:Api配置)
        $type = isset($params['type']) ? (int)$params['type'] : 0;
        // 配置键值
        $configKey = !empty($params['key']) ? addslashes($params['key']) : '';

        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::SUCCESS;
        switch ($type) {
            case 0: // 系统配置
                $response->data = !empty($configKey) ? app_conf($configKey) : '';
                break;
            case 1: // Api配置
                // 站点ID
                $siteId = isset($params['siteId']) ? (int)$params['siteId'] : 1;
                // 配置类型(1:公共配置，2:分站配置)
                $configType = isset($params['configType']) ? (int)$params['configType'] : 2;
                $response->data = \SiteApp::init()->dataCache->call(new \libs\rpc\Rpc(), 'local', array('ApiConfService\getApiAdvConf', array($configKey, $siteId, $configType)), 60);
                break;
        }
        return $response;
    }

    public function batchSendSmsFund(SimpleRequestBase $request) {
        $par        = $request->getParamArray();
        $userIds    = $par['userIds'];
        $smsKey     = $par['smsKey'];
        $smsContent = $par['smsContent'];
        $type       = $par['type'];
        $params     = $par['params'];
        $appName    = isset($par['appName']) ? $par['appName'] : 'p2p';

        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::SUCCESS;
        try {
            //用户短信开关判断
            $userSwitches = \core\dao\UserMsgConfigModel::instance()->getBatchSwitches($userIds, 'user_id, sms_switches');
            $sendUserIds = $closeUserIds = [];
            foreach ($userSwitches as $item) {
                $switches = json_decode($item['sms_switches'], true);
                if (isset($switches[$type]) && $switches[$type] == 0) {
                    $closeUserIds[$item['user_id']] = $item['user_id'];
                } else {
                    $sendUserIds[] = $item['user_id'];
                }
            }

            foreach ($userIds as $uid) { //当没有设置过开关时
                if (!isset($closeUserIds[$uid]) && !in_array($uid, $sendUserIds)) {
                    array_push($sendUserIds, $uid);
                }
            }

            if (!empty($closeUserIds)) {
                Logger::info(__METHOD__ . 'closed list:' . json_encode(array_keys($closeUserIds)));
            }

            if (empty($sendUserIds)) {
                $response = new ResponseBase();
                $response->resCode = RPCErrorCode::SUCCESS;
                return $response;
            }
            $sendUserIdsStr = implode(",", $sendUserIds);
            $userMobiles = \core\dao\UserModel::instance()->getMobileByIds($sendUserIdsStr);
            $sendList = [];
            foreach ($userMobiles as $userInfo) {
                if (!preg_match("/^1[34578]\d{9}$/", $userInfo['mobile'])) {
                    continue;
                }
                $sendList[] = $userInfo['mobile'];
                //$sendList[] = [
                    //'mobile'  => $userInfo['mobile'],
                    //'content' => $smsContent
                //];
            }

            //Sms::$app_name = $appName;
            //Sms::instance()->batch($smsKey, $sendList);
            $result = Sms::send('p2pmarketing', 'p2pmarketing', $sendList, str_replace('TPL_SMS', 'MARKETING', $smsKey), $params);
            Logger::info(__METHOD__ . 'send list:' . json_encode($sendUserIds)." | result:".json_encode($result));
        } catch (\Exception $e) {
            Logger::error(__METHOD__. 'exception:' . json_encode($e->getMessage()));
        }

        return $response;
    }
}
