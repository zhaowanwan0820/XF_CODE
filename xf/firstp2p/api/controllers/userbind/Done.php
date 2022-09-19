<?php
/**
 * 第三方用户完成授权(绑定)接口
 */

namespace api\controllers\userbind;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Done extends AppBaseAction {
    public function init (){
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            "mobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            "code" => array("filter" => "reg", "message" => 'ERR_SIGNUP_CODE', "option" => array("regexp" => "~^\d{6}$~")),
            "bind_data" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            "client_bind_sign" => array("filter" => "string"),
            "euid" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke()
    {
        $data = $this->form->data;

        $mobile = $data['mobile'];
        $code = $data['code'];
        $euid = trim($data['euid']);
        $bindData = json_decode(stripslashes($data['bind_data']), true);

        if(empty($bindData['checkMobile'])){
            $bindData['openBindData']['thirdUserInfo']['mobile'] = $mobile;
        }

        $scode = $this->rpc->local("MobileCodeService\getMobilePhoneTimeVcode", array($mobile,180,0));
        //校验验证码
        if($scode != $code){
            $this->setErr('ERR_SIGNUP_CODE');
            Logger::error("验证码错误, mobile:{$mobile}, code:{$code}");
            return false;
        }

        if (!empty($euid)) {
            $bindData['euid'] = $euid;
        }

        //没绑定过，也没有匹配到用户的先创建p2p用户
        if(empty($bindData['isUserBind']) && empty($bindData['isp2pUser'])){
            //当第三方没有用户手机号时，手机号时用户自己填的，这时需要再次判断用户是否是p2p用户
            $userInfo = $this->rpc->local('UserService\getByMobile', array($mobile));
            if(!empty($userInfo['id'])){
                $bindData['p2pUserId'] = $userInfo['id'];
            }else{
                $creatUserRet = $this->rpc->local("UserBindService\bindUserRegist", array($bindData));
                if ($creatUserRet['code']) {
                    if($creatUserRet['code'] == 2){
                        $this->setErr('ERR_SIGNUP_PHONE_UNIQUE', '手机号码已经被占用');
                    }else{
                        $this->setErr('ERR_SIGNUP', '创建登录用户失败');
                    }
                    Logger::error("创建用户失败, 输入:" . json_encode($bindData) . " , 输出: " . json_encode($creatUserRet));
                    return false;
                }else{
                    $bindData['p2pUserId'] = $creatUserRet['data']['user_id'];
                    // $this->rpc->local('AdunionDealService\triggerAdRecord', array($bindData['p2pUserId'], 1,0,0,0,0,$bindData['openBindData']['appInfo']['inviteCode'],$euid));
                }
            }
        }

        $linkCoupon = '';
        //没有绑定过，执行绑定
        if(empty($bindData['isUserBind'])){
            $saveBindRet = $this->rpc->local("UserBindService\doOpenUserBind", array($bindData));
            if (!$saveBindRet['code']) {//增加统计
                $linkCoupon = $bindData['openBindData']['appInfo']['inviteCode'];
                // $this->rpc->local('AdunionDealService\triggerAdRecord', array($bindData['p2pUserId'], 1,0,0,0,0,$linkCoupon,$euid));
            }else{
                $this->setErr('ERR_AUTH_FAIL', '授权绑定失败');
                return false;
            }
        }
        //取用户信息，完成授权
        $userInfo = $this->rpc->local("UserService\getUserByUserId", array($bindData['p2pUserId']));
        if(empty($userInfo)){
            $this->setErr('ERR_AUTH_FAIL', '绑定用户不存在');
            return false;
        }
        $bindSign = empty($data['client_bind_sign']) ? array() : unserialize($data['client_bind_sign']);
        $bindSign[] = $bindData['cookBindSign'];
        $bindSign = array_splice(array_unique($bindSign), -4);

        $token = $this->rpc->local("UserTokenService\genAppToken", array($bindData['p2pUserId']));

        $retUserInfo = array_merge(array("token"=>$token),$this->getRetUserInfo($userInfo));

        $this->json_data = array(
            "userInfo" => $retUserInfo,
            "bindSign" => serialize($bindSign),
            "link_coupon" => $linkCoupon,
        );

        return true;
    }

}
