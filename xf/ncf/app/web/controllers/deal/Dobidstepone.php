<?php

/**
 * DoBid class file.
 *
 * @author 杨庆<yangqing@ucfgroup.com>
 * */

namespace web\controllers\deal;

use libs\web\Form;
use web\controllers\BaseAction;
use app\models\service\LoanType;
use libs\utils\Logger;
use core\service\PaymentService;
use core\service\AdunionDealService;
use core\service\risk\RiskService;

class Dobidstepone extends BaseAction {

    private static $_minAge = 18;
    private static $_maxAge = 70;

    /**
     * init
     * 初始化相关操作
     * @access public
     * @return void
     */
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
            'idType' => array('filter' => 'int'),
            'idno' => array('filter' => 'string'),
            //'idno_re' => array('filter' => 'string'),
            'name' => array('filter' => 'string'),
            'birthday' => array('filter' => 'string'),
            //'user_name' => array('filter' => 'string'),
            'phone' => array('filter' => 'int'),
            'sex' => array('filter' => 'int'),
            'validateCode' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            echo -1;
            return false;
        }
    }

    /**
     * invoke
     * action
     * @access public
     * @return void
     */
    public function invoke() {
        $form_data = $this->form->data;
        $ajax = 1;
        if (!array_key_exists($form_data['idType'], $GLOBALS['dict']['ID_TYPE'])) {
            $this->show_error('验证类型错误', "", 1);
        }
        // 验证表单令牌
        if (!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", $ajax);
        }
        switch ($form_data['idType']) {
            case 1:
                $this->invoke_inland($form_data);
                break;

            case 2:
            case 4:
            case 6:
                $this->invoke_hk($form_data);
                break;

            default:
                break;
        }
        return;
    }

    public function invoke_inland($form_data) {
        $form_data['name'] = trim($form_data['name']);
        $form_data['idno'] = trim($form_data['idno']);
        $ajax = 1;

        if (empty($form_data['name'])) {
            return $this->show_error('请输入姓名', "", 1);
        }

        $name_len = mb_strlen($form_data['name'], 'UTF-8');
        if ($name_len > 20 || $name_len < 2) {
            return $this->show_error('姓名输入不正确', "", 1);
        }

        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $form_data['idno'])) {
            return $this->show_error('身份认证失败，平台仅支持二代身份证', "", 1);
        }

        if ($GLOBALS ['user_info'] ['idcardpassed'] != 1) {
            if (trim($form_data ['idno']) == "") {
                return $this->show_error($GLOBALS ['lang'] ['PLEASE_INPUT'] . $GLOBALS ['lang'] ['IDNO'], '', 1);
            }

            $age = $this->getageByID(trim($form_data['idno']));
            if (($age < self::$_minAge) || (($age > self::$_maxAge) && $this->checkReferee($GLOBALS['user_info']['refer_user_id']))) {
                return $this->show_error('身份认证失败，平台仅支持年龄为18-70周岁的用户进行投资', "", 1);
            }

            // 用id5验证身份证信息
            $len = strlen($form_data ['idno']);
            if ($len != 18) {
                return $this->show_error($GLOBALS ['lang'] ['IDNO_ERROR'], "", 1);
            } else {
                $user = $this->rpc->local('UserService\getUserByIdno', array($form_data ['idno'], $GLOBALS ['user_info']['id']));
                if (!empty($user)) {
                    return $this->show_error('身份验证失败,如需帮助请联系客服', '', 1);
                }

                $riskRet = RiskService::check('REALNAME', array(
                    'user_id'=>$user['id'],
                    'mobile'=>$user['mobile'],
                    'user_type'=>$user['user_type'],
                    'account_type'=>$user['user_purpose'],
                    'idno'=>$form_data['idno'],
                    'invite_code'=>$user['invite_code']
                ));

                if ($riskRet !== true) {
                    return $this->show_error('为了您的账户安全，请前往网信普惠APP进行实名认证', '请前往网信普惠APP', 1);
                }

                $id5 = new \libs\idno\CommonIdnoVerify();
                $flag = app_conf("ID5_VALID");
                // 身份认证接口
                if (!empty($flag)) {
                    $ret = $id5->checkIdno($form_data['name'], $form_data['idno']);
                    if ($ret['code'] == '0') {
                        $reinfo = 1;
                    } else {
                        $reinfo = $ret['code'];
                    }
                } else {
                    $reinfo = 1;
                }

                if ($reinfo == 1) {
                    $userData = new \core\data\UserData();
                    $ret = $userData->pushCreditReg(array('user_id' => $GLOBALS['user_info']['id'], 'ip' => get_client_ip(), 'time' => time()));
                    $data ['real_name'] = $form_data ['name'];
                    $data ['idno'] = trim($form_data ['idno']);
                    // FIX 1250 set id_type
                    $data ['id_type'] = 1;
                    $data ['idcardpassed'] = 1;
                    $data ['idcardpassed_time'] = time();
                    $data ['sex'] = $id5->getSex($data ['idno']);

                    // 设置出生日期
                    $birth = $id5->getBirthDay($data ['idno']);
                    $data ['byear'] = $birth ['year'];
                    $data ['bmonth'] = $birth ['month'];
                    $data ['bday'] = $birth ['day'];

                    // 记录日志文件
                    $log = array(
                        'type' => 'idno',
                        'user_name' => $form_data ['name'],
                        'user_login_name' => $GLOBALS ['user_info'] ['user_name'],
                        'indo' => $form_data ['idno'],
                        'path' => __FILE__,
                        'function' => 'dobidstepone',
                        'msg' => '身份证认证成功.',
                        'code' => $reinfo,
                        'flag' => $flag,
                        'time' => time()
                    );
                    Logger::wLog($log);
                } else {
                    // 记录日志文件
                    $log = array(
                        'type' => 'idno',
                        'user_name' => $form_data ['name'],
                        'user_login_name' => $GLOBALS ['user_info'] ['user_name'],
                        'indo' => $form_data ['idno'],
                        'path' => __FILE__,
                        'function' => 'dobidstepone',
                        'msg' => '身份证认证失败.',
                        'code' => $reinfo,
                        'flag' => $flag,
                        'time' => time()
                    );
                    Logger::wLog($log);
                    return $this->show_error($GLOBALS ['lang'] ['IDNO_ERROR'], "", 1);
                }
            }
        }

        if ($GLOBALS ['user_info'] ['mobilepassed'] == 0) {
            if (trim($form_data ['phone']) == "") {
                return $this->show_error($GLOBALS ['lang'] ['MOBILE_EMPTY_TIP'], '', 1);
            }
            if (!check_mobile(trim($form_data ['phone']))) {
                return $this->show_error($GLOBALS ['lang'] ['FILL_CORRECT_MOBILE_PHONE'], '', 1);
            }
            if (trim($form_data ['validateCode']) == "") {
                return $this->show_error($GLOBALS ['lang'] ['PLEASE_INPUT'] . $GLOBALS ['lang'] ['VERIFY_CODE'], '', 1);
            }
            if (trim($form_data ['validateCode']) != $GLOBALS ['user_info'] ['bind_verify']) {
                return $this->show_error($GLOBALS ['lang'] ['BIND_MOBILE_VERIFY_ERROR'], '', 1);
            }
            $data ['mobile'] = trim($form_data ['phone']);
            $data ['mobilepassed'] = 1;
        }
        if ($data) {
            $data['id'] = $GLOBALS ['user_info'] ['id'];
            $res = $this->rpc->local("UserService\updateInfo", array($data));
            if ($res['error']) {
                return $this->show_error('更新失败', '', 1);
            }
            $userInfo = ['cardNo' => trim($form_data['idno']), 'realName' => trim($form_data['name'])];
            $res = $this->rpc->local('PaymentService\register', array($GLOBALS['user_info']['id'], $userInfo));
        }

        //add by wangfei5@
        $this->rpc->local('RegisterService\afterAddBank', array());
        return $this->show_success($GLOBALS ['lang'] ['SUCCESS_TITLE'], '', 1, 0, '/account/addbank');
    }

    public function invoke_hk($form_data) {
        $uid = $GLOBALS['user_info']['id'];
        if (!$uid) {
            return app_redirect(url("index"));
        }

        //两个浏览器提交的变态问题修复
        if ($GLOBALS['user_info']['idcardpassed'] == 3) {
            return $this->show_error("通行证资料提交失败！已经提交过审核了,平台将在3个工作日內完成信息审核。审核结果将以短信、站內信或电子邮件等方式通知您。", "", 1);
        }

//        if ($GLOBALS['user_info']['idcardpassed'] == 3) {
//            return $this->show_error('認證信息提交成功,网信理财將在3個工作日內完成信息審核。審核結果將以短信、站內信或電子郵件等方式通知您。', "", 0);
//        }
        $info = $this->rpc->local('UserPassportService\getPassportInfo', array($uid));
        if ($info && $info['status'] == 1) {
            return app_redirect(url("index"));
        }
        $data = array();
        $data['uid'] = $uid;
        $data['name'] = trim($form_data['name']);
        $idType = $form_data['idType'];
        $data['region'] = mb_substr($GLOBALS['dict']['ID_TYPE'][$idType], 0, 2, 'utf-8');
        $data['sex'] = $form_data['sex'];
        $data['idno'] = trim($form_data['idno']);
        $data['passportid'] = trim($form_data['idno']);
        $data['valid_date'] = $form_data['valid_date'];
        $data['birthday'] = $form_data['birthday'];
        $data['file'] = serialize($_POST['path']);
        if (!empty($info['id']) && intval($info['id']) > 0) {//修改
            $data['utime'] = get_gmtime();
            $re = $this->rpc->local('UserPassportService\updateByIdAndUid', array($data, $info->id, $uid));
        } else {
            $data['ctime'] = get_gmtime();
            $re = $this->rpc->local('UserPassportService\addInfo', array($data));
        }
        //修改用户表状态
        $this->rpc->local('UserService\updateInfo', array(array('id' => $uid, 'idcardpassed' => 3)));

        if ($re) {
            //add by wangfei5@
            $this->rpc->local('RegisterService\afterAddBank', array());

            return $this->show_success("认证信息提交成功,平台将在3个工作日內完成信息审核。审核结果将以短信、站內信或电子邮件等方式通知您。", '', 1, 0, APP_ROOT . '/');
        }
        return $this->show_error("通行证资料提交失败！", "", 1);
    }

    /**
     * 根据身份证号获得用户年龄
     * @param type $id
     * @return string
     */
    public function getAgeByID($id) {
        if (empty($id))
            return '';
        $date = substr($id, 6, 8);
        $today = date("Ymd");
        $diff = substr($today, 0, 4) - substr($date, 0, 4);
        $age = substr($date, 4) > substr($today, 4) ? ($diff - 1) : $diff;

        return $age;
    }

    /**
     * 根据配置判定是否允许70岁以上的用户注册
     * @param int $refer_user_id
     * @return bool
     */
    private function checkReferee($refer_user_id) {
        if (!$refer_user_id) {
            return true;
        }

        $groups = explode(',', app_conf('INVEST_CONFIG_AGE_SEVENTY'));
        if (!$groups) {
            return true;
        }

        $refer_user_info = $this->rpc->local('UserService\getUser', array($refer_user_id));
        if (in_array($refer_user_info['group_id'], $groups)) {
            return false;
        }
        return true;
    }

}
