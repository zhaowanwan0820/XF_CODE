<?php

/**
 * 登录接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use core\service\risk\RiskServiceFactory;
use core\service\user\UserTrackService;
use core\service\user\UserService;
use core\service\face\FaceService;
use libs\utils\Risk;

/**
 * 用户登陆接口
 */
class Login extends AppBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();

        $this->form = new Form('post');
        $this->form->rules = array(
            "account" => array(
                "filter" => "required",
                "message" => 'ERR_USERNAME_ILLEGAL'
            ),
            "password" => array(
                "filter" => "reg",
                "message" => 'ERR_PASSWORD_ILLEGAL',
                "option" => array("regexp" => "/^.{5,25}$/")
            ),
            "verify" => array("filter" => "string"),
            "country_code" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $device = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : '';

        // 检查账号是否被冻结
        $freeze = FaceService::checkFreeze($data['account']);
        if ($freeze) {
            $this->setErr('ERR_MANUAL_REASON', $freeze);
        }

        $ret = RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API, Risk::getDevice($device))
            ->check($data, Risk::SYNC);

        // 风控异常
        if ($ret === false) {
            if (!FaceService::isFaceSwitchOn(FaceService::TYPE_LOGIN) || $this->isWapCall()) {
                $this->setErr('ERR_MANUAL_REASON', '登录异常');
            }

            // 如果验证失败
            if (!FaceService::checkVerify($data['verify'])) {
                $this->setErr('ERR_FACE_VERIFY');
            }
        }

        // ip限制
        $this->ipIsLimited(true);

        $this->verifyCode();

        // 先检查是否需要校验验证码
        if (Block::check('LOGIN_USERNAME', $data['account'], true) === false) {
            if (!FaceService::isFaceSwitchOn(FaceService::TYPE_LOGIN) || $this->isWapCall()) {
                if (!$data['verify']) {
                    $this->setErr('ERR_VERIFY_EMPTY');
                }
            }

            // 3表示人脸识别
            if (!FaceService::checkVerify($data['verify'])) {
                $this->setErr('ERR_FACE_VERIFY');
            }
        }

        $country_code = !empty($data['country_code']) ? $data['country_code'] : "cn";
        // 5为普惠wap，4为普惠app
        $loginFrom = $this->isWapCall() ? 5 : 4;
        $result = UserService::login($data['account'], $data['password'], $country_code, $loginFrom);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
        }

        // 记录用户登录站点
        $userTrackService = new UserTrackService();
        // 普惠的登陆站点为100，这里先写死100
        $userTrackService->setLoginSite($result['uid'], 100);

        // 风控记录
        RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API)->notify(array('userId'=>$result['uid']));
        $this->json_data = $result;
    }

    // 图文验证码
    private function verifyCode() {
        $data = $this->form->data;

        // 校验验证码
        $redis = \SiteApp::init()->cache;
        $verify = $redis->get("verify_" . md5($data['account']));
        if (!$verify) {
            return true;
        }

        // 存在图文验证
        if (!$data['verify']) {
            $this->setErr('ERR_VERIFY_EMPTY');
        }  

        $redis->delete("verify_" . md5($data['account']));
        $data['verify'] = strtolower(trim($data['verify']));
        if ($verify != md5($data['verify'])) {
            $this->setErr('ERR_VERIFY_ILLEGAL');
        }

        return true;
    }

    // IP地址限制
    private function ipIsLimited($checkOnly = false) {
        $ip = get_real_ip();
        if ($ip != 'unknown') {
            if (Block::check('USER_LOGIN_IP_LIMIT', $ip, $checkOnly) === false) {
                $this->setErr('ERR_IP_LIMIT');
            }
        }
    }
}
