<?php

namespace web\controllers\bind;

use libs\web\Bind;
use web\controllers\BaseAction;

class Check extends BaseAction {

    private function checkNeedFeilds() {
        return isset($_REQUEST['client_id']) && isset($_REQUEST['client_token']) && isset($_REQUEST['timestamp']) && isset($_REQUEST['sign']);
    }

    private function showErrorPage($data) {
        $this->tpl->assign('errmsg', $data['data']['errmsg']);
        $this->template = $data['template'];
    }

    private function checkSession() {
        if (Bind::isWapDevice()) {
            return false;
        }

        $clientToken = \es_session::get('pass_client_token');
        return $clientToken == trim($_REQUEST['client_token']);
    }

    private function setTplData($data) {
        $domain  = get_domain();
        $bindUrl = "{$domain}/user/login?source=anbind&backurl=" . urlencode("{$domain}/bind/done?type=login");
        $this->tpl->assign('bindUrl', $bindUrl);

        if (!empty($data['checkMobile'])) {
            $mobile = $data['checkMobile'];
            $this->tpl->assign('mobile', $mobile);
            $this->tpl->assign('mobile_sc',substr($mobile, 0, 3) . str_repeat('*', 4) . substr($mobile, -4));
        }

        $isp2pUser = isset($data['p2pUserId']) && $data['p2pUserId'] > 0;
        $this->tpl->assign('appInfo', $data['openBindData']['appInfo']);
        $this->tpl->assign('errmsg', $data['errmsg']);
        $this->tpl->assign('isp2pUser', $isp2pUser);
    }

    public function invoke() {
        //wap跳转到重构后的mo站点
        //if (in_array($this->appInfo['id'], array(101549, 101539))) {
            Bind::checkWapJump($this->appInfo);
        //}

        if (!$this->checkNeedFeilds()) {
            $data = Bind::getTplRet(false, Bind::SHOW_ERROR_PAGE, array('errmsg' => '参数错误, 授权失败！'));
            $params = array(
                'errmsg' => $data['data']['errmsg'],
                'device' => $data['device'],
            );
            $bindQuery = '?'.http_build_query($params);
            return header("Location:/bind/error" . $bindQuery);
        }

        if ($this->checkSession()) {
            $data  = \es_session::get('bind_data');
            $jump = Bind::getJumpUrl($data);
            header("Location:" . $jump);
            return true;
        }

        $chkBindRes = Bind::checkBindInfo();
        $chkData = $chkBindRes['data'];
        if ($chkBindRes['execute']) {
            $jump = !empty($chkData['openBindData']['url']) ? $chkData['openBindData']['url'] : Bind::getJumpUrl($chkData);
            header("Location:" . $jump);
            return true;
        }

        if ($chkBindRes['type'] == Bind::SHOW_ERROR_PAGE) {
            $params = array(
                'errmsg' => $chkData['errmsg'],
                'device' => $chkBindRes['device'],
            );
            $bindQuery = '?'.http_build_query($params);
            return header("Location:/bind/error" . $bindQuery);
        }
        $this->setTplData($chkData);
        $this->template = $chkBindRes['template'];

        return false;
    }

}
