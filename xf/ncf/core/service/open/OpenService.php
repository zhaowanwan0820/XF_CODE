<?php

namespace core\service\open;

use libs\web\Open;
use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Open\RequestGetList;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\AdunionDealService;
use core\service\CouponBindService;
use core\service\UserReservationService;
use core\service\dealload\DealLoadService;
use core\service\duotou\DtInvestNumService;
use core\service\o2o\CouponService;
use core\service\BaseService;
use core\service\conf\ApiConfService;
use core\service\user\UserService;

class OpenService extends BaseService {

    private static $funcMap = array(
        'openSiteConf' => array('domain'),//取分站的配置信息
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError($name.' method not exist', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }

        return self::rpc('ncfwx', 'ncfph/'.$name, $args);
    }

    //文章调用标签接口
    public function getArticletag($siteId, $type, $cnt, $title) {
        $request  = new SimpleRequestBase();
        $request->setParamArray(array(
            'siteId' => $siteId,
            'type' => $type,
            'cnt' =>  $cnt,
            'title' => $title,
        ));

        try {
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\OpenArticle',
                 'method' => 'getArticleTagList',
                 'args' => $request,
            ));
        } catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' rpc error, msg:' . $e->getMessage());
        }
        if ($response) {
            return $response;
        } else {
            return false;
        }
    }

    //文章列表
    public function getArticleLists($siteId, $type, $pageNo, $pageSize) {
        $pageNo = max(1, $pageNo);
        $pageSize <= 0 ? $pageSize=10 : $pageSize;
        $conds = "appId = :appId: AND status != 4";
        $binds = array('appId' => $siteId);
        if(!empty($type)){
            $conds .= ' AND category = :category:';
            $binds['category'] = intval($type);
        }

        $request = new RequestGetList();
        $request->setPageable(new Pageable($pageNo, $pageSize));
        $request->setCondition(array('conditions' => $conds, 'bind' => $binds, 'order'=> 'id DESC'));
        try {
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\OpenArticle',
                 'method' => 'getPagedList',
                 'args' => $request,
            ));
        } catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' rpc error, msg:' . $e->getMessage());
        }
        if (!empty($response)) {
            return array('page'=>$response->getPage(),'lists'=> $response->getList());
        } else {
            return false;
        }
    }

    //文章详情页
    public function getArticle($siteId, $id) {
        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array('id' => $id));
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\OpenArticle',
                 'method' => 'getOneByPKId',
                 'args' => $request,
            ));
        } catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' rpc error, msg:' . $e->getMessage());
        }
        if (!empty($response)) {
            return $response;
        } else {
            return false;
        }
    }

    //合作方发红包
    public function registSendBouns($mobile, $inviteCode, $userId) {
        if (!in_array(strtoupper($inviteCode), $GLOBALS['sys_config']['CHANNEL_REGIST_COUPON'])) {
            return true;
        }

        Logger::info(sprintf("第三方发红包, mobile:%s, inviteCode:%s, userId:%s", $mobile, $inviteCode, $userId));
        try {
            $params  = array('mobile' => $mobile, 'inviteCode' => $inviteCode, 'userId' => $userId);
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\OpenBill',
                 'method' => 'registSendBonus',
                 'args' => $request,
            ));
            if ($response->errno) {
                Logger::error(sprintf("注册发红包失败, 手机:%s, 邀请码:%s, 用户:%s", $mobile, $inviteCode, $userId));
            }
        } catch (\Exception $e) {
            Logger::error(sprintf("%s %s RPC ERROR, PARAMS:%s, MESSAGE:%s", __CLASS__ , __FUNCTION__, json_encode($params), $e->getMessage()));
        }
    }

    //gearman 异步任务
    public function doBackGroundTask($action, $params = array()) {
        $taskObj = new GTaskService();
        $openEvent = new \core\event\OpenEvent($action, $params);
        $taskId = $taskObj->doBackground($openEvent, 3);
        Logger::info(sprintf("add OpenEvent, action=%s, params:%s, taskId=%s", $action, json_encode($params, JSON_UNESCAPED_UNICODE), $taskId));
    }

    //根据邀请码获取分站的信息
    public function getAppInfoByInviteCode($inviteCode){
        $cacheKey = md5('inviteCode' . $inviteCode);
        $appInfo = \SiteApp::init()->cache->get($cacheKey);
        if (!empty($appInfo)) {
            return json_decode(gzdecode($appInfo), true);
        }

        try {
            $params  = array('inviteCode' => $inviteCode);
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\OpenApp',
                 'method' => 'getAppInfoByInviteCode',
                 'args' => $request,
            ));

            $appInfo = $response->data;
            if (!empty($appInfo)) {
                \SiteApp::init()->cache->set($cacheKey, gzencode(json_encode($appInfo), 6), 5 * 60);
            }

            return $appInfo;
        } catch (\Exception $e) {
            Logger::error(sprintf("getAppInfoByInviteCode根据邀请码获取分站的信息失败:%s,%s,%s,%s", __CLASS__ , __FUNCTION__, json_encode($params), $e->getMessage()));
        }

        return null;
    }

    //根据邀请码，拉取AppInfo的信息
    public function getAppInfoByUid($userId){
        $couponBindSrv = new CouponBindService();
        $bindInfo = $couponBindSrv->getByUserId($userId);
        if (empty($bindInfo['short_alias'])) {
            return false;
        }

        return $this->getAppInfoByInviteCode($bindInfo['short_alias']);
    }

    //获取euid
    public function getEuid($data) {
        $currEuid = $data['euid'];
        $userId = $data['userId'];
        if (empty($userId)) {
            return $currEuid;
        }

        if(!isset($data['euid_level'])) {
            $appInfo = $this->getAppInfoByUid($userId);
            $setParams = (array) json_decode($appInfo['setParams'], true);
            $data['euid_level'] = $setParams['euidLevel'];
        }

        $euidLevel = intval($data['euid_level']);
        if ($euidLevel <= 1) {
            return numTo32($userId);
        }

        if (empty($currEuid)) {
            $ads = new AdunionDealService();
            $currEuid = $ads->getEuidByUid($userId);
        }

        if (empty($currEuid)) {
            return numTo32($userId);
        }

        $euidSlice = explode('_', $currEuid);
        $euidNodes = array_slice($euidSlice, 0, $euidLevel - 1);
        $euidNodes[] = numTo32($userId);
        return implode('_', array_unique($euidNodes));
    }

    /**
    判断用户是否投资过
        1.p2p
        2.智多鑫
        3.随心约
    */
    public function isBid($userId)
    {
        do {
            if ((new DealLoadService())->isInvestByUserId($userId)) {
                return true;
            }
            if (!((new DtInvestNumService())->isFirstInvest($userId))) {
                return true;
            }
            if ((new UserReservationService())->isInvestByUserId($userId)) {
                return true;
            }
        } while (false);

        return false;
    }

    private function _num2word($num) {
        $map = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
        return $map[$num];
    }

    public function getDiscountBuyTag($data) {
        $money = intval($data['actMoney']);
        $level = intval($data['actType']);
        $desc = sprintf("优惠购%s阶%s分红包", $this->_num2word($level), $money);
        return ['name' => "DIS_LVL{$level}_{$money}", 'desc' => $desc];
    }

    //券码激活
    public function activeActivity($params) {
        if (!empty($params['ncf_mobile'])) {
            $userInfo = UserService::getUserByMobile($params['ncf_mobile'], 'id,idno,mobile');
            if (empty($userInfo)) {
                return ['errno' => 1, 'error' => '请先核对您输入的信息无误再激活！'];
            }

            $userInfo = $userInfo->_row;
            if (empty($params['ncf_idno']) || $params['ncf_idno'] != $userInfo['idno']) {
                return ['errno' => 1, 'error' => '请先核对您输入的信息无误再激活！'];
            }

            $params['userId'] = $userInfo['id'];
        }

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $result = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\OpenTicket',
                 'method' => 'activeActivity',
                 'args' => $request,
            ))->toArray();

            if ($result['errno']) { //业务层出现错误
                return $result;
            }

            $result = $result['data'];
            if ($result['actType'] > 1) { //打tag
                $tagInfo = $this->getDiscountBuyTag($result);
                $tagResult = (new \core\service\UserTagService())->autoAddUserTag($params['userId'], $tagInfo['name'] , $tagInfo['desc']);
                if (empty($tagResult)) {
                    return ['errno' => 1, 'error' => '激活失败, 请稍后重试'];
                } else {
                    if (!empty($result['couponId'])) {
                        $sendRes = CouponService::acquireDiscounts($result['uid'], $result['couponId'], sprintf('open-ticket-%s', $result['id']));
                        if (false === $sendRes) {
                            return ['errno' => 1, 'error' => '激活失败, 请稍后重试'];
                        }
                    }
                }
            }

            $appInfo = $result['appInfo'];
            $retData = array('siteId' => $appInfo['id']); //返回数据
            if ($result['actType'] <= 1) {
                if ($appInfo['usedWebDomain']) {
                    $retData['web_url'] = sprintf("http://%s/user/register?euid=%s", $appInfo['usedWebDomain'], $result['code']);
                }
                if ($appInfo['usedWapDomain']) {
                    $retData['wap_url'] = sprintf("http://%s/user/register?euid=%s", $appInfo['usedWapDomain'], $result['code']);
                }
            }
            return ['errno' => 0, 'data' => $retData];
        } catch (\Exception $e) {
            return ['errno' => 1, 'error' => '激活失败, 请稍后重试'];
        }
    }

    // 判断是否有发券活动
    public static function checkTicketOpen($appInfo, $ticketCode) {
        if (empty($appInfo)) {
            return false;
        }

        $setParams = (array) json_decode($appInfo['setParams'], true);
        return empty($setParams['couponExpire']) ? false : true;
    }

    //获取错误信息的文案
    public static function getTicketPack($status, $appInfo, $data = []) { // -1 无此编码 0，有效，1，已使用，2，过期，3，无效，4，已激活，5，已关闭
        $ticketStatus = array(
              0 => '券码正确, 可以注册',
              1 => '该链接已经被使用，请直接登录账号查看',
              2 => '活动已经结束，请您继续关注下次活动',
              3 => '该活动链接有误，请核实后重新激活',
         );

        $status = in_array($status, [-1, 5]) ? 3 : ($status == 4 ? 0 : $status);
        $setParams = (array) json_decode($appInfo['setParams'], true);
        //新用户红包暗绑邀请码, 页面提示文案
        if($setParams['isShowSuccPage']){
            $ticketStatus = array(
                0 => '券码正确, 可以注册',
                1 => '红包已领完',
                2 => '活动已结束',
                3 => '红包已领完',
            );
        }
        return ['status' => $status, 'msg' => $ticketStatus[$status], 'data' => $data];
    }

    // 检测活动编码是否正确
    public static function toCheckTicket($appInfo, $ticketCode){
        if (!self::checkTicketOpen($appInfo, $ticketCode)) {
            return self::getTicketPack(0, $appInfo);
        }

        if (empty($ticketCode)) {
            return self::getTicketPack(3, $appInfo);
        }

        $siteId = $appInfo['id'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if($redis) {
            $key = sprintf("ticket_siteId_%d_euid_%s", $siteId, $ticketCode);
            $status = $redis->get($key);
            if(!empty($status)) {
                return self::getTicketPack(1, $appInfo);
            }
        }

        $request = new SimpleRequestBase();
        $request->setParamArray(array('siteId' => $siteId, 'euid' => $ticketCode));
        $response = $GLOBALS['openbackRpc']->callByObject(array(
           'service'=>'NCFGroup\Open\Services\OpenTicket',
           'method'=>'checkTicket',
           'args'=>$request
        ));

        $data = $response->data;
        return (empty($data) || $data['actType'] > 1) ? self::getTicketPack(3, $appInfo) : self::getTicketPack($data['status'], $appInfo, $data);
    }

    //设置活动编码状态,redis存放10天
    public static function setTicketStatus($appInfo, $ticketCode, $userId){
        if (!self::checkTicketOpen($appInfo, $ticketCode) || empty($ticketCode)) {
            return true;
        }

        $siteId = $appInfo['id'];
        $key = sprintf("ticket_siteId_%d_euid_%s", $siteId, $ticketCode);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->setex($key, 10 * 24 * 3600, 1);

        date_default_timezone_set("RPC");
        $openService = new \core\service\OpenService();
        $openService->doBackGroundTask(\core\event\OpenEvent::USE_TICKET, array('euid' => $ticketCode, 'userId' => $userId, 'siteId' => $siteId, 'useTime' => time()));
    }

    public function getNewFzUrl($oldFzUrl)
    {
        $urlArr = array(
            'fq5r8y.ncfbaize.com' => 'fq5r8y.ncfwx.com',
            'fqngah.ncfbaize.com' => 'fqngah.ncfwx.com',
            'fqmh4e.ncfbaize.com' => 'fqmh4e.ncfwx.com',
            'fp7yr9.ncfbaize.com' => 'fp7yr9.ncfwx.com',
            'fp83v2.ncfbaize.com' => 'fp83v2.ncfwx.com',
            'fqj8ef.ncfbaize.com' => 'fqj8ef.ncfwx.com',
            'fqmdel.ncfbaize.com' => 'fqmdel.ncfwx.com',
            'fqk37q.ncfbaize.com' => 'fqk37q.ncfwx.com',
            'fpd5k6.ncfbaize.com' => 'fpd5k6.ncfwx.com',
            'fq9hk0.ncfbaize.com' => 'fq9hk0.ncfwx.com',
            'fp20bm.ncfbaize.com' => 'fp20bm.ncfwx.com',
            'fztvca.ncfbaize.com' => 'fztvca.ncfwx.com',
            'fqejb7.ncfbaize.com' => 'fqejb7.ncfwx.com',
            'fq3q18.ncfbaize.com' => 'fq3q18.ncfwx.com',
            'fqmnrf.ncfbaize.com' => 'fqmnrf.ncfwx.com',
            'fkttn1.ncfbaize.com' => 'fkttn1.ncfwx.com',
            'fp0ebm.ncfbaize.com' => 'fp0ebm.ncfwx.com',
            'fn6hfb.ncfbaize.com' => 'fn6hfb.ncfwx.com',
            'fqe3qs.ncfbaize.com' => 'fqe3qs.ncfwx.com',
            'fpzm5h.ncfbaize.com' => 'fpzm5h.ncfwx.com',
            'fjpln6.ncfbaize.com' => 'fjpln6.ncfwx.com',
            'fpznds.ncfbaize.com' => 'fpznds.ncfwx.com',
            'fqe2c2.ncfbaize.com' => 'fqe2c2.ncfwx.com',
            'fpttmt.ncfbaize.com' => 'fpttmt.ncfwx.com',
            'fp7pnq.ncfbaize.com' => 'fp7pnq.ncfwx.com',
            'fqylhu.ncfbaize.com' => 'fqylhu.ncfwx.com',
            'fq0eua.ncfbaize.com' => 'fq0eua.ncfwx.com',
            'fqf0bm.ncfbaize.com' => 'fqf0bm.ncfwx.com',
            'fznj4n.ncfbaize.com' => 'fznj4n.ncfwx.com',
            'fqkgmh.ncfbaize.com' => 'fqkgmh.ncfwx.com',
            'fqybds.ncfbaize.com' => 'fqybds.ncfwx.com',
            'fn0pht.ncfbaize.com' => 'fn0pht.ncfwx.com',
            'fqnufa.ncfbaize.com' => 'fqnufa.ncfwx.com',
            'fq49qr.ncfbaize.com' => 'fq49qr.ncfwx.com',
            'fp6szt.m.ncfbaize.com' => 'fp6sztm.ncfwx.com',
            'fpd5k6.m.ncfbaize.com' => 'fpd5k6m.ncfwx.com',
            'fpbyl8.m.ncfbaize.com' => 'fpbyl8m.ncfwx.com',
        );

        $returnUrl = '';
        $oldFzUrl = strtolower($oldFzUrl);
        if(isset($urlArr[$oldFzUrl])) {
            $returnUrl = $urlArr[$oldFzUrl];
        }
        return $returnUrl;
    }

    public function getTotalFzUrl($host){
        $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
        $url = $host;
        if(!empty($parseUrl['path'])){
            $url = $url . $parseUrl['path'];
        }
        if(!empty($parseUrl['query'])){
            $url = $url . '?' . $parseUrl['query'];
        }
        return $url;
    }

    public function getSiteConf($siteId, $name = '') {
        $return = [];

        if ($siteId > 1) { //分站逻辑
            $result = Open::getSiteConfBySiteId($siteId);
            if (empty($result)) {
                return $return;
            }

            foreach ($result['confInfo'] as $item) {
                if ($item['isEffect'] != 1 || !empty($name) && $name != $item['name']) { //取单一KEY
                    continue;
                }

                if ($item['confType'] == 1) {
                    $return['common'][] = ['name' => $item['name'], 'value' => $item['value']];
                } else {
                    $return['site'][] = ['name' => $item['name'], 'value' => $item['value']];
                }
            }
        } else { //主站逻辑
            $apiConfService = new ApiConfService;
            $result = \SiteApp::init()->dataCache->call($apiConfService, 'getApiConfBySiteId', array($siteId), 300);
            if (empty($result)) {
                return $return;
            }

            foreach ($result as $item) {
                if (!empty($name) && $name != $item['name']) { //取单一KEY
                    continue;
                }

                if ($item['conf_type'] == 1) {
                    $return['common'][] = ['name' => $item['name'], 'value' => $item['value']];
                } elseif ($item['site_id'] == $siteId) {
                    $return['site'][] = ['name' => $item['name'], 'value' => $item['value']];
                }
            }
        }

        return $return;
    }

    public static function getPreviewActivity($key) {
        if (empty($key)) {
            return [];
        }

        $result = \SiteApp::init()->dataCache->getRedisInstance()->get($key);
        return empty($result) ? [] : json_decode($result, true);
    }

}
