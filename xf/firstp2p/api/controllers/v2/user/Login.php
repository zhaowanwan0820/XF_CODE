<?php

/**
 * 登录接口
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use core\service\LogRegLoginService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\risk\RiskServiceFactory;
use core\service\UserTrackService;
use libs\utils\Risk;
use libs\utils\Monitor;
use core\service\UserService;
use core\service\UserTokenService;
use core\service\darkmoon\DarkMoonService;
use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\WeiXinService;

class Login extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "account" => array("filter" => "required", "message" => 'ERR_USERNAME_ILLEGAL'),
            "password" => array("filter" => "reg", "message" => 'ERR_PASSWORD_ILLEGAL', "option" => array("regexp" => "/^.{5,25}$/")),
            "verify" => array("filter" => "reg", "message" => 'ERR_VERIFY_ILLEGAL', "option" => array("regexp" => "/^[0-9a-zA-Z]{0,4}$/", 'optional' => true)),
            'site_id' => array('filter' => "int", 'option' => array('optional' => true)),
            "country_code" => array("filter" => "string"),
            'wxId' => array('filter' => "string", 'option' => array('optional' => true)),
            'openId' => array('filter' => "string", 'option' => array('optional' => true)),
            'sign' => array('filter' => "string", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check($data,Risk::SYNC);

        $siteId = !empty($data['site_id']) ? $data['site_id'] : 1;
        $blackSiteList = app_conf('FORBIDDEN_LOGIN_SITEIDS');
        if ($blackSiteList) {
            $blackSiteList = explode(',', $blackSiteList);
            if ($blackSiteList && in_array($siteId, $blackSiteList)) {
                $this->setErr('ERR_LOGIN_FAIL', '请您前往网信APP客户端登录');
            }
        }

        $logRegLoginService = new LogRegLoginService();

        // 验证绑定签名
        $isBind = false;
        if (isset($data['wxId']) && isset($data['openId']) && isset($data['sign'])) {
            $isBind = true;
            $params = [
                'wxId' => $data['wxId'],
                'openId' => $data['openId'],
                'sign' => $data['sign'],
            ];
            //验证签名
            if(!SignatureLib::verify($params, WeiXinService::BIND_SALT)) {  //不通过
                $this->setErr('ERR_SIGNATURE_FAIL');
                return false;
            }
            // 检查绑定情况
            $bindCode = (new WeiXinService)->isBinded($data['openId'], $data['account'], $data['wxId']);
            if ($bindCode == WeiXinService::STATUS_BINDED_OTHER_USERID ||
                $bindCode == WeiXinService::STATUS_BINDED_OTHER_OPENID)
            {
                $jsonData['isBind'] = true;
                $jsonData['bindRes'] = $bindCode == WeiXinService::STATUS_BINDED_OTHER_OPENID ?
                                       WeiXinService::BIND_OTHER_OPENID :
                                       WeiXinService::BIND_OTHER_USERID;
                $this->json_data = $jsonData;
                return true;

            }
        }

        // 校验验证码
        if(!empty($data['verify'])) {
            $verify = \SiteApp::init()->cache->get("verify_" . md5($data['account']));
            \SiteApp::init()->cache->delete("verify_" . md5($data['account']));
            $data['verify'] = strtolower($data['verify']);
            if ($verify != md5($data['verify'])) {
                Monitor::add('LOGIN_FAIL');
                $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 2);
                $this->setErr('ERR_VERIFY_ILLEGAL');
            }
        }

        $country_code = !empty($data['country_code']) ? $data['country_code'] : "cn";
        // 调用oauth接口进行登录验证
        $result = $this->rpc->local("UserService\apiNewLogin", array(
            $data['account'],
            $data['password'],
            false,
            UserTokenService::LOGIN_FROM_WX_WAP,
            $country_code
        ));

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
            $userTrackService->setLoginSite($result['user_id'], $siteId);

            $logRegLoginService->insert($result['user_name'], $result['user_id'], 0, 1, 2);

            if ($isBind) {
                // 做微信用户绑定
                $bindRes = WeiXinService::BIND_FAILED;
                if ($bindCode == WeiXinService::STATUS_UNBIND) {
                    $taskId = (new GTaskService())->doBackground((new \core\event\WeixinBindEvent($data['wxId'], $data['openId'], $result['user_id'])), 20);
                    Logger::info(implode('|', [__METHOD__, $taskId, $data['wxId'], $data['openId'], $result['user_id']]));
                    if ($taskId) $bindRes = WeiXinService::BIND_SUCCESS;
                } else if ($bindCode == WeiXinService::STATUS_BINDED_SELF) {
                    $bindRes = WeiXinService::BIND_SUCCESS;
                }
            }
        }

        RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API)->notify(array('userId'=>$result['user_id']));
        $token = $result['code'];
        // 调用oauth接口获取用户信息
        $info = $this->rpc->local("UserService\getUserByCode", array($token));

        if ($info['code']) {
            // 获取oauth用户信息失败
            $this->setErr('ERR_GET_USER_FAIL');
        }

        if ($info['status'] == 0) {
            // 获取本地用户数据失败
            $this->setErr('ERR_LOGIN_FAIL');
        }

        $jsonData = array_merge(array("token"=>$token, 'tokenExpireTime' => (time() + UserTokenService::API_TOKEN_EXPIRE)),$this->getRetUserInfo($info['user']));
        if ($this->isWapCall()) {
            $jsonData['isBid'] = $this->isBid($result['user_id']);
        }
        $jsonData['isBind'] = false;
        if ($isBind) {
            $jsonData['isBind'] = true;
            $jsonData['bindRes'] = $bindRes;
        }

        $this->json_data = $jsonData;
        return true;
    }

}
