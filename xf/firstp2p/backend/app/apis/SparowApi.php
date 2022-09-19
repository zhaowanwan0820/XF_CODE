<?php

namespace NCFGroup\Ptp\Apis;

use core\service\candy\CandyAccountService;
use core\service\candy\CandyEventService;
use libs\weixin\Weixin;
use core\service\O2OService;
use core\service\BonusBindService;
use NCFGroup\Common\Library\ApiService;
use core\service\marketing\DiscountCenterService;
use core\dao\BonusConfModel;
use core\service\BwlistService;
use core\service\LogRegLoginService;
use core\service\risk\RiskServiceFactory;
use core\service\UserTrackService;
use libs\utils\Risk;
use core\service\UserService;
use core\service\UserTokenService;
use core\service\SparowService;
use libs\utils\Logger;
use core\service\UserProfileService;
use core\service\UserImageService;

/**
 * 信力相关内部接口
 */
class SparowApi extends SparowBaseApi
{

    private $sourceConfig = [
        'xbtree' => CandyEventService::EVENT_ID_CANDYTREE,
        'xb666' => CandyEventService::EVENT_ID_CANDYDICE,
        'dzp' => CandyEventService::EVENT_ID_DZP,
    ];

    private $eventConfig = [
        CandyEventService::EVENT_ID_CANDYTREE => [
            CandyEventService::CHANGE_TYPE_CANDYTREE_PAY,
            CandyEventService::CHANGE_TYPE_CANDYTREE_AWARD
        ],
        CandyEventService::EVENT_ID_CANDYDICE => [
            CandyEventService::CHANGE_TYPE_CANDYDICE_PAY,
            CandyEventService::CHANGE_TYPE_CANDYDICE_AWARD,
        ],
        CandyEventService::EVENT_ID_DZP => [
            CandyEventService::CHANGE_TYPE_DZP_PAY,
            CandyEventService::CHANGE_TYPE_DZP_AWARD,
        ],
    ];


    /**
     * 检查用户是否为VIP
     */
    public function checkVip()
    {
        $userInfo = $this->getUserByToken();
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $vipInfo = (new \core\service\vip\VipService())->getVipInfo(intval($userInfo['id']));
        if (intval($vipInfo['service_grade']) > 0) {
            $this->echoJson(0, 'ok');
        } else {
            $this->echoJson(20001, 'check failed');
        }
    }

    /**
     * 检查用户是否在白名单
     */
    public function checkGame()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $res = (new \core\service\BwlistService)->inList('CANDY_GAME_WHITE', $userInfo['id']);
        if ($res) {
            $this->echoJson(0, 'ok');
        } else {
            $this->echoJson(20001, 'check failed');
        }
    }

    public function getUsable()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        $res = (new CandyAccountService())->getAccountInfo($userInfo['id']);
        $coin = $res['amount'];
        $this->echoJson(0, 'ok', ['coin' => $coin]);
    }

    public function consume()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $orderId = $this->req['orderId'];
        if (empty($orderId)) $this->echoJson(10002, 'orderid missing');

        $amount = $this->req['candy'];
        if ($amount <= 0) $this->echoJson(10003, 'candy amount error');

        try {

            $event = $this->getEvent();
            (new CandyEventService)->changeAmount($event['eventId'], $orderId, $userInfo['id'], -$amount, $event['pay']);

        } catch (\Exception $e) {

            if ($e->getCode() == CandyEventService::EXCEPTION_CODE_TOKEN_EXISTS) {
                $this->echoJson(0, 'ok');
            }
            $this->echoJson($e->getCode() ?: 10000, $e->getMessage());
        }
        $this->echoJson(0, 'ok');

    }

    public function acquire()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $orderId = $this->req['orderId'];
        if (empty($orderId)) $this->echoJson(10002, 'orderid missing');

        $amount = $this->req['candy'];
        if ($amount <= 0) $this->echoJson(10003, 'amount error');

        try {

            $event = $this->getEvent();
            (new CandyEventService)->changeAmount($event['eventId'], $orderId, $userInfo['id'], $amount, $event['award']);

        } catch (\Exception $e) {
            if ($e->getCode() == CandyEventService::EXCEPTION_CODE_TOKEN_EXISTS) {
                $this->echoJson(0, 'ok');
            }
            $this->echoJson($e->getCode() ?: 10000, $e->getMessage());
        }

        $this->echoJson(0, 'ok');
    }


    public function getBonus()
    {
        $userInfo = $this->getUserByToken();
        $userId = $userInfo['id'];
        $ruleId = $this->req['ruleId'];
        $isGroup = $this->req['isGroup'];
        $orderId = $this->req['orderId'];

        if (empty($ruleId) || empty($userId) || empty($orderId)) {
            $this->echoJson(10000, '参数有误');
        }

        if ($isGroup) {
            $templateId = $this->req['templateId'] ?: 0;
            $sn = \core\service\bonus\RpcService::acquireBonusRule($ruleId, $userId, '', $orderId, 39, $templateId);
            if ($sn) {
                $link = app_conf('BONUS_GROUP_GRAB_URL') . '?sn=' . $sn;
                $this->echoJson(0, 'ok', ['link' => $link]);
            }
        } else {
            $res = \core\service\bonus\RpcService::acquireBonusRule($ruleId, $userId, '', $orderId, 47);
            if ($res) $this->echoJson(0, 'ok');
        }
        $this->echoJson(10000, 'sys error');

    }

    public function getCoupon()
    {
        $userInfo = $this->getUserByToken();
        $userId = $userInfo['id'];
        $couponId = $this->req['couponId'];
        $orderId = $this->req['orderId'];

        if (empty($couponId) || empty($userId) || empty($orderId)) {
            $this->echoJson(10000, '参数有误');
        }

        $res = (new \core\service\O2OService)->acquireCoupons($userId, $couponId, $orderId);

        if ($res) $this->echoJson(0, 'ok', $res);
        $this->echoJson(10000, 'sys error');
    }


    private function getEvent()
    {
        $source = $this->req['source'];
        if (empty($source)) {
            $text = $this->req['text'];
            if (empty($text)) throw new \Exception("参与异常", 10004);
            return [
                'eventId' => CandyEventService::EVENT_ID_SPAROW,
                'pay' => $text,
                'award' => $text,
            ];
        } else {

            $eventId = $this->sourceConfig[$source];
            return [
                'eventId' => $eventId,
                'pay' => $this->eventConfig[$eventId][0],
                'award' => $this->eventConfig[$eventId][1],
            ];
        }
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->echoJson(1001, '获取用户信息失败');
        }

        $vipInfo = [];
        $image = '';
        if (isset($userInfo['id'])) {
            $vipInfo = (new \core\service\vip\VipService())->getVipInfo(intval($userInfo['id']));

            // 获取头像逻辑，参考Summary
            $avatar = (new UserImageService)->getUserImageInfo($userInfo['id']);
            if ($avatar && !empty($avatar['attachment'])) {
                if (stripos($avatar['attachment'], 'http') === 0) {
                    $image = $avatar['attachment'];
                } else {
                    $image = 'http:' . (isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : '//static.firstp2p.com') . '/' . $avatar['attachment'];
                }
            } else {
                $avatar = (new UserProfileService)->getUserHeadImg($userInfo['mobile']);
                if (!empty($avatar['headimgurl']) && stripos($avatar['headimgurl'], 'http') === 0) {
                    $image = $avatar['headimgurl'];
                }
            }
        }
        $data = [
            "userId" => isset($userInfo['id']) ? $userInfo['id'] : 0,
            "name" => isset($userInfo['real_name']) ? $userInfo['real_name'] : '',
            "level" => isset($vipInfo['service_grade']) ? intval($vipInfo['service_grade']) : 0,
            "sex" => isset($userInfo['sex']) ? $userInfo['sex'] : 0,
            "mobile" => substr_replace($userInfo['mobile'],'****', 3, 4),
            "mobile_num" => $userInfo['mobile'],
            "createTime" => $userInfo['create_time'],
            "image" => $image,
        ];

        //邀请码
        $coupon = (new \core\service\CouponService())->getOneUserCoupon($userInfo['id']);
        $data["shortAlias"] = $coupon['short_alias'];

        $this->echoJson(0, 'OK', $data);
    }

    /**
     * 发投资券
     * @return [type] [description]
     */
    public function acquireDiscount()
    {
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->echoJson(1001, '获取用户信息失败');
        }

        if ($userInfo['id']) {

            $res = (new O2OService())->acquireDiscount($userInfo['id'], $this->req['discoutGroupId'], $this->req['orderId']);


        } else {

            $res = DiscountCenterService::acquireSparowDiscountNoCheck($userInfo['mobile'], $this->req['discoutGroupId']);

            if (!$res || $res['code']) {
                $this->echoJson(1000, '领券失败');
            }
            $res = $res['data'];
        }


        if ($res) {
            $this->echoJson(0, 'OK', $res);
        } else {
            $this->echoJson(O2OService::$errorCode, O2OService::$errorMsg);
        }
    }

    /**
     * 获取微信JsSign
     */
    public function getWeixinInfo()
    {
        if (isset($this->req['wx']) && $this->req['wx'] == 'candy') {
            $appid = BonusConfModel::get('XINLI_WEIXIN_APPID');
            $secret = BonusConfModel::get('XINLI_WEIXIN_APPSECRET');
        } else {
            $appid = app_conf('WEIXIN_APPID');
            $secret = app_conf('WEIXIN_SECRET');
        }

        $shareLink = urldecode($this->req['shareLink']);
        if (empty($shareLink)) {
            $this->echoJson(1000, '参数有误');
        }

        $options = array(
            'appid' => $appid,
            'appsecret' => $secret,
        );
        $weObj = new Weixin($options);
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($shareLink, $timeStamp, $nonceStr, $appid);
        $res = [
            'appId' => $appid,
            'timeStamp' => $timeStamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
        ];
        $this->echoJson(0, 'OK', $res);
    }

    public function weixinBind()
    {

        $mobile = $this->req['mobile'];
        $weixinOpenId = $this->req['weixinOpenId'];

        if (empty($mobile) || empty($weixinOpenId)) {
            $this->echoJson(1000, '参数有误');
        }

        if (!is_mobile($mobile)) {
            $this->echoJson(1001, '手机号码格式不正确');
        }

        $bindSrv = new BonusBindService;

        if ($bindInfo = $bindSrv->getBindInfoByOpenid($weixinOpenId)) {
            if ($bindInfo['mobile'] == $mobile) {
                $this->echoJson(0, 'OK');
            }
            $this->echoJson(1002, '该微信绑定了其他手机号');
        }

        $res = (new BonusBindService)->bindUser($weixinOpenId, $mobile);
        if ($res) $this->echoJson(0, 'OK');
        $this->echoJson(1000, '绑定失败');
    }

    /**
     * 发券接口
     * 每个券组只能发一次
     */
    public function acquireDiscountUnique()
    {
        try {

            $userInfo = $this->getUserByToken();
            if (!$userInfo) {
                throw new \Exception("获取用户信息失败", 1001);
            }

            if ($userInfo['id']) {

                $res = ApiService::rpc('o2o', 'discounts/acquireDiscount', [
                    'userId' => $userInfo['id'],
                    'discountGroupId' => $this->req['discountGroupId'],
                    'token' => "GAME_{$userInfo['id']}_{$this->req['discountGroupId']}",
                ]);
                if ($res['isAcquired']) throw new \Exception("已领过", 90004);

                if (!$res) throw new \Exception("领券失败", 1000);

            } else {

                $res = DiscountCenterService::acquireSparowDiscount($userInfo['mobile'], $this->req['discountGroupId']);

                if (!$res || $res['code']) {
                    throw new \Exception("领券失败", 1000);
                }
                $res = $res['data'];
            }

        } catch (\Exception $e) {

            $this->echoJson($e->getCode(), $e->getMessage());

        }
        $this->echoJson(0, 'OK', $res);
    }

    /**
     * 替换券
     */
    public function replaceDiscountUnique()
    {

        try {

            $userInfo = $this->getUserByToken();
            if (!$userInfo) {
                throw new \Exception("获取用户信息失败", 1001);
            }

            if ($userInfo['id']) {

                $res = ApiService::rpc('o2o', 'discounts/replaceDiscount', [
                    'userId' => $userInfo['id'],
                    'primaryType' => 1,
                    'discountGroupId' => $this->req['discountGroupId'],
                    'primaryKey' => "GAME_{$userInfo['id']}_{$this->req['replacedGroupId']}",
                    'token' => "GAME_{$userInfo['id']}_{$this->req['discountGroupId']}",
                ]);

                if (!$res) {
                    $data = ApiService::getErrorData();
                    if ($data['applicationCode'] == 30001) {
                        throw new \Exception("原券已使用", 90003);
                    }

                    if ($data['applicationCode'] == 30002) {
                        throw new \Exception("已领过", 90004);
                    }
                    throw new \Exception("领券失败", 1000);
                }

            } else {

                $res = DiscountCenterService::replaceSparowDiscount($userInfo['mobile'], $this->req['discountGroupId'], $this->req['replacedGroupId']);

                if (!$res || $res['code']) throw new \Exception("领券失败", 1000);

                $res = $res['data'];

            }

        } catch (\Exception $e) {
            $this->echoJson($e->getCode(), $e->getMessage());
        }
        $this->echoJson(0, 'OK', $res);
    }

    /**
     * 排行榜接口
     */
    public function rankList()
    {
        $userInfo = $this->getUserByToken();

        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $rankId = $this->req['rankId'];
        $res = \core\service\rank\RankService::getRank($rankId, $userId);

        $uids = [];
        foreach ($res['list'] as $k => $v) {
            $uids[] = $v['userId'];
        }
        $userInfos = (new \core\service\UserService)->getUserInfoListByID($uids);
        foreach ($res['list'] as $k => $v) {
            $res['list'][$k]['mobile'] = substr_replace($userInfos[$v['userId']]['mobile'],'****', 3, 4);
        }

        $this->echoJson(0, 'OK', $res);

    }

    public function getOpenIdViaMobile()
    {
        $mobile = $this->req['mobile'];
        if (empty($mobile)) $this->echoJson(1001, '参数错误');
        $res = (new BonusBindService)->getBindInfoByMobile($mobile);
        foreach ($res as $i => $v) {
            $res[$i]['openId'] = $v['openid'];
        }
        $this->echoJson(0, 'OK', $res);
    }

    /**
     * 从o2o获取实时投资年化
     * 可选功能：触发宝箱
     */
    public function getDealMoney()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $rankId = $this->req['rankId'];

        if (empty($userId)) {
            return $this->echoJson(1001, "获取用户信息失败");
        }

        $data = ApiService::rpc('o2o', 'rank/getUserScore', [
            'userId' => $userId,
            'rankId' => $rankId,
        ]);

        if (ApiService::hasError()) {
            return $this->echoJson(ApiService::getErrorCode(), ApiService::getErrorMsg());
        }

        // 是否需要触发宝箱机会，兼容O2O排行榜不能触发，临时方案，后续不要复用
        $isTrigger = isset($this->req['isTrigger']) ? $this->req['isTrigger'] : false;
        if ($isTrigger && $data > 0) {
            $triggerData = urldecode($this->req['triggerData']);// 10000:key1|20000:key2
            $triggerData = explode('|', $triggerData);
            $code = $this->req['code'];
            $token = '';
            $rds = \SiteApp::init()->dataCache->getRedisInstance();

            foreach ($triggerData as $i => $item) {
                list($m, $idx) = explode(':', $triggerData[$i]);
                if ($data >= $m) {// 触发
                    $index = $idx;
                    $token = "{$userId}.{$m}";
                    $key = "SPAROW.PUSH.{$token}";
                    if (!$rds->get($key)) {
                        $srv = new SparowService($code);
                        if ($srv->gamePushTrigger($userId, $index, $token)) {
                            $rds->setex($key, 86400, 1);
                        }
                    }
                }
            }
        }

        return $this->echoJson(0, 'OK', $data);
    }

    /**
     * 从o2o获取中奖码
     */
    public function getAwardCode()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $gameId = $this->req['gameId'];

        if (empty($userId)) {
            return $this->echoJson(1001, "获取用户信息失败");
        }

        $data = ApiService::rpc('o2o', 'game/getUserGameCode', [
            'userId' => $userId,
            'gameId' => $gameId,
        ]);

        if (ApiService::hasError()) {
            return $this->echoJson(ApiService::getErrorCode(), ApiService::getErrorMsg());
        }
        return $this->echoJson(0, 'OK', $data ?: []);
    }

    public function checkEnterprise()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;

        if (empty($userId)) {
            return $this->echoJson(1001, "获取用户信息失败");
        }

        if (\core\dao\UserModel::instance()->isEnterpriseUser($userId)) {
            return $this->echoJson(30004, "仅限普通用户");
        }
        return $this->echoJson(0, "OK");

    }

    public function checkBwList()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $key = $this->req['key'];

        $type = $this->req['type']; // b or w
        $checkNewUser = $this->req['checkNewUser'];
        if (!in_array($type, ['b', 'w'])) $this->echoJson(10001, '参数错误');
        if (!in_array($checkNewUser, [1, 0])) $this->echoJson(10001, '参数错误');
        if (empty($key)) $this->echoJson(10001, '参数错误');

        if (empty($userId)) {
            if ($checkNewUser) $this->echoJson(20001, 'check failed');
            else $this->echoJson(0, 'OK');
        }

        if (\core\service\BwlistService::inList($key, $userId)) {
            $ret = $type == 'w' ? true : false;
        } else {
            $ret = $type == 'b' ? true : false;
        }
        if ($ret) $this->echoJson(0, 'OK');
        $this->echoJson(20001, 'check failed');

    }

    /**
     * 发红包接口
     * 支持新老用户、按照活动code是否唯一
     */
    public function acqBonus()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $mobile = $userInfo['mobile'];
        $ruleId = $this->req['ruleId'];
        $isGroup = $this->req['isGroup'];
        $orderId = $this->req['orderId'];
        $code = isset($this->req['code']) ? $this->req['code'] : '';
        $isUnique = isset($this->req['isUnique']) ? $this->req['isUnique'] : 0;
        $isNewAllow = isset($this->req['isNewAllow']) ? $this->req['isNewAllow'] : 0;

        if (empty($ruleId) || empty($orderId)) {
            $this->echoJson(10000, '参数有误');
        }

        if (empty($userId) && empty($mobile)) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        if (!$isNewAllow && empty($userId)) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        if ($isUnique) {
            if (empty($code)) $this->echoJson(10003, '参数有误');
            $token = "{$code}.{$ruleId}." . ($userId > 0 ? $userId : $mobile);
        } else {
            $token = $orderId;
        }

        if ($isGroup) {
            if (empty($userId)) {
                $this->echoJson(10002, '参数有误');
            }
            $templateId = $this->req['templateId'] ?: 0;
            $sn = \core\service\bonus\RpcService::acquireBonusRule($ruleId, $userId, '', $token, 39, $templateId, $code);
            if ($sn) {
                $link = app_conf('BONUS_GROUP_GRAB_URL') . '?sn=' . $sn;
                $this->echoJson(0, 'ok', ['link' => $link]);
            }
        } else {
            $res = \core\service\bonus\RpcService::acquireBonusRule($ruleId, $userId, $mobile, $token, 47, 0, $code);
            if ($res) $this->echoJson(0, 'ok');
        }
        $this->echoJson(10000, 'sys error');

    }

    /**
     * 发礼券
     */
    public function acqCoupon()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $mobile = $userInfo['mobile'];

        $couponId = $this->req['couponId'];
        $orderId = $this->req['orderId'];
        $code = isset($this->req['code']) ? $this->req['code'] : '';
        $isUnique = isset($this->req['isUnique']) ? $this->req['isUnique'] : 0;
        $prizeLogId = isset($this->req['prizeLogId']) ? $this->req['prizeLogId'] : 0;
        $isNewAllow = isset($this->req['isNewAllow']) ? $this->req['isNewAllow'] : 0;

        if (empty($couponId) || empty($orderId)) {
            $this->echoJson(10000, '参数有误');
        }

        if (empty($userId) && empty($mobile)) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        if (!$isNewAllow && empty($userId)) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        if ($isUnique) {
            if (empty($code)) $this->echoJson(10003, '参数有误');
            $token = "{$code}.{$couponId}." . ($userId > 0 ? $userId : $mobile);
            $prizeLogId = 0;
        } else {
            $token = $orderId;
        }

        if ($userId) {
            $res = (new \core\service\O2OService)->acquireCoupons($userId, $couponId, $token);
        } else {
            $res = \core\service\marketing\DiscountCenterService::acquireSparowCoupon($mobile, $couponId, $prizeLogId);
        }

        if ($res) $this->echoJson(0, 'ok', $res);
        $this->echoJson(10000, 'sys error');
    }

    /**
     * 发投资券
     */
    public function acqDiscount()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $mobile = $userInfo['mobile'];

        $discountId = $this->req['discountId'];
        $orderId = $this->req['orderId'];
        $code = isset($this->req['code']) ? $this->req['code'] : '';
        $isUnique = isset($this->req['isUnique']) ? $this->req['isUnique'] : 0;
        $prizeLogId = isset($this->req['prizeLogId']) ? $this->req['prizeLogId'] : 0;
        $isNewAllow = isset($this->req['isNewAllow']) ? $this->req['isNewAllow'] : 0;

        if (empty($discountId) || empty($orderId)) {
            $this->echoJson(10000, '参数有误');
        }

        if (empty($userId) && empty($mobile)) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        if (!$isNewAllow && empty($userId)) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        if ($isUnique) {
            if (empty($code)) $this->echoJson(10003, '参数有误');
            $token = "{$code}.{$discountId}." . ($userId > 0 ? $userId : $mobile);
            $prizeLogId = 0;
        } else {
            $token = $orderId;
        }

        if ($userId) {
            $res = ApiService::rpc('o2o', 'discounts/acquireDiscount', [
                'userId' => $userId,
                'discountGroupId' => $discountId,
                'token' => $token,
            ]);
        } else {
            $res = \core\service\marketing\DiscountCenterService::acquireSparowDiscount($mobile, $discountId, $prizeLogId);
        }

        if ($res) $this->echoJson(0, 'ok', $res);
        $this->echoJson(10000, 'sys error');
    }

    public function hasLoan()
    {
        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        if (empty($userId)) return $this->echoJson(10001, '获取用户信息失败');

        $hasLoan = (new UserService)->hasLoan($userId);

        return $this->echoJson(0, 'OK', $hasLoan);
    }

    /**
     * 按照WAP登录处理
     */
    public function login()
    {
        try {

            $mobile = $this->req['mobile'];
            $password = $this->req['password'];
            $serverHttpOs = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "";
            RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_API,Risk::getDevice($serverHttpOs))->check($data,Risk::SYNC);

            $logRegLoginService = new LogRegLoginService();
            $userService = new UserService();
            // 调用oauth接口进行登录验证
            $result = $userService->apiNewLogin(
                $mobile,
                $password,
                false,
                UserTokenService::LOGIN_FROM_WX_WAP,
                'cn'
            );

            if ($result['success'] !== true) {
                // 登录失败则向频次险种中插入记录
                if (!empty($result['code']) && $result['code'] == '20007') {
                    $this->setErr('ERR_ENTERPRISE_ABANDON');
                }

                if (!empty($result['code']) && $result['code'] == '-33') {
                    $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
                }

                if ($result['code'] = '20003' || $result['code'] = '20004') {
                    // 如果超过限制，则提示需要填写验证码
                    $this->setErr('ERR_VERIFY', "用户名或密码错误");
                } else {
                    // 未超过限制泽提示登录失败
                    $this->setErr('ERR_AUTH_FAIL');
                }

            } else {

                // 记录用户登录站点
                $userTrackService = new UserTrackService();
                $userTrackService->setLoginSite($result['user_id'], 1);

                $logRegLoginService->insert($result['user_name'], $result['user_id'], 0, 1, 2);

            }

            RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API)->notify(array('userId'=>$result['user_id']));
            $token = $result['code'];
            // // 调用oauth接口获取用户信息
            // $info = $userService->getUserByCode($token);

            // if ($info['code']) {
            //     // 获取oauth用户信息失败
            //     $this->setErr('ERR_GET_USER_FAIL');
            // }

            // if ($info['status'] == 0) {
            //     // 获取本地用户数据失败
            //     $this->setErr('ERR_LOGIN_FAIL');
            // }

            // $jsonData = array_merge(array("token"=>$token, 'tokenExpireTime' => (time() + UserTokenService::API_TOKEN_EXPIRE)),$this->getRetUserInfo($info['user']));

        } catch (\Exception $e) {
            $this->echoJson($e->getCode(), $e->getMessage());
        }
        $this->echoJson(0, 'OK', [
            'token' => $token,
            'tokenExpire' => UserTokenService::API_TOKEN_EXPIRE,
        ]);
    }

}
