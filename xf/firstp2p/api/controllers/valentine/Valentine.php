<?php
/**
 * 七夕贺卡分享活动
 * User: yangshuo
 */

namespace api\controllers\valentine;

use api\controllers\AppBaseAction;
use core\service\WeixinInfoService;
use core\service\WeiXinService;
use libs\web\Form;

class Valentine extends AppBaseAction
{

    const IS_H5 = true;

    public function init(){
        $weixin = new WeiXinService();
        if ($weixin->isWinXin()) {
            $_SERVER['HTTP_VERSION'] = 480;
        }
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "token" => array('filter' => 'string', "option" => array("optional" => true)),
            "weixinOpenId" => array('filter' => 'string', "option" => array("optional" => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {

        //原始数据（背景图、文字图案、二维码、姓名（网信/微信）、时间）
        $data = $this->form->data;
        // 0开关 ？
        if (app_conf('QIXI_SWITCH') === '0') {
            $this->setErr('ERR_MANUAL_REASON', '活动已关闭');
            return false;
        }
        $isLatestVersion = 1;
        $weixin = new WeiXinService();
        if (!$weixin->isWinXin()) {
            $isLatestVersion = $this->app_version >= 495 ? '1' : '0';
        }
        $this->tpl->assign('isLatestVersion', $isLatestVersion);
        $this->tpl->assign('shareUrl', app_conf('QIXI_WEIXIN_SHARE_URL'));
        $result = [];
        // 姓名 时间
        $time = date('Y.m.d', time());
        $result['time'] = $time;
        $userInfo = $this->getUserByToken(false);
        $wxOpenId = $data['weixinOpenId'];

        if (!empty($userInfo)) {
            $result['name'] = !empty($userInfo['real_name']) ? $userInfo['real_name'] : 'Yours';
        } elseif (!empty($wxOpenId)) {
            $wxinfoService = new WeixinInfoService();
            $weixinInfo = $wxinfoService->getWeixinInfo($wxOpenId, true);
            $userInfo = $weixinInfo['user_info'];
            $result['name'] = $userInfo['nickname'];
        } else {
            //其他浏览器
            $result['name'] = 'Yours';
        }
        // 获取背景图
        $backgrounds = explode(',', str_replace(['，', ''], ',', trim(app_conf('QIXI_BACKGROUND_IMG'))));           //背景图
        $blessings   = explode(',', str_replace(['，', ''], ',', trim(app_conf('QIXI_OTHER_IMG'))));                //祝福语
        $qrcode      = app_conf('QIXI_QRCODE_IMG');   //二维码
        $backs = $this->getImgUrl($backgrounds);
        $bles  = $this->getImgUrl($blessings);
        $qrcodeImg = $this->getImgUrl($qrcode);

        $isApp = isset($_SERVER['HTTP_APIVERSION']);
        // 微信分享js签名

        $this->tpl->assign("isApp", $isApp);

        $weixin = new WeiXinService();
        $jsApiSingature = $weixin->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $this->tpl->assign("backgrounds", json_encode($backs));
        $this->tpl->assign("blessings", $bles);
        $this->tpl->assign("qrcode", $qrcodeImg);
        $this->tpl->assign("name", $result['name']);
        $this->tpl->assign("time", $time);
        $this->template = $this->getTemplate('');

    }

    public function getImgUrl($aids)
    {
        if (empty($aids)) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '图片id不能为空');
            return false;
        }
        $url = [];

        if (is_array($aids)) {
            foreach ($aids as $aid) {
                $url[] = sprintf($this->getHost().'/common/publicImage?image_id=%s', \libs\utils\Aes::encryptForDeal($aid));
            }
        } else {
            $url = sprintf($this->getHost().'/common/publicImage?image_id=%s', \libs\utils\Aes::encryptForDeal($aids));
        }

        return $url;
    }

}
