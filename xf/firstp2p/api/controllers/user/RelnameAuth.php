<?php
namespace api\controllers\user;

use api\conf\Error;
use api\controllers\FundBaseAction;
use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\account\UserInfo;

/**
 * RelnameAuth
 * @abstract 用户实名认证接口,默认已经通过了公安部的实名认证
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 * @date   2015-06-18
 */
class RelnameAuth extends FundBaseAction 
{
    private static $_minAge = 18;
    private static $_maxAge = 70;
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules=array(
                'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
                'name' => array('filter' =>"required", 'message'=> '姓名不能为空！'),
                'idno'=>array('filter'=>"required", 'message'=> '身份证号不能为空！'),
                'userid'=>array('filter'=>"required", 'message'=> '用户id不能为空！'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }

    }

    public function invoke()
    {
        $data=$this->form->data;
        if (empty($data['name']) || empty($data['idno']) || empty($data['userid'])) {
            $this->setErr('ERR_MANUAL_REASON', '请检查输入参数，有空参数!');
            return false;
        }
        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($data['idno']))) {
            $msg = '身份认证失败，平台仅支持二代身份证';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        if (strpos($data['name'], ' ') !== false ) {
            $msg = '身份认证失败，用户真实姓名不能包含空格';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        if (!is_numeric($data['userid'])) {
            $msg='用户id必须为数字！';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        $data['name'] = trim($data['name']);

        $age = $this->getAgeByID(trim($data['idno']));
        if (($age < self::$_minAge) || (($age > self::$_maxAge) && $this->checkReferee($userinfo['refer_user_id']))) {
            $msg = '身份认证失败，平台仅支持年龄为18-70周岁的用户进行投资';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        } 

        $user = $this->rpc->local('UserService\getUserByIdno', array($data['idno'], $data['userid']));

        if (!empty($user)) {
            $msg = "身份验证失败,如需帮助请联系客服";
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        $userinfo=$this->rpc->local('UserService\getUser', array($data['userid']));
        if (empty($userinfo)) {
            $msg = "该用户不存在";
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        } else if ($userinfo['idcardpassed'] == 1 && $userinfo['idno'] == $data['idno']) { 
            $msg = "该用户已经认证过了，不需要再认证了！";
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        } elseif ($userinfo['idcardpassed'] == 1 && $userinfo['idno'] != $data['idno']) {
            $msg = "用户信息和身份证号不匹配！";
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        if ($this->rpc->local('UserService\psCheckUserNoid', array($data['name'],$data['idno'],$data['userid']))) {
            $res['success'] = ConstDefine::RESULT_SUCCESS;
            $res['msg'] = '用户认证成功';
        } else {
            $msg = "用户存在但是认证失败，请重试！";
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }   
        $this->json_data = $res;
        return true;
    }

    /**
     * 根据身份证号获得用户年龄
     * @param type $id
     * @return string
     */
    public function getAgeByID($id) 
    {
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
    private function checkReferee($refer_user_id) 
    {
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
