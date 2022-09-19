<?php

/**
 * 微信登陆与绑定
 * @author xiaoan
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use libs\utils\Logger;
use core\service\WeiXinService;

class WeiXinLogin extends BaseAction {

    public static $callback = '';

    public function init() {
        // 防止手机存满cookie
        $this->rpc->local("WeiXinService\clearCookie",array(1));
        $is_winxin = $this->rpc->local("WeiXinService\isWinXin",array());
        if ($is_winxin === false){
            $this->show_error('请在微信中打开');
            return false;
        }
        $this->form = new Form();
        $this->form->rules = array(
            'username' => array('filter' => 'string'),
            'password' => array('filter' => 'string'),
            'captcha' => array('filter' => 'string'),
            'code' => array('filter' => 'string'),
        );
        $this->form->validate();
        self::$callback = (app_conf('ACTIVITY_WEIXIN_HOST') ? app_conf('ACTIVITY_WEIXIN_HOST'): 'http://www.firstp2p.com').'/user/WeiXinLogin';
    }

    public function invoke() {

        // 有code 直接获取
        if ($this->form->data['code']){
            $wxService = new WeiXinService();
            $ret = $wxService->winXinCallback($this->form->data['code']);
            if (!empty($ret['err_code'])){
                $this->show_error('微信忙不来鸟!');
                return false;
            }
            if ($wxService::$openId) {
                $openid = $wxService::$openId;
                // 查询绑定关系
                $weixinBindInfo = $this->rpc->local('WeiXinService\getByOpenid', array($openid, 'user_id'));
                if (!empty($weixinBindInfo)) {
                    $this->jumpPublicWelfare($weixinBindInfo['user_id'], $openid);
                }
            }
            // 显示登陆页面
        }else{

            // 符合登陆
            $ret = $this->login();
            if (!empty($ret)){
                $this->tpl->assign('errMsg',$ret);
                $this->tpl->assign('username',htmlspecialchars($_POST['username']));
                $this->template = "web/views/v2/user/weixin_login.html";
                return false;
            }
            // 是否授权
            $wxCache = $this->rpc->local('WeiXinService\getCookie', array(1));
            if (empty($wxCache)){
                $this->rpc->local('WeiXinService\grantAuthorization', array(self::$callback));
                return false;
            }else{
                // 是否绑定
                $openid = $wxCache['openid'];
                // 查询绑定关系
                $weixinBindInfo = $this->rpc->local('WeiXinService\getByOpenid',array($openid,'user_id'));
                if (!empty($weixinBindInfo)){
                    $this->jumpPublicWelfare($weixinBindInfo['user_id'], $openid);
                }
            }


        }
        $this->template = "web/views/v2/user/weixin_login.html";
    }

    /**
     * 绑定关系
     * @param int $user_id
     * @return jump 跳转到我的公益报告
     */
    private  function weixinBind($user_id){

        $weixinService = new WeiXinService();
        // 更新用户头像昵称等信息
        $weixinService->hasAuthorized(self::$callback);
        if (empty($weixinService::$wxCache)){
            $this->rpc->local('WeiXinService\grantAuthorization', array(self::$callback));
        }
        $openid = $weixinService::$wxCache['openid'];
        // 查询绑定关系
        $weixinBindInfo = $this->rpc->local('WeiXinService\getByOpenid',array($openid));
        if (empty($weixinBindInfo)){
            $data = array(
                'user_id' => $user_id,
                'openid' => $openid,
            );
            $weixinService->insertWeixinBind($data);
        }

        $this->jumpPublicWelfare($user_id, $openid);

    }
    /**
     * 跳转到公益页面
     */
    private function jumpPublicWelfare($user_id, $openid){
        $value = array('user_id' => $user_id,'openid' => $openid);
        $weixinService = new WeiXinService();
        $sn = $weixinService->setAesValue(1, $value);
        // 跳转到我的公益报告页面
        $jump = (app_conf('ACTIVITY_WEIXIN_HOST') ? app_conf('ACTIVITY_WEIXIN_HOST'): 'http://www.firstp2p.com').'/user/PublicWelfare?u='.urlencode($sn).'#page1';
        header("Location:$jump");
        exit;
    }

    /**
     * 登陆操作
     * @return string
     */
    private function login(){
        $err = '';
        if ($this->form->data['username'] && $this->form->data['password'] && empty($this->form->data['captcha'])){
            return $err = '请填写验证!';
        }
        if (( empty($this->form->data['username']) || empty($this->form->data['password']) ) && !empty($this->form->data['captcha'])){
            return $err = '请填写完整信息';
        }
        if (empty($this->form->data['username']) && !empty($this->form->data['password']) && !empty($this->form->data['captcha'])){
            return $err = '用户名不能为空';
        }
        if (!empty($this->form->data['username']) && empty($this->form->data['password']) && !empty($this->form->data['captcha'])){
            return $err = '密码不能为空';
        }
        if ($this->form->data['username'] && $this->form->data['password'] && $this->form->data['captcha']){
            // 验证表单令牌
            if (!check_token()) {
                return $err = '请不要重复提交表单';
            }

            $verify = \es_session::get('verify');
            if (md5($this->form->data['captcha']) !== $verify) {
                return $err = '验证码错误';
            }
            $bo = BOFactory::instance('web');
            $ret = $bo->authenticate($this->form->data['username'], $this->form->data['password']);
            if ($ret['code'] != 0){
                return $err = '账号或密码错误';
            }
            $this->weixinBind($ret['user_id']);
            return $err;
        }
    }
    /**
     * 显示错误
     *
     * @param $msg 消息内容
     * @param int $ajax
     * @param string $jump 调整链接
     * @param int $stay 是否停留不跳转
     * @param int $time 跳转等待时间
     */
    public function show_error($msg, $title = '', $ajax = 0, $stay = 0, $jump = '', $refresh_time = 3)
    {
        if($ajax == 1)
        {
            $result['status'] = 0;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
        }
        else
        {
            $title = empty($title) ? '查看公益报告' : $title;
            $this->tpl->assign('page_title',$title);
            $this->tpl->assign('error_title',$msg);

            if($jump==''){
                $jump = $_SERVER['HTTP_REFERER'];
            }
            if(!$jump&&$jump==''){
                $jump = APP_ROOT."/";
            }

            $this->tpl->assign('jump',$jump);
            $this->tpl->assign("stay",$stay);
            $this->tpl->assign("host", APP_HOST);
            $this->tpl->assign("refresh_time",$refresh_time);
            $this->tpl->display("web/views/error_h5.html");
            $this->template = null;

        }
        setLog(
            array('output' => array('ajax' => $ajax, 'jump' => $jump, 'msg'=> $msg ))
        );
        return false;
    }
}
