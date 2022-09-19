<?php
/**
 * Bind.php
 * Passport 回调入口
 *
 */
namespace web\controllers\passport;

use libs\web\Form;
use libs\utils\Logger;
use libs\utils\Risk;
use libs\utils\Block;
use web\controllers\BaseAction;
use core\service\user\PassportService;
use core\service\user\UserService;
use core\service\user\UserLoginService;
use core\service\user\LogRegLoginService;
use core\service\risk\RiskServiceFactory;
use core\service\coupon\CouponService;

class Bind extends BaseAction
{

    const IS_H5 = false;

    public function init()
    {
        $this->form = new Form('post');
        $this->form->rules = array(
            'code'=> array('filter'=>'string'),
            'mobile'=>array('filter'=>'string'),
        );
        if (!$this->form->validate()) {
            $this->errno = -3;
            $this->error = '参数验证错误';
            return;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        // 短信验证码校验
        if (\es_session::get('passportNeedVerify') || \es_session::get('localNeedVerify')) {
            if (empty($data['code']) || empty($data['mobile'])) {
                $this->errno = -2;
                $this->error = '非法的请求';
                return;
            }
            // 检测用户输入验证码错误信息
            $ip = get_client_ip();
            //最多验证4次
            $checkIpMinuteResult = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_IP', $ip,false);
            $checkPhoneMinuteResult = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_PHONE', $data['mobile'],false);
            if($checkIpMinuteResult === false || $checkPhoneMinuteResult ==false) {
                $this->errno = -1;
                $this->error = "短信验证错误次数太多，请稍后再试";
                return;
            }

            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($data['mobile']));
            if (empty($vcode) || $vcode != $data['code']) {
                setLog(array('restrict_vcode_verify'=>0));
                $this->errno = 1;
                $this->error = '短信校验错误';
                return;
            } else {
                \es_session::delete('passportNeedVerify');
                setLog(array('restrict_vcode_verify'=>1));
            }
        }

        $ppId = \es_session::get('ppId');
        try {
            if (!\es_session::get('localNeedVerify')) {
                $userInfo = PassportService::userBind($ppId);
            } else {
                $userInfo = UserService::getUserByMobile($data['mobile']);
            }
            $this->doLogin($userInfo);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->errno = -100;
        }
        return;
    }

    /**
     * web登录逻辑复制
     */
    private function doLogin($userInfo)
    {
        $userId = $userInfo['id'];
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert($userInfo['user_name'], $userId, 0, 1);

        //获取用户邀请码
        $couponLatest = CouponService::getCouponLatest($userId);
        $inviteCode = $couponLatest['coupon']['short_alias'];

        //euid如果非空，则进行track
        $isEuid = false;
        $cEuid = \es_cookie::get('euid');
        if(!empty($_GET['euid']) || !empty($cEuid)){
            $isEuid = true;
        }

        //用户行为追踪，根据配置邀请码，用户是否需要种 trackId
        $track_id = \es_session::get('track_id');
        if (!$track_id && (in_array(strtoupper($inviteCode), $GLOBALS['sys_config']['adunion_coupon']) || $isEuid)) {
            $track_id = hexdec(\libs\utils\Logger::getLogId());
            \es_session::set('track_id', $track_id);
            \es_session::set('track_on', 1);
        }

        RiskServiceFactory::instance(Risk::BC_LOGIN)->notify(array('userId'=>$userId));
        // 回跳URL
        $jumpUrl = get_login_gopreview();
        $userInfo = array('user_name' => $userInfo['user_name'], 'password' => $userInfo['user_pwd']);

        $ret = UserLoginService::doLoginOld($userInfo, $jumpUrl);
        if ($ret['code'] == 0) {
            if ($this->isModal()) {
                setcookie('modal_login_succ', 1, 0, '/', get_root_domain());
                return true;
            }
        } else {
            throw new \Exception($ret['msg']);
        }
        return true;
    }
}