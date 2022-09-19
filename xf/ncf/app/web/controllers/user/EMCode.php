<?php
/**
 * 获取手机验证码 已登录用户
 * @author 长路<pengchanglu@ucfgroup.com>
 * 
 */
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\user\UserService;
use core\service\sms\MobileCodeService;

class EMCode extends BaseAction {
    public function init() {
    }

    public function invoke() {
        $form = new Form('post');
        $form->rules = array(
            'isrsms' => array('filter' => 'int','message' => '参数错误'),
            'is_edit' => array('filter' => 'int','message' => '参数错误'),
            'is_delivery' => array('filter' => 'int','message' => '参数错误'),
            'is_weblogin' => array('filter' => 'int','message' => '参数错误'),
            'mobile' => array('filter' => 'int','message' => '参数错误'),
        );
        $form->validate();

        $isrsms = false;
        $user_id = isset($GLOBALS['user_info']['id']) ? intval($GLOBALS['user_info']['id']) : 0;
        // 已登录用户修改手机号码
        $is_edit = isset($form->data['is_edit']) ? (int)$form->data['is_edit'] : 0;
        // 修改或设置收获地址，短信发送
        $is_delivery = isset($form->data['is_delivery']) ? (int)$form->data['is_delivery'] : 0;
        // 用户登录身份验证,短信发送$is_weblogin=9
        $is_weblogin = isset($form->data['is_weblogin']) ? (int)$form->data['is_weblogin'] : 0;

        if ($is_edit || $is_delivery) {
            if ($user_id) {
                $user_info = $GLOBALS['user_info'];
                $mobileCodeObj = new MobileCodeService();
                $is_send = $mobileCodeObj->isSend($user_info['mobile'], 2);
                if ($is_send != 1) {
                    $error_msg = $mobileCodeObj->getError($is_send);
                    echo json_encode($error_msg);
                    return;
                }

                $country_code = empty($user_info['country_code'])? 'cn': $user_info['country_code'];
                // 已登录用户修改手机号码
                if ($is_edit) {
                    if (isset($_POST['req']) && $_POST['req']=='resetbankcard') {
                        $rs = $mobileCodeObj->sendVerifyCode($user_info['mobile'], 1, $isrsms, 15, $country_code);
                    } else {
                        $rs = $mobileCodeObj->sendVerifyCode($user_info['mobile'], 1, $isrsms, 3, $country_code);
                    }
                }

                // 修改或设置收获地址，短信发送
                if ($is_delivery) {
                    $rs = $mobileCodeObj->sendVerifyCode($user_info['mobile'], 1, $isrsms, $is_delivery, $country_code);
                }
                return;
            }
       }

       $user_info['mobile'] = !empty($form->data['mobile']) ? $form->data['mobile'] : '';
       if ($is_weblogin) {
           $mobileCodeObj = new MobileCodeService();
           $is_send = $mobileCodeObj->isSend($user_info['mobile'], 2);
           if ($is_send != 1) {
               $error_msg = $mobileCodeObj->getError($is_send);
               echo json_encode($error_msg);
               return;
           }
           $rs = $mobileCodeObj->sendVerifyCode($user_info['mobile'], 1, $isrsms, $is_weblogin);
           return;
       }
    }
}