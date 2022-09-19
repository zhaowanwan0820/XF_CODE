<?php

FP::import("libs.libs.msgcenter");

class ConfirmAction extends BaseAction {

    private function getAdminInfo() {
        $callback = trim($_GET['jsoncallback']);
        $adminInfo = \es_session::get(md5(app_conf("AUTH_KEY")));
        if (empty($adminInfo)) {
            $return = array('errno' => 1, 'error' => '登录已经过期，请重新登录');
            die(sprintf('%s(%s)', $callback, json_encode($return)));
        }

        if (empty($adminInfo['mobile'])) {
            $return = array('errno' => 1, 'error' => '已登录账户没有设置手机号');
            die(sprintf('%s(%s)', $callback, json_encode($return)));
        }

        return $adminInfo;
    }

    //敏感操作验证手机号
    public function send() {
        header("Access-Control-Allow-Origin:*");
        $callback = trim($_GET['jsoncallback']);
        $adminInfo = $this->getAdminInfo();

        $rand = rand(100000, 999999);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $result = $redis->setex('admin_' . $adminInfo['adm_id'] . '_confirm', 60, $rand);
        if (!$result) {
            $return = array('errno' => 1, 'error' => '短信发送失败');
            die(sprintf('%s(%s)', $callback, json_encode($return)));
        }

        $msgcenter = new \MsgCenter();
        $msgcenter->sendSmsMsg($adminInfo['mobile'], -1, sprintf('验证码:%s, 一分钟有效', $rand));
        $result = $msgcenter->save();
        if (!$result) {
            $return = array('errno' => 1, 'error' => '短信发送失败');
            die(sprintf('%s(%s)', $callback, json_encode($return)));
        }

        $return = array('errno' => 0);
        die(sprintf('%s(%s)', $callback, json_encode($return)));
    }

    public function check() {
        header("Access-Control-Allow-Origin:*");
        $callback = trim($_GET['jsoncallback']);
        $adminInfo = $this->getAdminInfo();

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $code = $redis->get('admin_' . $adminInfo['adm_id'] . '_confirm');
        if ($code != trim($_GET['code'])) {
            $return = array('errno' => 1, 'error' => '短信验证码错误');
            die(sprintf('%s(%s)', $callback, json_encode($return)));
        }

        $return = array('errno' => 0);
        die(sprintf('%s(%s)', $callback, json_encode($return)));
    }

}
