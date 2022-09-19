<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\ConstDefine;
use core\service\PaymentService;
use libs\utils\PaymentApi;
use core\service\UserVerifyService;
use core\service\risk\RiskService;

/**
 * AuthInfo
 * 用户实名认证接口
 *
 * @uses PayBaseAction
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author wangjiansong@ucfgroup.com
 * @license PHP Version 4 & 5 {@link http://www.php.net/license/3_01.txt}
 */
class AuthInfo extends AppBaseAction {

    private static $_minAge = 18;
    private static $_maxAge = 70;

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录过期，请重新登录'),
            'name' => array('filter' => 'string'),
            'idno' => array('filter' => 'string'),
            'wxFreepayment' => array('filter' => 'int'),
            'site_id' => array('filter' => 'int','message'=>'site_id error'),
            'reqSource' => array('filter' => 'int'),
            'returnUrl' => array('filter' => 'string'),
            'failUrl' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_MANUAL_REASON', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        // 判断身份实名认证服务是否可用，如果服务不可用则直接返回相应的说明信息
        if (intval(app_conf("ID5_VALID")) === 3) {
            $msg = app_conf("ID5_MAINTEN_MSG");
            $this->setErr('ERR_MANUAL_REASON', $msg);
        }

        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($data['idno']))) {
            $msg = '身份认证失败，平台仅支持二代身份证';
            $this->setErr('ERR_MANUAL_REASON', $msg);
        }

        if (strpos($data['name'], ' ') !== false ) {
            $msg = '身份认证失败，用户真实姓名不能包含空格';
            $this->setErr('ERR_MANUAL_REASON', $msg);
        }

        $data['name'] = trim($data['name']);
        $age = $this->getAgeByID(trim($data['idno']));
        if (($age < self::$_minAge) || (($age > self::$_maxAge) && $this->checkReferee($loginUser['refer_user_id']))) {
            $msg = '身份认证失败，平台仅支持年龄为18-70周岁的用户进行投资';
            $this->setErr('ERR_MANUAL_REASON', $msg);
        }

        $user = $this->rpc->local('UserService\getUserByIdno', array($data['idno'], $loginUser['id']));
        if (!empty($user)) {
            $msg = "身份验证失败,如需帮助请联系客服";
            $this->setErr('ERR_MANUAL_REASON', $msg);
        }

        // 风控判断处理
        $riskRet = RiskService::check('REALNAME', array(
            'user_id'=>$loginUser['id'],
            'mobile'=>$loginUser['mobile'],
            'idno'=>$data['idno'],
            'user_type'=>$loginUser['user_type'],
            'account_type'=>$loginUser['user_purpose'],
            'invite_code'=>$loginUser['invite_code']
        ));

        if ($riskRet !== true) {
            $this->setErr('ERR_MANUAL_REASON', '为了您的账户安全，需要下载网信APP完成实名认证，投资体验也会更佳哦');
        }

        $params = array(
            'userId' => $loginUser['id'],
            'reqSource' => (empty($data['reqSource']) ? 1: $data['reqSource']),
            'returnUrl' => $data['returnUrl'],
            'failUrl' => $data['failUrl'],
        );

        // 普惠实名认证成功去标准开户
        $svUrl = '';
        if ($data['site_id'] == 100) {
            $svUrl = sprintf(
                $this->getHost() . "/payment/Transit?params=%s",
                urlencode(json_encode(['srv' => 'registerStandard', 'return_url' => 'storemanager://api?type=closecgpages']))
            );
        }

        if ($loginUser['idcardpassed'] == 1) {
            $msg = '用户已经认证过';
            $ret['h5AuthCardUrl'] = $svUrl ?: $this->rpc->local('PaymentUserAccountService\h5AuthBindCard', array($params));
            $ret['success'] = ConstDefine::RESULT_SUCCESS;
            $ret['msg'] = '您已通过身份验证';
        } else {
            $ret['success'] = ConstDefine::RESULT_SUCCESS;
            $ret['msg'] = '您已通过身份验证';
            try {
                $regData = array(
                    'cardNo' => trim($data['idno']),
                    'realName' => trim($data['name']),
                );
                $res = $this->rpc->local('PaymentService\register', array($loginUser['id'], $regData));
                if ($res === PaymentService::REGISTER_FAILURE) {
                    throw new \Exception('注册失败');
                }
                $ret['h5AuthCardUrl'] = $svUrl ?: $this->rpc->local('PaymentUserAccountService\h5AuthBindCard', array($params));
                if (!empty($data['wxFreepayment'])) {
                    $this->rpc->local('UserService\signWxFreepayment', array($loginUser['id']));
                }
            } catch(\Exception $e) {
                $msg = '身份验证失败';
                PaymentApi::log('mobileRegister failed, msg:'.$e->getMessage());
                $this->setErr('ERR_MANUAL_REASON', $msg);
            }
        }

        RiskService::report('REALNAME', RiskService::STATUS_SUCCESS, array(
            'user_id'=>$loginUser['id'],
            'mobile'=>$loginUser['mobile'],
            'idno'=>$data['idno'],
            'invite_code'=>$loginUser['invite_code'],
            'user_type'=>$loginUser['user_type'],
            'account_type'=>$loginUser['user_purpose']
        ));

        $this->json_data = $ret;
        return true;
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
