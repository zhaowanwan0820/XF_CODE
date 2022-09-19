<?php

namespace web\controllers\bind;

use libs\web\Bind;
use web\controllers\BaseAction;
use \libs\utils\Logger;

class Third extends BaseAction {

    private function checkNeedFeilds() {
        return isset($_REQUEST['client_id']) && isset($_REQUEST['client_token']) && isset($_REQUEST['timestamp']) && isset($_REQUEST['sign']);
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

        $this->tpl->assign('appInfo', $data['openBindData']['appInfo']);
        $this->tpl->assign('errmsg', $data['errmsg']);
    }

    public function invoke() {
        if (!$this->checkNeedFeilds()) {
            $data = Bind::getTplRet(false, Bind::SHOW_ERROR_PAGE, array('errmsg' => '参数错误, 授权失败！'));
            $params = array(
                'errmsg' => $data['data']['errmsg'],
                'device' => $data['device'],
            );
            $bindQuery = '?'.http_build_query($params);
            return header("Location:/bind/error" . $bindQuery);
        }

        $chkBindRes = Bind::checkBindInfo();

        $chkData = $chkBindRes['data'];
        if (true === $chkData['isUserBind']) {
            $jump = !empty($chkData['openBindData']['url']) ? $chkData['openBindData']['url'] : Bind::getJumpUrl($chkData);
            header("Location:" . $jump);
            return true;
        }

        if ($chkBindRes['type'] == Bind::SHOW_ERROR_PAGE) {
            return $this->showErrorPage($chkData['errmsg']);
        }

        $notNeedAuth = ($chkData['isIdentify'] || !$chkData['isp2pUser']) && ($chkData['checkMobile'] == $chkData['openBindData']['thirdUserInfo']['mobile']);

        $isAgreed = intval($_REQUEST['isAgreed']);
        if (empty($isAgreed) && $notNeedAuth) {
            $this->setTplData($chkData);
            $this->template = "web/views/bind/auth.html";
            return false;
        }

        if (!$chkData['isp2pUser']) {
            $createRes = Bind::createUser($chkData);
            if ($createRes['code']) {
                Logger::error("CreateUserError,IN:" . json_encode($data) . " ,OUT: " . json_encode($createRes));
                return ($createRes['code'] == 2) ? $this->showErrorPage('手机号码已经被占用') : $this->showErrorPage('创建登录用户失败');
            }
            $chkData['p2pUserId'] = $createRes['data']['user_id'];
        }

        if ($notNeedAuth) {
            $bindRes = Bind::saveOpenBind($chkData);
            if ($bindRes['code']) {
                Logger::error("BindError,IN:" . json_encode($data) . ",OUT: " . json_encode($bindRes));
                return $this->showErrorPage('授权绑定失败');
            }
            $jump = !empty($bindRes['data']['url']) ? $bindRes['data']['url'] : Bind::getJumpUrl($data);
            header("Location:" . $jump);
            return true;
        } else {
            $this->setTplData($chkData);
            $this->template = $chkBindRes['template'];
            return false;
        }
    }

    private function showErrorPage($msg) {
        $params = array(
            'errmsg' => $msg,
            'device' => 'wap',
        );
        $bindQuery = '?'.http_build_query($params);
        return header("Location:/bind/error" . $bindQuery);
    }

}
