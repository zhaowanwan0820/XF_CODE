<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;
use libs\utils\Aes;
use core\service\marketing\AssistanceService;
use core\service\BonusBindService;
use core\service\WeixinInfoService;
use core\service\BwlistService;
use core\service\UserService;

class AssistanceService extends BaseService
{

    private $eventId = '';
    public $config = [];

    const SN_KEY = 'happy_new_year_2019';

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
        $this->getConfig();
    }

    public function getUserEventInfo($userId, $likerUid, $mobile = '')
    {
        $params = ['constName' => $this->eventId, 'userId' => $userId, 'likerUid' => $likerUid];
        $result = ApiService::rpc('marketing', 'EventLike/getInfo', $params);
        $result['assistanceUrl'] = get_domain()."/activity/DoAssistance?eventId={$this->eventId}&sn=".Aes::encryptHex($userId, self::SN_KEY).($_REQUEST['token'] ? "&token={$_REQUEST['token']}" : '');
        $result['shareUrl'] = $this->getShareUrl($userId);
        $result['pageType'] = $result['code'];
        $result['eventId']  = $this->eventId;
        $result['sn'] = Aes::encryptHex($userId, self::SN_KEY);
        $result['moreUrl'] = $this->getShareUrl($likerUid);//get_domain()."/activity/Assistance?eventId=$this->eventId";
        if (isset($this->config['white_list_key']) && $this->config['white_list_key'] != '') {
            $isWhite = (new BwlistService())->inList($this->config['white_list_key'], $likerUid);
        } else {
            $isWhite = true;
        }

        if (!$isWhite) {
            $result['acquireType'] = 0;
        } else {
            $isAcquired = ApiService::rpc('marketing', 'EventLike/isLike', ['constName' => $this->eventId, 'uid' => $likerUid, 'likerUid' => $likerUid]);
            $result['acquireType'] = 1;
            if ($isAcquired) {
                $result['acquireType'] = 2;
            }
        }

        $result['role'] = $userId == $likerUid ? 0 : 1;
        $result['mobile'] =substr_replace($mobile, '****', 3, 4);
        return $result;
    }

    public function doLike($userId, $likerUid, $mobile, $isNewer = false)
    {
        list($startTime, $endTime) = $this->config['time_scope'];
        if ($startTime > $_SERVER['REQUEST_TIME'] || $endTime < $_SERVER['REQUEST_TIME']) {
            return ['code' => 9, 'msg' => '活动已结束', 'data' => []];
        }

        if (isset($this->config['white_list_key']) && $this->config['white_list_key'] != '') {
            $isWhite = (new BwlistService())->inList($this->config['white_list_key'], $userId);
        } else {
            $isWhite = true;
        }
        if (!$isWhite)  {
            return ['code' => 1001, 'msg'=> '本活动仅限2019年2月3日0点前达到网信VIP等级的用户开启。非VIP用户可点击其他用户分享的活动链接，参与助力获得红包奖励。', 'data' => []];
        }
        $params = ['constName' => $this->eventId, 'userId' => $userId, 'likerUid' => $likerUid, 'isNewer' => $isNewer];
        list($params['nickname'], $params['headimg']) = $this->getNicknameAndHeadimagByMobile($mobile);
        $result = ApiService::rpc('marketing', 'EventLike/doLike', $params);
        return $result;
    }

    private function getNicknameAndHeadimagByMobile($mobile)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $bindRedisKey = 'assistance_service_dolike_weixin_bind_info_'.$mobile;
        $bindInfo = $redis->get($bindRedisKey);
        if (empty($bindInfo)) {
            $bindInfo = array_pop((new BonusBindService())->getBindInfoByMobile($mobile));
        }

        $nickname = '网信用户';
        $headimg = '';
        if (!empty($bindInfo['openid'])) {
            $weixinInfo = (new WeixinInfoService())->getWeixinInfo($bindInfo['openid']);
            if (!empty($weixinInfo) && isset($weixinInfo['user_info'])) {
                $nickname = $weixinInfo['user_info']['nickname'];
                $headimg = $weixinInfo['user_info']['headimgurl'];
            }
        }

        return [$nickname, $headimg];
    }

    /**
     * getShareUrl
     *
     * @param mixed $ownerUid
     * @access public
     * @return void
     */
    public function getShareUrl($ownerUid)
    {
        return get_domain()."/activity/Assistance?eventId={$this->eventId}&sn=".Aes::encryptHex($ownerUid, self::SN_KEY);
    }

    private function getConfig()
    {
        $this->config = ApiService::rpc('marketing', 'EventLike/getConfig', ['constName' => $this->eventId]);
    }

}

