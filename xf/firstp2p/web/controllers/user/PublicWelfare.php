<?php

/**
 * 查看公益标
 * @author xiaoan
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use core\service\WeiXinService;
use core\service\DealLoadService;

class PublicWelfare extends BaseAction {

    public static $seeMeUrl = '';

    public function init() {
        if (empty($_GET['u'])){
            $this->show_error('参数错误');
        }
        self::$seeMeUrl = (app_conf('ACTIVITY_WEIXIN_HOST') ? app_conf('ACTIVITY_WEIXIN_HOST'):'http://www.firstp2p.com').'/user/WeiXinLogin';
    }

    public function invoke() {
        $weixinService = new WeiXinService();
        $info = $weixinService->getAesValue(1,$_GET['u']);
        if (empty($info)){
            $this->show_error('微信忙不过鸟');
            return false;
        }
        $is_self=0;
        $wxCookie = $weixinService->getCookie(1);
        if (!empty($wxCookie) && $wxCookie['openid']==$info['openid']){
            $is_self = 1;
        }
        $dealLoadService = new DealLoadService();
        $dealInfo = $dealLoadService->getCrowdfundingByUser($info['user_id']);
        if ($dealInfo['count'] == 0){
            // 没有公益报告
           $this->template = 'web/views/v2/user/welfare_no.html';
            return false;
        }
        // 微信信息
        $wxinfo = $weixinService->getWinXinInfo($info['openid']);
        $wxUserInfo = $wxinfo['user_info'];
        // 分享相关参数
        $jsApiSingature = $weixinService->getJsApiSignature();

        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);
        $this->tpl->assign('wxShareTitle', app_conf('PUBLIC_WELFARE_REPORT_WEIXIN_SHARE_TITLE'));
        $this->tpl->assign('wxShareDesc', app_conf('PUBLIC_WELFARE_REPORT_WEIXIN_SHARE_DESC'));
        $this->tpl->assign('wxShareImg', app_conf('PUBLIC_WELFARE_REPORT_WEIXIN_SHARE_IMG'));

        $this->tpl->assign('is_self',$is_self);
        $this->tpl->assign('seeMeUrl',self::$seeMeUrl);
        $this->tpl->assign('currentUrl',(app_conf('ACTIVITY_WEIXIN_HOST') ? app_conf('ACTIVITY_WEIXIN_HOST'):'http://www.firstp2p.com').'/user/PublicWelfare?u='.urlencode($_GET['u']));
        $this->tpl->assign('wxinfo',$wxUserInfo);
        $this->tpl->assign('dealInfo',$dealInfo);
        $this->template = 'web/views/v2/user/welfare.html';
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
