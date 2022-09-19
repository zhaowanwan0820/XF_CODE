<?php

namespace core\service;

use libs\utils\Logger;
use libs\utils\Site;
use core\service\user\WebBO;
use core\service\PaymentService;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\UserTagService;
use core\service\UserService;

class UserBindService extends BaseService {

    const PAYMENT_REGIST = 1;
    const BIND_BINK_CARD = 2;

    public static $sBindResetPwd = "THIRD_AUTO_REG_RESETPWD";

    private function checkParams($params) {
        $clientId = trim($params['client_id']);
        if (!preg_match('~[a-zA-Z0-9]{10,32}~', $clientId)) {
            return dataPack(1, '无效的client_id');
        }

        $clientToken = trim($params['client_token']);
        if (!preg_match('~[a-zA-Z0-9]{10,32}~', $clientToken)) {
            return dataPack(1, '无效的client_token');
        }

        $clientTime = trim($params['timestamp']);
        if (!preg_match('~\d{10}~', $clientTime)) {
            return dataPack(1, '无效的timestamp');
        }

        if (abs(time() - $clientTime) > 10 * 60) {
            return dataPack(1, '无效的timestamp');
        }

        $clientSign = trim($params['sign']);
        if (!preg_match('~[a-zA-Z0-9]{32}~', $clientSign)) {
            return dataPack(1, '无效的sign');
        }

        return dataPack(0);
    }

    private function openUserBind($params, $loginUser, $options) {
        $rpcParam = array('params' => $params, 'loginUser' => $loginUser, 'options' => $options);
        Logger::info(__CLASS__ . ' ' .__FUNCTION__ . ' rpc open, param : ' . json_encode($rpcParam));

        $request  = new SimpleRequestBase();
        $request->setParamArray($rpcParam);

        try {
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\AuthorizationBind',
                 'method' => 'getUserBind',
                 'args' => $request,
            ));
        }catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' rpc open error, msg:' . $e->getMessage());
            return dataPack(1, '与OPEN RPC 失败');
        }

        $rpcRes = $response->rpcRes;
        Logger::info(__CLASS__ . ' ' .__FUNCTION__ . ' rpc open, response : ' . json_encode($rpcRes));

        return dataPack($rpcRes['code'], $rpcRes['msg'], $rpcRes['data']);
    }

    private function getCookieBindSign($thirdUserInfo) {
        return md5('open_id_' . $thirdUserInfo['openId'] . '_bind_firstp2p');
    }

    private function getFirstp2pUserByThirdInfo($thirdUserInfo) {
        if (empty($thirdUserInfo['mobile']) && empty($thirdUserInfo['idno'])) {
            return array();
        }

        $userService = new \core\service\UserService();
        $userInfo = $userService->getUserByMobileORIdno($thirdUserInfo['mobile'], $thirdUserInfo['idno']);

        Logger::info("检查是否是网信用户, 输入: " . json_encode($thirdUserInfo) . " , 结果: " . json_encode($userInfo));
        return empty($userInfo) ? array() : $userInfo;
    }

    private function getCheckMobile($firstp2pUser, $thirdUserInfo, &$isIdentify) {
        $dataHash = $this->hashP2PUserInfo($firstp2pUser);
        if (!empty($thirdUserInfo['idno']) && !empty($dataHash[$thirdUserInfo['idno']])) { //身份证号找到了用户
            return $dataHash[$thirdUserInfo['idno']]['mobile'];
        }

        if (!empty($thirdUserInfo['mobile']) && !empty($dataHash[$thirdUserInfo['mobile']])) {//手机号找到了用户
            $userInfo = $dataHash[$thirdUserInfo['mobile']];

            if (empty($userInfo['idno'])) {
                $isIdentify = false;
            }

            if (!empty($userInfo['idno']) && !empty($thirdUserInfo['idno']) && $userInfo['idno'] != $thirdUserInfo['idno']) {
                return false;
            }
            return $thirdUserInfo['mobile'];
        }

        return $thirdUserInfo['mobile']; //都没有找到
    }

    private function getBindUserId($firstp2pUser, $thirdUserInfo) {
        $dataHash = $this->hashP2PUserInfo($firstp2pUser);
        if (!empty($thirdUserInfo['idno'])) {
            if (!empty($dataHash[$thirdUserInfo['idno']])) {
                return $dataHash[$thirdUserInfo['idno']]['id'];
            }
        }

        if (!empty($thirdUserInfo['mobile'])) {
            if (!empty($dataHash[$thirdUserInfo['mobile']])) {
                return $dataHash[$thirdUserInfo['mobile']]['id'];
            }
        }

        $current = current($firstp2pUser);
        return $current['id'];
    }

    private function dealUnbindData($params, $loginUser, $options, $openBindData) {
        $thirdUserInfo = $openBindData['thirdUserInfo'];
        $retData = array(
            'isUserBind'    => false,
            'openBindData'  => $openBindData,
            'checkMobile'   => $thirdUserInfo['mobile'],
            'cookBindSign'  => $this->getCookieBindSign($openBindData['thirdUserInfo']),
        );

        $firstp2pUser  = $this->getFirstp2pUserByThirdInfo($thirdUserInfo);
        $retData['isp2pUser'] = !empty($firstp2pUser);
        if ($retData['isp2pUser']) {
            $isIdentify = true;
            $retData['checkMobile'] = $this->getCheckMobile($firstp2pUser, $thirdUserInfo, $isIdentify);
            if (false == $retData['checkMobile']) {
                return dataPack(100, '身份证信息不一致，若有疑问请联系平台客服:' . $GLOBALS['sys_config']['SHOP_TEL']);
            }
            $retData['p2pUserId'] = $this->getBindUserId($firstp2pUser, $thirdUserInfo);
            $retData['isIdentify'] = $isIdentify;
        }

        $retData['hasLoginUser'] = !empty($loginUser['id']);
        if ($retData['hasLoginUser']) {
            $loginUserId = $loginUser['id'];
            $retData['loginUserId']   = $loginUserId;
            $retData['isLoginUserBind'] = !empty($openBindData['bindUserInfo'][$loginUserId]);
        }

        return dataPack(0, '', $retData);
    }

    private function checkIsNormal($cookBindSign, $options, $appInfo) {
        $setParams = (array) json_decode($appInfo['setParams'], true);
        if ($setParams['AnBindNotCheckCookie']) {
            return true;
        }

        //第二次请求,已经绑定,如果需要手机验证，去掉下面注释
        return in_array($cookBindSign, $options['bind_sign']);
    }

    private function getFirstp2pUserByP2PUserId($userId) {
        $userService = new \core\service\UserService();
        $userInfo = $userService->getUser($userId);

        Logger::info("检查是否是网信用户, 输入: {$userId}, 结果: " . json_encode($userInfo));
        return empty($userInfo) ? array() : $userInfo->getRow();
    }

    private function dealBindData($params, $loginUser, $options, $openBindData) {
        $thirdOpenId  = $openBindData['thirdUserInfo']['openId'];
        $p2pUserId    = $openBindData['bindUserInfo'][$thirdOpenId]['userId'];
        $cookBindSign = $this->getCookieBindSign($openBindData['thirdUserInfo']);

        $firstp2pUser = $this->getFirstp2pUserByP2PUserId($p2pUserId);
        if (empty($firstp2pUser)) {
            Logger::error("找不到网信的用户, 输入: {$p2pUserId}, 结果: " . json_encode($firstp2pUser));
            return dataPack(1, '找不到已经关联的用户');
        }

        $retData = array(
            'isUserBind'    => true,
            'openBindData'  => $openBindData,
            'p2pUserId'     => $p2pUserId,
            'dataIsNormal'  => $this->checkIsNormal($cookBindSign, $options, $openBindData['appInfo']),
            'checkMobile'   => $firstp2pUser['mobile'],
            'hasLoginUser'  => !empty($loginUser['id']),
            'cookBindSign'  => $cookBindSign,
        );

        if ($retData['hasLoginUser']) {
            $loginUserId = $loginUser['id'];
            $retData['loginUserId']   = $loginUserId;
            $retData['isLoginUserBind'] = !empty($openBindData['bindUserInfo'][$loginUserId]);
        }

        return dataPack(0, '', $retData);
    }

    public function checkUserBind($params, $loginUser, $options) {
        $paramCheckRes = $this->checkParams($params);
        if (0 != $paramCheckRes['code']) {
            return $paramCheckRes;
        }

        $openBindRes = $this->openUserBind($params, $loginUser, $options);
        if (0 != $openBindRes['code']) {
            return $openBindRes;
        }

        $openBindData = $this->hashOpenBindInfo($openBindRes['data']);
        $thirdOpenId  = $openBindData['thirdUserInfo']['openId'];
        if (!empty($openBindData['bindUserInfo'][$thirdOpenId])) {
            return $this->dealBindData($params, $loginUser, $options, $openBindData);
        } else {
            return $this->dealUnbindData($params, $loginUser, $options, $openBindData);
        }
    }

    public function doOpenUserBind($rpcParam) {
        $setParams = (array) json_decode($rpcParam['openBindData']['appInfo']['setParams'], true);
        if ($setParams['BindType'] == self::PAYMENT_REGIST && isset($rpcParam['isIdentify']) && false === $rpcParam['isIdentify']) {
            $IdUserInfo['cardNo'] = $rpcParam['openBindData']['thirdUserInfo']['idno'];
            $IdUserInfo['realName'] = $rpcParam['openBindData']['thirdUserInfo']['realName'];
            if (PaymentService::REGISTER_FAILURE == (new PaymentService())->register($rpcParam['p2pUserId'], $IdUserInfo)) {
                Logger::info('AddThirdUcfRegistFail:'.$rpcParam['p2pUserId'].'|'.json_encode($IdUserInfo));
            }
        }

        Logger::info(__CLASS__ . ' ' .__FUNCTION__ . ' rpc open, param : ' . json_encode($rpcParam));
        $request  = new SimpleRequestBase();
        $request->setParamArray($rpcParam);

        try {
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\AuthorizationBind',
                 'method' => 'saveUserBind',
                 'args' => $request,
            ));
        }catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' rpc open error, msg:' . $e->getMessage());
            return dataPack(1, '与OPEN RPC 失败');
        }

        $rpcRes = $response->rpcRes;
        Logger::info(__CLASS__ . ' ' .__FUNCTION__ . ' rpc open, response : ' . json_encode($rpcRes));

        return dataPack($rpcRes['code'], $rpcRes['msg'], $rpcRes['data']);
    }

    private function _getSex($id) {
        $pos = strlen($id) == 15 ? 14 : 16;
        return substr($id, $pos, 1) % 2;
    }

    public function bindUserRegist($data) {
        $openData  = $data['openBindData'];
        $mobile    = $openData['thirdUserInfo']['mobile'];
        $siteId    = $openData['appInfo']['id'];
        $password  = substr(md5($mobile . mt_rand(1000000, 9999999)), 0, 10);
        $userInfo  = array('mobile' => $mobile, 'site_id' => $siteId, 'password' => $password);

        $idno = trim($openData['thirdUserInfo']['idno']);
        $realName = trimSpace($openData['thirdUserInfo']['realName']);
        $setParams = (array) json_decode($openData['appInfo']['setParams'], true);
        if (!empty($setParams['GroupId'])) {
            $userInfo['group_id'] = intval($setParams['GroupId']);
        }
        if (!empty($setParams['CouponLevelId'])) {
            $userInfo['coupon_level_id'] = intval($setParams['CouponLevelId']);
        }

        $appInfo = $openData['appInfo'];
        if (!empty($appInfo['inviteCode'])) {
            $userInfo['invite_code'] = $appInfo['inviteCode'];
        } else {
            $userInfo['invite_code'] = Site::getCoupon();
        }

        $euid = isset($data['euid']) && trim($data['euid']) ? trim($data['euid']) : Site::getEuid();
        if (!empty($euid)) {
            $userInfo['euid'] = $euid;
        }

        $webboObj  = new WebBO('web');
        $logUserInfo = $userInfo;
        unset($logUserInfo['password']);
        Logger::info('Third_InsertUserinfo:'.json_encode($logUserInfo));

        $registRes = $webboObj->insertInfo($userInfo, false);
        if (empty($registRes)) {
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' user info : ' . json_encode($userInfo) . ' regist res :' .  json_encode($registRes));
            return dataPack(1, '用户注册失败');
        }

        if (0 === $registRes['status']) {
            //添加可以改密码tag
            $this->addUserCanResetPwdTag($registRes['user_id']);

            $retData = (array) $registRes['data'];
            $retData['user_id'] = $registRes['user_id'];

            if ($setParams['BindType'] == self::PAYMENT_REGIST) { //支付开户 信分期
                $isId = !empty($idno) && !empty($realName);
                $IdUserInfo['cardNo'] = $idno;
                $IdUserInfo['realName'] = $realName;
                if ($isId && PaymentService::REGISTER_FAILURE == (new PaymentService())->register($registRes['user_id'], $IdUserInfo)) {
                    $IdUserInfo['cardNo'] = idnoFormat($idno);
                    Logger::info('ThirdPartyUcfRegisterFail:'.json_encode($IdUserInfo));
                }
            } elseif ($setParams['BindType'] == self::BIND_BINK_CARD) { //绑卡 邻友圈
                $bankInfo = (array) json_decode($openData['thirdUserInfo']['bankInfo'], true);
                $bankInfo = array_pop($bankInfo);
                if (!empty($bankInfo)) {
                    $paymentService = new PaymentService();
                    $result = $paymentService->getCardBinInfoByCardNo($bankInfo); //验证卡号的正确性
                    if (empty($result) || $result['respCode'] != '00') {
                        Logger::error(sprintf('查询card bin信息失败, 卡号:%s, 错误:%s', $bankInfo, json_encode($result)));
                    }

                    $paymentData = array(
                        'cardName'   => $realName,
                        'realName'   => $realName,
                        'cardNo'     => $idno,
                        'bankCardNo' => $result['cardNo'],
                        'bankName'   => $result['bankId'],
                        'source'     => 1,
                    );
                    $result = $paymentService->combineRegister($registRes['user_id'], $paymentData); //绑定卡号
                    if (empty($result) || $result['status'] != 1) {
                        Logger::error(sprintf('绑定银行卡失败, 用户:%s, 卡号:%s, 错误:%s', $registRes['user_id'], $bankInfo, json_encode($result)));
                    }
                }
            }

            return dataPack(0, '', $retData);
        }

        if (!empty($registRes['data']['mobile'])){
            Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' user info : ' . json_encode($userInfo) . ' regist res :' .  json_encode($registRes));
            return dataPack(2, '手机号已经被占用');
        }

        Logger::error(__CLASS__ . ' ' .__FUNCTION__ . ' user info : ' . json_encode($userInfo) . ' regist res :' .  json_encode($registRes));
        return dataPack(1, '用户注册失败');
    }

    private function hashOpenBindInfo($openBindData) {
        $bindUserInfo = array();
        foreach ((array)$openBindData['bindUserInfo'] as $item) {
            $bindUserInfo[$item['openId']] = $item;
            $bindUserInfo[$item['userId']] = $item;
        }
        $openBindData['bindUserInfo'] = $bindUserInfo;
        return $openBindData;
    }

    private function hashP2PUserInfo($firstp2pUser) {
        $dataHash = array();
        foreach ($firstp2pUser as $item) {
            $dataHash[$item['mobile']] = $item;
            $dataHash[$item['idno']] = $item;
        }
        return $dataHash;
    }

    public function getUnionToken($uid, $client_id, $scope) {
        $request  = new SimpleRequestBase();
        $request->setParamArray(
            array(
                'user_id' => $uid,
                'client_id' => $client_id,
                'scope' => $scope
            )
        );

        try {
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\Oauth',
                 'method' => 'getAccessToken',
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

    //给第三方授权自动注册用户添加可以修改密码tag
    public function addUserCanResetPwdTag($uid){
        $uid = intval($uid);
        if(empty($uid)){
            return false;
        }
        $oUserTagService = new UserTagService();
        return $oUserTagService->autoAddUserTag($uid, self::$sBindResetPwd, "第三方授权自动注册用户可改密码");
    }

    //删除用户身上的可以修改密码tag
    public function delUserCanResetPwdTag($uid){
        $uid = intval($uid);
        if(empty($uid)){
            return false;
        }
        $oUserTagService = new UserTagService();
        return $oUserTagService->delUserTagsByConstName($uid, self::$sBindResetPwd);
    }

    public function delUserCanResetPwdTagByMobile($mobile){
        $oUserService = new UserService();
        $uinfo = $oUserService->getByMobile($mobile);
        if($uinfo['site_id'] > 1){ //分站
            return $this->delUserCanResetPwdTag($uinfo['id']);
        }else{
            return true;
        }
    }

    //查询用户是否有可以修改密码tag
    public function isUserCanResetPwd($uid){
        $uid = intval($uid);
        if(empty($uid)){
            return false;
        }
        $oUserTagService = new UserTagService();
        $aTags = $oUserTagService->getTags($uid, self::$sBindResetPwd);
        $bRet = false;
        foreach($aTags as $one){
            if($one['const_name'] == self::$sBindResetPwd){
                $bRet = ture;
                break;
            }
        }
        return $bRet;
    }

    public function isUserCanResetPwdByMobile($mobile){
        $oUserService = new UserService();
        $uinfo = $oUserService->getByMobile($mobile);
        if($uinfo['site_id'] > 1){ //分站
            return $this->isUserCanResetPwd($uinfo['id']);
        }else{
            return false;
        }
    }
}
