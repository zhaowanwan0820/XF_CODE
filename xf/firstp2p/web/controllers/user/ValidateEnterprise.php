<?php

/**
 * 新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;

class ValidateEnterprise extends BaseAction {

    public function init() {
    }

    public function invoke() {
        $checkResult = ['code' => 0, 'data' => []];
        $checkPassed = true;
        $form = $_POST;

        $verify = \es_session::get('verify');
        $captcha = $form['captcha'];
        if (empty($captcha)) {
            $checkResult['data'][] = ['field' => 'captcha', 'message' => '图形验证码不能为空'];
            $checkPassed = false;
        } else {
            if (md5($captcha) !== $verify) {
                $checkResult['data'][] = ['field' => 'captcha', 'message' => '图形验证码不正确'];
                $checkPassed = false;
            }
        }

        $service = new \core\service\EnterpriseService();
        // 企业用户登录名
        if (empty($form['user_name'])) {
            $checkResult['data'][] = ['field' => 'user_name', 'message' => '请输入4-20位字母、数字、下划线、横线，首位只能为字母'];
            $checkPassed = false;
        } else {
            if (!preg_match('/^([A-Za-z])[\w-]{3,19}$/', $form['user_name'])) {
                $checkResult['data'][] = ['field' => 'user_name', 'message' => '请输入4-20位字母、数字、下划线、横线，首位只能为字母'];
                $checkPassed = false;
            }
            if (!$service->canRegisterLoginName($form['user_name'])) {
                $checkResult['data'][] = ['field' => 'user_name', 'message' => '企业用户登录名已经存在'];
                $checkPassed = false;
            }
        }

        // 密码校验
        if (empty($form['password'])) {
            $checkResult['data'][] = ['field' => 'password', 'message' => '密码应为6-20位数字/字母/标点'];
            $checkPassed = false;
        } else {
            if (strlen($form['password']) < 6 || strlen($form['passord']) > 20) {
                $checkResult['data'][] = ['field' => 'password', 'message' => '密码应为6-20位数字/字母/标点'];
                $checkPassed = false;
            }
        }

        // 接收短信通知手机号码
        if (empty($form['sms_phone'])) {
            $checkResult['data'][] = ['field' => 'sms_phone', 'message' => '接收短信通知手机号码不能为空'];
            $checkPassed = false;
        } else {
            if (!empty($_REQUEST['sms_country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['is_show']){
               if (!preg_match("/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['regex']}/", $form['sms_phone'])) {
                    $checkResult['data'][] = ['field' => 'sms_phone', 'message' => '接收短信通知手机号码格式错误'];
                    $checkPassed = false;
               }
            } else {
                if (!preg_match('/^1[3456789]\d{9}$/', $form['sms_phone'])) {
                    $checkResult['data'][] = ['field' => 'sms_phone', 'message' => '接收短信通知手机号码应为7-11位数字'];
                    $checkPassed = false;
                }
            }

            /* 企业注册手机号不做唯一验证
            if (!$service->canRegisterPhone($form['sms_phone'])) {
                $checkResult['data'][] = ['field' => 'sms_phone', 'message' => '接收短信通知手机号码已经注册'];
                $checkPassed = false;
            }
            */
        }


        // 推荐人姓名和优惠码验证
        if (!empty($form['invite'])) {
            $invite_code = strtoupper($form['invite']);
            $coupon = $this->rpc->local('CouponService\checkCoupon', array($invite_code));
            if ($coupon === FALSE || $coupon['coupon_disable']) {
                $checkResult['data'][] = ['field' => 'invite', 'message' => $GLOBALS['lang']['COUPON_DISABLE']];
                $checkPassed = false;
            }

            if ($coupon['refer_user_id']) {
                $referUser = $this->rpc->local('UserService\getUserArray', array($coupon['refer_user_id'], 'mobile, real_name'));
                if (empty($referUser) || (!empty($form['inviter_name']) && $referUser['real_name'] != $form['inviter_name'])) {
                    $checkResult['data'][] = ['field' => 'invite', 'message' => '输入的邀请人姓名与邀请码不符，请核对后重新填写'];
                    $checkPassed = false;
                }
            }

        }

        if (!$checkPassed) {
            $checkResult['code'] = -1;
        }

        // 用户参数校验通过后记录session
        if ($checkResult['code'] == 0) {
            \es_session::set(sprintf('validEnterprise_%s', trim($form['sms_phone'])), 1);
        }
        echo json_encode($checkResult);
        exit;
    }
}
